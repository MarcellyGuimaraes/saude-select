<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
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
     * @return array{hospitais: array<int, array{id: int|null, nome: string}>, query: string, region: int, raw: array}
     */
    public function searchHospitalsForAutocomplete(string $query, ?int $regionId = null): array
    {
        $regionId = $regionId ?? self::DefaultRegionId;

        $response = $this->client()
            ->withHeaders([
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Referer' => $this->baseUrl.'/comparativo/',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get($this->baseUrl.'/ope/adm/credenciados/1.json', ['regiao' => $regionId, 'q' => $query]);

        $body = $response->body();
        $isValidJson = $response->successful() && $body !== '' && (str_starts_with(trim($body), '[') || str_starts_with(trim($body), '{'));
        if (! $isValidJson) {
            $this->login();
            $response = $this->client()
                ->withHeaders([
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'Referer' => $this->baseUrl.'/comparativo/',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->get($this->baseUrl.'/ope/adm/credenciados/1.json', ['regiao' => $regionId, 'q' => $query]);
            $body = $response->body();
        }

        if (! $response->successful()) {
            throw new \Exception('Erro ao buscar hospitais: Status '.$response->status().' - Body: '.substr($body, 0, 200));
        }

        $decoded = $response->json();
        if ($decoded === null && $body !== '') {
            throw new \Exception('Resposta não é JSON válido. Body: '.substr($body, 0, 500));
        }

        $rawList = [];
        if (is_array($decoded)) {
            if (isset($decoded[0])) {
                $rawList = $decoded;
            } else {
                $extracted = $decoded['data'] ?? $decoded['results'] ?? $decoded['hospitais'] ?? $decoded['items'] ?? $decoded;
                $rawList = is_array($extracted) ? $extracted : [];
            }
        }

        $hospitais = array_map(fn (array $item): array => [
            'id' => $item['id'] ?? null,
            'nome' => $item['nome'] ?? $item['name'] ?? '',
        ], $rawList);

        return [
            'hospitais' => array_values($hospitais),
            'query' => $query,
            'region' => $regionId,
            'raw' => $rawList,
        ];
    }

    /**
     * @param  array{profile: string, lives: array<string, int>, hospitalId?: int}  $validatedInput
     * @return array<int, array<string, mixed>>
     */
    public function searchPlans(array $validatedInput): array
    {
        $profile = $validatedInput['profile'] ?? 'cpf';
        $tipoTabela = match ($profile) {
            'pme' => 3,
            'adesao' => 4,
            default => 2,
        };

        $lives = $validatedInput['lives'] ?? [];
        $faixas = [];
        $totalVidas = 0;
        foreach (self::AgeBracketKeys as $index => $key) {
            $vidas = (int) ($lives[$key] ?? 0);
            $faixas[$index] = ['vidas' => $vidas];
            $totalVidas += $vidas;
        }

        $payload = [
            'tipoTabela' => $tipoTabela,
            'totalVidas' => $totalVidas,
            'regiao' => self::DefaultRegionId,
            'faixas' => $faixas,
        ];
        $hospitalId = $validatedInput['hospitalId'] ?? null;
        if ($hospitalId !== null && $hospitalId !== '') {
            $payload['info'] = [(int) $hospitalId];
        }

        $formData = [
            'simulacao[destNome]' => '',
            'simulacao[destContato]' => '',
            'simulacao[destEmail]' => '',
            'simulacao[tipoTabela]' => $payload['tipoTabela'],
            'simulacao[tipoPlano]' => 1,
            'simulacao[acomodacao]' => '',
            'simulacao[totalVidas]' => $payload['totalVidas'],
            'simulacao[filtros][abrangencia]' => '',
            'simulacao[filtros][tipoOperadora]' => '',
            'simulacao[filtros][segmento]' => '',
            'simulacao[filtros][fatormoder]' => '',
            'simulacao[regiao]' => $payload['regiao'],
            'simulacao[corretorEmail]' => '',
            'simulacao[textoInicial]' => 'Primeiramente, agradecemos pelo seu contato.\r\nInformamos que os custos e as condições abaixo são determinadas por suas respectivas operadoras.\r\n',
        ];
        $formData['simulacao[info][0]'] = 1;
        $formData['simulacao[info][1]'] = 2;
        $formData['simulacao[info][2]'] = 3;
        $formData['simulacao[info][3]'] = 4;
        $formData['simulacao[info][4]'] = 5;
        if (isset($payload['info']) && is_array($payload['info'])) {
            foreach ($payload['info'] as $i => $id) {
                $formData['simulacao[info]['.(5 + $i).']'] = $id;
            }
        }
        foreach ($payload['faixas'] as $index => $faixa) {
            $formData["simulacao[faixas][{$index}][vidas]"] = $faixa['vidas'] ?? 0;
        }

        $novaPageHtml = $this->client()->get($this->baseUrl.'/simulacao/nova')->body();
        if (preg_match('/name="simulacao\[_token\]"\s+value="([^"]+)"/', $novaPageHtml, $matches)) {
            $formData['simulacao[_token]'] = $matches[1];
        }

        $response = $this->client()
            ->asForm()
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Origin' => $this->baseUrl,
                'Referer' => $this->baseUrl.'/simulacao/nova',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post($this->baseUrl.'/simulacao/planos', $formData);

        $planosHtml = $response->body();
        $respostaInvalida = ! $response->successful() || (! str_contains($planosHtml, 'sim-op-planos') && ! str_contains($planosHtml, '<table'));
        if ($respostaInvalida) {
            $this->login();
            if (preg_match('/name="simulacao\[_token\]"\s+value="([^"]+)"/', $this->client()->get($this->baseUrl.'/simulacao/nova')->body(), $m)) {
                $formData['simulacao[_token]'] = $m[1];
            }
            $response = $this->client()
                ->asForm()
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Origin' => $this->baseUrl,
                    'Referer' => $this->baseUrl.'/simulacao/nova',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->post($this->baseUrl.'/simulacao/planos', $formData);
            $planosHtml = $response->body();
        }

        if (! $response->successful()) {
            throw new \Exception('Erro ao buscar planos: Status '.$response->status().' - Body: '.substr($planosHtml, 0, 200));
        }

        $planosParsed = $this->parsePlanosTableHtml($planosHtml);
        $planosFiltrados = $this->filtrarPlanosPorFaixaEtariaEUnicoPorOperadora($planosParsed, $lives);

        return array_values($planosFiltrados);
    }

    /**
     * Extrai lista de planos da tabela HTML (tr.sim-op-planos). Ignora acomodação AMB.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parsePlanosTableHtml(string $html): array
    {
        $planos = [];
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//tr[@class="sim-op-planos"]') as $row) {
            $operadoraNome = trim($xpath->query('.//p', $row)->item(0)?->textContent ?? '');
            $operadoraLogo = null;
            $img = $xpath->query('.//img', $row)->item(0);
            if ($img instanceof \DOMElement) {
                $operadoraLogo = $img->getAttribute('src');
            }
            $operadoraDesc = trim($xpath->query('.//b[@class="fz-7"]', $row)->item(0)?->textContent ?? '');

            foreach ($xpath->query('.//ul[@class="collapser smallest planos ta-l"]/li', $row) as $planLi) {
                $acomodacaoSigla = trim($xpath->query('.//span[@class="tipsy"]', $planLi)->item(0)?->textContent ?? '');
                if ($acomodacaoSigla === 'AMB') {
                    continue;
                }
                $acomodacaoLabel = match ($acomodacaoSigla) {
                    'E' => 'Enfermaria',
                    'A' => 'Apartamento',
                    default => $acomodacaoSigla,
                };
                $planos[] = [
                    'id' => (int) $planLi->getAttribute('data-id'),
                    'operadora' => $operadoraNome,
                    'operadora_logo' => $operadoraLogo,
                    'operadora_descricao' => $operadoraDesc,
                    'nome' => trim($xpath->query('.//i', $planLi)->item(0)?->textContent ?? ''),
                    'acomodacao' => $acomodacaoLabel,
                    'acomodacao_sigla' => $acomodacaoSigla,
                    'vigencia' => $planLi->getAttribute('data-vi'),
                ];
            }
        }

        return $planos;
    }

    /**
     * Remove planos 50+/59+ quando não há vida na faixa; mantém um plano por operadora.
     *
     * @param  array<int, array<string, mixed>>  $planos
     * @param  array<string, int>  $lives
     * @return array<int, array<string, mixed>>
     */
    protected function filtrarPlanosPorFaixaEtariaEUnicoPorOperadora(array $planos, array $lives): array
    {
        $planos = array_filter($planos, function (array $plan) use ($lives): bool {
            $texto = ($plan['nome'] ?? '').' '.($plan['operadora'] ?? '').' '.($plan['operadora_descricao'] ?? '');
            $idadeMinima = null;
            if (str_contains($texto, '50+') || str_contains($texto, '+50')) {
                $idadeMinima = 50;
            } elseif (str_contains($texto, '59+') || str_contains($texto, '+59')) {
                $idadeMinima = 59;
            }
            if ($idadeMinima !== null) {
                $temVidaNaFaixa = false;
                foreach (self::AgeBracketKeys as $key) {
                    if ((int) ($lives[$key] ?? 0) <= 0) {
                        continue;
                    }
                    $maxIdadeFaixa = $key === '59+' ? 999 : (int) explode('-', $key)[1];
                    if ($maxIdadeFaixa >= $idadeMinima) {
                        $temVidaNaFaixa = true;
                        break;
                    }
                }
                if (! $temVidaNaFaixa) {
                    return false;
                }
            }

            return true;
        });

        $vistos = [];
        $umPorOperadora = [];
        foreach ($planos as $plan) {
            $op = $plan['operadora'] ?? '';
            if ($op === '' || isset($vistos[$op])) {
                continue;
            }
            $vistos[$op] = true;
            $umPorOperadora[] = $plan;
        }

        return $umPorOperadora;
    }

    protected function client(): PendingRequest
    {
        return Http::withOptions([
            'cookies' => $this->cookieJar,
            'verify' => config('services.simulador_online.verify_ssl', false),
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

        $cookiesArray = [];
        foreach ($response->cookies() as $cookie) {
            $cookiesArray[$cookie->getName()] = $cookie->getValue();
        }

        return [
            'success' => $response->successful() || $response->redirect(),
            'status' => $response->status(),
            'body' => $response->body(),
            'cookies' => $cookiesArray,
            'headers' => $response->headers(),
            'csrf_token' => $csrfToken,
            'session_token' => $cookiesArray['PHPSESSID'] ?? null,
        ];
    }

    public function checkAuthentication(): bool
    {
        try {
            $response = $this->client()->get($this->baseUrl.'/ope/adm/credenciados/1.json', [
                'regiao' => self::DefaultRegionId,
                'q' => 'test',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
