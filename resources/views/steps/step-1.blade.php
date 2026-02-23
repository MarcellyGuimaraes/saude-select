<!-- PASSO 1: Nome do Usuário (20%) -->
<div id="step-1" class="step-content p-6">
    <div class="text-center mb-8 mt-2">
        <h2 class="text-lg font-bold text-gray-800 leading-snug">
            💡 Para quem devemos preparar o Diagnóstico Inteligente?
        </h2>
    </div>

    <!-- Campo do Nome Real (Corrigido para não usar DIV simulando input) -->
    <div class="relative mb-8">
        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
            <div class="p-1.5 bg-blue-50 rounded text-blue-600 border border-blue-100 flex items-center justify-center">
                <i class="fas fa-user"></i>
            </div>
        </div>
        <input type="text" id="user-name"
            class="block w-full py-4 pl-14 pr-4 text-base font-bold text-gray-900 border-2 border-blue-600 rounded-xl bg-gray-50 focus:bg-white focus:ring-0 focus:border-blue-700 transition-all shadow-sm"
            placeholder="Ex: Ana Silva" autocomplete="name"
            onkeypress="if(event.key === 'Enter') nextStep(2)">
    </div>

    <!-- Regra de Ouro / Confiança -->
    <div class="mb-6 bg-gray-50 p-3 rounded-lg border border-gray-200">
        <p class="text-[10px] text-gray-600 font-medium text-center">
            <i class="fas fa-shield-alt text-gray-400 mr-1 hidden"></i> 🛡️ Identidade Protegida e Criptografada
        </p>
    </div>

    <!-- Botão de Ação -->
    <button onclick="nextStep(2)" class="w-full bg-azul-royal text-white py-4 rounded-xl font-black text-lg shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition uppercase tracking-wide flex justify-center items-center">
        GERAR MEU ACESSO PERSONALIZADO
    </button>
    
    <!-- Regra de Ouro (Premium/Footer) -->
    <div class="mt-8 text-center">
        <p class="text-[10px] text-gray-500 italic bg-white inline-block px-3 rounded-full shadow-sm border border-gray-100">
            <strong>✨ Nota Técnica:</strong> O diagnóstico incluirá as regras de valores para adesões em {{ date('Y') }}.
        </p>
    </div>
</div>
