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

        $formData['simulacao[corretorEmail]'] = config('services.simulador_online.corretor_email', '');
        $formData['simulacao[adesao][opes]'] = '';
        $formData['simulacao[adesao][administradora]'] = '';
        $formData['simulacao[adesao][entidade]'] = '';
        
        // Map Profession ID if available
        $profId = $validatedInput['profession_id'] ?? '';
        $formData['simulacao[adesao][profissao]'] = ($profile === 'adesao') ? $profId : '';

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
        $planosFiltrados = $this->filtrarPlanosPorFaixaEtariaECategorias($planosParsed, $lives);

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
     * Filtra planos por faixa etária e mantém um por categoria (Acomodação + Coparticipação) para cada operadora.
     */
    protected function filtrarPlanosPorFaixaEtariaECategorias(array $planos, array $lives): array
    {
        // Passo 1: Filtrar por Faixa Etária (Lógica existente)
        $planosValidos = array_filter($planos, function (array $plan) use ($lives): bool {
            $rawText = ($plan['nome'] ?? '').' '.($plan['operadora'] ?? '').' '.($plan['operadora_descricao'] ?? '');
            $texto = mb_strtolower($rawText);
            
            $idadeMinima = null;
            if (str_contains($texto, '50+') || str_contains($texto, '+50')) {
                $idadeMinima = 50;
            } elseif (str_contains($texto, '59+') || str_contains($texto, '+59')) {
                $idadeMinima = 59;
            } elseif (str_contains($texto, 'senior') || str_contains($texto, 'sênior')) {
                $idadeMinima = 49;
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

        // Passo 2: Agrupar por Operadora e depois por Categoria
        $grouped = [];

        foreach ($planosValidos as $plan) {
            $rawOp = $plan['operadora'] ?? 'Outros';
            
            // Extrair "Nome Base" mantendo os grupos de produtos importantes mas limpando o sufixo descritivo
            $opParts = explode(' - ', $rawOp);
            $baseOp = trim($opParts[0]);

            // Se for "Bradesco Copart." que apareceu no JSON, limpa apenas os parênteses
            if (str_contains($baseOp, 'BRADESCO')) {
                 $baseOp = preg_replace('/ \(.*\)$/', '', $baseOp); // Resultará em "BRADESCO", "BRADESCO COPART.", "BRADESCO HOSPITALAR"
                 $baseOp = trim($baseOp);
            }
            // Para outras marcas, manteremos como base até antes do traço
            // O Amil e Amil One já vão vir naturalmente separados ("AMIL" e "AMIL ONE") antes do traço

            
            // Se limpou tudo acidentalmente, volta para o raw
            $opKey = empty($baseOp) ? $rawOp : $baseOp;
            
            // Detectar Coparticipação
            $fullText = mb_strtoupper(($plan['operadora'] ?? '') . ' ' . ($plan['nome'] ?? '') . ' ' . ($plan['operadora_descricao'] ?? ''));
            $copartStatus = 'SEM_COPART'; // Padrão será SEM_COPART se não falar nada

            if (str_contains($fullText, 'PARCIAL') || preg_match('/[0-9]+%/', $fullText)) {
                // Se diz "Parcial" explícito ou cita um percentual (ex: 30%)
                $copartStatus = 'COPART_PARCIAL';
            } elseif (str_contains($fullText, 'COPART') || str_contains($fullText, 'COM COPART') || str_contains($fullText, 'C/ COPART')) {
                // É com coparticipação tradicional sem percentual especificado
                $copartStatus = 'COPART_TOTAL'; 
            } elseif (str_contains($fullText, 'SEM COPART') || str_contains($fullText, 'S/ COPART')) {
                $copartStatus = 'SEM_COPART';
            }

            $acomodacao = $plan['acomodacao'] === 'Apartamento' ? 'APT' : 'ENF';
            
            $categoryKey = "{$acomodacao}_{$copartStatus}";

            if (!isset($grouped[$opKey][$categoryKey])) {
                $grouped[$opKey][$categoryKey] = $plan;
            }
        }

        // Flatten results, ignorando a chave da operadora agregada (ex: AMIL, BRADESCO) para a saída final
        $finalList = [];
        foreach ($grouped as $brandGroup => $categories) {
            foreach ($categories as $plan) {
                $finalList[] = $plan;
            }
        }

        return $finalList;
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
    public function getSimulationRawHtml(array $planIds, array $lives, string $profile, ?string $nome = null, ?string $professionId = null): string
    {
        $this->login();

        $tipoTabela = match ($profile) {
            'pme' => 3,
            'adesao' => 4,
            default => 2,
        };

        // If Adesao, ensure tipoTabela is 4
        if ($profile === 'adesao') {
            $tipoTabela = 4;
        }

        $totalVidas = array_sum($lives);
        
        $novaPageHtml = $this->client()->get($this->baseUrl.'/simulacao/nova')->body();
        $csrfToken = null;
        if (preg_match('/name="simulacao\[_token\]"\s+value="([^"]+)"/', $novaPageHtml, $matches)) {
            $csrfToken = $matches[1];
        }

        $corretorEmail = config('services.simulador_online.corretor_email', '');
        $textoInicial = "Primeiramente, agradecemos pelo seu contato.\r\nInformamos que os custos e as condições abaixo são determinadas por suas respectivas operadoras.\r\n";

        $params = [
            'simulacao[destNome]' => $nome ?? '',
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
        $bodyParts[] = rawurlencode('simulacao[regiao]').'=2'; // Should be dynamic too, but sticking to request scope
        $bodyParts[] = rawurlencode('simulacao[corretorEmail]').'='.rawurlencode($corretorEmail);
        $bodyParts[] = rawurlencode('simulacao[adesao][opes]').'=';
        $bodyParts[] = rawurlencode('simulacao[adesao][administradora]').'=';
        $bodyParts[] = rawurlencode('simulacao[adesao][entidade]').'=';
        
        // Inject Profession ID if Adesao
        $profValue = ($profile === 'adesao' && $professionId) ? $professionId : '';
        $bodyParts[] = rawurlencode('simulacao[adesao][profissao]').'='.rawurlencode($profValue);
        
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
    /**
     * Analisa o HTML do cliente e identifica planos que não possuem "H" (internação eletiva) na rede credenciada.
     */
    public static function identifyPlansWithoutInternacao(string $cleanHtml, ?string $targetHospital = null): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // Hack to handle UTF-8 correctly in loadHTML
        $dom->loadHTML('<?xml encoding="UTF-8">'.$cleanHtml);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $plansWithoutInternacao = [];
        
        // Normalize search term if present
        $normalizedTarget = $targetHospital ? self::normalizeString($targetHospital) : null;

        foreach ($xpath->query('//div[contains(@class,"operadora")]') as $operadora) {
            /** @var \DOMElement $operadora */
            // Busca o nome do plano/operadora
            $nomeOperadora = trim($xpath->query('.//p[contains(@class, "fz-12")]', $operadora)->item(0)?->textContent ?? '');
            if (!$nomeOperadora) {
               $nomeOperadora = trim($xpath->query('.//div[contains(@class, "logotipo")]/p', $operadora)->item(0)?->textContent ?? 'Plano Desconhecido');
            }
            
            $hasH = false;
            $hospitalFound = false;

            foreach ($xpath->query('.//div[contains(@class,"bloco")]', $operadora) as $bloco) {
                /** @var \DOMElement $bloco */
                $h4 = $xpath->query('.//h4', $bloco)->item(0);
                
                // Verifica bloco de Rede Credenciada
                if ($h4 && trim($h4->textContent) === 'Rede Credenciada') {
                    $textoBloco = $bloco->textContent;
                    $normalizedBloco = self::normalizeString($textoBloco);

                    if ($normalizedTarget) {
                        // Verifica se o hospital alvo está neste bloco (case-insensitive & accent-insensitive)
                        // Using normalized strings for comparison
                        if (str_contains($normalizedBloco, $normalizedTarget)) {
                            $hospitalFound = true;
                            
                            // Check for H in the original text or normalized? 
                            // The "H" usually appears as " - H" or " (H)" or just " H" at end of line.
                            // Let's use a robust regex on the ORIGINAL text to preserve case if needed, 
                            // but actually "H" is simple.
                            // Regex covers:
                            // 1. " - H" (space hyphen space H)
                            // 2. "(H)" (parentheses H)
                            // 3. " H" (space H) at end of a name/line (less safe, might match middle name starting with H)
                            // Safer validation: " - H" is the standard from Simulador. "(H)" is another common one.
                            // We look for these patterns specifically near the hospital name? 
                            // Complex to find "near" in a blob.
                            // Rule: if hospital is found, does the block *contain* the H indicator?
                            // Issue: If there are multiple hospitals, we might match the H of another hospital.
                            // However, we are limited to the block text.
                            // Ideally, we'd split lines.
                            // Let's try to be smarter: split block by lines (often separated by <br> or newlines)
                            // But $bloco->textContent strips <br> mostly or joins them.
                            // Let's stick to "if block contains - H" for now as per previous logic, just improved regex.
                            
                            // Regex Explanation:
                            // \s : whitespace
                            // [-\(]? : optional hyphen or open parenthesis
                            // \s* : optional whitespace
                            // H : literal H
                            // [\)]? : optional close parenthesis
                            // \b : word boundary (so it doesn't match House)
                            if (preg_match('/(\s-\s*H\b|\(H\))/i', $textoBloco)) {
                                $hasH = true;
                            }
                        }
                    } else {
                        // Lógica Genérica (sem hospital alvo)
                        if (preg_match('/(\s-\s*H\b|\(H\))/i', $textoBloco)) {
                            $hasH = true;
                        }
                    }
                    // Se já achou rede credenciada, break
                    break; 
                }
            }

            if ($targetHospital) {
                // Se busca hospital específico:
                if (!$hospitalFound) {
                    $plansWithoutInternacao[] = ['name' => $nomeOperadora, 'reason' => 'missing_hospital'];
                } elseif (!$hasH) {
                    // Found hospital but didn't find " - H" or "(H)" in that block
                    $plansWithoutInternacao[] = ['name' => $nomeOperadora, 'reason' => 'no_elective'];
                }
            } else {
                // Busca genérica
                if (!$hasH) {
                    $plansWithoutInternacao[] = ['name' => $nomeOperadora, 'reason' => 'no_elective'];
                }
            }
        }

        return $plansWithoutInternacao;
    }

    /**
     * Remove accents and converts to lowercase for comparison.
     */
    protected static function normalizeString(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = str_replace(
            ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ'],
            ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'],
            $str
        );
        return $str;
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
