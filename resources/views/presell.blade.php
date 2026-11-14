@extends('layouts.site')

@section('title', 'Compare planos de saúde da sua cidade | BuscarPlanos')
@section('description', 'Informe quem vai usar o plano, compare as opções disponíveis na sua região e receba o comparativo em PDF no WhatsApp, com valores e rede credenciada.')

@section('header_cta')
    <a href="/" data-cta="topo" class="btn btn-sm js-cta">Comparar planos</a>
@endsection

@php
    $faq = [
        [
            'q' => 'Qual a diferença entre plano individual, empresarial e por adesão?',
            'a' => '<ul>
                <li><strong>Individual ou familiar:</strong> contratado diretamente por pessoa física, com CPF. Poucas operadoras comercializam e o reajuste anual é limitado pela ANS.</li>
                <li><strong>Empresarial (PME):</strong> contratado por meio de um CNPJ, incluindo MEI conforme as regras de cada operadora. O reajuste é negociado e as condições de carência podem ser diferentes.</li>
                <li><strong>Por adesão:</strong> contratado por meio de uma entidade de classe, associação ou sindicato, e exige vínculo profissional com ela.</li>
            </ul>',
        ],
        [
            'q' => 'O que é coparticipação?',
            'a' => '<p>É quando, além da mensalidade, você paga uma parte de cada consulta, exame ou procedimento que usar. Em geral, planos com coparticipação têm mensalidade menor. Planos sem coparticipação costumam ter mensalidade maior e não cobram por uso. Há também a coparticipação parcial, com cobrança só em alguns procedimentos ou com limites.</p>',
        ],
        [
            'q' => 'O que é carência?',
            'a' => '<p>É o prazo de espera, contado a partir da contratação, até poder usar certos serviços. A lei estabelece limites máximos:</p>
            <ul>
                <li>urgência e emergência: 24 horas;</li>
                <li>consultas, exames e internações: até 180 dias;</li>
                <li>parto: até 300 dias.</li>
            </ul>
            <p>Para doenças e lesões preexistentes, pode haver cobertura parcial temporária de até 24 meses para procedimentos de alta complexidade. Cada operadora define as regras dentro desses limites.</p>',
        ],
        [
            'q' => 'Enfermaria ou apartamento?',
            'a' => '<p>É o tipo de acomodação em caso de internação. Na <strong>enfermaria</strong>, o quarto é compartilhado. No <strong>apartamento</strong>, o quarto é individual, e a mensalidade costuma ser maior.</p>',
        ],
        [
            'q' => 'O que conferir na rede credenciada?',
            'a' => '<p>Veja se os hospitais, laboratórios e clínicas que você usa ficam na sua cidade e fazem parte do plano. Alguns planos têm rede reduzida ou cobertura restrita, inclusive só de pronto-socorro. Confira a lista completa antes de contratar.</p>',
        ],
        [
            'q' => 'Como saber se a operadora é regulada?',
            'a' => '<p>Toda operadora de plano de saúde precisa ter registro na ANS (Agência Nacional de Saúde Suplementar). Você pode consultar o registro e a situação da operadora no <a href="https://www.gov.br/ans/pt-br" target="_blank" rel="noopener noreferrer">site da ANS</a>.</p>',
        ],
    ];
@endphp

@push('schema')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn ($i) => [
        '@type' => 'Question',
        'name' => $i['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($i['a'])],
    ], $faq),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')

{{-- Seção 1: promessa + ação --}}
<section class="hero">
    <div class="wrap">
        <div>
            <span class="eyebrow rise">Comparador de planos de saúde</span>
            <h1 class="rise d1">Compare planos de saúde da sua cidade, com calma e sem compromisso.</h1>
            <p class="lede rise d2">
                Conte quem vai usar o plano e veja as opções disponíveis na sua região.
                Você recebe o comparativo em PDF no WhatsApp, com valores e rede credenciada.
            </p>
            <div class="rise d3">
                <a href="/" data-cta="hero" class="btn js-cta">
                    Comparar planos da minha cidade <span class="arrow" aria-hidden="true">→</span>
                </a>
                <p class="cta-note"><strong>Leva cerca de 1 minuto.</strong> Sem custo para simular.</p>
            </div>
        </div>

        <div class="ficha-wrap" aria-hidden="true">
            <div class="ficha">
                <div class="ficha-head"><strong>Seu comparativo</strong><span>Exemplo ilustrativo</span></div>
                <div class="ficha-row">
                    <div class="bar w1"></div>
                    <div class="chips"><span class="chip">Enfermaria</span><span class="chip">Coparticipação parcial</span></div>
                </div>
                <div class="ficha-row">
                    <div class="bar w2"></div>
                    <div class="chips"><span class="chip">Apartamento</span><span class="chip ok">Sem coparticipação</span></div>
                </div>
                <div class="ficha-row">
                    <div class="bar w3"></div>
                    <div class="chips"><span class="chip">Enfermaria</span><span class="chip ok">Sem coparticipação</span></div>
                </div>
                <p class="ficha-foot">Valores por faixa de idade e rede credenciada vão no PDF.</p>
            </div>
            <span class="ficha-tag">PDF no seu WhatsApp</span>
        </div>
    </div>
</section>

{{-- Seção 2: como funciona --}}
<section class="section" id="como-funciona">
    <div class="wrap">
        <div class="section-head">
            <h2>Como funciona</h2>
            <p>Três passos rápidos. Você escolhe o que quer comparar e recebe tudo reunido em um só documento.</p>
        </div>
        <ol class="steps">
            <li>
                <h3>Conte quem vai usar</h3>
                <p>Informe o tipo de contratação e quantas pessoas entram no plano, por faixa de idade.</p>
            </li>
            <li>
                <h3>Escolha o que comparar</h3>
                <p>Mostramos as opções disponíveis para a sua cidade, e você marca as que quer ver lado a lado.</p>
            </li>
            <li>
                <h3>Receba o PDF no WhatsApp</h3>
                <p>Valores, tipo de acomodação, coparticipação e rede credenciada reunidos em um comparativo.</p>
            </li>
        </ol>
    </div>
</section>

{{-- Seção 3: guia rápido --}}
<section class="section" id="guia">
    <div class="wrap guide">
        <div class="section-head">
            <h2>O que vale entender antes de escolher</h2>
            <p>Seis dúvidas comuns, respondidas de forma direta. Elas ajudam a ler o comparativo com mais segurança.</p>
        </div>
        <div>
            @foreach ($faq as $item)
                <details>
                    <summary>{{ $item['q'] }}</summary>
                    <div class="answer">{!! $item['a'] !!}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- Faixa final --}}
<section class="band">
    <div class="wrap">
        <div>
            <h2>Pronto para comparar?</h2>
            <p>Leva cerca de 1 minuto e você escolhe o que quer ver.</p>
        </div>
        <a href="/" data-cta="faixa" class="btn btn-light js-cta">Comparar planos <span class="arrow" aria-hidden="true">→</span></a>
    </div>
</section>

@endsection

@push('scripts')
<script>
(function () {
    var T = window.fsTrack;
    if (typeof T === 'function') {
        T('presell_view', { step: null });

        var sent50 = false;
        window.addEventListener('scroll', function () {
            if (sent50) return;
            var h = document.documentElement;
            if ((h.scrollTop + window.innerHeight) / h.scrollHeight >= 0.5) { sent50 = true; T('presell_scroll_50', {}); }
        }, { passive: true });
    }

    // Clique no CTA: registra e segue para o quiz mantendo a query string (UTMs).
    // Cliques com Ctrl/Cmd/botão do meio seguem o comportamento normal do navegador.
    document.querySelectorAll('.js-cta').forEach(function (a) {
        a.addEventListener('click', function (e) {
            if (typeof T !== 'function' || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
            T('presell_cta_click', { cta_position: a.getAttribute('data-cta') });
            e.preventDefault();
            var url = '/' + window.location.search;
            setTimeout(function () { window.location.href = url; }, 150);
        });
    });
})();
</script>
@endpush
