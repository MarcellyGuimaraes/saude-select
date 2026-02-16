
<!-- PASSO FINAL: Sucesso -->
<div id="step-final" class="step-content p-8 text-center animate-fade-in-up">
    
    <div class="mb-6">
        <div class="inline-block p-4 bg-green-100 rounded-full text-green-600 text-4xl mb-4 animate-scale-in">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Sucesso! 🚀</h2>
        <p class="text-gray-600 text-sm leading-relaxed max-w-md mx-auto">
            O <strong>Dossiê SaúdeSelect 2026</strong> foi enviado para o seu WhatsApp com sucesso.
            <br><br>
            A equipe técnica já recebeu seu perfil e em breve validará sua elegibilidade para as tabelas selecionadas.
        </p>
    </div>

    <div class="space-y-4 max-w-sm mx-auto mb-8">
        <!-- PWA Button -->
        <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl">
            <p class="text-xs text-blue-800 font-bold mb-2">Deseja salvar a SaúdeSelect no seu celular?</p>
            <button onclick="installPWA()" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold text-sm hover:bg-blue-700 transition flex items-center justify-center gap-2">
                <i class="fas fa-mobile-alt"></i> ADICIONAR À TELA INICIAL
            </button>
        </div>

        <!-- Viralization Button -->
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl">
            <p class="text-xs text-green-800 font-bold mb-2 leading-tight">Este diagnóstico foi útil? Ajude um amigo de <span id="city-name-viral"></span> a economizar em 2026 também! 🚀</p>
            <a href="https://wa.me/?text=Oi!%20Acabei%20de%20fazer%20um%20diagn%C3%B3stico%20de%20sa%C3%BAde%20na%20Sa%C3%BAdeSelect%20e%20vi%20os%20pre%C3%A7os%20para%202026.%20Vale%20a%20pena,%20veja%20aqui:%20https://saudeselect.com.br" 
               target="_blank"
               class="block w-full bg-green-500 text-white py-3 rounded-lg font-bold text-sm hover:bg-green-600 transition flex items-center justify-center gap-2">
                <i class="fab fa-whatsapp"></i> RECOMENDAR NO WHATSAPP
            </a>
        </div>
    </div>

    <!-- Legal Footer -->
    <div class="border-t border-gray-100 pt-6 mt-8">
        <div class="text-[10px] text-gray-400 leading-relaxed max-w-lg mx-auto text-justify">
            <p class="font-bold text-center mb-2">SaúdeSelect – Inteligência e Seleção de Benefícios</p>
            <p class="text-center mb-2">Uma plataforma de tecnologia e consultoria operada por: Renan Lima <br> Consultor Técnico SUSEP: (21) 98012-7961</p>
            
            <p class="mb-2">
                As simulações apresentadas utilizam o motor de busca oficial vigente para 2026. A rede credenciada e os valores podem sofrer alterações pelas operadoras sem aviso prévio. A formalização final dos planos é realizada via canais operacionais parceiros, sujeita à análise de risco e aceitação da seguradora.
            </p>
            
            <div class="flex justify-center gap-4 mt-4">
                <a href="#" class="text-gray-400 hover:text-blue-500 transition border-b border-dashed border-gray-300">Termos de Uso</a>
                <a href="#" class="text-gray-400 hover:text-blue-500 transition border-b border-dashed border-gray-300">Privacidade (LGPD)</a>
            </div>
        </div>
    </div>

</div>

<script>
    // Simple PWA Install Prompt Logic (Mocked)
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
    });

    function installPWA() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the A2HS prompt');
                }
                deferredPrompt = null;
            });
        } else {
            alert('Para instalar, use a opção "Adicionar à Tela Inicial" do seu navegador.');
        }
    }
</script>
