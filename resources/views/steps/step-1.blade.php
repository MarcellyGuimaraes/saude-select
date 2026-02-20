<!-- PASSO 1: Busca de Hospitais (20%) -->
<div id="step-1" class="step-content p-6">
    <div class="text-center mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Qual o seu nome?</h2>
        <p class="text-sm text-gray-600 bg-blue-50 p-2 rounded-lg border border-blue-100 inline-block">
            👋 Para começarmos, como podemos te chamar?
        </p>
    </div>

    <div class="relative mb-6">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <i class="fas fa-user text-gray-400"></i>
        </div>
        <input type="text" id="user-name"
            class="block w-full p-4 pl-10 pr-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            placeholder="Ex: João Silva" autocomplete="name">
    </div>

    <button onclick="nextStep(2)" class="w-full py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-md">
        Continuar
    </button>
</div>
