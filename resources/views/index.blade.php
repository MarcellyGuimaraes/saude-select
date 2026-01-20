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
        <div class="flex justify-between items-center mb-2">
            <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-heartbeat text-blue-600 mr-2"></i>SaúdeSelect</h1>
            <div class="text-xs text-gray-500 flex items-center bg-white px-2 py-1 rounded-full shadow-sm cursor-pointer hover:bg-gray-50">
                <i class="fas fa-map-marker-alt mr-1 text-red-500"></i>
                <span id="location-text">Niterói</span>
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
    <main class="w-full max-w-md bg-white rounded-xl shadow-lg overflow-hidden transition-smooth min-h-[500px] relative">

        <!-- PASSO 1: Busca de Hospitais (20%) -->
        <div id="step-1" class="step-content p-6">
            <div class="text-center mb-6">
                <h2 class="text-lg font-bold text-gray-800 mb-2">Qual seu hospital preferido?</h2>
                <p class="text-sm text-gray-500">Digite o nome para ver apenas os planos que atendem lá.</p>
            </div>

            <div class="relative mb-6">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="hospital-search"
                    class="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Ex: CHN, Hospital Icaraí...">

                <!-- Autocomplete Mock (Escondido inicialmente) -->
                <div id="autocomplete-list" class="hidden absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto">
                    <!-- Preenchido via JS -->
                </div>
            </div>

            <button onclick="nextStep(2)" class="w-full py-3 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                Não tenho preferência / Ver todos
            </button>
        </div>

        <!-- PASSO 2: Perfil Jurídico (40%) -->
        <div id="step-2" class="step-content hidden p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Como você prefere contratar?</h2>

            <div class="space-y-3">
                <!-- Opção PME -->
                <div onclick="selectProfile('pme')" id="btn-pme" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all group relative overflow-hidden">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg text-blue-600 mr-3">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Sou Empresa ou MEI</h3>
                            <p class="text-xs text-green-600 font-semibold">Economia de até 40%</p>
                        </div>
                    </div>
                    <div class="absolute right-0 top-0 bg-green-100 text-green-800 text-[10px] px-2 py-1 rounded-bl-lg">
                        Mais Barato
                    </div>
                </div>

                <!-- Opção Adesão -->
                <div onclick="selectProfile('adesao')" id="btn-adesao" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg text-purple-600 mr-3">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Por Profissão</h3>
                            <p class="text-xs text-gray-500">Estudante, Servidor, Formado...</p>
                        </div>
                    </div>
                    <!-- Campo Profissão (Escondido) -->
                    <div id="profissao-input" class="hidden mt-3 pt-3 border-t">
                        <input type="text" placeholder="Digite sua profissão (Ex: Engenheiro)" class="w-full text-sm p-2 border rounded bg-gray-50">
                    </div>
                </div>

                <!-- Opção CPF -->
                <div onclick="selectProfile('cpf')" id="btn-cpf" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all">
                    <div class="flex items-center">
                        <div class="p-2 bg-orange-100 rounded-lg text-orange-600 mr-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Para mim ou família (CPF)</h3>
                            <p class="text-xs text-gray-500">Contratação Individual</p>
                        </div>
                    </div>
                </div>
            </div>

            <button id="btn-step-2-next" onclick="nextStep(3)" class="w-full mt-6 bg-azul-royal text-white py-3 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition hidden">
                Continuar <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </div>

        <!-- PASSO 3: Grupo de Vidas (60%) -->
        <div id="step-3" class="step-content hidden p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-1 text-center">Quem fará parte do plano?</h2>
            <p class="text-xs text-gray-500 text-center mb-6">Adicione a quantidade de pessoas por idade.</p>

            <div class="space-y-4 mb-6">
                <!-- Faixa 0-18 -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="font-medium text-gray-700">0 a 18 anos</span>
                    <div class="flex items-center bg-white rounded-lg shadow-sm border">
                        <button onclick="updateLives('0-18', -1)" class="px-3 py-1 text-gray-400 hover:text-blue-600 font-bold">-</button>
                        <span id="count-0-18" class="w-8 text-center font-bold text-gray-800">0</span>
                        <button onclick="updateLives('0-18', 1)" class="px-3 py-1 text-blue-600 hover:text-blue-800 font-bold">+</button>
                    </div>
                </div>
                <!-- Faixa 19-23 -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="font-medium text-gray-700">19 a 23 anos</span>
                    <div class="flex items-center bg-white rounded-lg shadow-sm border">
                        <button onclick="updateLives('19-23', -1)" class="px-3 py-1 text-gray-400 hover:text-blue-600 font-bold">-</button>
                        <span id="count-19-23" class="w-8 text-center font-bold text-gray-800">0</span>
                        <button onclick="updateLives('19-23', 1)" class="px-3 py-1 text-blue-600 hover:text-blue-800 font-bold">+</button>
                    </div>
                </div>
                 <!-- Faixa 24-28 -->
                 <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="font-medium text-gray-700">24 a 58 anos</span>
                    <div class="flex items-center bg-white rounded-lg shadow-sm border">
                        <button onclick="updateLives('24-58', -1)" class="px-3 py-1 text-gray-400 hover:text-blue-600 font-bold">-</button>
                        <span id="count-24-58" class="w-8 text-center font-bold text-gray-800">0</span>
                        <button onclick="updateLives('24-58', 1)" class="px-3 py-1 text-blue-600 hover:text-blue-800 font-bold">+</button>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 p-3 rounded-lg flex justify-between items-center mb-4">
                <span class="text-sm font-semibold text-blue-800">Total de Vidas:</span>
                <span id="total-lives" class="text-xl font-bold text-blue-800">0</span>
            </div>

            <!-- Area de Alertas Dinâmicos -->
            <div id="validation-alert" class="hidden mb-4 p-3 bg-yellow-50 text-yellow-800 text-xs rounded border border-yellow-200">
                <i class="fas fa-exclamation-triangle mr-1"></i> <span id="alert-msg">Mensagem de alerta</span>
            </div>

            <button onclick="validateAndProceedStep3()" class="w-full bg-azul-royal text-white py-3 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition">
                Ver Resultados <i class="fas fa-search-dollar ml-2"></i>
            </button>
        </div>

        <!-- PASSO 4: Vitrine (80%) -->
        <div id="step-4" class="step-content hidden bg-gray-50 min-h-[600px]">
            <div class="sticky top-0 bg-white z-20 p-4 shadow-sm border-b">
                <h2 class="text-md font-bold text-gray-800">Melhores Opções para <span id="result-profile-name" class="text-blue-600">Seu Perfil</span></h2>
                <p class="text-xs text-gray-500">Ordenado por: Menor Investimento</p>
            </div>

            <div class="p-4 space-y-4 pb-24">
                <!-- Card 1 -->
                <div class="plan-card bg-white p-4 rounded-xl shadow-sm border border-gray-100 relative transition-all" onclick="togglePlanSelection(this)">
                    <div class="flex justify-between items-start mb-2">
                        <div class="bg-gray-200 h-8 w-20 rounded animate-pulse"></div> <!-- Logo Mock -->
                        <span class="text-[10px] font-bold bg-green-100 text-green-700 px-2 py-1 rounded">MATCH TÉCNICO</span>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg">Amil Fácil S80</h3>
                    <p class="text-xs text-gray-500 mb-3">Enfermaria | Regional</p>

                    <div class="flex gap-2 mb-3">
                         <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">C/ Copar</span>
                         <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">CHN</span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-dashed border-gray-200 flex justify-between items-end">
                        <div class="text-xs text-gray-400">Mensalidade:</div>
                        <div class="blur-price text-xl font-bold text-blue-600 bg-gray-100 px-2 rounded">R$ 450,00</div>
                    </div>

                    <!-- Checkbox visual -->
                    <div class="selection-check absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden">
                        <i class="fas fa-check-circle text-4xl text-blue-600 bg-white rounded-full"></i>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="plan-card bg-white p-4 rounded-xl shadow-sm border border-gray-100 relative transition-all" onclick="togglePlanSelection(this)">
                    <div class="flex justify-between items-start mb-2">
                        <div class="bg-gray-200 h-8 w-20 rounded animate-pulse"></div>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg">Bradesco Efetivo</h3>
                    <p class="text-xs text-gray-500 mb-3">Apartamento | Nacional</p>

                    <div class="flex gap-2 mb-3">
                         <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">Reembolso</span>
                         <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">H. Icaraí</span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-dashed border-gray-200 flex justify-between items-end">
                        <div class="text-xs text-gray-400">Mensalidade:</div>
                        <div class="blur-price text-xl font-bold text-blue-600 bg-gray-100 px-2 rounded">R$ 620,00</div>
                    </div>
                     <div class="selection-check absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden">
                        <i class="fas fa-check-circle text-4xl text-blue-600 bg-white rounded-full"></i>
                    </div>
                </div>
            </div>

            <!-- Sticky Footer -->
            <div class="absolute bottom-0 w-full bg-white border-t p-4 shadow-lg-up">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500"><span id="selected-count">0</span> planos selecionados</span>
                    <button onclick="nextStep(5)" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 text-sm">
                        LIBERAR PREÇOS <i class="fas fa-lock-open ml-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- PASSO 5: Captura (100%) -->
        <div id="step-5" class="step-content hidden p-8 text-center">
            <div class="mb-6">
                <div class="inline-block p-4 bg-green-100 rounded-full text-green-600 text-3xl mb-4">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Quase lá!</h2>
                <p class="text-gray-600 text-sm">Para onde enviamos o PDF oficial com a Rede Credenciada e os Preços desbloqueados?</p>
            </div>

            <div class="mb-6 text-left">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Seu WhatsApp</label>
                <input type="tel" id="whatsapp-input" placeholder="(21) 99999-9999" class="w-full p-3 border-2 border-green-200 rounded-lg focus:outline-none focus:border-green-500 text-lg">
                <p class="text-[10px] text-gray-400 mt-2"><i class="fas fa-lock"></i> Seus dados estão seguros. Não enviamos spam.</p>
            </div>

            <button onclick="finishProcess()" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold shadow-lg hover:bg-green-700 transition transform hover:-translate-y-1">
                REVELAR TABELA 2026
            </button>
        </div>

        <!-- Tela Final -->
        <div id="step-final" class="hidden p-8 text-center min-h-[400px] flex flex-col justify-center items-center">
             <i class="fas fa-check-circle text-6xl text-green-500 mb-4 animate-bounce"></i>
             <h2 class="text-2xl font-bold text-gray-800 mb-2">Sucesso!</h2>
             <p class="text-gray-600 mb-6">O Dossiê SaúdeSelect 2026 foi enviado para seu WhatsApp.</p>
             <div class="p-4 bg-blue-50 rounded-lg w-full mb-4">
                 <p class="text-sm text-blue-800 font-semibold">Gostou da experiência?</p>
                 <button class="mt-2 text-xs bg-white text-blue-600 border border-blue-200 px-3 py-1 rounded shadow-sm">Compartilhar com amigo</button>
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
            // Esconder passo atual
            document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));

            // Mostrar novo passo
            document.getElementById(`step-${step}`).classList.remove('hidden');

            // Atualizar barra
            const progress = step * 20;
            document.getElementById('progress-bar').style.width = `${progress}%`;
            document.getElementById('step-text').innerText = `Passo ${step} de 5`;

            // Lógica específica ao entrar no passo
            if (step === 4) renderResults();

            state.step = step;
            window.scrollTo(0, 0);
        }

        // --- PASSO 1: BUSCA ---
        const searchInput = document.getElementById('hospital-search');
        const listDiv = document.getElementById('autocomplete-list');

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
                            nextStep(2); // Avança automático
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
            const zap = document.getElementById('whatsapp-input').value;
            if (zap.length < 8) {
                alert("Digite um WhatsApp válido.");
                return;
            }

            // Loading fake
            const btn = document.querySelector('#step-5 button');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando PDF...';

            setTimeout(() => {
                document.getElementById('step-5').classList.add('hidden');
                document.getElementById('step-final').classList.remove('hidden');
                // Aqui você faria o POST para seu controller Laravel
            }, 1500);
        }
    </script>
</body>
</html>
