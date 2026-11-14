@extends('layouts.site')

@section('title', 'Escolha seu plano de saúde e receba cotações no WhatsApp | BuscarPlanos')
@section('description', 'Escolha seu plano de saúde em menos de 3 minutos e receba as cotações pelo WhatsApp, com apoio de um corretor para fechar a contratação.')

@php
    $faq = [
        [
            'q' => 'Individual, empresarial ou por adesão?',
            'a' => '<ul>
                <li><strong>Individual ou familiar:</strong> contratado com CPF. Poucas operadoras vendem, e o reajuste anual é limitado pela ANS.</li>
                <li><strong>Empresarial (PME):</strong> contratado com CNPJ, incluindo MEI conforme a operadora. O reajuste é negociado.</li>
                <li><strong>Por adesão:</strong> contratado por associação, sindicato ou entidade de classe, com vínculo profissional.</li>
            </ul>',
        ],
        [
            'q' => 'O que é coparticipação?',
            'a' => '<p>Além da mensalidade, você paga uma parte de cada consulta ou exame que usar. Em geral a mensalidade é menor. Sem coparticipação, a mensalidade costuma ser maior e não há cobrança por uso.</p>',
        ],
        [
            'q' => 'O que é carência?',
            'a' => '<p>É o prazo de espera até poder usar certos serviços. Limites máximos previstos em lei:</p>
            <ul>
                <li>urgência e emergência: 24 horas;</li>
                <li>consultas, exames e internações: até 180 dias;</li>
                <li>parto: até 300 dias.</li>
            </ul>
            <p>Para doenças preexistentes, pode haver cobertura parcial temporária de até 24 meses em procedimentos de alta complexidade.</p>',
        ],
        [
            'q' => 'O que conferir na rede credenciada?',
            'a' => '<p>Veja se os hospitais e laboratórios que você usa ficam na sua cidade e fazem parte do plano. Alguns têm rede reduzida ou cobertura restrita, inclusive só de pronto-socorro. Confirme também se a operadora tem registro no <a href="https://www.gov.br/ans/pt-br" target="_blank" rel="noopener noreferrer">site da ANS</a>.</p>',
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

{{-- Seção 1: promessa, ação e o caminho em 3 pontos --}}
<section class="hero">
    <div class="wrap">
        <h1 class="rise d1">Escolha seu plano de saúde em menos de 3 minutos e receba as cotações pelo WhatsApp.</h1>
        <p class="lede rise d2">
            Compare valores e rede credenciada e conte com um corretor para fechar a contratação, tudo pelo WhatsApp.
        </p>

        <div class="rise d3">
            <a href="/" data-cta="hero" id="hero-cta" class="btn js-cta">
                Quero meu plano de saúde <span class="arrow" aria-hidden="true">→</span>
            </a>
            <p class="cta-note"><strong>Menos de 3 minutos.</strong> Sem custo para simular.</p>
        </div>

        <ol class="flow" aria-label="Como funciona">
            <li>
                <span class="node" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <i class="n">1</i>
                </span>
                <b>Conte quem usa</b>
                <span class="sub">tipo e idades</span>
            </li>
            <li>
                <span class="node" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>
                    <i class="n">2</i>
                </span>
                <b>Escolha o plano</b>
                <span class="sub">da sua cidade</span>
            </li>
            <li>
                <span class="node" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="m9 12 2 2 4-4"/></svg>
                    <i class="n">3</i>
                </span>
                <b>Receba</b>
                <span class="sub">pelo WhatsApp</span>
            </li>
        </ol>
    </div>
</section>

{{-- Seção 2: dúvidas comuns --}}
<section class="section" id="guia">
    <div class="wrap guide">
        <div class="section-head">
            <h2>Antes de contratar</h2>
            <p>Quatro pontos que ajudam a escolher com segurança.</p>
            <a href="/" data-cta="guia" class="btn btn-sm js-cta guide-cta">Quero meu plano <span class="arrow" aria-hidden="true">→</span></a>
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

{{-- Botão fixo no celular: aparece depois que o botão principal sai da tela --}}
<div class="sticky-cta" id="sticky-cta" aria-hidden="true">
    <a href="/" data-cta="fixo" class="btn js-cta" tabindex="-1">Quero meu plano de saúde <span class="arrow" aria-hidden="true">→</span></a>
</div>

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

    // Botão fixo (celular): mostra quando o CTA principal sai da tela.
    var hero = document.getElementById('hero-cta');
    var sticky = document.getElementById('sticky-cta');
    if (hero && sticky && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            var visible = !entries[0].isIntersecting && entries[0].boundingClientRect.top < 0;
            sticky.classList.toggle('on', visible);
            sticky.setAttribute('aria-hidden', visible ? 'false' : 'true');
            sticky.querySelector('a').tabIndex = visible ? 0 : -1;
        }).observe(hero);
    }

    // Clique no CTA: registra e segue para o quiz mantendo a query string (UTMs).
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
