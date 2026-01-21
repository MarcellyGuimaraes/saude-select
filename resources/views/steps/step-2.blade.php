<!-- PASSO 2: Perfil Jurídico (40%) -->
<div id="step-2" class="step-content p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Como você prefere contratar?</h2>

    <div class="space-y-3">
        <!-- Opção PME -->
        <div onclick="selectProfile('pme')" id="btn-pme" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all group relative overflow-hidden">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg text-blue-600 mr-3">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800">Sou Empresa ou MEI</h3>
                    <p class="text-xs text-green-600 font-semibold">Economia de até 40%</p>
                </div>
            </div>
            <div class="absolute right-0 top-0 bg-green-100 text-green-800 text-[10px] px-2 py-1 rounded-bl-lg">
                Mais Barato
            </div>
        </div>

        <!-- Opção Adesão -->
        <div onclick="selectProfile('adesao')" id="btn-adesao" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg text-purple-600 mr-3">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800">Por Profissão</h3>
                    <p class="text-xs text-gray-500">Estudante, Servidor, Formado...</p>
                </div>
            </div>
            <!-- Campo Profissão (Escondido) -->
            <div id="profissao-input" class="hidden mt-3 pt-3 border-t">
                <input type="text" placeholder="Digite sua profissão (Ex: Engenheiro)" class="w-full text-sm p-2 border rounded bg-gray-50">
            </div>
        </div>

        <!-- Opção CPF -->
        <div onclick="selectProfile('cpf')" id="btn-cpf" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg text-orange-600 mr-3">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800">Para mim ou família (CPF)</h3>
                    <p class="text-xs text-gray-500">Contratação Individual</p>
                </div>
            </div>
        </div>
    </div>

    <button id="btn-step-2-next" onclick="nextStep(3)" class="w-full mt-6 bg-azul-royal text-white py-3 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition hidden">
        Continuar <i class="fas fa-arrow-right ml-2"></i>
    </button>
</div>
