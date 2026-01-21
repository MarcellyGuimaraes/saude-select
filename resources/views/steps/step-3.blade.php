<!-- PASSO 3: Grupo de Vidas (60%) -->
<div id="step-3" class="step-content p-6">
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
