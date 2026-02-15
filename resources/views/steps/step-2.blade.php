<!-- PASSO 2: Perfil Jurídico (40%) -->
<div id="step-2" class="step-content p-6">
    <div class="text-center mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-2">💡 Como você prefere contratar para liberar sua tabela oficial 2026?</h2>
    </div>

    <div class="space-y-3">
        <!-- Opção PME -->
        <div onclick="selectProfile('pme')" id="btn-pme" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all group relative overflow-hidden bg-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg text-blue-600 mr-3">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Sou Empresa ou MEI</h3>
                        <p class="text-xs text-green-600 font-bold bg-green-50 px-2 py-0.5 rounded inline-block mt-1">Economia até 40%</p>
                    </div>
                </div>
                <div class="w-4 h-4 rounded-full border border-gray-300 flex items-center justify-center selected-indicator">
                    <div class="w-2 h-2 rounded-full bg-white hidden"></div>
                </div>
            </div>
            
            <!-- Aviso PME (Escondido) -->
            <div id="pme-warning" class="hidden mt-3 pt-3 border-t border-dashed border-yellow-200">
                <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-100 text-xs text-yellow-800 leading-relaxed">
                    <strong>⚠️ Aviso de Aceitação 2026:</strong> Para garantir o desconto desta tabela, seu CNPJ ou MEI deve ter no mínimo 6 meses de abertura. Caso sua empresa seja mais recente, selecione a opção CPF.
                </div>
            </div>
        </div>

        <!-- Opção Adesão -->
        <div onclick="selectProfile('adesao')" id="btn-adesao" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all bg-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg text-purple-600 mr-3">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Sou Formado, Estudante ou Servidor</h3>
                        <p class="text-xs text-purple-600 font-bold bg-purple-50 px-2 py-0.5 rounded inline-block mt-1">Tabelas por Profissão</p>
                    </div>
                </div>
                <div class="w-4 h-4 rounded-full border border-gray-300 flex items-center justify-center selected-indicator">
                    <div class="w-2 h-2 rounded-full bg-white hidden"></div>
                </div>
            </div>
            
            <!-- Campo Profissão (Escondido) -->
            <div id="profissao-input" class="hidden mt-3 pt-3 border-t border-gray-100 relative">
                <label class="text-xs text-gray-500 font-semibold mb-1 block">Digite sua profissão:</label>
                <div class="relative">
                    <input type="text" id="prof-search"
                        oninput="debounceProfissao(this.value)"
                        placeholder="Ex: Engenheiro, Médico, Advogado..." 
                        class="w-full text-sm p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-gray-50">
                    <div id="prof-loading" class="absolute right-3 top-3 hidden">
                        <i class="fas fa-spinner fa-spin text-blue-500"></i>
                    </div>
                </div>
                <!-- Dropdown de Sugestões -->
                <div id="prof-suggestions" class="hidden absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-40 overflow-y-auto"></div>
            </div>
        </div>

        <!-- Opção CPF -->
        <div onclick="selectProfile('cpf')" id="btn-cpf" class="profile-option p-4 border rounded-xl cursor-pointer hover:border-blue-500 transition-all bg-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-orange-100 rounded-lg text-orange-600 mr-3">
                        <i class="fas fa-user mb-1"></i><i class="fas fa-users text-[8px] -ml-1"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Para mim ou minha família (CPF)</h3>
                        <p class="text-xs text-gray-500">Contratação Individual/Familiar</p>
                    </div>
                </div>
                <div class="w-4 h-4 rounded-full border border-gray-300 flex items-center justify-center selected-indicator">
                    <div class="w-2 h-2 rounded-full bg-white hidden"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Regra de Ouro -->
    <div class="mt-8 bg-blue-50 p-4 rounded-xl border border-blue-100">
        <p class="text-[10px] text-blue-800 leading-relaxed text-center">
            <strong>✨ Regra de Ouro (Versão Premium):</strong> Para garantir precisão total, nossa inteligência calcula preços e regras de rede em tempo real. Cada perfil acima possui benefícios exclusivos para 2026. Para comparar caminhos diferentes, basta realizar uma nova consulta após receber seu PDF.
        </p>
    </div>

    <button id="btn-step-2-next" onclick="nextStep(3)" class="w-full mt-6 bg-azul-royal text-white py-4 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition hidden text-lg shadow-blue-500/30">
        Continuar <i class="fas fa-arrow-right ml-2"></i>
    </button>
</div>
