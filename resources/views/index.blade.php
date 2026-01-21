<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <div class="flex items-center justify-center min-h-[500px]">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                <p class="text-gray-500">Carregando...</p>
            </div>
        </div>
    </main>

    <!-- LOGICA JAVASCRIPT (Simulando Backend) -->
    <script>
        // --- ESTADO DA APLICAÇÃO ---
        const state = {
            step: 1,
            hospital: '',
            profile: null, // 'pme', 'adesao', 'cpf'
            lives: { '0-18': 0, '19-23': 0, '24-58': 0 },
            totalLives: 0,
            selectedPlans: []
        };

        // --- DADOS MOCK (Simulando Banco de Dados) ---
        const hospitalsDB = [
            "Complexo Hospitalar de Niterói (CHN)",
            "Hospital Icaraí",
            "Hospital Santa Martha",
            "Hospital de Olhos Niterói",
            "Clínica São Geraldo"
        ];

        // --- FUNÇÕES DE NAVEGAÇÃO ---
        function nextStep(step) {
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

            if (searchInput && listDiv) {
                searchInput.addEventListener('input', (e) => {
                    const val = e.target.value.toLowerCase();
                    listDiv.innerHTML = '';

                    if (val.length > 0) {
                        const filtered = hospitalsDB.filter(h => h.toLowerCase().includes(val));
                        if (filtered.length > 0) {
                            listDiv.classList.remove('hidden');
                            filtered.forEach(h => {
                                const div = document.createElement('div');
                                div.className = "p-3 hover:bg-gray-100 cursor-pointer text-sm border-b";
                                div.innerText = h;
                                div.onclick = () => {
                                    searchInput.value = h;
                                    state.hospital = h;
                                    listDiv.classList.add('hidden');
                                    nextStep(2);
                                };
                                listDiv.appendChild(div);
                            });
                        } else {
                            listDiv.classList.add('hidden');
                        }
                    } else {
                        listDiv.classList.add('hidden');
                    }
                });
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
            const criancas = state.lives['0-18'];
            const adultos = state.lives['19-23'] + state.lives['24-58'];

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

        function togglePlanSelection(card) {
            // Logica visual simples de seleção
            const check = card.querySelector('.selection-check');
            const isSelected = !check.classList.contains('hidden');

            if (isSelected) {
                check.classList.add('hidden');
                card.classList.remove('ring-2', 'ring-green-500');
                state.selectedPlans.pop();
            } else {
                if (state.selectedPlans.length >= 3) return; // Max 3
                check.classList.remove('hidden');
                card.classList.add('ring-2', 'ring-green-500');
                state.selectedPlans.push(1);
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
                    locationError.classList.add('hidden');
                } else {
                    locationText.innerText = 'Localização não identificada';
                    locationError.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Erro ao obter nome da cidade:', error);
                const locationText = document.getElementById('location-text');
                locationText.innerText = 'Erro ao carregar';
            });
        }

        function requestLocation() {
            const locationText = document.getElementById('location-text');
            const locationError = document.getElementById('location-error');
            const locationContainer = document.getElementById('location-container');

            if (!navigator.geolocation) {
                locationText.innerText = 'Navegador não suporta';
                locationError.classList.remove('hidden');
                return;
            }

            locationText.innerText = 'Solicitando...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    getCityName(latitude, longitude);
                },
                (error) => {
                    locationText.innerText = 'Permissão negada';
                    locationError.classList.remove('hidden');

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

        // Solicita localização quando a página carregar e carrega primeira etapa
        document.addEventListener('DOMContentLoaded', () => {
            requestLocation();
            nextStep(1); // Carrega a primeira etapa
        });
    </script>
</body>
</html>
