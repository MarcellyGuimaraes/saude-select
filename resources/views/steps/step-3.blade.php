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
    <h2 class="text-lg font-bold text-gray-800 mb-1 text-center">Quem fará parte do plano?</h2>
    <p class="text-xs text-gray-500 text-center mb-4">Adicione a quantidade de pessoas por faixa etária (ANS).</p>

    <div class="space-y-3 mb-4 max-h-[320px] overflow-y-auto pr-1">
        @foreach ($faixas as $f)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <span class="font-medium text-gray-700 text-sm">{{ $f['label'] }}</span>
            <div class="flex items-center bg-white rounded-lg shadow-sm border">
                <button type="button" onclick="updateLives('{{ $f['key'] }}', -1)" class="px-3 py-1 text-gray-400 hover:text-blue-600 font-bold">-</button>
                <span id="count-{{ $f['key'] }}" class="w-8 text-center font-bold text-gray-800">0</span>
                <button type="button" onclick="updateLives('{{ $f['key'] }}', 1)" class="px-3 py-1 text-blue-600 hover:text-blue-800 font-bold">+</button>
            </div>
        </div>
        @endforeach
    </div>

    <div class="bg-blue-50 p-3 rounded-lg flex justify-between items-center mb-4">
        <span class="text-sm font-semibold text-blue-800">Total de Vidas:</span>
        <span id="total-lives" class="text-xl font-bold text-blue-800">0</span>
    </div>

    <div id="validation-alert" class="hidden mb-4 p-3 bg-yellow-50 text-yellow-800 text-xs rounded border border-yellow-200">
        <i class="fas fa-exclamation-triangle mr-1"></i> <span id="alert-msg">Mensagem de alerta</span>
    </div>

    <button type="button" onclick="validateAndProceedStep3()" class="w-full bg-azul-royal text-white py-3 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition">
        Ver Resultados <i class="fas fa-search-dollar ml-2"></i>
    </button>
</div>
