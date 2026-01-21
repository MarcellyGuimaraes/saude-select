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
