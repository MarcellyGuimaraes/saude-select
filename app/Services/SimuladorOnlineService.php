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
     * Busca hospitais para o autocomplete.
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
            'nome' => $item['nome'] ?? $item['name'] ?? $item['full_descricao'] ?? '',
        ], $rawList);

        return [
            'hospitais' => array_values($hospitais),
            'query' => $query,
            'region' => $regionId,
            'raw' => $rawList,
        ];
    }

    /**
     * Monta a simulação e retorna os planos encontrados.
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
            'regiao' => $validatedInput['regiao'] ?? self::DefaultRegionId,
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
     * Extrai lista de planos da tabela HTML (tr.sim-op-planos), ignorando acomodação AMB.
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
     * Remove planos 50+/59+ sem vidas na faixa e mantém um plano por operadora.
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

    /**
     * Retorna o HTML bruto da simulação para fins de teste (Adesão padrão).
     */
    public function getAdesaoRawHtml(): string
    {
        $planIds = [408270, 420159, 384619];
        $lives = ['29-33' => 2];
        
        return $this->getSimulationRawHtml($planIds, $lives, 'adesao');
    }

    /**
     * Gera o HTML bruto da simulação para planos específicos.
     */
    public function getSimulationRawHtml(array $planIds, array $lives, string $profile): string
    {
        $this->login();

        $tipoTabela = match ($profile) {
            'pme' => 3,
            'adesao' => 4,
            default => 2,
        };

        $totalVidas = array_sum($lives);
        
        $novaPageHtml = $this->client()->get($this->baseUrl.'/simulacao/nova')->body();
        $csrfToken = null;
        if (preg_match('/name="simulacao\[_token\]"\s+value="([^"]+)"/', $novaPageHtml, $matches)) {
            $csrfToken = $matches[1];
        }

        $corretorEmail = config('services.simulador_online.corretor_email', '');
        $textoInicial = "Primeiramente, agradecemos pelo seu contato.\r\nInformamos que os custos e as condições abaixo são determinadas por suas respectivas operadoras.\r\n";

        $params = [
            'simulacao[destNome]' => '',
            'simulacao[destContato]' => '',
            'simulacao[destEmail]' => '',
            'simulacao[tipoTabela]' => $tipoTabela,
            'simulacao[tipoPlano]' => '1',
            'simulacao[acomodacao]' => '',
            'simulacao[totalVidas]' => $totalVidas,
            'simulacao[filtros][abrangencia]' => '',
            'simulacao[filtros][tipoOperadora]' => '',
            'simulacao[filtros][segmento]' => '',
            'simulacao[filtros][fatormoder]' => '',
        ];

        $bodyParts = [];
        foreach ($params as $k => $v) {
            $bodyParts[] = rawurlencode($k).'='.rawurlencode($v);
        }
        
        foreach ([1, 2, 3, 4, 5] as $v) {
            $bodyParts[] = rawurlencode('simulacao[info][]').'='.$v;
        }

        foreach (self::AgeBracketKeys as $index => $key) {
            $vidas = (int) ($lives[$key] ?? 0);
            $bodyParts[] = rawurlencode("simulacao[faixas][{$index}][vidas]").'='.$vidas;
        }

        foreach ($planIds as $id) {
            $bodyParts[] = rawurlencode('simulacao[tabelas][]').'='.$id;
        }

        $bodyParts[] = rawurlencode('simulacao[textoInicial]').'='.rawurlencode($textoInicial);
        $bodyParts[] = rawurlencode('simulacao[regiao]').'=2';
        $bodyParts[] = rawurlencode('simulacao[corretorEmail]').'='.rawurlencode($corretorEmail);
        $bodyParts[] = rawurlencode('simulacao[adesao][opes]').'=';
        $bodyParts[] = rawurlencode('simulacao[adesao][administradora]').'=';
        $bodyParts[] = rawurlencode('simulacao[adesao][entidade]').'=';
        $bodyParts[] = rawurlencode('simulacao[adesao][profissao]').'=';
        
        if ($csrfToken) {
            $bodyParts[] = rawurlencode('simulacao[_token]').'='.rawurlencode($csrfToken);
        }

        $bodyString = implode('&', $bodyParts);
        $postUrl = $this->baseUrl.'/simulacao/nova?'.time();

        $response = $this->client()
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Origin' => $this->baseUrl,
                'Referer' => $this->baseUrl.'/simulacao/nova',
            ])
            ->withBody($bodyString, 'application/x-www-form-urlencoded')
            ->post($postUrl);

        if (! $response->successful()) {
            throw new \Exception('Erro ao buscar simulação: Status '.$response->status().' - Body: '.substr($response->body(), 0, 200));
        }

        return $response->body();
    }

    /**
     * Verifica quais planos da simulação não possuem internação eletiva.
     */
    public function getPlansWithoutInternacao(array $planIds, array $lives, string $profile): array
    {
        $rawHtml = $this->getSimulationRawHtml($planIds, $lives, $profile);
        $cleanHtml = self::extractClientProposalContent($rawHtml, $this->baseUrl);

        return self::identifyPlansWithoutInternacao($cleanHtml);
    }

    /**
     * Analisa o HTML do cliente e identifica planos que não possuem "H" (internação eletiva) na rede credenciada.
     */
    public static function identifyPlansWithoutInternacao(string $cleanHtml): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$cleanHtml);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $plansWithoutInternacao = [];

        foreach ($xpath->query('//div[contains(@class,"operadora")]') as $operadora) {
            /** @var \DOMElement $operadora */
            // Busca o nome do plano/operadora de forma mais flexível
            $nomeOperadora = trim($xpath->query('.//p[contains(@class, "fz-12")]', $operadora)->item(0)?->textContent ?? '');
            if (!$nomeOperadora) {
               $nomeOperadora = trim($xpath->query('.//div[contains(@class, "logotipo")]/p', $operadora)->item(0)?->textContent ?? 'Plano Desconhecido');
            }
            
            $hasH = false;
            foreach ($xpath->query('.//div[contains(@class,"bloco")]', $operadora) as $bloco) {
                /** @var \DOMElement $bloco */
                $h4 = $xpath->query('.//h4', $bloco)->item(0);
                if ($h4 && trim($h4->textContent) === 'Rede Credenciada') {
                    // Verifica se existe " - H" ou " -H" no texto, garantindo que seja um código isolado
                    if (preg_match('/\s-\s*H\b/', $bloco->textContent)) {
                        $hasH = true;
                    }
                    break;
                }
            }

            if (!$hasH) {
                $plansWithoutInternacao[] = $nomeOperadora;
            }
        }

        return $plansWithoutInternacao;
    }

    /**
     * Limpa o HTML bruto do simulador para o formato "sistema",
     * equivalente ao exemplo html-simulacao-sistema-v.html.
     *
     * Mantém a estrutura principal (#geral, seção de beneficiários, operadoras,
     * assinaturas e rodapé), mas remove cabeçalho do sistema, menu,
     * alertas, barra de ações e tags de script/estilo.
     */
    public static function extractProposalContent(string $fullHtml, string $baseUrl = 'https://app.simuladoronline.com'): string
    {
        $sourceDom = new DOMDocument;
        libxml_use_internal_errors(true);
        $sourceDom->loadHTML('<?xml encoding="UTF-8">'.$fullHtml);
        libxml_clear_errors();
        $sourceXpath = new DOMXPath($sourceDom);

        $outDom = new DOMDocument('1.0', 'UTF-8');
        $outDom->formatOutput = false;

        $htmlEl = $outDom->createElement('html');
        $htmlEl->setAttribute('lang', 'pt-BR');
        $outDom->appendChild($htmlEl);

        $bodyEl = $outDom->createElement('body');
        $htmlEl->appendChild($bodyEl);

        /** @var \DOMElement|null $origBody */
        $origBody = $sourceXpath->query('//body')->item(0);
        if ($origBody instanceof \DOMElement) {
            foreach ($origBody->attributes as $attr) {
                $bodyEl->setAttribute($attr->nodeName, $attr->nodeValue);
            }
        }

        /** @var \DOMElement|null $geral */
        $geral = $sourceXpath->query('//*[@id="geral"]')->item(0);
        if (! $geral instanceof \DOMElement) {
            if ($origBody instanceof \DOMElement) {
                foreach ($origBody->childNodes as $child) {
                    $bodyEl->appendChild($outDom->importNode($child, true));
                }

                return $outDom->saveHTML();
            }

            return $fullHtml;
        }

        $geralImported = $outDom->importNode($geral, true);
        $bodyEl->appendChild($geralImported);

        $outXpath = new DOMXPath($outDom);

        // Remove header, nav e alertas internos
        foreach (['//header', '//nav', '//div[contains(@class,"alert")]'] as $query) {
            foreach ($outXpath->query($query) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Remove barra de ações (Voltar / Imprimir / E-mail / WhatsApp)
        foreach ($outXpath->query('//div[contains(@class,"top-actions")]') as $node) {
            $node->parentNode?->removeChild($node);
        }

        // Ajusta logotipo principal da simulação para usar a marca Saúde Select (somente texto, sem imagem)
        /** @var \DOMElement|null $simulacao */
        $simulacao = $outXpath->query('//div[contains(@class,"simulacao") and contains(@class,"printable")]')->item(0);
        if ($simulacao instanceof \DOMElement) {
            foreach ($simulacao->childNodes as $child) {
                if (! $child instanceof \DOMElement) {
                    continue;
                }
                if (strtolower($child->tagName) === 'div' && str_contains((string) $child->getAttribute('class'), 'logotipo')) {
                    while ($child->firstChild !== null) {
                        $child->removeChild($child->firstChild);
                    }
                    $span = $outDom->createElement('span', 'Saúde Select');
                    $span->setAttribute('style', 'font-weight: 700; font-size: 16pt; color: #1e40af;');
                    $child->appendChild($span);

                    break;
                }
            }
        }

        // Remove scripts/estilos que possam ter ficado dentro do #geral
        foreach (['script', 'style', 'link', 'base'] as $tag) {
            foreach ($outXpath->query('//'.$tag) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Inliner de imagens para garantir exibição no PDF (evita dependência de carregamento remoto)
        self::inlineImages($outDom, $outXpath, $baseUrl);

        // Retorna apenas o conteúdo da simulação (para ser injetado em proposta-pdf.blade.php)
        /** @var \DOMElement|null $simNode */
        $simNode = $outXpath->query('//div[contains(@class,"simulacao") and contains(@class,"printable")]')->item(0);
        if (! $simNode instanceof \DOMElement) {
            /** @var \DOMElement|null $geralNode */
            $geralNode = $outXpath->query('//*[@id="geral"]')->item(0);
            if (! $geralNode instanceof \DOMElement) {
                return $outDom->saveHTML();
            }
            $simNode = $geralNode;
        }

        $html = '';
        foreach ($simNode->childNodes as $child) {
            $html .= $outDom->saveHTML($child);
        }

        return self::normalizeBreaks($html);
    }

    /**
     * Limpa o HTML bruto do simulador para o formato "cliente",
     * equivalente ao exemplo html-simulacao-cliente-v.html.
     *
     * Mantém: cabeçalho da simulação, tabela de beneficiários,
     * para cada operadora: logotipo, linha de vigência, primeira tabela
     * de faixas/valores e bloco "Rede Credenciada" (com suas legendas).
     */
    public static function extractClientProposalContent(string $fullHtml, string $baseUrl = 'https://app.simuladoronline.com'): string
    {
        $baseHtml = self::extractProposalContent($fullHtml, $baseUrl);

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$baseHtml);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $operadoras = $xpath->query('//div[contains(@class,"operadora")]');
        foreach ($operadoras as $operadora) {
            if (! $operadora instanceof \DOMElement) {
                continue;
            }

            $tablesKept = 0;
            $nodesToRemove = [];

            foreach (iterator_to_array($operadora->childNodes) as $child) {
                if (! $child instanceof \DOMElement) {
                    // Mantém textos e quebras de linha
                    continue;
                }

                $tag = strtolower($child->tagName);
                $class = (string) $child->getAttribute('class');

                $isLogotipo = $tag === 'div' && str_contains($class, 'logotipo');
                $isDateTaC = $tag === 'div' && str_contains($class, 'ta-c');
                $isBr = $tag === 'br';

                if ($isLogotipo || $isDateTaC || $isBr) {
                    continue;
                }

                if ($tag === 'table') {
                    if ($tablesKept === 0) {
                        $tablesKept++;

                        continue;
                    }
                    $nodesToRemove[] = $child;

                    continue;
                }

                $isBloco = $tag === 'div' && str_contains($class, 'bloco');
                if ($isBloco) {
                    /** @var \DOMElement|null $h4 */
                    $h4 = $xpath->query('.//h4', $child)->item(0);
                    $title = $h4 instanceof \DOMElement ? trim($h4->textContent) : '';
                    if ($title === 'Rede Credenciada') {
                        continue;
                    }

                    $nodesToRemove[] = $child;

                    continue;
                }

                // Qualquer outro elemento direto dentro da operadora é removido
                $nodesToRemove[] = $child;
            }

            foreach ($nodesToRemove as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Remove blocos técnicos remanescentes diretamente sob a simulação, se houver
        foreach ($xpath->query('//div[contains(@class,"simulacao") and contains(@class,"printable")]//div[contains(@class,"bloco")]') as $bloco) {
            if (! $bloco instanceof \DOMElement) {
                continue;
            }
            /** @var \DOMElement|null $h4 */
            $h4 = $xpath->query('.//h4', $bloco)->item(0);
            $title = $h4 instanceof \DOMElement ? trim($h4->textContent) : '';
            if ($title === 'Rede Credenciada') {
                continue;
            }

            $bloco->parentNode?->removeChild($bloco);
        }

        // Garante remoção de scripts/estilos no resultado final
        foreach (['script', 'style', 'link', 'base'] as $tag) {
            foreach ($xpath->query('//'.$tag) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Inliner de imagens também para a versão do cliente
        self::inlineImages($dom, $xpath, $baseUrl);

        // Retorna apenas o conteúdo da simulação para o PDF do cliente
        /** @var \DOMElement|null $simNode */
        $simNode = $xpath->query('//div[contains(@class,"simulacao") and contains(@class,"printable")]')->item(0);
        if (! $simNode instanceof \DOMElement) {
            /** @var \DOMElement|null $bodyNode */
            $bodyNode = $xpath->query('//body')->item(0);
            if (! $bodyNode instanceof \DOMElement) {
                return $dom->saveHTML();
            }
            $simNode = $bodyNode;
        }

        $html = '';
        foreach ($simNode->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return self::normalizeBreaks($html);
    }

    /**
     * Converte imagens <img> em data URLs base64 para garantir que apareçam no PDF,
     * mesmo se o DomPDF não puder buscar recursos remotos.
     */
    protected static function inlineImages(DOMDocument $dom, DOMXPath $xpath, string $baseUrl): void
    {
        foreach ($xpath->query('//img') as $img) {
            if (! $img instanceof \DOMElement) {
                continue;
            }

            $src = trim($img->getAttribute('src'));
            if ($src === '' || str_starts_with($src, 'data:')) {
                continue;
            }

            $url = $src;
            if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                $url = rtrim($baseUrl, '/').'/'.ltrim($url, '/');
            }

            try {
                $response = Http::withOptions([
                    'verify' => config('services.simulador_online.verify_ssl', false),
                    'timeout' => 15,
                ])->get($url);

                if (! $response->successful()) {
                    continue;
                }

                $mime = $response->header('Content-Type') ?? 'image/jpeg';
                if (! str_starts_with($mime, 'image/')) {
                    $mime = 'image/jpeg';
                }

                $img->setAttribute(
                    'src',
                    'data:'.$mime.';base64,'.base64_encode($response->body())
                );
            } catch (\Throwable $e) {
                // Em caso de erro ao baixar a imagem, simplesmente mantém o src original
                continue;
            }
        }
    }

    /**
     * Normaliza quebras de linha <br>, removendo repetições excessivas.
     */
    protected static function normalizeBreaks(string $html): string
    {
        // Colapsa sequências de 2+ <br> (com ou sem barra) em um único <br>
        $html = preg_replace('/(\s*<br\s*\/?>\s*){2,}/i', '<br>', $html ?? '') ?? $html;

        return $html;
    }
}
