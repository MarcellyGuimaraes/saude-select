
<!-- PASSO 5: Captura (100%) -->
<div id="step-5" class="step-content p-8 text-center">
    <div class="mb-6">
        <div class="inline-block p-4 bg-green-100 rounded-full text-green-600 text-3xl mb-4 animate-bounce">
            <i class="fab fa-whatsapp"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">🏆 Seu Dossiê está pronto! (100%)</h2>
        <p class="text-gray-600 text-sm leading-relaxed max-w-md mx-auto">
            Para onde enviamos seu <strong>PDF oficial com Preços e Rede de <span id="dynamic-city-step5">sua cidade</span></strong>? <br>
            Informe seu WhatsApp para revelar os valores dos planos selecionados.
        </p>
    </div>

    <div id="selected-plans-summary" class="mb-6 space-y-2 max-w-sm mx-auto">
        <!-- Selecionados serão injetados aqui via JS -->
    </div>

    <div class="mb-6 text-left max-w-sm mx-auto">
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">📱 Digite seu WhatsApp</label>
        <input type="tel" id="whatsapp-input" placeholder="(21) 99999-9999" class="w-full p-4 border-2 border-green-200 rounded-xl focus:outline-none focus:border-green-500 text-lg shadow-sm transition-all focus:shadow-md">
        
        <p class="text-[10px] text-gray-400 mt-3 text-center leading-tight">
            ⚖️ Ao clicar, você concorda com os <u>Termos de Uso</u> e autoriza o envio do Dossiê SaúdeSelect {{ date('Y') }} via WhatsApp.
        </p>
    </div>

    <button onclick="finishProcess()" class="w-full max-w-sm bg-green-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:bg-green-700 transition transform hover:-translate-y-1 hover:shadow-xl flex items-center justify-center gap-2">
        🚀 REVELAR TABELA AGORA
    </button>
</div>
