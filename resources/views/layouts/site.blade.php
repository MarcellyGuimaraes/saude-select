@php($site = config('site'))
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.tracking.gtm-head')
    @include('partials.tracking.core')

    <title>@yield('title')</title>
    <meta name="description" content="@yield('description')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#F6F3EC">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="{{ $site['name'] }}">
    <meta property="og:title" content="@yield('title')">
    <meta property="og:description" content="@yield('description')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('logo-site.png') }}">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/site.css?v={{ @filemtime(public_path('css/site.css')) }}">

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $site['name'],
        'url' => url('/'),
        'logo' => asset('logo-site.png'),
        'sameAs' => [$site['facebook'], $site['instagram']],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @stack('schema')
</head>
<body>
    @include('partials.tracking.gtm-body')
    <a class="skip" href="#conteudo">Pular para o conteúdo</a>

    <header class="site-header">
        <div class="wrap">
            <a class="logo" href="/presell" aria-label="{{ $site['name'] }} — página inicial">
                <img src="/logo-site.png" alt="{{ $site['name'] }}" width="350" height="90">
            </a>
            @hasSection('header_cta')
                @yield('header_cta')
            @endif
        </div>
    </header>

    <main id="conteudo">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <div class="footer-grid">
                <div>
                    <a class="logo" href="/presell" aria-label="{{ $site['name'] }}">
                        <img src="/logo-site.png" alt="{{ $site['name'] }}" width="350" height="90" loading="lazy">
                    </a>
                    <p>Ajudamos você a comparar planos de saúde disponíveis na sua região e a receber o comparativo em PDF, com valores e rede credenciada.</p>
                </div>

                <nav aria-label="Informações legais">
                    <h3>Institucional</h3>
                    <ul>
                        <li><a href="/privacidade">Política de Privacidade</a></li>
                        <li><a href="/termos">Termos de Uso</a></li>
                        <li><a href="https://www.gov.br/ans/pt-br" target="_blank" rel="noopener noreferrer">Agência Nacional de Saúde (ANS)</a></li>
                    </ul>
                </nav>

                <div>
                    <h3>Contato</h3>
                    <ul>
                        @if ($site['email'])<li><a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></li>@endif
                        @if ($site['phone'])<li>{{ $site['phone'] }}</li>@endif
                        @if ($site['address'])<li>{{ $site['address'] }}</li>@endif
                        <li>
                            <a href="{{ $site['instagram'] }}" target="_blank" rel="noopener noreferrer">Instagram</a> ·
                            <a href="{{ $site['facebook'] }}" target="_blank" rel="noopener noreferrer">Facebook</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="legal">
                <p>
                    <strong>{{ $site['legal_name'] ?: $site['name'] }}</strong>
                    @if ($site['cnpj']) · CNPJ {{ $site['cnpj'] }}@endif
                    @if ($site['susep']) · Registro SUSEP {{ $site['susep'] }}@endif
                </p>
                <p>
                    O {{ $site['name'] }} é um serviço de comparação e apoio à contratação de planos de saúde. <strong>Não somos operadora de plano de saúde</strong> e não vendemos cobertura própria.
                    As informações têm caráter orientativo e não substituem a análise do contrato e do material oficial de cada operadora.
                </p>
                <p>
                    Valores, rede credenciada, carências e disponibilidade dependem da região, do perfil de contratação e das regras de cada operadora, e podem ser alterados sem aviso prévio.
                    Nomes e marcas de operadoras pertencem aos respectivos titulares e são citados apenas para identificação.
                </p>
                <p>&copy; {{ date('Y') }} {{ $site['name'] }}. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
