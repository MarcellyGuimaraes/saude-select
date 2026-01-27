<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SaúdeSelect 2026 - MVP</title>
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
            <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-heartbeat text-blue-600 mr-2"></i>SaúdeSelect</h1>
            <div id="location-container" class="text-xs text-gray-500 flex items-center bg-white px-2 py-1 rounded-full shadow-sm cursor-pointer hover:bg-gray-50">
                <i class="fas fa-map-marker-alt mr-1 text-red-500"></i>
                <span id="location-text">Carregando...</span>
                <i class="fas fa-chevron-down ml-1"></i>
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

    <!-- LOGICA JAVASCRIPT (Simulando Backend) -->
    <script>
        // --- ESTADO DA APLICAÇÃO ---
        const state = {
            step: 1,
            hospital: '',
            hospitalId: null,
            profile: null, // 'pme', 'adesao', 'cpf'
            lives: { '0-18': 0, '19-23': 0, '24-28': 0, '29-33': 0, '34-38': 0, '39-43': 0, '44-48': 0, '49-53': 0, '54-58': 0, '59+': 0 },
            totalLives: 0,
            selectedPlans: [],
            locationGranted: false,
            city: null,
            planos: [],
            planosPaginaAtual: 1
        };

        // Dados mock removidos - agora usa API real

        // --- FUNÇÕES DE NAVEGAÇÃO ---
        function nextStep(step) {
            // Verificar se a localização foi concedida antes de permitir avançar
            if (!state.locationGranted) {
                // Mostrar mensagem de bloqueio
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

            const container = document.getElementById('step-container');

            // Mostrar loading
            container.innerHTML = `
                <div class="flex items-center justify-center min-h-[500px]">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                        <p class="text-gray-500">Carregando...</p>
                    </div>
                </div>
            `;

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
                    container.innerHTML = html;
                    state.step = step;

                    // Reinicializar eventos específicos da etapa
                    initializeStep(step);

                    window.scrollTo(0, 0);
                })
                .catch(error => {
                    console.error('Erro:', error);
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
            const container = document.getElementById('step-container');

            container.innerHTML = `
                <div class="flex items-center justify-center min-h-[500px]">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                        <p class="text-gray-500">Carregando...</p>
                    </div>
                </div>
            `;

            fetch('/step-final')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao carregar etapa final');
                    }
                    return response.text();
                })
                .then(html => {
                    container.innerHTML = html;
                    document.getElementById('progress-bar').style.width = '100%';
                    document.getElementById('step-text').innerText = 'Concluído';
                    window.scrollTo(0, 0);
                })
                .catch(error => {
                    console.error('Erro:', error);
                    container.innerHTML = `
                        <div class="p-6 text-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                            <p class="text-red-600">Erro ao carregar etapa final.</p>
                        </div>
                    `;
                });
        }

        function initializeStep(step) {
            switch(step) {
                case 1:
                    initializeStep1();
                    break;
                case 2:
                    initializeStep2();
                    break;
                case 3:
                    initializeStep3();
                    break;
                case 4:
                    initializeStep4();
                    break;
                case 5:
                    initializeStep5();
                    break;
            }
        }

        function initializeStep1() {
            const searchInput = document.getElementById('hospital-search');
            const listDiv = document.getElementById('autocomplete-list');
            let timeoutId = null;

            if (searchInput && listDiv) {
                searchInput.addEventListener('input', (e) => {
                    const val = e.target.value.trim();
                    listDiv.innerHTML = '';

                    // Limpa timeout anterior
                    if (timeoutId) {
                        clearTimeout(timeoutId);
                    }

                    if (val.length < 1) {
                        listDiv.classList.add('hidden');
                        return;
                    }

                    // Debounce: aguarda 300ms após o usuário parar de digitar
                    timeoutId = setTimeout(() => {
                        buscarHospitaisAPI(val, listDiv, searchInput);
                    }, 300);
                });
            }
        }

        async function buscarHospitaisAPI(query, listDiv, searchInput) {
            try {
                // Mostra loading
                listDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">Buscando...</div>';
                listDiv.classList.remove('hidden');

                const params = new URLSearchParams({
                    regiao: 2,
                    q: query,
                });

                const response = await fetch(`/api/hospitais/buscar?${params}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                listDiv.innerHTML = '';

                if (data.success && data.hospitais && data.hospitais.length > 0) {
                    data.hospitais.forEach(hospital => {
                        const nome = hospital.nome || hospital.name || '';
                        if (!nome) return;

                        const div = document.createElement('div');
                        div.className = "p-3 hover:bg-gray-100 cursor-pointer text-sm border-b";
                        div.innerText = nome;
                        div.onclick = () => {
                            searchInput.value = nome;
                            state.hospital = nome;
                            state.hospitalId = hospital.id || null;
                            listDiv.classList.add('hidden');
                            nextStep(2);
                        };
                        listDiv.appendChild(div);
                    });
                    listDiv.classList.remove('hidden');
                } else {
                    listDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">Nenhum hospital encontrado</div>';
                    listDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro ao buscar hospitais:', error);
                listDiv.innerHTML = '<div class="p-3 text-sm text-red-500 text-center">Erro ao buscar hospitais</div>';
                listDiv.classList.remove('hidden');
            }
        }

        function initializeStep2() {
            // Eventos já estão nos onclick inline, mas podemos adicionar lógica adicional se necessário
        }

        function initializeStep3() {
            // Eventos já estão nos onclick inline
        }

        function initializeStep4() {
            renderResults();
            buscarPlanosAPI();
        }

        function initializeStep5() {
            // Eventos já estão nos onclick inline
        }

        // PASSO 1 será inicializado na função initializeStep1()

        // --- PASSO 2: PERFIL ---
        function selectProfile(profile) {
            state.profile = profile;

            // Reset visual
            document.querySelectorAll('.profile-option').forEach(el => {
                el.classList.remove('active-card', 'ring-2', 'ring-blue-500');
                el.classList.add('dimmed');
            });

            // Activate selected
            const selectedBtn = document.getElementById(`btn-${profile}`);
            selectedBtn.classList.remove('dimmed');
            selectedBtn.classList.add('active-card');

            // Logica Adesão Input
            const profissaoInput = document.getElementById('profissao-input');
            if(profile === 'adesao') {
                profissaoInput.classList.remove('hidden');
            } else {
                profissaoInput.classList.add('hidden');
            }

            // Mostrar botão next
            document.getElementById('btn-step-2-next').classList.remove('hidden');
        }

        // --- PASSO 3: VIDAS ---
        function updateLives(range, delta) {
            const newVal = state.lives[range] + delta;
            if (newVal >= 0) {
                state.lives[range] = newVal;
                document.getElementById(`count-${range}`).innerText = newVal;
                updateTotal();
            }
        }

        function updateTotal() {
            state.totalLives = Object.values(state.lives).reduce((a, b) => a + b, 0);
            document.getElementById('total-lives').innerText = state.totalLives;

            // Limpa alertas ao mexer
            document.getElementById('validation-alert').classList.add('hidden');
        }

        function validateAndProceedStep3() {
            const alertBox = document.getElementById('validation-alert');
            const alertMsg = document.getElementById('alert-msg');

            // Validação Vazia
            if (state.totalLives === 0) {
                alertMsg.innerText = "Por favor, adicione pelo menos uma pessoa.";
                alertBox.classList.remove('hidden');
                return;
            }

            // Regra: MEI exige min 2 vidas (Simulação)
            if (state.profile === 'pme' && state.totalLives < 2) {
                alertMsg.innerText = "Para Tabela PME/CNPJ, o mínimo são 2 vidas. Adicione mais alguém ou mudaremos para CPF.";
                alertBox.classList.remove('hidden');

                // Sugestão de ação automática poderia ser aqui
                // Mas por enquanto só alerta
                return;
            }

            // Regra: Criança Sozinha (0-18 > 0 e resto 0)
            const criancas = state.lives['0-18'] || 0;
            const adultos = Object.entries(state.lives).filter(([k]) => k !== '0-18').reduce((a, [, v]) => a + (v || 0), 0);

            if (criancas > 0 && adultos === 0) {
                if (state.profile !== 'cpf') {
                    // Força mudança para CPF
                    state.profile = 'cpf';
                    alert("Atenção: Criança sem titular adulto só pode contratar no CPF Individual. Ajustamos seu perfil automaticamente.");
                }
            }

            nextStep(4);
        }

        // --- PASSO 4: RESULTADOS ---
        function renderResults() {
            let title = "Individual (CPF)";
            if (state.profile === 'pme') title = "Empresarial (CNPJ)";
            if (state.profile === 'adesao') title = "Coletivo por Adesão";

            document.getElementById('result-profile-name').innerText = title;
        }

        async function buscarPlanosAPI() {
            try {
                // Mostra loading
                const container = document.querySelector('#step-4 .p-4.space-y-4');
                if (container) {
                    container.innerHTML = '<div class="p-8 text-center"><i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i><p class="text-gray-600">Buscando planos...</p></div>';
                }

                const response = await fetch('/api/planos/buscar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        profile: state.profile,
                        lives: state.lives,
                        hospital: state.hospital,
                        hospitalId: state.hospitalId
                    })
                });

                const data = await response.json();

                if (data.success && data.planos && data.planos.length > 0) {
                    state.planos = data.planos;
                    state.planosPaginaAtual = 1;
                    renderizarPlanos();
                } else {
                    const container = document.querySelector('#step-4 .p-4.space-y-4');
                    if (container) {
                        container.innerHTML = '<div class="p-8 text-center"><i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i><p class="text-gray-600">Nenhum plano encontrado</p></div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao buscar planos:', error);
                const container = document.querySelector('#step-4 .p-4.space-y-4');
                if (container) {
                    container.innerHTML = '<div class="p-8 text-center"><i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i><p class="text-red-600">Erro ao buscar planos</p></div>';
                }
            }
        }

        const PLANOS_POR_PAGINA = 5;

        function renderizarPlanos() {
            const container = document.querySelector('#step-4 .p-4.space-y-4');
            if (!container) return;

            const pagina = state.planosPaginaAtual || 1;
            const totalPaginas = Math.max(1, Math.ceil(state.planos.length / PLANOS_POR_PAGINA));
            const inicio = (pagina - 1) * PLANOS_POR_PAGINA;
            const toShow = state.planos.slice(inicio, inicio + PLANOS_POR_PAGINA);
            container.innerHTML = '';

            toShow.forEach(plano => {
                const isSelected = state.selectedPlans.includes(plano.id);
                const card = document.createElement('div');
                card.className = 'plan-card bg-white p-4 rounded-xl shadow-sm border border-gray-100 relative transition-all cursor-pointer' + (isSelected ? ' ring-2 ring-green-500' : '');
                card.onclick = () => togglePlanSelection(card);
                card.setAttribute('data-plano-id', plano.id);

                const logoHtml = plano.operadora_logo
                    ? `<img src="${plano.operadora_logo}" alt="${plano.operadora}" class="h-8 w-auto mb-2" />`
                    : '<div class="bg-gray-200 h-8 w-20 rounded animate-pulse mb-2"></div>';

                const badges = [];
                if (plano.operadora_descricao && plano.operadora_descricao.includes('Copart')) {
                    badges.push('<span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">C/ Copar</span>');
                }
                if (plano.acomodacao_sigla === 'AMB') {
                    badges.push('<span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">AMB</span>');
                } else if (plano.acomodacao_sigla === 'E') {
                    badges.push('<span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">Enfermaria</span>');
                } else if (plano.acomodacao_sigla === 'A') {
                    badges.push('<span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">Apartamento</span>');
                }

                card.innerHTML = `
                    <div class="flex justify-between items-start mb-2">
                        ${logoHtml}
                        <span class="text-[10px] font-bold bg-green-100 text-green-700 px-2 py-1 rounded">MATCH TÉCNICO</span>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg">${plano.nome || plano.operadora}</h3>
                    <p class="text-xs text-gray-500 mb-3">${plano.acomodacao} | ${plano.operadora}</p>
                    ${badges.length > 0 ? `<div class="flex gap-2 mb-3">${badges.join('')}</div>` : ''}
                    <div class="mt-4 pt-3 border-t border-dashed border-gray-200 flex justify-between items-end">
                        <div class="text-xs text-gray-400">Mensalidade:</div>
                        <div class="blur-price text-xl font-bold text-blue-600 bg-gray-100 px-2 rounded">R$ --</div>
                    </div>
                    <div class="selection-check absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 ${isSelected ? '' : 'hidden'}">
                        <i class="fas fa-check-circle text-4xl text-blue-600 bg-white rounded-full"></i>
                    </div>
                `;

                container.appendChild(card);
            });

            if (totalPaginas > 1) {
                const pagWrap = document.createElement('div');
                pagWrap.className = 'pt-4 pb-2 flex flex-col items-center gap-3';
                const paginas = botoesPagina(pagina, totalPaginas);
                let html = `<p class="text-xs text-gray-500">Página ${pagina} de ${totalPaginas} · ${state.planos.length} planos (${PLANOS_POR_PAGINA} por página)</p>`;
                html += '<div class="flex flex-wrap justify-center items-center gap-1">';
                if (pagina > 1) {
                    html += `<button type="button" onclick="irParaPagina(${pagina - 1})" class="w-9 h-9 rounded-lg border border-gray-300 hover:bg-gray-100 flex items-center justify-center text-sm" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>`;
                }
                paginas.forEach(p => {
                    if (p === '...') {
                        html += `<span class="px-1 text-gray-400">…</span>`;
                    } else {
                        const ativa = p === pagina ? ' bg-blue-600 text-white border-blue-600' : ' border-gray-300 hover:bg-gray-100';
                        html += `<button type="button" onclick="irParaPagina(${p})" class="w-9 h-9 rounded-lg border flex items-center justify-center text-sm font-medium${ativa}">${p}</button>`;
                    }
                });
                if (pagina < totalPaginas) {
                    html += `<button type="button" onclick="irParaPagina(${pagina + 1})" class="w-9 h-9 rounded-lg border border-gray-300 hover:bg-gray-100 flex items-center justify-center text-sm" aria-label="Próxima"><i class="fas fa-chevron-right"></i></button>`;
                }
                html += '</div>';
                pagWrap.innerHTML = html;
                container.appendChild(pagWrap);
            }
        }

        function botoesPagina(atual, total) {
            if (total <= 7) {
                return Array.from({ length: total }, (_, i) => i + 1);
            }
            const out = [1];
            if (atual > 3) out.push('...');
            for (let i = Math.max(2, atual - 1); i <= Math.min(total - 1, atual + 1); i++) {
                if (!out.includes(i)) out.push(i);
            }
            if (atual < total - 2) out.push('...');
            if (total > 1) out.push(total);
            return out;
        }

        function irParaPagina(n) {
            state.planosPaginaAtual = n;
            renderizarPlanos();
            const step = document.getElementById('step-4');
            if (step) step.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function togglePlanSelection(card) {
            // Logica visual simples de seleção
            const check = card.querySelector('.selection-check');
            const isSelected = !check.classList.contains('hidden');
            const planoId = parseInt(card.getAttribute('data-plano-id'));

            if (isSelected) {
                check.classList.add('hidden');
                card.classList.remove('ring-2', 'ring-green-500');
                state.selectedPlans = state.selectedPlans.filter(id => id !== planoId);
            } else {
                if (state.selectedPlans.length >= 3) return; // Max 3
                check.classList.remove('hidden');
                card.classList.add('ring-2', 'ring-green-500');
                state.selectedPlans.push(planoId);
            }

            document.getElementById('selected-count').innerText = state.selectedPlans.length;
        }

        // --- PASSO 5: FINALIZAR ---
        function finishProcess() {
            const zap = document.getElementById('whatsapp-input');
            if (!zap || zap.value.length < 8) {
                alert("Digite um WhatsApp válido.");
                return;
            }

            // Loading fake
            const btn = document.querySelector('#step-5 button');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando PDF...';
            }

            setTimeout(() => {
                loadFinalStep();
                // Aqui você faria o POST para seu controller Laravel
            }, 1500);
        }

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

                if (data && data.address) {
                    // Tenta obter o nome da cidade de diferentes campos possíveis
                    const city = data.address.city ||
                                data.address.town ||
                                data.address.municipality ||
                                data.address.village ||
                                data.address.county ||
                                'Localização não identificada';

                    locationText.innerText = city;
                    state.city = city;
                    state.locationGranted = true; // Marca que a localização foi concedida
                    locationError.classList.add('hidden');

                    // Se ainda não carregou a primeira etapa, carrega agora
                    const container = document.getElementById('step-container');
                    if (state.step === 1 && (container.innerHTML.includes('Aguardando Localização') || container.innerHTML.includes('Localização Necessária'))) {
                        nextStep(1);
                    }
                } else {
                    locationText.innerText = 'Localização não identificada';
                    locationError.classList.add('hidden');
                    // Mesmo sem cidade identificada, consideramos que a permissão foi concedida
                    state.locationGranted = true;
                    const container = document.getElementById('step-container');
                    if (state.step === 1 && (container.innerHTML.includes('Aguardando Localização') || container.innerHTML.includes('Localização Necessária'))) {
                        nextStep(1);
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao obter nome da cidade:', error);
                const locationText = document.getElementById('location-text');
                locationText.innerText = 'Erro ao carregar';
                // Em caso de erro na API, ainda consideramos que a permissão foi concedida
                state.locationGranted = true;
                const container = document.getElementById('step-container');
                if (state.step === 1 && (container.innerHTML.includes('Aguardando Localização') || container.innerHTML.includes('Localização Necessária'))) {
                    nextStep(1);
                }
            });
        }

        function requestLocation() {
            const locationText = document.getElementById('location-text');
            const locationError = document.getElementById('location-error');
            const locationContainer = document.getElementById('location-container');

            if (!navigator.geolocation) {
                locationText.innerText = 'Navegador não suporta';
                locationError.classList.remove('hidden');
                state.locationGranted = false;

                // Atualiza a tela principal para mostrar mensagem de erro
                const container = document.getElementById('step-container');
                container.innerHTML = `
                    <div class="p-8 text-center min-h-[500px] flex flex-col justify-center items-center">
                        <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Não conseguimos pegar sua localização</h2>
                        <p class="text-gray-600 mb-6 text-sm px-4">Seu navegador não suporta geolocalização. Por favor, use um navegador mais recente.</p>
                        <p class="text-xs text-gray-500 px-6">Recomendamos usar Chrome, Firefox, Safari ou Edge atualizados.</p>
                    </div>
                `;
                return;
            }

            locationText.innerText = 'Solicitando...';
            locationError.classList.add('hidden');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    getCityName(latitude, longitude);
                },
                (error) => {
                    locationText.innerText = 'Permissão negada';
                    locationError.classList.remove('hidden');
                    state.locationGranted = false;

                    // Atualiza a tela principal para mostrar mensagem de erro
                    const container = document.getElementById('step-container');
                    container.innerHTML = `
                        <div class="p-8 text-center min-h-[500px] flex flex-col justify-center items-center">
                            <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Não conseguimos pegar sua localização</h2>
                            <p class="text-gray-600 mb-6 text-sm px-4">É necessário permitir o acesso à sua localização para continuar usando o sistema.</p>
                            <button onclick="requestLocation()" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition">
                                <i class="fas fa-location-arrow mr-2"></i>Tentar Novamente
                            </button>
                            <p class="text-xs text-gray-500 mt-4 px-6">Verifique as configurações de privacidade do seu navegador e permita o acesso à localização.</p>
                        </div>
                    `;

                    // Adiciona evento de clique no container para tentar novamente
                    locationContainer.onclick = () => {
                        locationError.classList.add('hidden');
                        requestLocation();
                    };
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        // Solicita localização quando a página carregar
        // A primeira etapa só será carregada após a localização ser concedida
        document.addEventListener('DOMContentLoaded', () => {
            requestLocation();
            // Não carrega a primeira etapa imediatamente - será carregada após localização ser concedida
        });
    </script>
</body>
</html>
