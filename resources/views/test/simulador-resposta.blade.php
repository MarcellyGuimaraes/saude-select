<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo ?? 'Teste: Resposta do Simulador' }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 1rem; background: #f5f5f5; }
        h1 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        h2 { font-size: 1rem; margin: 1rem 0 0.5rem; color: #333; }
        .meta { font-size: 0.875rem; color: #666; margin-bottom: 1rem; }
        pre, textarea { background: #1e1e1e; color: #d4d4d4; padding: 1rem; border-radius: 8px; overflow: auto; font-size: 12px; }
        textarea { width: 100%; min-height: 300px; box-sizing: border-box; }
        .block { margin-bottom: 1.5rem; }
        .resposta-renderizada { background: #fff; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 200px; }
        .error { background: #fee; color: #c00; padding: 1rem; border-radius: 8px; white-space: pre-wrap; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <h1>{{ $titulo ?? 'Teste: Resposta do Simulador' }}</h1>
    @if(isset($payloadInfo))
        <p class="meta">{{ $payloadInfo }} Use a busca do navegador (Ctrl+F) para inspecionar o HTML.</p>
    @else
        <p class="meta">Use a busca (Ctrl+F) para inspecionar o conteúdo.</p>
    @endif

    @if(isset($error))
        <div class="error block">{{ $error }}</div>
    @else
        <p class="meta"><a href="{{ route('test.simulador-adesao.pdf') }}">📄 Baixar PDF (com marca {{ config('app.name') }})</a></p>
        <div class="block">
            <h2>HTML renderizado (o que vem do simulador — para fins comparativos)</h2>
            <div class="resposta-renderizada">{!! $rawHtml ?? '' !!}</div>
        </div>
        <div class="block">
            <h2>HTML bruto ({{ strlen($rawHtml ?? '') }} caracteres)</h2>
            <textarea readonly>{{ $rawHtml ?? '' }}</textarea>
        </div>
        @if(!empty($planosParsed))
            <div class="block">
                <h2>Planos parseados ({{ count($planosParsed) }} itens)</h2>
                <pre>{{ json_encode($planosParsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    @endif

    <p class="meta"><a href="{{ url()->previous('/') }}">← Voltar</a></p>
</body>
</html>
