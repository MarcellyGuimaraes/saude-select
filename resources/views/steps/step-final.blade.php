
<!-- PASSO FINAL: Sucesso -->
<div id="step-final" class="step-content p-8 text-center animate-fade-in-up">
    
    <div class="mb-6">
        <div class="inline-block p-4 bg-green-100 rounded-full text-green-600 text-4xl mb-4 animate-scale-in">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Sucesso! 🚀</h2>
        <p class="text-gray-600 text-sm leading-relaxed max-w-md mx-auto">
            O <strong>Dossiê BuscarPlanos {{ date('Y') }}</strong> foi enviado para o seu WhatsApp com sucesso.
            <br><br>
            A equipe técnica já recebeu seu perfil e em breve validará sua elegibilidade para as tabelas selecionadas.
        </p>
    </div>

    <div class="space-y-4 max-w-sm mx-auto mb-8">
        <!-- PWA Button -->
        <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl">
            <p class="text-xs text-blue-800 font-bold mb-2">Deseja salvar a BuscarPlanos no seu celular?</p>
            <button onclick="installPWA()" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold text-sm hover:bg-blue-700 transition flex items-center justify-center gap-2">
                <i class="fas fa-mobile-alt"></i> ADICIONAR À TELA INICIAL
            </button>
        </div>

        <!-- Viralization Button -->
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl">
            <p class="text-xs text-green-800 font-bold mb-2 leading-tight">Este diagnóstico foi útil? Ajude um amigo a economizar em {{ date('Y') }} também! 🚀</p>
            <a href="https://wa.me/?text=Oi!%20Acabei%20de%20fazer%20um%20diagn%C3%B3stico%20de%20sa%C3%BAde%20na%20BuscarPlanos%20e%20vi%20os%20pre%C3%A7os%20para%20{{ date('Y') }}.%20Vale%20a%20pena,%20veja%20aqui:%20https://buscarplanos.com.br" 
               target="_blank"
               class="block w-full bg-green-500 text-white py-3 rounded-lg font-bold text-sm hover:bg-green-600 transition flex items-center justify-center gap-2">
                <i class="fab fa-whatsapp"></i> RECOMENDAR NO WHATSAPP
            </a>
        </div>
    </div>

    <!-- Legal Footer -->
    <div class="border-t border-gray-100 pt-6 mt-8">
        <div class="text-[10px] text-gray-400 leading-relaxed max-w-lg mx-auto text-justify">
            <p class="font-bold text-center mb-2">BuscarPlanos – Inteligência e Seleção de Benefícios</p>
            <p class="text-center mb-2">Uma plataforma de tecnologia e consultoria operada por: Renan Lima <br> Consultor Técnico SUSEP: 231152113 <br> Número de telefone: (21) 98012-7961</p>
            
            <p class="mb-2">
                As simulações apresentadas utilizam o motor de busca oficial vigente para {{ date('Y') }}. A rede credenciada e os valores podem sofrer alterações pelas operadoras sem aviso prévio. A formalização final dos planos é realizada via canais operacionais parceiros, sujeita à análise de risco e aceitação da seguradora.
            </p>
            
            <div class="flex justify-center gap-4 mt-4">
                <a href="#" class="text-gray-400 hover:text-blue-500 transition border-b border-dashed border-gray-300">Termos de Uso</a>
                <a href="#" onclick="openPrivacyModal(); return false;" class="text-gray-400 hover:text-blue-500 transition border-b border-dashed border-gray-300">Privacidade (LGPD)</a>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div id="privacy-modal" class="fixed inset-0 z-[120] hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closePrivacyModal()"></div>
        <div class="absolute inset-x-0 bottom-0 md:top-20 md:bottom-auto md:left-1/2 md:-translate-x-1/2 md:w-full md:max-w-lg bg-white rounded-t-3xl md:rounded-2xl shadow-2xl transform transition-all duration-300 translate-y-0 flex flex-col max-h-[85vh] overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Política de Privacidade (LGPD)</h3>
                    <p class="text-xs text-gray-500 mt-1">Como usamos sua localização para personalizar a experiência</p>
                </div>
                <button onclick="closePrivacyModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-5 overflow-y-auto text-justify">
                <p class="text-[11px] text-gray-600 leading-relaxed">
                    Para exibir planos regionais, podemos estimar sua <strong>cidade</strong> com base no seu <strong>endereço IP</strong> no primeiro acesso.
                    Essa estimativa é aproximada e não depende de permissão de GPS do navegador.
                </p>

                <p class="text-[11px] text-gray-600 leading-relaxed mt-3">
                    Se você optar por usar sua localização com <strong>GPS</strong>, o navegador pode solicitar acesso à localização precisa.
                    Nesses casos, a cidade é calculada a partir de coordenadas fornecidas pelo dispositivo.
                </p>

                <p class="text-[11px] text-gray-600 leading-relaxed mt-3">
                    Para realizar a estimativa/consulta geográfica, podemos utilizar serviços de terceiros, como:
                    <strong>ipapi.co</strong> (estimativa por IP) e <strong>Nominatim (OpenStreetMap)</strong> (geocodificação reversa).
                    Esses serviços recebem dados necessários para processar a localização (como IP e/ou coordenadas).
                </p>

                <p class="text-[11px] text-gray-600 leading-relaxed mt-3">
                    Você pode continuar usando o site mesmo sem compartilhar localização com GPS. Para dúvidas e solicitações relacionadas aos seus dados, você pode entrar em contato com nosso suporte.
                </p>
            </div>

            <div class="bg-gray-50 p-4 flex justify-end">
                <button onclick="closePrivacyModal()" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 transition text-sm">
                    Entendi
                </button>
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

    function openPrivacyModal() {
        const modal = document.getElementById('privacy-modal');
        if (!modal) return;

        modal.classList.remove('hidden');
    }

    function closePrivacyModal() {
        const modal = document.getElementById('privacy-modal');
        if (!modal) return;

        modal.classList.add('hidden');
    }

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closePrivacyModal();
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
