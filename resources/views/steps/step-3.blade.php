@php
    $faixas = [
        ['key' => '0-18', 'label' => '0 a 18 anos'],
        ['key' => '19-23', 'label' => '19 a 23 anos'],
        ['key' => '24-28', 'label' => '24 a 28 anos'],
        ['key' => '29-33', 'label' => '29 a 33 anos'],
        ['key' => '34-38', 'label' => '34 a 38 anos'],
        ['key' => '39-43', 'label' => '39 a 43 anos'],
        ['key' => '44-48', 'label' => '44 a 48 anos'],
        ['key' => '49-53', 'label' => '49 a 53 anos'],
        ['key' => '54-58', 'label' => '54 a 58 anos'],
        ['key' => '59+', 'label' => '59 anos ou mais'],
    ];
@endphp
<!-- PASSO 3: Grupo de Vidas (60%) -->
<div id="step-3" class="step-content p-6">
    <div class="text-center mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-2">💡 Quem fará parte do plano?</h2>
        <p class="text-xs text-gray-500">Adicione a quantidade de pessoas por idade nos botões [ + ] e [ - ].</p>
    </div>

    <div class="space-y-2 mb-6 max-h-[320px] overflow-y-auto pr-1">
        @foreach ($faixas as $f)
        <div id="row-{{ $f['key'] }}" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-transparent transition-all duration-300">
            <span class="font-medium text-gray-700 text-sm">{{ $f['label'] }}</span>
            <div class="flex items-center bg-white rounded-lg shadow-sm border border-gray-200">
                <button type="button" onclick="updateLives('{{ $f['key'] }}', -1)" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 font-bold transition rounded-l-lg hover:bg-gray-50">-</button>
                <span id="count-{{ $f['key'] }}" class="w-8 text-center font-bold text-gray-800 text-sm">0</span>
                <button type="button" onclick="updateLives('{{ $f['key'] }}', 1)" class="w-8 h-8 flex items-center justify-center text-blue-600 hover:text-blue-800 font-bold transition rounded-r-lg hover:bg-blue-50">+</button>
            </div>
        </div>
        @endforeach
    </div>

    <div class="bg-blue-50 p-4 rounded-xl flex justify-between items-center mb-6 border border-blue-100">
        <span class="text-sm font-semibold text-blue-800"><i class="fas fa-users mr-2"></i>Total:</span>
        <span class="text-lg font-bold text-blue-800"><span id="total-lives">0</span> vidas selecionadas</span>
    </div>

    <div id="validation-alert" class="hidden mb-4 p-3 bg-yellow-50 text-yellow-800 text-semismall rounded border border-yellow-200">
        <i class="fas fa-exclamation-triangle mr-1"></i> <span id="alert-msg">Mensagem de alerta</span>
    </div>

    <button type="button" onclick="validateAndProceedStep3()" class="w-full bg-azul-royal text-white py-4 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition text-lg shadow-blue-500/30">
        Ver Resultados <i class="fas fa-search-dollar ml-2"></i>
    </button>
</div>
