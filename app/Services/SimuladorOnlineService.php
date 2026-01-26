<?php

namespace App\Services;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SimuladorOnlineService
{
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
     * Cria um cliente HTTP com cookies compartilhados
     */
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

    /**
     * Extrai o token CSRF do HTML
     */
    protected function extractCsrfToken(string $html): ?string
    {
        if (preg_match('/name="login\[_token\]"\s+value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Realiza login na API SimuladorOnline
     */
    public function login(?string $username = null, ?string $password = null): array
    {
        $username = $username ?? $this->username;
        $password = $password ?? $this->password;

        if (! $username || ! $password) {
            throw new \Exception('Credenciais não configuradas no .env');
        }

        // 1. Busca página de login para obter cookies e token CSRF
        /** @var Response $loginPageResponse */
        $loginPageResponse = $this->client()->get($this->baseUrl.$this->loginPath);
        $loginPageHtml = $loginPageResponse->body();
        $csrfToken = $this->extractCsrfToken($loginPageHtml);

        if (! $csrfToken) {
            throw new \Exception('Não foi possível extrair o token CSRF da página de login');
        }

        // 2. Faz POST para /login_check (mesmo endpoint do formulário HTML)
        /** @var Response $response */
        $response = $this->client()->asForm()->post($this->baseUrl.'/login_check', [
            'login[usuario]' => $username,
            'login[senha]' => $password,
            'login[_token]' => $csrfToken,
        ]);

        // Login bem-sucedido geralmente retorna 302 (redirect) ou 200
        $success = $response->successful() || $response->redirect();

        // Extrai cookies do CookieJar
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
            'session_token' => $sessionId, // PHPSESSID - token de sessão
        ];
    }

    /**
     * Verifica se está autenticado
     */
    public function checkAuthentication(): bool
    {
        try {
            /** @var Response $response */
            $response = $this->client()->get($this->baseUrl.'/ope/adm/credenciados/1.json', [
                'regiao' => 2,
                'q' => 'test',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Busca hospitais (tenta primeiro, faz login se necessário)
     */
    public function buscarHospitais(int $regiao, string $query): array
    {
        // Tenta buscar primeiro (pode já estar autenticado)
        /** @var Response $response */
        $response = $this->client()
            ->withHeaders([
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => $this->baseUrl.'/comparativo/',
                'X-Requested-With' => 'XMLHttpRequest',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            ])
            ->get($this->baseUrl.'/ope/adm/credenciados/1.json', [
                'regiao' => $regiao,
                'q' => $query,
            ]);

        // Verifica se retornou dados válidos (status 200 e conteúdo JSON)
        $body = $response->body();
        $isJson = ! empty($body) && $response->successful() && str_starts_with(trim($body), '[') || str_starts_with(trim($body), '{');

        // Se não conseguiu ou retornou vazio/HTML, faz login e tenta novamente
        if (! $response->successful() || ! $isJson || empty($body)) {
            $this->login();

            // Tenta novamente após login
            $response = $this->client()
                ->withHeaders([
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Referer' => $this->baseUrl.'/comparativo/',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                ])
                ->get($this->baseUrl.'/ope/adm/credenciados/1.json', [
                    'regiao' => $regiao,
                    'q' => $query,
                ]);

            $body = $response->body();
        }

        if (! $response->successful()) {
            throw new \Exception('Erro ao buscar hospitais: Status '.$response->status().' - Body: '.substr($body, 0, 200));
        }

        // Tenta decodificar JSON
        $json = $response->json();

        if ($json === null && ! empty($body)) {
            // Se não conseguiu decodificar, retorna o body para debug
            throw new \Exception('Resposta não é JSON válido. Body: '.substr($body, 0, 500));
        }

        // Se retornou um objeto, tenta extrair array de propriedades comuns
        if (is_array($json) && ! empty($json) && ! isset($json[0])) {
            // Pode ser um objeto com propriedades como 'data', 'results', 'hospitais', etc.
            $json = $json['data'] ?? $json['results'] ?? $json['hospitais'] ?? $json['items'] ?? $json;
        }

        // Garante que retorna array
        if (! is_array($json)) {
            return [];
        }

        return $json;
    }
}
