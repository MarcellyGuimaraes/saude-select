<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    @include('partials.tracking.gtm-head')
    @include('partials.tracking.core')

    <title>Compare planos de saúde da sua cidade | BuscarPlanos</title>
    <meta name="description" content="Responda 5 perguntas rápidas, compare planos de saúde disponíveis na sua região e receba o comparativo em PDF no WhatsApp.">
    <link rel="icon" href="/logo-app.png" type="image/png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F3F4F6; }
        .cta { background: #2563EB; box-shadow: 0 8px 20px rgba(37, 99, 235, .35); transition: transform .2s, background .2s; }
        .cta:hover { background: #1D4ED8; transform: translateY(-2px); }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center pb-28">
    @include('partials.tracking.gtm-body')

    <header class="w-full max-w-md px-4 pt-4 pb-2">
        <img src="/logo-site.png" alt="BuscarPlanos" class="h-9 w-auto" onerror="this.style.display='none'">
    </header>

    <main class="w-full max-w-md px-4 space-y-5 mt-2">
        {{-- Dobra 1: promessa + CTA --}}
        <section class="bg-white rounded-2xl shadow-lg p-6 text-center">
            <span class="inline-block text-[11px] font-bold uppercase tracking-wide text-blue-700 bg-blue-50 px-3 py-1 rounded-full mb-4">
                <i class="fas fa-map-marker-alt mr-1"></i> Planos disponíveis na sua região
            </span>
            <h1 class="text-2xl font-extrabold text-gray-900 leading-tight mb-3">
                Descubra quais planos de saúde atendem você e sua família
            </h1>
            <p class="text-gray-600 text-sm leading-relaxed mb-6">
                Responda 5 perguntas rápidas, compare as opções da sua cidade e receba o comparativo
                com valores e rede credenciada em PDF, direto no seu WhatsApp.
            </p>
            <a href="/" data-cta="hero" class="cta js-cta block w-full text-white font-bold text-lg py-4 rounded-xl">
                Ver planos da minha cidade <i class="fas fa-arrow-right ml-1"></i>
            </a>
            <p class="text-[11px] text-gray-400 mt-3"><i class="fas fa-lock mr-1"></i> Leva cerca de 1 minuto. Sem compromisso.</p>
        </section>

        {{-- Como funciona --}}
        <section class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Como funciona</h2>
            <ol class="space-y-4">
                <li class="flex gap-3">
                    <span class="flex-none w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center">1</span>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Conte quem vai usar o plano</p>
                        <p class="text-gray-500 text-xs">Perfil de contratação e quantas pessoas, por faixa de idade.</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex-none w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center">2</span>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Escolha os planos que quer comparar</p>
                        <p class="text-gray-500 text-xs">Mostramos as opções disponíveis para a sua cidade.</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex-none w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center">3</span>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Receba o PDF no WhatsApp</p>
                        <p class="text-gray-500 text-xs">Valores e rede credenciada reunidos em um só documento.</p>
                    </div>
                </li>
            </ol>
        </section>

        {{-- O que você recebe --}}
        <section class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-3">O que vem no comparativo</h2>
            <ul class="space-y-2 text-sm text-gray-700">
                <li><i class="fas fa-check-circle text-green-600 mr-2"></i>Valores por faixa de idade</li>
                <li><i class="fas fa-check-circle text-green-600 mr-2"></i>Tipo de acomodação e coparticipação</li>
                <li><i class="fas fa-check-circle text-green-600 mr-2"></i>Rede credenciada de cada operadora</li>
                <li><i class="fas fa-check-circle text-green-600 mr-2"></i>Atendimento de um corretor para tirar dúvidas</li>
            </ul>
            <a href="/" data-cta="meio" class="cta js-cta block w-full text-center text-white font-bold py-3 rounded-xl mt-5">
                Quero comparar agora
            </a>
        </section>

        <p class="text-[10px] text-gray-400 text-center leading-snug px-2">
            Os valores e a disponibilidade dependem da região, do perfil e das regras de cada operadora.
            Ao continuar, você será direcionado ao simulador do BuscarPlanos.
        </p>
    </main>

    {{-- CTA fixo no rodapé (mobile) --}}
    <div class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t border-gray-200 p-3 flex justify-center z-40">
        <a href="/" data-cta="rodape" class="cta js-cta w-full max-w-md text-center text-white font-bold py-3 rounded-xl">
            Ver planos da minha cidade <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>

    <script>
        (function () {
            if (typeof window.fsTrack !== 'function') return;
            var T = window.fsTrack;

            T('presell_view', { step: null });

            // Rolou 50% da página (uma vez)
            var sent50 = false;
            window.addEventListener('scroll', function () {
                if (sent50) return;
                var h = document.documentElement;
                var pct = (h.scrollTop + window.innerHeight) / h.scrollHeight;
                if (pct >= 0.5) { sent50 = true; T('presell_scroll_50', {}); }
            }, { passive: true });

            // Clique em qualquer CTA: registra e segue para o quiz mantendo a query string (UTMs)
            document.querySelectorAll('.js-cta').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    T('presell_cta_click', { cta_position: a.getAttribute('data-cta') });
                    e.preventDefault();
                    var url = '/' + window.location.search;
                    setTimeout(function () { window.location.href = url; }, 150);
                });
            });
        })();
    </script>
</body>
</html>
