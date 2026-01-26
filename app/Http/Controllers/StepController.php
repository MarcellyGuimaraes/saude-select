<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StepController extends Controller
{
    public function __construct(
        protected \App\Services\SimuladorOnlineService $simuladorService
    ) {}

    public function show(int $step): View
    {
        // Valida se o step é válido (1 a 5)
        if ($step < 1 || $step > 5) {
            abort(404);
        }

        return view("steps.step-{$step}");
    }

    public function final(): View
    {
        return view('steps.step-final');
    }

    /**
     * Busca hospitais para autocomplete (Step 1)
     */
    public function buscarHospitais(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        try {
            $query = $request->input('q');

            // Sempre usa região 2 (Rio de Janeiro)
            $regiao = 2;

            $hospitais = $this->simuladorService->buscarHospitais($regiao, $query);

            // Formata a resposta para o frontend
            // A API pode retornar array de objetos ou array de strings
            $hospitaisFormatados = [];

            if (is_array($hospitais)) {
                foreach ($hospitais as $hospital) {
                    if (is_array($hospital)) {
                        // dd($hospital);
                        // Tenta diferentes formatos de resposta da API
                        $nome = $hospital['full_descricao'];

                        // Se ainda não encontrou, tenta pegar o primeiro valor não-numérico
                        if (empty($nome)) {
                            foreach ($hospital as $key => $value) {
                                if (is_string($value) && ! is_numeric($key)) {
                                    $nome = $value;
                                    break;
                                }
                            }
                        }

                        if ($nome) {
                            $hospitaisFormatados[] = [
                                'id' => $hospital['id'] ?? $hospital['codigo'] ?? null,
                                'nome' => $nome,
                            ];
                        }
                    } elseif (is_string($hospital)) {
                        // Formato string simples
                        $hospitaisFormatados[] = [
                            'id' => null,
                            'nome' => $hospital,
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'hospitais' => $hospitaisFormatados,
                'debug' => config('app.debug') ? [
                    'raw_response' => $hospitais,
                    'query' => $query,
                    'regiao' => $regiao,
                ] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca planos para exibição (Step 4)
     */
    public function buscarPlanos(Request $request): JsonResponse
    {
        $request->validate([
            'profile' => 'required|string|in:pme,adesao,cpf',
            'lives' => 'required|array',
            'hospital' => 'nullable|string',
            'hospitalId' => 'nullable|integer',
        ]);

        try {
            // Mapeia profile para tipoTabela
            $tipoTabela = match ($request->input('profile')) {
                'pme' => 3,
                'adesao' => 4,
                'cpf' => 2,
                default => 2,
            };

            // Calcula total de vidas
            $lives = $request->input('lives', []);
            $totalVidas = (int) ($lives['0-18'] ?? 0) + (int) ($lives['19-23'] ?? 0) + (int) ($lives['24-58'] ?? 0);

            // Mapeia faixas do frontend para faixas da API
            // Frontend: 0-18, 19-23, 24-58
            // API: faixa_0 (0-18), faixa_1 (19-23), faixa_2 (24-28), faixa_3 (29-33), etc.
            $faixas = [];
            $faixas[0] = ['vidas' => (int) ($lives['0-18'] ?? 0)]; // 0-18
            $faixas[1] = ['vidas' => (int) ($lives['19-23'] ?? 0)]; // 19-23

            // Distribui 24-58 nas faixas 2-9 (24-28, 29-33, 34-38, 39-43, 44-48, 49-53, 54-58, 59+)
            $vidas24_58 = (int) ($lives['24-58'] ?? 0);
            // Por enquanto, coloca tudo na faixa_2 (24-28) como simplificação
            // TODO: Distribuir melhor entre as faixas se necessário
            $faixas[2] = ['vidas' => $vidas24_58];
            for ($i = 3; $i < 10; $i++) {
                $faixas[$i] = ['vidas' => 0];
            }

            // Prepara dados para a API
            $dadosSimulacao = [
                'tipoTabela' => $tipoTabela,
                'totalVidas' => $totalVidas,
                'regiao' => 2, // Sempre região 2 (Rio de Janeiro)
                'faixas' => $faixas,
            ];

            // Se houver hospital selecionado, adiciona ao info
            // O service sempre adiciona 1,2,3,4,5, então só passamos o hospital_id adicional se houver
            if ($request->has('hospitalId') && $request->input('hospitalId')) {
                $dadosSimulacao['info'] = [(int) $request->input('hospitalId')];
            }

            // Busca planos (retorna HTML)
            $html = $this->simuladorService->buscarPlanos($dadosSimulacao);

            // Parseia HTML e extrai dados
            $planos = $this->parsearPlanosHTML($html);

            return response()->json([
                'success' => true,
                'planos' => $planos,
                'total' => count($planos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Parseia HTML da tabela de planos e extrai dados estruturados
     */
    public function parsearPlanosHTML(string $html): array
    {
        $planos = [];
        $dom = new \DOMDocument;

        // Suprime erros de HTML malformado
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Encontra todas as linhas de operadoras (tr.sim-op-planos)
        $operadoras = $xpath->query('//tr[@class="sim-op-planos"]');

        foreach ($operadoras as $operadora) {
            // Extrai logo
            $imgNode = $xpath->query('.//img', $operadora)->item(0);
            $logo = $imgNode ? $imgNode->getAttribute('src') : null;

            // Extrai nome da operadora
            $nomeOperadoraNode = $xpath->query('.//p', $operadora)->item(0);
            $nomeOperadora = $nomeOperadoraNode ? trim($nomeOperadoraNode->textContent) : '';

            // Extrai descrição adicional (se houver)
            $descNode = $xpath->query('.//b[@class="fz-7"]', $operadora)->item(0);
            $descricao = $descNode ? trim($descNode->textContent) : '';

            // Extrai planos (li dentro de ul.planos)
            $planosList = $xpath->query('.//ul[@class="collapser smallest planos ta-l"]/li', $operadora);

            foreach ($planosList as $planoLi) {
                // ID da tabela
                $tabelaId = $planoLi->getAttribute('data-id');

                // Data de vigência
                $vigencia = $planoLi->getAttribute('data-vi');

                // Nome do plano
                $nomePlanoNode = $xpath->query('.//i', $planoLi)->item(0);
                $nomePlano = $nomePlanoNode ? trim($nomePlanoNode->textContent) : '';

                // Tipo de acomodação (AMB, E, A)
                $acomodacaoNode = $xpath->query('.//span[@class="tipsy"]', $planoLi)->item(0);
                $acomodacao = $acomodacaoNode ? trim($acomodacaoNode->textContent) : '';

                if ($acomodacao === 'AMB') {
                    continue;
                }
                // Mapeia acomodação
                $tipoAcomodacao = match ($acomodacao) {
                    'E' => 'Enfermaria',
                    'A' => 'Apartamento',
                    default => $acomodacao,
                };

                $planos[] = [
                    'id' => (int) $tabelaId,
                    'operadora' => $nomeOperadora,
                    'operadora_logo' => $logo,
                    'operadora_descricao' => $descricao,
                    'nome' => $nomePlano,
                    'acomodacao' => $tipoAcomodacao,
                    'acomodacao_sigla' => $acomodacao,
                    'vigencia' => $vigencia,
                ];
            }
        }

        return $planos;
    }
}
