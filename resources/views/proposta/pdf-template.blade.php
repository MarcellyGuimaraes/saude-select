<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $titulo ?? 'Proposta de Plano de Saúde' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #333; line-height: 1.35; margin: 0; padding: 12px; }
        .marca { text-align: center; padding: 10px 0; border-bottom: 2px solid #1e40af; margin-bottom: 12px; }
        .marca h1 { margin: 0; font-size: 16pt; color: #1e40af; }
        .marca .subtitulo { font-size: 9pt; color: #64748b; margin-top: 2px; }
        .conteudo-proposta { width: 100%; }
        .conteudo-proposta > .logotipo { text-align: center; margin-bottom: 8px; }
        .conteudo-proposta > .logotipo img { max-width: 140px; max-height: 70px; }
        .conteudo-proposta h2.titulo { font-size: 14pt; color: #1e293b; margin: 8px 0 6px; }
        .conteudo-proposta p { margin: 4px 0; }
        .conteudo-proposta table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 9pt; }
        .conteudo-proposta td, .conteudo-proposta th { border: 1px solid #94a3b8; padding: 5px 6px; vertical-align: top; }
        .conteudo-proposta tr.bgGray td, .conteudo-proposta .bgGray, .conteudo-proposta td.bgGray { background: #e2e8f0; font-weight: bold; }
        .conteudo-proposta .ta-c, .conteudo-proposta td.ta-c { text-align: center; }
        .conteudo-proposta .ta-l, .conteudo-proposta td.ta-l { text-align: left; }
        .conteudo-proposta .ta-r { text-align: right; }
        .conteudo-proposta .va-t { vertical-align: top; }
        .conteudo-proposta h4, .conteudo-proposta .margin { font-size: 10pt; margin: 10px 0 4px; color: #334155; }
        .conteudo-proposta .operadora { margin: 14px 0; padding: 8px 0; border-top: 1px solid #cbd5e1; page-break-inside: avoid; }
        .conteudo-proposta .operadora .logotipo { text-align: center; margin-bottom: 4px; }
        .conteudo-proposta .operadora .logotipo img { max-width: 120px; max-height: 55px; }
        .conteudo-proposta .operadora .logotipo.w200 img { max-width: 100px; }
        .conteudo-proposta .bloco { margin: 8px 0; page-break-inside: avoid; }
        .conteudo-proposta .static.small { font-size: 8pt; }
        .conteudo-proposta img { max-width: 120px; max-height: 55px; }
        .conteudo-proposta .fz-7 { font-size: 7pt; }
        .conteudo-proposta .fz-11 { font-size: 8pt; }
        .conteudo-proposta .fz-12 { font-size: 9pt; }
        .conteudo-proposta hr { border: none; border-top: 1px solid #cbd5e1; margin: 12px 0; }
        .conteudo-proposta .assinatura { margin-top: 12px; padding: 8px 0; font-size: 9pt; }
        .conteudo-proposta .footer { font-size: 8pt; color: #64748b; margin: 8px 0; }
        .rodape { margin-top: 16px; padding-top: 8px; border-top: 1px solid #cbd5e1; font-size: 8pt; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="conteudo-proposta">
        {!! $content ?? '' !!}
    </div>

    <div class="rodape">
        Documento gerado por Buscar Planos — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
