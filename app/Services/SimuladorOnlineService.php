<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SimuladorOnlineService
{
    protected const int DefaultRegionId = 2;

    protected const array AgeBracketKeys = [
        '0-18', '19-23', '24-28', '29-33', '34-38',
        '39-43', '44-48', '49-53', '54-58', '59+',
    ];

    protected string $baseUrl;

    protected string $loginPath;

    protected ?string $username;

    protected ?string $password;

    protected ?CookieJar $cookieJar;

    public function __construct()
    {
        $this->baseUrl = config('services.simulador_online.base_url');
        $this->loginPath = config('services.simulador_online.login_path');
        $this->username = config('services.simulador_online.username');
        $this->password = config('services.simulador_online.password');
        $this->cookieJar = new CookieJar;
    }

    /**
     * Search hospitals for autocomplete (Step 1). Returns formatted data plus meta for optional debug.
     *
     * @return array{hospitais: array<int, array{id: int|null, nome: string}>, query: string, region: int, raw: array}
     */
    public function searchHospitalsForAutocomplete(string $query, ?int $regionId = null): array
    {
        $regionId = $regionId ?? self::DefaultRegionId;
        $raw = $this->fetchHospitalsFromApi($regionId, $query);
        $hospitais = $this->formatHospitalsForAutocomplete($raw);

        return [
            'hospitais' => $hospitais,
            'query' => $query,
            'region' => $regionId,
            'raw' => $raw,
        ];
    }

    protected function fetchHospitalsFromApi(int $regionId, string $query): array
    {
        $response = $this->performHospitalsRequest($regionId, $query);
        $body = $response->body();

        $validJson = $response->successful() && $body !== '' && (str_starts_with(trim($body), '[') || str_starts_with(trim($body), '{'));
        if (! $validJson) {
            $this->login();
            $response = $this->performHospitalsRequest($regionId, $query);
            $body = $response->body();
        }

        if (! $response->successful()) {
            throw new \Exception('Erro ao buscar hospitais: Status '.$response->status().' - Body: '.substr($body, 0, 200));
        }

        $decoded = $response->json();
        if ($decoded === null && $body !== '') {
            throw new \Exception('Resposta não é JSON válido. Body: '.substr($body, 0, 500));
        }

        if (! is_array($decoded)) {
            return [];
        }
        if (empty($decoded)) {
            return [];
        }
        if (isset($decoded[0])) {
            return $decoded;
        }
        $extracted = $decoded['data'] ?? $decoded['results'] ?? $decoded['hospitais'] ?? $decoded['items'] ?? $decoded;

        return is_array($extracted) ? $extracted : [];
    }

    protected function performHospitalsRequest(int $regionId, string $query): Response
    {
        $headers = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer' => $this->baseUrl.'/comparativo/',
            'X-Requested-With' => 'XMLHttpRequest',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
        ];

        /** @var Response $response */
        $response = $this->client()
            ->withHeaders($headers)
            ->get($this->baseUrl.'/ope/adm/credenciados/1.json', [
                'regiao' => $regionId,
                'q' => $query,
            ]);

        return $response;
    }

    /**
     * Search health plans (Step 4). Builds payload, fetches HTML, parses and returns structured data.
     *
     * @param  array{profile: string, lives: array<string, int>, hospital?: string, hospitalId?: int}  $validatedInput
     * @return array<int, array<string, mixed>>
     */
    public function searchPlans(array $validatedInput): array
    {
        $payload = $this->buildSimulationPayload($validatedInput);
        $html = $this->fetchPlansHtml($payload);

        $plans = $this->parsePlansFromHtml($html);

        $filteredPlans = $this->applyFilters($plans);

        return $filteredPlans;
    }

    /**
     * @param  array{profile: string, lives: array<string, int>, hospitalId?: int}  $input
     * @return array{tipoTabela: int, totalVidas: int, regiao: int, faixas: array<int, array{vidas: int}>, info?: array<int, int>}
     */
    protected function buildSimulationPayload(array $input): array
    {
        $profile = $input['profile'] ?? 'cpf';
        $tableType = match ($profile) {
            'pme' => 3,
            'adesao' => 4,
            'cpf' => 2,
            default => 2,
        };

        $lives = $input['lives'] ?? [];
        $faixas = [];
        $totalVidas = 0;
        foreach (self::AgeBracketKeys as $index => $key) {
            $vidas = (int) ($lives[$key] ?? 0);
            $faixas[$index] = ['vidas' => $vidas];
            $totalVidas += $vidas;
        }

        $payload = [
            'tipoTabela' => $tableType,
            'totalVidas' => $totalVidas,
            'regiao' => self::DefaultRegionId,
            'faixas' => $faixas,
        ];

        $hospitalId = $input['hospitalId'] ?? null;
        if ($hospitalId !== null && $hospitalId !== '') {
            $payload['info'] = [(int) $hospitalId];
        }

        return $payload;
    }

    /**
     * @param  array{tipoTabela: int, totalVidas: int, regiao: int, faixas: array, info?: array<int, int>}  $payload
     */
    protected function fetchPlansHtml(array $payload): string
    {
        $formData = $this->buildPlansFormData($payload);
        $response = $this->performPlansRequest($formData);
        $body = $response->body();

        $hasPlansTable = str_contains($body, 'sim-op-planos') || str_contains($body, '<table');
        if (! $response->successful() || ! $hasPlansTable) {
            $this->login();
            $formData = $this->buildPlansFormData($payload);
            $response = $this->performPlansRequest($formData);
            $body = $response->body();
        }

        if (! $response->successful()) {
            throw new \Exception('Erro ao buscar planos: Status '.$response->status().' - Body: '.substr($body, 0, 200));
        }

        return $body;
    }

    /**
     * @param  array{tipoTabela: int, totalVidas: int, regiao: int, faixas: array, info?: array<int, int>}  $payload
     * @return array<string, mixed>
     */
    protected function buildPlansFormData(array $payload): array
    {
        $form = [
            'simulacao[destNome]' => '',
            'simulacao[destContato]' => '',
            'simulacao[destEmail]' => '',
            'simulacao[tipoTabela]' => $payload['tipoTabela'] ?? 2,
            'simulacao[tipoPlano]' => 1,
            'simulacao[acomodacao]' => '',
            'simulacao[totalVidas]' => $payload['totalVidas'] ?? 0,
            'simulacao[filtros][abrangencia]' => '',
            'simulacao[filtros][tipoOperadora]' => '',
            'simulacao[filtros][segmento]' => '',
            'simulacao[filtros][fatormoder]' => '',
            'simulacao[regiao]' => $payload['regiao'] ?? 2,
            'simulacao[corretorEmail]' => '',
            'simulacao[textoInicial]' => 'Primeiramente, agradecemos pelo seu contato.\r\nInformamos que os custos e as condições abaixo são determinadas por suas respectivas operadoras.\r\n',
        ];

        $form['simulacao[info][0]'] = 1;
        $form['simulacao[info][1]'] = 2;
        $form['simulacao[info][2]'] = 3;
        $form['simulacao[info][3]'] = 4;
        $form['simulacao[info][4]'] = 5;
        if (isset($payload['info']) && is_array($payload['info'])) {
            foreach ($payload['info'] as $i => $id) {
                $form['simulacao[info]['.(5 + $i).']'] = $id;
            }
        }

        if (isset($payload['faixas']) && is_array($payload['faixas'])) {
            foreach ($payload['faixas'] as $index => $faixa) {
                $form["simulacao[faixas][{$index}][vidas]"] = $faixa['vidas'] ?? 0;
            }
        }

        if (isset($payload['adesao']) && is_array($payload['adesao'])) {
            $a = $payload['adesao'];
            $form['simulacao[adesao][opes]'] = $a['opes'] ?? '';
            $form['simulacao[adesao][administradora]'] = $a['administradora'] ?? '';
            $form['simulacao[adesao][entidade]'] = $a['entidade'] ?? '';
            $form['simulacao[adesao][profissao]'] = $a['profissao'] ?? '';
        }

        $pageHtml = $this->client()->get($this->baseUrl.'/simulacao/nova')->body();
        if (preg_match('/name="simulacao\[_token\]"\s+value="([^"]+)"/', $pageHtml, $matches)) {
            $form['simulacao[_token]'] = $matches[1];
        }

        return $form;
    }

    /**
     * @param  array<string, mixed>  $formData
     */
    protected function performPlansRequest(array $formData): Response
    {
        $headers = [
            'Accept' => '*/*',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin' => $this->baseUrl,
            'Referer' => $this->baseUrl.'/simulacao/nova',
            'X-Requested-With' => 'XMLHttpRequest',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
        ];

        /** @var Response $response */
        $response = $this->client()
            ->asForm()
            ->withHeaders($headers)
            ->post($this->baseUrl.'/simulacao/planos', $formData);

        return $response;
    }

    /**
     * Parse plans table HTML and return structured plan data.
     *
     * @return array<int, array{id: int, operadora: string, operadora_logo: string|null, operadora_descricao: string, nome: string, acomodacao: string, acomodacao_sigla: string, vigencia: string}>
     */
    protected function parsePlansFromHtml(string $html): array
    {
        $plans = [];
        $dom = new DOMDocument;

        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $operatorRows = $xpath->query('//tr[@class="sim-op-planos"]');

        foreach ($operatorRows as $row) {
            $pNode = $xpath->query('.//p', $row)->item(0);
            $operatorName = $pNode ? trim($pNode->textContent) : '';
            $imgNode = $xpath->query('.//img', $row)->item(0);
            $operatorLogo = $imgNode instanceof \DOMElement ? $imgNode->getAttribute('src') : null;
            $descNode = $xpath->query('.//b[@class="fz-7"]', $row)->item(0);
            $operatorDesc = $descNode ? trim($descNode->textContent) : '';
            $planItems = $xpath->query('.//ul[@class="collapser smallest planos ta-l"]/li', $row);

            /** @var \DOMElement $planLi */
            foreach ($planItems as $planLi) {
                $tipsyNode = $xpath->query('.//span[@class="tipsy"]', $planLi)->item(0);
                $accommodation = $tipsyNode ? trim($tipsyNode->textContent) : '';

                if ($accommodation === 'AMB') {
                    continue;
                }

                $accommodationLabel = match ($accommodation) {
                    'E' => 'Enfermaria',
                    'A' => 'Apartamento',
                    default => $accommodation,
                };
                $nomeNode = $xpath->query('.//i', $planLi)->item(0);
                $plans[] = [
                    'id' => (int) $planLi->getAttribute('data-id'),
                    'operadora' => $operatorName,
                    'operadora_logo' => $operatorLogo,
                    'operadora_descricao' => $operatorDesc,
                    'nome' => $nomeNode ? trim($nomeNode->textContent) : '',
                    'acomodacao' => $accommodationLabel,
                    'acomodacao_sigla' => $accommodation,
                    'vigencia' => $planLi->getAttribute('data-vi'),
                ];
            }
        }

        return $plans;
    }

    /**
     * Filtra a lista de planos para retornar apenas operadoras únicas.
     *
     * @param  array<int, array<string, mixed>>  $plans
     * @return array<int, array<string, mixed>>
     */
    protected function applyFilters(array $plans): array
    {
        $uniqueOperators = [];
        $seenOperators = [];

        foreach ($plans as $plan) {
            $operatorName = $plan['operadora'] ?? '';

            if ($operatorName === '') {
                continue;
            }

            if (! isset($seenOperators[$operatorName])) {
                $seenOperators[$operatorName] = true;
                $uniqueOperators[] = $plan;
            }
        }

        return array_values($uniqueOperators);
    }

    protected function client(): PendingRequest
    {
        $verifySsl = config('services.simulador_online.verify_ssl', false);

        return Http::withOptions([
            'cookies' => $this->cookieJar,
            'verify' => $verifySsl,
            'timeout' => 30,
        ])->withHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
    }

    public function login(?string $username = null, ?string $password = null): array
    {
        $username = $username ?? $this->username;
        $password = $password ?? $this->password;

        if (! $username || ! $password) {
            throw new \Exception('Credenciais não configuradas no .env');
        }

        $loginPageHtml = $this->client()->get($this->baseUrl.$this->loginPath)->body();
        $csrfToken = null;
        if (preg_match('/name="login\[_token\]"\s+value="([^"]+)"/', $loginPageHtml, $matches)) {
            $csrfToken = $matches[1];
        }

        if (! $csrfToken) {
            throw new \Exception('Não foi possível extrair o token CSRF da página de login');
        }

        $response = $this->client()->asForm()->post($this->baseUrl.'/login_check', [
            'login[usuario]' => $username,
            'login[senha]' => $password,
            'login[_token]' => $csrfToken,
        ]);

        $success = $response->successful() || $response->redirect();
        $cookieJar = $response->cookies();
        $cookiesArray = [];
        $sessionId = null;

        foreach ($cookieJar as $cookie) {
            $cookiesArray[$cookie->getName()] = $cookie->getValue();
            if ($cookie->getName() === 'PHPSESSID') {
                $sessionId = $cookie->getValue();
            }
        }

        return [
            'success' => $success,
            'status' => $response->status(),
            'body' => $response->body(),
            'cookies' => $cookiesArray,
            'headers' => $response->headers(),
            'csrf_token' => $csrfToken,
            'session_token' => $sessionId,
        ];
    }

    public function checkAuthentication(): bool
    {
        try {
            $response = $this->client()->get($this->baseUrl.'/ope/adm/credenciados/1.json', [
                'regiao' => 2,
                'q' => 'test',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
