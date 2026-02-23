<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Simulador de Planos de Saúde Online. Compare preços e coberturas dos melhores planos de saúde (Unimed, Bradesco, Amil, SulAmérica e mais). Cotação rápida, personalizada e gratuita para você e sua família.">
    <meta name="keywords" content="plano de saúde, simulador online, cotação plano de saúde, unimed, bradesco saúde, amil, sulamérica, plano de saúde empresarial, plano de saúde familiar, plano de saúde rj, preços planos de saúde">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="author" content="Saúde Select">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="SaúdeSelect - Simulador de Planos de Saúde Online">
    <meta property="og:description" content="Compare os melhores planos de saúde em segundos. Cotação online gratuita das principais operadoras do Brasil.">
    <meta property="og:image" content="{{ asset('apple-touch-icon.png') }}">
    <meta property="og:site_name" content="SaúdeSelect">
    <meta property="og:locale" content="pt_BR">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="SaúdeSelect - Simulador de Planos de Saúde Online">
    <meta property="twitter:description" content="Compare preços e coberturas dos melhores planos de saúde. Simulação rápida e fácil.">
    <meta property="twitter:image" content="{{ asset('apple-touch-icon.png') }}">

    <!-- Polices -->
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563EB">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>SaúdeSelect {{ date('Y') }} - Compare Planos de Saúde Online</title>
    
    <!-- JSON-LD Structured Data -->
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebSite",
      "name": "SaúdeSelect",
      "url": "{{ url('/') }}",
      "potentialAction": {
        "@@type": "SearchAction",
        "target": "{{ url('/') }}?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Organization",
      "name": "SaúdeSelect",
      "url": "{{ url('/') }}",
      "logo": "{{ asset('apple-touch-icon.png') }}",
      "sameAs": [
        "https://www.facebook.com/saudeselect",
        "https://www.instagram.com/saudeselect"
      ]
    }
    </script>

    <!-- Tailwind CSS (Via CDN para não depender de Node) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F3F4F6; }
        .azul-royal { color: #2563EB; }
        .bg-azul-royal { background-color: #2563EB; }
        .dimmed { opacity: 0.4; filter: grayscale(80%); transition: all 0.3s ease; }
        .active-card { border: 2px solid #2563EB; box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.39); transform: scale(1.02); }
        .blur-price { filter: blur(6px); user-select: none; cursor: pointer; transition: filter 0.3s; }
        .blur-price:hover { filter: blur(4px); }
        .transition-smooth { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }

        /* Animação da barra de progresso */
        .progress-fill { transition: width 0.6s ease-out; }

        /* Toast Notifications */
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
        .toast { pointer-events: auto; background: white; padding: 16px 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; transform: translateX(120%); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55); min-width: 300px; max-width: 400px; }
        .toast.show { transform: translateX(0); }
        .toast-success { border-left: 4px solid #10B981; }
        .toast-error { border-left: 4px solid #EF4444; }
        .toast-info { border-left: 4px solid #3B82F6; }
        .toast-warning { border-left: 4px solid #F59E0B; }

        /* Custom Modal */
        #modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s; backdrop-filter: blur(2px); }
        #modal-overlay.show { opacity: 1; pointer-events: auto; }
        #modal-box { background: white; border-radius: 16px; width: 90%; max-width: 420px; padding: 0; transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 20px 50px rgba(0,0,0,0.2); overflow: hidden; }
        #modal-overlay.show #modal-box { transform: scale(1); }
        
        /* Custom Scrollbar for Modal */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-start pt-4 pb-12">

    <!-- Header & Barra de Progresso -->
    <header class="w-full max-w-md px-4 mb-6 sticky top-0 bg-[#F3F4F6] z-50 pt-2 pb-2">
        <!-- Mensagem de erro de localização -->
        <div id="location-error" class="hidden mb-3 bg-red-50 border border-red-200 rounded-lg p-3 shadow-sm">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-xs font-semibold text-red-800">Acesso à localização necessário</p>
                    <p class="text-xs text-red-600 mt-1">É necessário permitir o acesso à sua localização para que o sistema funcione corretamente. Por favor, atualize as permissões do navegador.</p>
                </div>
            </div>
        </div>
        <div class="flex justify-between items-center mb-2 relative">
            <h1 class="text-xs md:text-sm font-semibold text-gray-500 italic flex-1 mr-2">
                ⏳ Gerando sua tabela oficial {{ date('Y') }}...
            </h1>
            <div id="location-container" class="text-[10px] md:text-xs text-gray-500 flex items-center bg-white px-3 py-1.5 rounded-full shadow-sm cursor-pointer hover:bg-blue-50 border border-gray-100 transition-all group">
                <span class="mr-1 hidden md:inline">📍 Você está em </span>
                <span class="mr-1 md:hidden">📍</span>
                <span id="location-text" class="font-bold text-gray-700 group-hover:text-blue-600 transition-colors">detectando...</span>
                <i class="fas fa-chevron-down ml-2 text-blue-400 group-hover:text-blue-600"></i>
            </div>
        </div>

        <!-- Location Search Modal -->
        <div id="location-modal" class="fixed inset-0 z-[100] hidden">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="location-modal-backdrop"></div>
            
            <!-- Modal Content -->
            <div class="absolute inset-x-0 bottom-0 md:top-20 md:bottom-auto md:left-1/2 md:-translate-x-1/2 md:w-full md:max-w-md bg-white rounded-t-3xl md:rounded-2xl shadow-2xl transform transition-all duration-300 translate-y-full md:scale-95 md:translate-y-0 opacity-0 flex flex-col max-h-[90vh]" id="location-modal-content">
                
                <!-- Handle para Mobile -->
                <div class="md:hidden w-full flex justify-center pt-3 pb-1">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                </div>

                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-white rounded-t-3xl md:rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Onde você está?</h3>
                        <p class="text-xs text-gray-500">Defina sua localização para ver planos regionais.</p>
                    </div>
                    <button onclick="closeLocationModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="p-5 overflow-y-auto custom-scrollbar">
                    <!-- Search Input -->
                    <div class="relative mb-6">
                        <i class="fas fa-search absolute left-4 top-3.5 text-blue-500"></i>
                        <input type="text" id="city-search-input" 
                            class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-medium text-gray-700 placeholder-gray-400"
                            placeholder="Buscar cidade..."
                            oninput="debounceSearchCities(this.value)">
                        <div id="search-loading" class="absolute right-4 top-3.5 hidden">
                            <i class="fas fa-spinner fa-spin text-blue-500"></i>
                        </div>
                    </div>

                    <!-- Botão Usar Minha Localização -->
                    <button onclick="requestLocation(); closeLocationModal();" class="w-full flex items-center gap-3 p-3 mb-6 bg-blue-50 text-blue-700 rounded-xl font-semibold hover:bg-blue-100 transition border border-blue-100 group">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center group-hover:bg-white transition">
                            <i class="fas fa-location-arrow text-sm"></i>
                        </div>
                        <span class="text-sm">Usar minha localização atual</span>
                    </button>

                    <!-- Conteúdo Inicial: Cidades Sugeridas -->
                    <div id="suggested-cities">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Principais Regiões</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="selectCity('Rio de Janeiro', 'Rio de Janeiro')" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition text-left border border-transparent hover:border-gray-200">
                                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                                <span class="text-sm font-medium text-gray-700">Rio de Janeiro</span>
                            </button>
                            <button onclick="selectCity('São Paulo', 'São Paulo')" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition text-left border border-transparent hover:border-gray-200">
                                <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                <span class="text-sm font-medium text-gray-700">São Paulo</span>
                            </button>
                            <button onclick="selectCity('Brasília', 'Distrito Federal')" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition text-left border border-transparent hover:border-gray-200">
                                <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                <span class="text-sm font-medium text-gray-700">Brasília</span>
                            </button>
                            <button onclick="selectCity('Belo Horizonte', 'Minas Gerais')" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition text-left border border-transparent hover:border-gray-200">
                                <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                <span class="text-sm font-medium text-gray-700">Belo Horizonte</span>
                            </button>
                        </div>
                    </div>

                    <!-- Resultados da Busca -->
                    <div id="city-results" class="space-y-1 hidden"></div>
                </div>
            </div>
        </div>

        <!-- Barra de Progresso -->
        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1 overflow-hidden">
            <div id="progress-bar" class="bg-azul-royal h-2.5 rounded-full progress-fill" style="width: 20%"></div>
        </div>
        <p id="step-text" class="text-xs text-blue-600 font-semibold text-right">Passo 1 de 5</p>
    </header>

    <!-- CONTAINER PRINCIPAL -->
    <main id="step-container" class="w-full max-w-md bg-white rounded-xl shadow-lg overflow-hidden transition-smooth min-h-[500px] relative">
        <!-- Conteúdo será carregado dinamicamente aqui -->
        <div class="p-8 text-center min-h-[500px] flex flex-col justify-center items-center">
            <i class="fas fa-map-marker-alt text-6xl text-blue-500 mb-4 animate-pulse"></i>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Aguardando Localização</h2>
            <p class="text-gray-600 mb-6 text-sm px-4">Por favor, permita o acesso à sua localização para continuar.</p>
            <div class="flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Custom Modal -->
    <div id="modal-overlay">
        <div id="modal-box">
            <div class="p-6">
                <div id="modal-icon" class="mb-4 text-center text-3xl text-blue-600"></div>
                <h3 id="modal-title" class="text-xl font-bold text-gray-800 mb-2 text-center"></h3>
                <p id="modal-message" class="text-gray-600 text-center text-sm leading-relaxed"></p>
            </div>
            <div class="bg-gray-50 p-4 flex gap-3 justify-center">
                <button id="modal-cancel-btn" class="px-5 py-2.5 rounded-lg text-gray-600 font-medium hover:bg-gray-200 transition text-sm">Cancelar</button>
                <button id="modal-confirm-btn" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 transition shadow-md hover:shadow-lg text-sm">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- LOGICA JAVASCRIPT (Simulando Backend) -->
    <script>
        // --- ESTADO DA APLICAÇÃO ---
        const state = {
            step: 1,
            nome: '',
            profile: null, // 'pme', 'adesao', 'cpf'
            lives: { '0-18': 0, '19-23': 0, '24-28': 0, '29-33': 0, '34-38': 0, '39-43': 0, '44-48': 0, '49-53': 0, '54-58': 0, '59+': 0 },
            totalLives: 0,
            selectedPlans: [],
            locationGranted: false,
            city: null,
            planos: [],
            planosPaginaAtual: 1,
            regionId: 2 // Default: Rio de Janeiro
        };

        const REGION_MAP = {
            'Acre': 18, 'Alagoas': 24, 'Amapá': 19, 'Amazonas': 9, 'Bahia': 5,
            'Distrito Federal': 6, 'Espírito Santo': 11, 'Ceará': 10, 'Goiás': 15,
            'Maranhão': 25, 'Mato Grosso': 17, 'Mato Grosso do Sul': 16,
            'Minas Gerais': 8, 'Pará': 20, 'Paraíba': 26, 'Paraná': 7,
            'Pernambuco': 14, 'Piauí': 27, 'Rio de Janeiro': 2,
            'Rio Grande do Norte': 28, 'Rio Grande do Sul': 12, 'Rondônia': 21,
            'Roraima': 22, 'Santa Catarina': 13, 'São Paulo': 1, 'Sergipe': 29,
            'Tocantins': 23
        };

        // Click handler for location header
        document.getElementById('location-container').onclick = openLocationModal;

        let searchTimeout;

        function openLocationModal() {
            const modal = document.getElementById('location-modal');
            const backdrop = document.getElementById('location-modal-backdrop');
            const content = document.getElementById('location-modal-content');
            
            // Reset state
            document.getElementById('city-search-input').value = '';
            document.getElementById('city-results').classList.add('hidden');
            document.getElementById('suggested-cities').classList.remove('hidden');
            document.getElementById('city-results').innerHTML = '';
            
            modal.classList.remove('hidden');
            
            // Animation
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                content.classList.remove('translate-y-full', 'opacity-0', 'md:scale-95');
            }, 10);

            // Focus after animation to prevent layout jumps on mobile
            setTimeout(() => {
                document.getElementById('city-search-input').focus();
            }, 100);
        }

        function closeLocationModal() {
            const modal = document.getElementById('location-modal');
            const backdrop = document.getElementById('location-modal-backdrop');
            const content = document.getElementById('location-modal-content');

            backdrop.classList.add('opacity-0');
            content.classList.add('translate-y-full', 'opacity-0', 'md:scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function debounceSearchCities(query) {
            clearTimeout(searchTimeout);
            
            if (query.length < 3) {
                document.getElementById('city-results').classList.add('hidden');
                document.getElementById('suggested-cities').classList.remove('hidden');
                document.getElementById('search-loading').classList.add('hidden');
                return;
            }

            document.getElementById('search-loading').classList.remove('hidden');
            searchTimeout = setTimeout(() => searchCities(query), 500);
        }

        function searchCities(query) {
            if (query.length < 3) return;

            const loading = document.getElementById('search-loading');
            const results = document.getElementById('city-results');
            const suggestions = document.getElementById('suggested-cities');
            
            loading.classList.remove('hidden');
            
            // Using Nominatim API
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=br&addressdetails=1&limit=5`)
                .then(res => res.json())
                .then(data => {
                    loading.classList.add('hidden');
                    
                    suggestions.classList.add('hidden');
                    results.classList.remove('hidden');
                    results.innerHTML = '';

                    if (data.length === 0) {
                        results.innerHTML = `
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-search-location text-3xl mb-2 opacity-30"></i>
                                <p class="text-sm">Nenhuma cidade encontrada.</p>
                            </div>
                        `;
                        return;
                    }

                    data.forEach(item => {
                        const city = item.address.city || item.address.town || item.address.municipality || item.address.village;
                        const stateName = item.address.state;
                        
                        // Only show if we found a valid state and city
                        if (city && stateName) {
                            const div = document.createElement('div');
                            div.className = 'p-4 hover:bg-gray-50 rounded-xl cursor-pointer border border-transparent hover:border-gray-100 transition-all flex items-center justify-between group';
                            div.innerHTML = `
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-blue-100 group-hover:text-blue-500 transition">
                                        <i class="fas fa-map-marker-alt text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800 text-sm">${city}</div>
                                        <div class="text-xs text-gray-500">${stateName}</div>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-gray-300 text-xs group-hover:text-blue-500 transition-colors"></i>
                            `;
                            div.onclick = () => selectCity(city, stateName);
                            results.appendChild(div);
                        }
                    });
                })
                .catch(err => {
                    console.error(err);
                    loading.classList.add('hidden');
                    results.classList.remove('hidden');
                    suggestions.classList.add('hidden');
                    results.innerHTML = '<div class="text-center text-red-500 py-4 text-sm">Erro ao buscar cidades</div>';
                });
        }

        function selectCity(city, stateName) {
            state.city = city;
            
            // Map state name to ID
            let regionId = REGION_MAP[stateName] || 2; // Default to Rio if not found
            
            // Special cases normalization
            if (stateName === 'Distrito Federal') regionId = 6;
            
            const oldRegion = state.regionId;
            state.regionId = regionId;

            document.getElementById('location-text').innerText = city;
            closeLocationModal();
            showToast(`Localização definida para ${city} (${stateName})`, 'success');

            // Refresh data if region changed
            if (oldRegion !== regionId) {
                if (state.step === 1) {
                    // Clear hospital search as hospitals are regional
                    const input = document.getElementById('hospital-search');
                    if (input) {
                        input.value = '';
                        document.getElementById('autocomplete-list').classList.add('hidden');
                        state.hospitalId = null;
                        state.hospital = '';
                    }
                } else if (state.step === 4) {
                    // Reload plans for the new region
                    state.selectedPlans = []; // Clear selection as plans might change
                    document.getElementById('selected-count').innerText = '0';
                    buscarPlanosAPI();
                }
            }
        }

        const VALID_PROFESSIONS = [
            {id: "200", name: "ACUPUNTURISTAS"}, {id: "193", name: "ADM. EMPRESAS"}, {id: "1", name: "ADMINISTRADOR"}, {id: "2", name: "ADVOGADO"},
            {id: "300", name: "Aeronauta"}, {id: "112", name: "AEROVIÁRIOS"}, {id: "3", name: "AGRÔNOMO"}, {id: "150", name: "AMBULANTE"},
            {id: "317", name: "ANALISTAS DE SISTEMAS"}, {id: "198", name: "ANESTESISTAS"}, {id: "352", name: "ANTROPÓLOGO"}, {id: "82", name: "APOSENTADO"},
            {id: "373", name: "ARQUEÓLOGO"}, {id: "4", name: "ARQUITETO"}, {id: "234", name: "ARQUIVISTAS"}, {id: "249", name: "ARQUIVOLOGIA"},
            {id: "323", name: "ARRUMADOR"}, {id: "166", name: "ASSISTENTE SOCIAL"}, {id: "376", name: "ASTRÔNOMO"}, {id: "167", name: "ATUÁRIO"},
            {id: "194", name: "AUDITORES"}, {id: "83", name: "AUTARQUIAS"}, {id: "250", name: "AUTOMAÇÃO INDUSTRIAL"}, {id: "210", name: "AUTÔNOMOS"},
            {id: "7", name: "AUXILIARES EM ENFERMAGEM"}, {id: "326", name: "BABÁ"}, {id: "127", name: "BACHARÉIS EM DIREITO"}, {id: "154", name: "BIBLIOTECÁRIO"},
            {id: "251", name: "BIBLIOTECONOMIA"}, {id: "8", name: "BIOLOGO"}, {id: "9", name: "BIOMÉDICO"}, {id: "178", name: "BOMBEIROS"},
            {id: "353", name: "BOTÂNICO"}, {id: "10", name: "CABELEREIRO"}, {id: "252", name: "CIÊNCIAS AERONÁUTICAS"}, {id: "240", name: "CIÊNCIAS DA COMPUTAÇÃO"},
            {id: "355", name: "CIENTISTA POLÍTICO"}, {id: "253", name: "CINEMA"}, {id: "11", name: "CIRURGIÃO DENTISTA"}, {id: "341", name: "COMERCIANTE"},
            {id: "84", name: "COMERCIÁRIO"}, {id: "119", name: "COMÉRCIO EXTERIOR"}, {id: "369", name: "COMUNICAÇÃO SOCIAL"}, {id: "354", name: "COMUNICÓLOGO"},
            {id: "85", name: "CONTABILISTA"}, {id: "12", name: "CONTADOR"}, {id: "324", name: "COPEIRO"}, {id: "13", name: "CORRETOR DE IMÓVEIS"},
            {id: "120", name: "CORRETOR DE SEGUROS"}, {id: "205", name: "CUIDADOR DE IDOSO"}, {id: "322", name: "CUIDADOR DE SAÚDE"}, {id: "213", name: "DEFENSOR PÚBLICO"},
            {id: "248", name: "DENTISTA"}, {id: "14", name: "DENTISTAS"}, {id: "254", name: "DESENHO INDUSTRIAL"}, {id: "298", name: "Designer"},
            {id: "255", name: "DESIGNER DE INTERIOR"}, {id: "472", name: "DESIGNER DE MODA"}, {id: "15", name: "DESIGNER GRÁFICOS"}, {id: "121", name: "DESPACHANTE"},
            {id: "315", name: "DESPACHANTE ADUANEIRO"}, {id: "465", name: "DISTRIBUIDOR DE GÁS E PETRÓLEO"}, {id: "374", name: "ECÓLOGO"}, {id: "16", name: "ECONOMISTA"},
            {id: "191", name: "EDUCAÇÃO FÍSICA"}, {id: "215", name: "EMPREGADO DO COMÉRCIO"}, {id: "204", name: "EMPREGADOS DOMÉSTICOS"}, {id: "378", name: "EMPRESÁRIOS"},
            {id: "17", name: "EMPRESAS DO COMÉRCIO, INDÚSTRIA E PRESTAÇÃO DE SERVIÇOS"}, {id: "18", name: "ENFERMEIRO"}, {id: "19", name: "ENGENHEIRO"},
            {id: "20", name: "ESTAGIÁRIO DE DIREITO"}, {id: "124", name: "ESTATÍSTICO"}, {id: "257", name: "ESTATÍSTICOS"}, {id: "132", name: "ESTUDANTE"},
            {id: "21", name: "ESTUDANTE DE CONTABILIDADE"}, {id: "25", name: "ESTUDANTE DO ENSINO FUNDAMENTAL"}, {id: "192", name: "ESTUDANTE DO ENSINO INFANTIL"},
            {id: "26", name: "ESTUDANTE DO ENSINO SUPERIOR"}, {id: "28", name: "ESTUDANTES DE ECONOMIA"}, {id: "99", name: "ESTUDANTES SECUNDARISTAS"},
            {id: "135", name: "ESTUDANTE UNIVERSITÁRIO"}, {id: "358", name: "ETNÓGRAFO E DEMÓGRAFO"}, {id: "29", name: "FARMACÊUTICO"}, {id: "258", name: "FILOSOFIA"},
            {id: "259", name: "FÍSICO"}, {id: "30", name: "FISIOTERAPEUTA"}, {id: "31", name: "FONOAUDIÓLOGO"}, {id: "260", name: "FOTOGRAFIA"},
            {id: "177", name: "FUNCIONÁRIO DO COMÉRCIO"}, {id: "153", name: "FUNCIONÁRIO PÚBLICO"}, {id: "32", name: "FUNCIONÁRIO PÚBLICO ESTADUAL"},
            {id: "33", name: "FUNCIONÁRIO PÚBLICO FEDERAL"}, {id: "34", name: "FUNCIONÁRIO PÚBLICO MUNICIPAL"},
            {id: "413", name: "FUNCIONÁRIOS DA ECT (EMPRESA BRASILEIRA DE CORREIOS E TELÉGRAFOS)"}, {id: "35", name: "FUNCIONÁRIOS DO COMÉRCIO"},
            {id: "207", name: "FUNCIONÁRIOS E EMPRESÁRIOS DO COMÉRCIO"}, {id: "261", name: "GASTRONOMIA"}, {id: "359", name: "GEOFÍSICO"}, {id: "262", name: "GEOGRAFIA"},
            {id: "36", name: "GEÓGRAFO"}, {id: "263", name: "GEOLOGIA"}, {id: "37", name: "GEÓLOGO"}, {id: "183", name: "GEÓLOGOS"}, {id: "264", name: "GESTÃO AMBIENTAL"},
            {id: "265", name: "GESTÃO COMERCIAL"}, {id: "266", name: "GESTÃO DA TECNOLOGIA DA INFORMAÇÃO"}, {id: "267", name: "GESTÃO DE RECURSOS HUMANOS"},
            {id: "268", name: "GESTÃO DE SEGURANÇA PRIVADA"}, {id: "269", name: "GESTÃO DE SEGUROS"}, {id: "270", name: "GESTÃO DE TURISMO"},
            {id: "271", name: "GESTÃO FINANCEIRA"}, {id: "272", name: "GESTÃO HOSPITALAR E PÚBLICA"}, {id: "327", name: "GOVERNANTA"}, {id: "273", name: "HISTÓRIA"},
            {id: "231", name: "HISTORIADORES"}, {id: "274", name: "HOTELARIA"}, {id: "187", name: "INDÚSTRIA"}, {id: "366", name: "INTERPRETE"}, {id: "325", name: "JARDINEIRO"},
            {id: "38", name: "JORNALISTA"}, {id: "102", name: "JUIZ FEDERAL"}, {id: "286", name: "LETRAS"}, {id: "287", name: "LOGISTICA"}, {id: "39", name: "LOJISTA"},
            {id: "103", name: "MAGISTRADOS"}, {id: "104", name: "MAGISTRADOS DA JUSTIÇA DO TRABALHO"}, {id: "243", name: "MARKETING"}, {id: "288", name: "MATEMÁTICA"},
            {id: "40", name: "MÉDICO"}, {id: "289", name: "MÉDICOS"}, {id: "41", name: "METEOROLOGISTA"}, {id: "211", name: "MICROEMPREENDEDORES INDIVIDUAIS"},
            {id: "42", name: "MICRO. E PEQUENO EMPRESÁRIO"}, {id: "43", name: "MILITAR"}, {id: "238", name: "MILITARES E SERVIDORES CIVIS DA MARINHA DO BRASIL"},
            {id: "329", name: "MORDOMO"}, {id: "328", name: "MOTORISTA"}, {id: "316", name: "MOTORISTA DE APLICATIVO"}, {id: "232", name: "MUSEÓLOGOS"},
            {id: "44", name: "MÚSICO"}, {id: "294", name: "NEGÓCIOS IMOBILIÁRIOS"}, {id: "45", name: "NUTRICIONISTA"}, {id: "46", name: "ODONTOLOGISTA"},
            {id: "47", name: "PEDAGOGO"}, {id: "462", name: "PENSIONISTAS"}, {id: "467", name: "POLICIAL PENAL"}, {id: "49", name: "PÓS-GRADUANDO"},
            {id: "50", name: "PRESTADOR DE SERVIÇOS"}, {id: "51", name: "PRESTADOR DE SERVIÇOS NA ÁREA DE SISTEMA DE INFORMÁTICA"}, {id: "105", name: "PROCURADOR DA REPÚBLICA"},
            {id: "106", name: "PROCURADOR DO TRABALHO"}, {id: "54", name: "PROFESSOR"}, {id: "55", name: "PROFESSORES DO ENSINO OFICIAL"},
            {id: "56", name: "PROFESSORES DO ENSINO PARTICULAR"}, {id: "396", name: "PROFISSIONAIS EM ÓRGÃOS PÚBLICOS E PRIVADOS"}, {id: "435", name: "PROFISSIONAL DA EDUCAÇÃO"},
            {id: "220", name: "PROFISSIONAL DA INDÚSTRIA"}, {id: "285", name: "PROFISSIONAL DA INFORMÁTICA"}, {id: "59", name: "PROFISSIONAL DE BIOMEDICINA"},
            {id: "60", name: "PROFISSIONAL DE EDUCAÇÃO FÍSICA"}, {id: "217", name: "PROFISSIONAL DE ESTÉTICA"}, {id: "96", name: "PROFISSIONAL DE RELAÇOES PÚBLICAS"},
            {id: "470", name: "PROFISSIONAL DO AGRONEGÓCIO"}, {id: "434", name: "PROFISSIONAL DO COMÉRCIO"}, {id: "62", name: "PROFISSIONAL DO COMÉRCIO/SERVIÇOS"},
            {id: "168", name: "PROFISSIONAL LIBERAL"}, {id: "466", name: "PROFISSIONAL LIBERAL E EMPREENDEDOR"}, {id: "97", name: "PROFISSIONAL, MICRO PEQUENA EMPRESA"},
            {id: "63", name: "PROFISSIONAL REGISTRADO NO CREA"}, {id: "469", name: "PROFISSIONAL RURAL"}, {id: "199", name: "PROTÉTICOS"}, {id: "64", name: "PSICÓLOGO"},
            {id: "310", name: "PSICOPEDAGOGO"}, {id: "415", name: "PSICOTERAPEUTA"}, {id: "242", name: "PUBLICIDADE E PROPAGANDA"}, {id: "65", name: "PUBLICITÁRIO"},
            {id: "290", name: "QUÍMICA"}, {id: "66", name: "QUÍMICO"}, {id: "275", name: "RADIOLOGIA"}, {id: "244", name: "RECURSOS HUMANOS"},
            {id: "276", name: "RELAÇÕES INTERNACIONAIS"}, {id: "67", name: "RELAÇOES PÚBLICAS"}, {id: "377", name: "REPÓRTER"}, {id: "68", name: "REPRESENTANTE COMERCIAL"},
            {id: "212", name: "REPRESENTANTES COMERCIAIS"}, {id: "464", name: "REVENDEDOR DE GÁS E PETRÓLEO"}, {id: "277", name: "SECRETARIADO"},
            {id: "278", name: "SEGURANÇA NO TRABALHO"}, {id: "436", name: "SERVIDORES DA DATAPREV"}, {id: "109", name: "SERVIDOR PÚBLICO"},
            {id: "72", name: "SERVIDOR PÚBLICO, ATIVOS OU INATIVOS"}, {id: "431", name: "SERVIDOR PÚBLICO CIVIL"}, {id: "69", name: "SERVIDOR PÚBLICO ESTADUAL"},
            {id: "70", name: "SERVIDOR PÚBLICO FEDERAL"}, {id: "221", name: "SERVIDOR PÚBLICO - MAG. DA JUSTIÇA DO TRABALHO"}, {id: "432", name: "SERVIDOR PÚBLICO MILITAR"},
            {id: "71", name: "SERVIDOR PÚBLICO MUNICIPAL"}, {id: "279", name: "SOCIOLOGIA"}, {id: "73", name: "SOCIÓLOGO"}, {id: "439", name: "TÉCNICO EM ADMINISTRAÇÃO"},
            {id: "75", name: "TÉCNICO EM CONTABILIDADE"}, {id: "76", name: "TÉCNICO EM ENFERMAGEM"}, {id: "280", name: "TÉCNICOS CONTABILISTAS"},
            {id: "281", name: "TÉCNICOS DE ENFERMAGEM"}, {id: "282", name: "TÉCNICOS EM LABORATÓRIOS"}, {id: "247", name: "TECNOLOGIA DA INFORMAÇÃO"},
            {id: "136", name: "TECNÓLOGO"}, {id: "284", name: "TEÓLOGOS"}, {id: "80", name: "TERAPEUTAS OCUPACIONAL"}, {id: "170", name: "TRABALHADORES COOPERADOS"},
            {id: "319", name: "TRABALHADORES DO PETRÓLEO E GÁS"}, {id: "123", name: "TRADUTOR"}, {id: "241", name: "TURISMO"}, {id: "139", name: "URBANISTA"},
            {id: "149", name: "VENDEDOR AUTÔNOMO"}, {id: "81", name: "VETERINÁRIO"}, {id: "367", name: "ZOÓLOGOS"}, {id: "368", name: "ZOOTECNISTAS"}
        ];

        let profDebounce;

        function selectProfile(profile) {
            state.profile = profile;
            
            // Visuals: Dim others, Highlight selected
            const options = document.querySelectorAll('.profile-option');
            if(options.length > 0) {
                options.forEach(el => {
                    el.classList.remove('border-blue-500', 'ring-2', 'ring-blue-500', 'bg-blue-50', 'opacity-100');
                    el.classList.add('border-gray-200', 'opacity-40', 'bg-white');
                    
                    const indicator = el.querySelector('.selected-indicator');
                    if(indicator) {
                        indicator.classList.remove('bg-blue-500', 'border-blue-500');
                        indicator.classList.add('border-gray-300');
                        indicator.querySelector('div').classList.add('hidden');
                    }
                });
            }

            const selectedBtn = document.getElementById(`btn-${profile}`);
            if(selectedBtn) {
                selectedBtn.classList.remove('border-gray-200', 'opacity-40', 'bg-white');
                selectedBtn.classList.add('border-blue-500', 'ring-2', 'ring-blue-500', 'bg-blue-50', 'opacity-100');
                
                const indicator = selectedBtn.querySelector('.selected-indicator');
                if(indicator) {
                    indicator.classList.remove('border-gray-300');
                    indicator.classList.add('bg-blue-500', 'border-blue-500');
                    indicator.querySelector('div').classList.remove('hidden');
                }
            }

            // PME Warning
            const pmeWarning = document.getElementById('pme-warning');
            if(pmeWarning) {
                if(profile === 'pme') pmeWarning.classList.remove('hidden');
                else pmeWarning.classList.add('hidden');
            }

            // Profession Input Logic
            const profissaoInput = document.getElementById('profissao-input');
            const btnContinue = document.getElementById('btn-step-2-next');

            if(profissaoInput && btnContinue) {
                if(profile === 'adesao') {
                    profissaoInput.classList.remove('hidden');
                    // Hide continue until profession is selected/valid
                    // Check if already has value
                    const profValue = document.getElementById('prof-search')?.value;
                    if(profValue && profValue.length > 2) {
                        btnContinue.classList.remove('hidden');
                    } else {
                        btnContinue.classList.add('hidden');
                    }
                    setTimeout(() => document.getElementById('prof-search')?.focus(), 100);
                } else {
                    profissaoInput.classList.add('hidden');
                    btnContinue.classList.remove('hidden');
                    // Ensure display block for non-adesao
                    btnContinue.style.display = 'block';
                    btnContinue.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }

        function debounceProfissao(query) {
            clearTimeout(profDebounce);
            const loading = document.getElementById('prof-loading');
            const suggestions = document.getElementById('prof-suggestions');
            const btnContinue = document.getElementById('btn-step-2-next');

            if (query.length < 2) {
                suggestions.classList.add('hidden');
                if(btnContinue) btnContinue.classList.add('hidden');
                return;
            }

            if(loading) loading.classList.remove('hidden');

            profDebounce = setTimeout(() => {
                if(loading) loading.classList.add('hidden');
                
                // Filter Valid Professions by Name
                const matches = VALID_PROFESSIONS.filter(p => p.name.toLowerCase().includes(query.toLowerCase()));
                
                if (matches.length > 0) {
                    suggestions.innerHTML = matches.map(p => `
                        <div class="p-3 hover:bg-gray-100 cursor-pointer border-b last:border-0 text-sm text-gray-700" 
                             onclick="selectProfissao('${p.name}', '${p.id}')">
                            <i class="fas fa-user-tie mr-2 text-gray-400"></i> ${p.name}
                        </div>
                    `).join('');
                    suggestions.classList.remove('hidden');
                } else {
                     suggestions.innerHTML = `
                        <div class="p-3 text-sm text-gray-500 text-center cursor-pointer hover:bg-gray-50" onclick="fallbackToCPF()">
                            Nenhuma tabela específica encontrada.<br>
                            <span class="text-blue-600 font-bold">Clique aqui para ver planos por CPF</span>
                        </div>
                    `;
                    suggestions.classList.remove('hidden');
                }
            }, 300);
        }

        function selectProfissao(profName, profId) {
            const input = document.getElementById('prof-search');
            const suggestions = document.getElementById('prof-suggestions');
            const btnContinue = document.getElementById('btn-step-2-next');

            if(input) input.value = profName;
            if(suggestions) suggestions.classList.add('hidden');
            
            state.profession = profName;
            state.professionId = profId; // Store Profession ID
            
            if(btnContinue) {
                btnContinue.classList.remove('hidden');
                btnContinue.style.display = 'block'; // Force display
                btnContinue.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function fallbackToCPF() {
            document.getElementById('prof-suggestions').classList.add('hidden');
            showToast('Redirecionando para planos Pessoais (CPF)...', 'info');
            selectProfile('cpf');
            setTimeout(() => nextStep(3), 1000); 
        }

        function updateLives(key, delta) {
            const current = state.lives[key] || 0;
            const newValue = Math.max(0, current + delta);
            state.lives[key] = newValue;
            
            // UI Update
            const counterEl = document.getElementById(`count-${key}`);
            if(counterEl) counterEl.innerText = newValue;

            // Visual Highlight Logic
            const rowEl = document.getElementById(`row-${key}`);
            if(rowEl) {
                if(newValue > 0) {
                    rowEl.classList.add('bg-blue-50', 'border-blue-200');
                    rowEl.classList.remove('bg-gray-50', 'border-transparent');
                } else {
                    rowEl.classList.add('bg-gray-50', 'border-transparent');
                    rowEl.classList.remove('bg-blue-50', 'border-blue-200');
                }
            }
            
            // Total Calculation
            let total = 0;
            for(let k in state.lives) total += state.lives[k];
            state.totalLives = total;
            
            const totalEl = document.getElementById('total-lives');
            if(totalEl) totalEl.innerText = total;

            // Hide alerts on change
            const alertBox = document.getElementById('validation-alert');
            if(alertBox) alertBox.classList.add('hidden');
        }

        function validateAndProceedStep3() {
            // 1. Basic Check: At least 1 life
            if (state.totalLives === 0) {
                 const alertBox = document.getElementById('validation-alert');
                 if(alertBox) {
                    document.getElementById('alert-msg').innerText = 'Adicione pelo menos uma pessoa.';
                    alertBox.classList.remove('hidden');
                 } else {
                    showToast('Adicione pelo menos uma pessoa.', 'warning');
                 }
                 return;
            }

            // 2. Child-Only Rule (0-18 only)
            const lives018 = state.lives['0-18'] || 0;
            const livesOthers = state.totalLives - lives018;

            if (lives018 > 0 && livesOthers === 0) {
                // If Profile is PME or Adesao -> Alert and Switch to CPF
                if (state.profile === 'pme' || state.profile === 'adesao') {
                    showModal(
                        'Aviso de Aceitação',
                        'Para o perfil de crianças (0-18 anos) sem um adulto titular, a contratação em ' + state.city + ' deve ser feita via CPF (Individual).\n\nAjustaremos seu perfil automaticamente para garantir a emissão do plano.',
                        () => {
                            selectProfile('cpf');
                            nextStep(4);
                        },
                        null,
                        'Entendi, Ajustar Agora',
                        'Cancelar'
                    );
                    return;
                }
            }

            // 3. PME Volume Rule (Min 2 lives)
            if (state.profile === 'pme' && state.totalLives === 1) {
                showModal(
                    'Regra de Mínimo de Vidas',
                    'Para contratar via CNPJ ou MEI, o mínimo é de 2 vidas.\n\nDeseja adicionar um dependente agora?',
                    () => {
                        // User chose to Add Dependent -> Stay here.
                        // Ideally focus on a + button or just close logic.
                    },
                    () => {
                        // User chose to view for 1 person -> Switch to CPF and Proceed
                        selectProfile('cpf');
                        nextStep(4);
                    },
                    'Adicionar Dependente',
                    'Ver tabelas p/ 1 pessoa (CPF)'
                );
                return;
            }

            // All good
            nextStep(4);
        }

        // --- UI HELPERS ---
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            let icon = 'fa-info-circle text-blue-500';
            let title = 'Informação';
            if(type === 'success') { icon = 'fa-check-circle text-green-500'; title = 'Sucesso'; }
            if(type === 'error') { icon = 'fa-times-circle text-red-500'; title = 'Erro'; }
            if(type === 'warning') { icon = 'fa-exclamation-triangle text-yellow-500'; title = 'Atenção'; }

            toast.innerHTML = `
                <i class="fas ${icon} text-2xl"></i>
                <div class="flex-1">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">${title}</h4>
                    <p class="text-sm font-medium text-gray-800 leading-snug">${message}</p>
                </div>
                <button onclick="this.parentElement.style.opacity='0'; setTimeout(()=>this.parentElement.remove(),300)" class="text-gray-300 hover:text-gray-500 transition"><i class="fas fa-times"></i></button>
            `;

            container.appendChild(toast);

            // Trigger animation
            requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 5000);
        }

        let currentModalOnCancel = null;

        function showModal(title, message, onConfirm, onCancel = null, confirmText = 'Confirmar', cancelText = 'Cancelar') {
            const overlay = document.getElementById('modal-overlay');
            const icon = document.getElementById('modal-icon');
            
            document.getElementById('modal-title').innerText = title;
            document.getElementById('modal-message').innerHTML = message.replace(/\n/g, '<br>'); // Support line breaks
            
            const confirmBtn = document.getElementById('modal-confirm-btn');
            const cancelBtn = document.getElementById('modal-cancel-btn');
            
            confirmBtn.innerText = confirmText;
            cancelBtn.innerText = cancelText;

            // Icon logic
            icon.innerHTML = '<i class="fas fa-question-circle"></i>';
            if(title.toLowerCase().includes('erro') || title.toLowerCase().includes('atenção')) {
                icon.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-500"></i>';
            }

            confirmBtn.onclick = () => {
                closeModal();
                if(onConfirm) onConfirm();
            };

            currentModalOnCancel = onCancel;
            cancelBtn.onclick = () => {
                closeModal();
                if(onCancel) onCancel();
            };

            overlay.classList.add('show');
        }

        function closeModal() {
            document.getElementById('modal-overlay').classList.remove('show');
        }

        function setLoading(btn, isLoading, text = 'Carregando...') {
            if(!btn) return;
            if(isLoading) {
                if(!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> ${text}`;
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
            } else {
                if(btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }
        
        function showMainLoading(text = 'Carregando...') {
            const container = document.getElementById('step-container');
            container.innerHTML = `
                <div class="flex items-center justify-center min-h-[500px]">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                        <p class="text-gray-500">${text}</p>
                    </div>
                </div>
            `;
        }
        
        function nextStep(step) {
            // Verificar se a localização foi concedida antes de permitir avançar
            if (!state.locationGranted) {
                // ... (mantendo lógica anterior de bloqueio se não houver replace) ...
                // Simplificando o replace para focar no Loading:
                const container = document.getElementById('step-container');
                container.innerHTML = `
                    <div class="p-8 text-center min-h-[500px] flex flex-col justify-center items-center">
                        <i class="fas fa-map-marker-alt text-6xl text-red-500 mb-4"></i>
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Localização Necessária</h2>
                        <p class="text-gray-600 mb-6 text-sm px-4">É necessário permitir o acesso à sua localização para continuar usando o sistema.</p>
                        <button onclick="requestLocation()" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition">
                            <i class="fas fa-location-arrow mr-2"></i>Permitir Localização
                        </button>
                    </div>
                `;
                return;
            }

            // Se estivermos saindo do passo 1, salva o nome
            if (state.step === 1 && step > 1) {
                const input = document.getElementById('user-name');
                if (input) {
                    state.nome = input.value.trim();
                }
            }

            showMainLoading();

            // Atualizar barra de progresso
            const progress = step * 20;
            document.getElementById('progress-bar').style.width = `${progress}%`;
            document.getElementById('step-text').innerText = `Passo ${step} de 5`;

            // Carregar etapa via AJAX
            fetch(`/step/${step}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao carregar etapa');
                    }
                    return response.text();
                })
                .then(html => {
                    const container = document.getElementById('step-container');
                    container.innerHTML = html;
                    state.step = step;

                    // Reinicializar eventos específicos da etapa
                    initializeStep(step);

                    window.scrollTo(0, 0);

                    // Inject City in Step 5 if applicable
                    if(step === 5) {
                        const citySpan = document.getElementById('dynamic-city-step5');
                        if(citySpan) citySpan.innerText = state.city || 'Niterói';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    const container = document.getElementById('step-container');
                    container.innerHTML = `
                        <div class="p-6 text-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                            <p class="text-red-600">Erro ao carregar etapa. Tente novamente.</p>
                            <button onclick="nextStep(${step})" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg">Tentar novamente</button>
                        </div>
                    `;
                });
        }

        function loadFinalStep() {
            showMainLoading('Finalizando...');

            fetch('/step-final')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao carregar etapa final');
                    }
                    return response.text();
                })
                .then(html => {
                    const container = document.getElementById('step-container');
                    container.innerHTML = html;
                    document.getElementById('progress-bar').style.width = '100%';
                    document.getElementById('step-text').innerText = 'Concluído';
                    window.scrollTo(0, 0);
                })
                .catch(error => {
                    console.error('Erro:', error);
                    const container = document.getElementById('step-container');
                    container.innerHTML = `
                        <div class="p-6 text-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                            <p class="text-red-600">Erro ao carregar etapa final.</p>
                        </div>
                    `;
                });
        }

        // --- API CALLS ---
        async function buscarHospitaisAPI(query) {
            try {
                const response = await fetch(`/api/hospitais/buscar?q=${encodeURIComponent(query)}&regiao=${state.regionId}`);
                const data = await response.json();
                return data.hospitais || [];
            } catch (error) {
                console.error('Erro ao buscar hospitais:', error);
                return [];
            }
        }

        // --- PAGINATION STATE ---
        const ITEMS_PER_PAGE = 5;
        let currentPage = 1;

        async function buscarPlanosAPI() {
            // Reset pagination
            currentPage = 1;

            const plansContainer = document.querySelector('#step-4 .space-y-4');
            
            if (plansContainer) {
                plansContainer.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                        <p class="text-gray-500">Buscando as melhores opções para você...</p>
                    </div>
                `;
            } else {
                showMainLoading('Buscando as melhores opções...');
            }
            
            try {
                const response = await fetch('/api/planos/buscar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        profile: state.profile,
                        lives: state.lives,
                        nome: state.nome,
                        regiao: state.regionId,
                        profession_id: state.professionId || null
                    })
                });

                const data = await response.json();
                if (data.success) {
                    state.planos = data.planos;
                    // Sorting Logic: Numbers first (Ascending), then others
                    state.planos.sort((a, b) => {
                        const getNum = (str) => {
                            const match = str.match(/(\d+)/);
                            return match ? parseInt(match[0], 10) : Number.MAX_SAFE_INTEGER;
                        };
                        
                        const numA = getNum(a.nome);
                        const numB = getNum(b.nome);
                        
                        // If both have numbers, sort by number
                        if (numA !== Number.MAX_SAFE_INTEGER && numB !== Number.MAX_SAFE_INTEGER) {
                            return numA - numB;
                        }

                        // If one has number and other doesn't, number comes first
                        if (numA !== numB) {
                            return numA - numB;
                        }

                        // Fallback: Alphabetical by Name if no numbers or same number
                        return a.nome.localeCompare(b.nome);
                    });
                    renderPlanos();
                } else {
                    showToast('Erro ao buscar planos: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showToast('Erro de conexão ao buscar planos.', 'error');
            }
        }

        function renderPlanos() {
            const container = document.querySelector('#step-4 .space-y-4');
            if (!container) return;

            const planos = state.planos;

            if (planos.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-search text-4xl mb-3 opacity-30"></i>
                        <p>Nenhum plano encontrado para o perfil selecionado na região.</p>
                        <button onclick="nextStep(1)" class="text-blue-600 font-bold mt-2">Tentar outra busca</button>
                    </div>
                `;
                document.getElementById('selected-count').innerText = '0';
                return;
            }

            // Pagination Logic
            const totalPages = Math.ceil(planos.length / ITEMS_PER_PAGE);
            const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
            const endIndex = startIndex + ITEMS_PER_PAGE;
            const currentPlans = planos.slice(startIndex, endIndex);

            const plansHtml = currentPlans.map((plano, index) => {
                let matchNote = '';
                let noteClass = 'hidden';

                // Tags Logic (Dynamic based on strings)
                const tags = [];
                const fullText = ((plano.operadora || '') + ' ' + (plano.nome || '') + ' ' + (plano.operadora_descricao || '')).toUpperCase();

                // 1. Acomodação (Always show if present)
                if (plano.acomodacao) {
                    const isApt = plano.acomodacao.toLowerCase().includes('apartamento');
                    tags.push({ 
                        label: isApt ? 'APT' : 'ENF', 
                        desc: isApt ? 'Acomodação: Apartamento (Privativo)' : 'Acomodação: Enfermaria (Coletivo)', 
                        icon: isApt ? 'fa-home' : 'fa-bed',
                        class: isApt ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-green-50 text-green-700 border-green-100'
                    });
                }

                // 2. Coparticipação
                if (fullText.includes('SEM') || fullText.includes('S/')) {
                    tags.push({ label: 'S/ Copar', desc: 'Plano sem Coparticipação', icon: 'fa-coins', class: 'bg-gray-50 text-gray-600 border-gray-100' } );
                }
                if (fullText.includes('COM') || fullText.includes('C/')) {
                    tags.push({ label: 'C/ Copar', desc: 'Plano com Coparticipação', icon: 'fa-check-circle', class: 'bg-green-50 text-green-600 border-green-100' });
                }

                // 3. Obstetrícia
                if (fullText.includes('OBST') || fullText.includes('PARTO')) {
                    tags.push({ label: 'C/ PARTO', desc: 'Inclui Obstetrícia', icon: 'fa-baby', class: 'bg-pink-50 text-pink-600 border-pink-100' });
                }

                // 4. Abrangência (Nacional/Regional/Municipal)
                if (fullText.includes('NACIONAL')) {
                     tags.push({ label: 'NAC', desc: 'Abrangência Nacional', icon: 'fa-globe-americas', class: 'bg-purple-50 text-purple-600 border-purple-100' });
                } else if (fullText.includes('REGIONAL') || fullText.includes('GRUPO DE MUNICIPIOS')) {
                     tags.push({ label: 'REG', desc: 'Abrangência Regional', icon: 'fa-map', class: 'bg-indigo-50 text-indigo-600 border-indigo-100' });
                } else if (fullText.includes('MUNICIPAL')) {
                     tags.push({ label: 'MUN', desc: 'Abrangência Municipal', icon: 'fa-map-marker-alt', class: 'bg-yellow-50 text-yellow-700 border-yellow-100' });
                }

                let tagsHtml = tags.map(t => `
                    <span class="text-[9px] px-1.5 py-0.5 rounded border flex items-center gap-1 font-semibold ${t.class}" title="${t.desc}">
                        ${t.icon ? `<i class="fas ${t.icon}"></i>` : ''} ${t.label}
                    </span>
                `).join('');

                // Tech Recommendation Tag (Fixed logic or distinct)
                // if (index === 0) tagsHtml += `<span class="text-[9px] bg-cyan-50 text-cyan-600 px-1.5 py-0.5 rounded border border-cyan-100 flex items-center font-bold">💎 RECOMENDAÇÃO</span>`;



                return `
                <div class="plan-card bg-white rounded-xl shadow-sm border border-gray-200 relative transition-all cursor-pointer hover:shadow-md overflow-hidden group mb-4" 
                     data-id="${plano.id}"
                     onclick="togglePlanSelection(this, ${plano.id})">
                    
                    <div class="p-4 pb-2">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center">
                                ${plano.operadora_logo 
                                    ? `<img src="${plano.operadora_logo}" class="h-6 mr-2 object-contain" alt="${plano.operadora}">` 
                                    : `<span class="font-bold text-gray-700 text-sm mr-2">${plano.operadora}</span>`}
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm leading-tight">${plano.nome} - ${plano.operadora}</h3>
                                    <span class="text-[10px] text-gray-500 uppercase tracking-wide leading-tight mt-0.5">
                                        ${plano.operadora_descricao}
                                    </span>
                                </div>
                            </div>
                            
                        </div>

                        ${matchNote ? `
                        <div class="mb-3 p-2 rounded-lg border text-[10px] leading-relaxed ${noteClass}">
                            ${matchNote}
                        </div>
                        ` : ''}

                        <div class="flex flex-wrap gap-1 mb-3">
                            ${tagsHtml}
                            <span class="text-[9px] bg-cyan-50 text-cyan-600 px-1.5 py-0.5 rounded border border-cyan-100 flex items-center font-bold">💎 RECOMENDAÇÃO TÉCNICA</span>
                        </div>
                        
                        <p class="text-[9px] text-gray-400 italic mb-2">📅 Preços e condições válidos para adesões em {{ date('Y') }}.</p>

                        <div class="mb-1 flex justify-between items-end border-t border-dashed border-gray-100 pt-2">
                            <div>
                                <div class="blur-price text-lg font-bold text-blue-800 bg-gray-50 px-2 rounded select-none filter blur-[4px] group-hover:blur-[3px] transition-all">
                                    R$ [░░░░░░]
                                </div>
                            </div>
                            <div class="text-[9px] text-green-600 font-bold bg-green-50 px-2 py-1 rounded cursor-pointer hover:bg-green-100 transition">
                                + SELECIONAR
                            </div>
                        </div>
                    </div>

                    <!-- Selection Overlay -->
                    <div class="selection-overlay absolute inset-0 bg-blue-600 bg-opacity-10 hidden flex-col items-center justify-center border-2 border-blue-600 rounded-xl z-10 pointer-events-none">
                        <div class="bg-white rounded-full p-2 shadow-lg mb-2">
                             <i class="fas fa-check text-blue-600 text-xl"></i>
                        </div>
                        <span class="bg-blue-600 text-white text-xs font-bold px-2 py-0.5 rounded">SELECIONADO</span>
                    </div>
                </div>
                `;
            }).join('');

            // Sticky Legend Footer (Global for the list)
            const legendHtml = `
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 my-4">
                    <div class="flex flex-wrap justify-center gap-x-4 gap-y-2 text-[10px] text-gray-500">
                        <span><i class="fas fa-map-marker-alt mr-1"></i>MUN: Municipal</span>
                        <span><i class="fas fa-map mr-1"></i>REG: Regional</span>
                        <span><i class="fas fa-globe-americas mr-1"></i>NAC: Nacional</span>
                        <span><i class="fas fa-home mr-1"></i>APT: Privativo</span>
                        <span><i class="fas fa-bed mr-1"></i>ENF: Coletivo</span>
                        <span><i class="fas fa-baby mr-1"></i>C/ PARTO: Inclui Obstetrícia</span>
                        <span><i class="fas fa-coins mr-1"></i>c/ Copar: Com taxas</span>
                        <span><i class="fas fa-check-circle mr-1"></i>s/ Copar: Sem taxas</span>
                        <span><i class="fas fa-tooth mr-1"></i>Odonto</span>
                    </div>
                </div>
            `;

            // Pagination Controls
            const paginationHtml = `
                <div class="flex justify-between items-center mt-2 pt-4 border-t border-gray-200">
                    <button onclick="changePage(-1)" ${currentPage === 1 ? 'disabled class="opacity-30 cursor-not-allowed"' : ''} class="text-gray-600 hover:text-blue-600 font-bold text-sm flex items-center">
                        <i class="fas fa-chevron-left mr-2"></i> Anterior
                    </button>
                    <span class="text-xs text-gray-500">Página ${currentPage} de ${totalPages}</span>
                    <button onclick="changePage(1)" ${currentPage === totalPages ? 'disabled class="opacity-30 cursor-not-allowed"' : ''} class="text-gray-600 hover:text-blue-600 font-bold text-sm flex items-center">
                        Próximo <i class="fas fa-chevron-right ml-2"></i>
                    </button>
                </div>
            `;

            container.innerHTML = plansHtml + legendHtml + paginationHtml;
            
            // Re-apply selection state
            state.selectedPlans.forEach(id => {
                const card = document.querySelector(`.plan-card[data-id="${id}"]`);
                if(card) {
                    card.classList.add('ring-2', 'ring-blue-500', 'border-blue-500');
                    card.querySelector('.selection-overlay').classList.remove('hidden');
                    card.querySelector('.selection-overlay').classList.add('flex');
                }
            });
            updateSelectionVisuals();
            
            // Scroll to top of list if changing page
            const title = document.querySelector('#result-profile-name');
            if(title) title.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function changePage(delta) {
            currentPage += delta;
            renderPlanos();
        }

        function togglePlanSelection(card, id) {
            const index = state.selectedPlans.indexOf(id);
            const overlay = card.querySelector('.selection-overlay');
            
            if (index > -1) {
                // Deselect
                state.selectedPlans.splice(index, 1);
                card.classList.remove('ring-2', 'ring-blue-500', 'border-blue-500');
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
            } else {
                // Select
                // if (state.selectedPlans.length >= 3) {
                //     showToast('Você pode selecionar no máximo 3 planos.', 'warning');
                //     return;
                // }
                state.selectedPlans.push(id);
                card.classList.add('ring-2', 'ring-blue-500', 'border-blue-500');
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
            }

            document.getElementById('selected-count').innerText = state.selectedPlans.length;
            
            if (state.selectedPlans.length > 0) {
                 const alertBox = document.getElementById('step-4-validation-alert');
                 if(alertBox) alertBox.classList.add('hidden');
            }

            updateSelectionVisuals();
        }

        function updateSelectionVisuals() {
            const allCards = document.querySelectorAll('.plan-card');
            // const isFull = state.selectedPlans.length >= 3;

            allCards.forEach(c => {
                const id = parseInt(c.getAttribute('data-id'));
                const isSelected = state.selectedPlans.includes(id);

                // if (isFull && !isSelected) {
                //     c.classList.add('opacity-40', 'grayscale');
                // } else {
                    c.classList.remove('opacity-40', 'grayscale');
                // }
            });
        }

        // --- STEP INITIALIZATION ---
        function initializeStep(step) {
            console.log('Initializing step:', step);
            
            // Step 1: Hospital (Autocomplete)
            if (step === 1) {
                const input = document.getElementById('user-name');
                if (input) {
                    input.focus();
                    input.addEventListener('keypress', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            nextStep(2);
                        }
                    });
                }
            }

            // Step 2: Lives (Validation/Interactivity)
            if (step === 2) {
                // ... (Logic for lives counter usually handles itself via alpine or inline onclicks, but we can add global listeners if needed)
                // For now, assuming inline onclicks in step-2.blade.php handle state updates or we need to bind them.
                // Checking step-2.blade.php content would confirm, but let's assume standard behavior.
                updateLivesSummary();
            }

            // Step 4: Plans (Fetch)
            if (step === 4) {
                buscarPlanosAPI();
            }
            
            // Step 5: WhatsApp Mask
            if (step === 5) {
                const input = document.getElementById('whatsapp-input');
                if (input) {
                     input.addEventListener('input', function (e) {
                        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
                        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
                    });
                }
                renderSelectedPlansSummary();
            }
        }

        function updateLivesSummary() {
            // Helper if needed for step 2
        }

        async function validateAndProceedStep4() {
            if (state.selectedPlans.length === 0) {
                const alertBox = document.getElementById('step-4-validation-alert');
                if (alertBox) alertBox.classList.remove('hidden');
                if (alertBox) alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // Usa o loading principal agora
            showMainLoading('Gerando sua proposta personalizada...');
            
            try {
                const response = await fetch('/proposta/gerar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        planIds: state.selectedPlans,
                        lives: state.lives,
                        profile: state.profile,
                        profession_id: state.professionId || null,
                        nome: state.nome || null
                    })
                });

                const data = await response.json();
                console.log(data);
                if (data.success) {
                    if (data.plans_without_internacao && data.plans_without_internacao.length > 0) {
                        const noElective = data.plans_without_internacao.filter(p => p.reason === 'no_elective').map(p => p.name);

                        let msg = '';
                        
                        if (noElective.length > 0) {
                            msg += `
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-3 text-left">
                                    <h4 class="font-bold text-yellow-800 text-sm mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Atenção: Cobertura Hospitalar</h4>
                                    <p class="text-xs text-yellow-700 leading-relaxed mb-2">
                                        Identificamos que o(s) plano(s) abaixo(os) oferecem cobertura <strong>exclusiva para Pronto-Socorro (Emergência)</strong>.
                                    </p>
                                    <div class="mt-1 text-xs font-semibold text-yellow-800 bg-white p-2 rounded border border-yellow-200">
                                        ${noElective.join(', ')}
                                    </div>
                                    </div>`;
                        }

                        if (msg !== '') {
                            msg += "<p class='text-sm text-gray-600 mt-2 text-center'>Deseja ver o comparativo completo mesmo assim?</p>";
                            
                            // Remove newlines from msg to prevent double BRs
                            msg = msg.replace(/\n/g, '');

                            showModal('Atenção - Detalhes da Rede', msg, 
                                () => nextStep(5), // Confirm
                                () => nextStep(4), // Cancel - Volta para o passo 4
                                'Ver Comparativo', // Botão Confirmar
                                'Revisar Planos'   // Botão Cancelar
                            );
                            return;
                        }
                    }
                    nextStep(5);
                } else {
                    showToast('Erro ao gerar simulação: ' + (data.error || 'Erro desconhecido'), 'error');
                    nextStep(4); // Volta para o passo 4 em caso de erro
                }
            } catch (error) {
                console.error('Erro:', error);
                showToast('Erro na conexão ao gerar simulação.', 'error');
                nextStep(4); // Volta para o passo 4 em caso de erro
            }
        }

        function renderSelectedPlansSummary() {
            const container = document.getElementById('selected-plans-summary');
            if (!container) return;

            const selected = state.planos.filter(p => state.selectedPlans.includes(p.id));
            
            if (selected.length === 0) {
                container.innerHTML = '';
                return;
            }

            let html = '<p class="text-[10px] font-bold text-gray-400 uppercase text-left mb-2">Planos Selecionados:</p>';
            selected.forEach(plano => {
                html += `
                    <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg border border-gray-100">
                        ${plano.operadora_logo 
                            ? `<img src="${plano.operadora_logo}" alt="${plano.operadora}" class="h-6 w-auto" />`
                            : `<div class="w-10 h-6 bg-gray-200 rounded animate-pulse"></div>`
                        }
                        <div class="text-left">
                            <p class="text-xs font-bold text-gray-800 leading-tight">${plano.nome || plano.operadora}</p>
                            <p class="text-[10px] text-gray-500">${plano.operadora}</p>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // --- PASSO 5: FINALIZAR ---
        async function finishProcess() {
            const zapInput = document.getElementById('whatsapp-input');
            const zap = zapInput ? zapInput.value.replace(/\D/g, '') : '';
            
            if (zap.length < 10) {
                showToast("Por favor, digite um WhatsApp válido com DDD.", 'warning');
                return;
            }

            const btn = document.querySelector('#step-5 button');
            setLoading(btn, true, 'Enviando Proposta...');

            try {
                const response = await fetch('/proposta/enviar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        phone: zap
                    })
                });

                const data = await response.json();

                if (data.success) {
                    loadFinalStep();
                } else {
                    showToast('Erro ao enviar proposta: ' + (data.message || data.error || 'Erro desconhecido'), 'error');
                    setLoading(btn, false);
                }
            } catch (error) {
                console.error('Erro:', error);
                showToast('Erro de conexão. Tente novamente.', 'error');
                setLoading(btn, false);
            }
        }

        // --- LOCALIZAÇÃO ---
        // --- LOCALIZAÇÃO ---
        function getCityName(latitude, longitude) {
            // Usando API gratuita do OpenStreetMap (Nominatim) para geocodificação reversa
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&addressdetails=1`;

            fetch(url, {
                headers: {
                    'User-Agent': 'SaudeSelect/1.0'
                }
            })
            .then(response => response.json())
            .then(data => {
                const locationText = document.getElementById('location-text');
                const locationError = document.getElementById('location-error');
                const headerCityName = document.getElementById('header-city-name');

                if (data && data.address) {
                    // Tenta obter o nome da cidade de diferentes campos possíveis
                    const city = data.address.city ||
                                data.address.town ||
                                data.address.municipality ||
                                data.address.village ||
                                data.address.county ||
                                'Localização não identificada';

                    const stateName = data.address.state;
                    
                    updateLocationUI(city, stateName);

                    state.city = city;
                    
                    // Update Region ID based on detected state
                    if (stateName && REGION_MAP[stateName]) {
                        state.regionId = REGION_MAP[stateName];
                    }

                    state.locationGranted = true; // Marca que a localização foi concedida
                    locationError.classList.add('hidden');

                    proceedIfReady();
                } else {
                   fallbackToIP();
                }
            })
            .catch(error => {
                console.error('Erro ao obter nome da cidade (GPS):', error);
                fallbackToIP();
            });
        }

        function fallbackToIP() {
            console.log('Tentando obter localização via IP...');
            // Fallback para API de IP (ex: ipapi.co - limite gratuito generoso para frontend)
            fetch('https://ipapi.co/json/')
                .then(res => res.json())
                .then(data => {
                    const city = data.city || 'Sua Região';
                    const stateName = data.region; // ipapi retorna nome do estado ex: "Rio de Janeiro"
                    
                    console.log('Localização via IP:', city, stateName);

                    updateLocationUI(city, stateName);
                    
                    state.city = city;
                     // Update Region ID based on detected state
                     // ipapi region key matches our map mostly
                    if (stateName && REGION_MAP[stateName]) {
                        state.regionId = REGION_MAP[stateName];
                    }

                    state.locationGranted = true;
                    document.getElementById('location-error').classList.add('hidden');
                    proceedIfReady();
                })
                .catch(err => {
                    console.error('Erro ao obter loc via IP:', err);
                     // Último caso: Default
                    updateLocationUI('Rio de Janeiro', 'Rio de Janeiro');
                    state.locationGranted = true; // Assume true para não bloquear
                    proceedIfReady();
                });
        }

        function updateLocationUI(city, stateName) {
             const locationText = document.getElementById('location-text');
             const headerCityName = document.getElementById('header-city-name');
             
             if(locationText) locationText.innerText = city;
             if(headerCityName) headerCityName.innerText = city;
        }

        function proceedIfReady() {
             // Se ainda não carregou a primeira etapa, carrega agora
             const container = document.getElementById('step-container');
             if (state.step === 1 && (container.innerHTML.includes('Aguardando Localização') || container.innerHTML.includes('Localização Necessária') || container.innerHTML.includes('Não conseguimos pegar'))) {
                 nextStep(1);
             }
        }

        function requestLocation() {
            const locationText = document.getElementById('location-text');
            const locationError = document.getElementById('location-error');
            const locationContainer = document.getElementById('location-container');

            locationText.innerText = 'Detectando...';
            locationError.classList.add('hidden');

            if (!navigator.geolocation) {
                fallbackToIP();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    getCityName(latitude, longitude);
                },
                (error) => {
                    console.warn('GPS negado ou erro, usando IP fallback.', error);
                    fallbackToIP();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 7000,
                    maximumAge: 0
                }
            );
        }

        // Solicita localização quando a página carregar
        document.addEventListener('DOMContentLoaded', () => {
            requestLocation();
        });
    </script>
    <script>
        // --- PWA LOGIC ---
        let deferredPrompt;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('PWA: Evento beforeinstallprompt capturado.');
            
            // Opcional: Mostrar botão de instação se estiver oculto
            // document.getElementById('btn-install-pwa').style.display = 'block';
        });

        function installPWA() {
            if (!deferredPrompt) {
                console.log('PWA: Prompt de instalação não disponível (ainda não capturado ou já instalado).');
                alert('A instalação não está disponível no momento. Tente recarregar a página ou usar o menu do navegador.');
                return;
            }

            deferredPrompt.prompt();
            
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('PWA: Usuário aceitou a instalação');
                } else {
                    console.log('PWA: Usuário recusou a instalação');
                }
                deferredPrompt = null;
            });
        }

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('PWA: Service Worker registrado com sucesso:', registration.scope);
                    })
                    .catch(err => {
                        console.error('PWA: Falha ao registrar Service Worker:', err);
                    });
            });
        }
    </script>
</body>
</html>
