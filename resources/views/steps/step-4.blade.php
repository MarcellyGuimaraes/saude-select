<!-- PASSO 4: Vitrine (80%) -->
<div id="step-4" class="step-content bg-gray-50 min-h-[600px]">
    <div class="sticky top-0 bg-white z-20 p-4 shadow-sm border-b">
        <h2 class="text-md font-bold text-gray-800">Melhores Opções para <span id="result-profile-name" class="text-blue-600">Seu Perfil</span></h2>
        <!-- <p class="text-xs text-gray-500">Ordenado por: Menor Investimento</p> -->
    </div>

    <div class="p-4 space-y-4 pb-24">
        <!-- Cards mockados temporariamente -->
        <div class="plan-card bg-white p-4 rounded-xl shadow-sm border border-gray-100 relative transition-all" onclick="togglePlanSelection(this)">
            <div class="flex justify-between items-start mb-2">
                <div class="bg-gray-200 h-8 w-20 rounded animate-pulse"></div>
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
            <div class="selection-check absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden">
                <i class="fas fa-check-circle text-4xl text-blue-600 bg-white rounded-full"></i>
            </div>
        </div>
    </div>

    <!-- Sticky Footer -->
    <div class="absolute bottom-0 w-full bg-white border-t p-4 shadow-lg-up">
        <div class="flex justify-between items-center">
            <span class="text-xs text-gray-500"><span id="selected-count">0</span> planos selecionados</span>
            <button onclick="validateAndProceedStep4()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 text-sm">
                LIBERAR PREÇOS <i class="fas fa-lock-open ml-1"></i>
            </button>
        </div>
        <div id="step-4-validation-alert" class="hidden mt-2 p-2 bg-yellow-50 text-yellow-800 text-[10px] rounded border border-yellow-200">
            <i class="fas fa-exclamation-triangle mr-1"></i> <span id="step-4-alert-msg">Por favor, selecione pelo menos um plano.</span>
        </div>
    </div>
</div>
