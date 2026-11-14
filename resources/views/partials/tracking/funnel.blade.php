{{-- Eventos do quiz. Só ENVOLVE as funções existentes (não altera a lógica do funil)
     e observa o #step-container. Incluir DEPOIS dos scripts principais do index. --}}
<script>
(function () {
    if (typeof window.fsTrack !== 'function') return;
    var T = window.fsTrack;

    var STEP_NAMES = { 1: 'nome', 2: 'perfil', 3: 'vidas', 4: 'planos', 5: 'whatsapp', 6: 'sucesso' };

    function ctx() {
        try {
            return {
                profile: state.profile || null,
                state_name: state.stateName || null,
                city: state.city || null,
                total_lives: state.totalLives || 0,
                plans_selected: (state.selectedPlans || []).length
            };
        } catch (e) { return {}; }
    }
    function merge(a, b) { var o = {}; var k; for (k in a) o[k] = a[k]; for (k in b) o[k] = b[k]; return o; }

    // Envolve uma função global: before roda antes, after roda depois (ou quando a promise resolve).
    function wrap(name, before, after) {
        var orig = window[name];
        if (typeof orig !== 'function') return;
        window[name] = function () {
            var args = arguments;
            try { if (before) before.apply(null, args); } catch (e) {}
            var result = orig.apply(this, args);
            try {
                if (after) {
                    if (result && typeof result.then === 'function') {
                        result.then(function () { try { after.apply(null, args); } catch (e) {} }, function () {});
                    } else {
                        after.apply(null, args);
                    }
                }
            } catch (e) {}
            return result;
        };
    }

    // --- Abertura do quiz ---
    document.addEventListener('DOMContentLoaded', function () { T('quiz_start', {}); });

    // --- Visualização de cada passo (observa a troca do conteúdo) ---
    var container = document.getElementById('step-container');
    if (container && window.MutationObserver) {
        var lastId = null;
        new MutationObserver(function () {
            var el = container.querySelector('#step-1, #step-2, #step-3, #step-4, #step-5, #step-final');
            if (!el) { lastId = null; return; }
            if (el.id === lastId) return;
            lastId = el.id;

            var n = el.id === 'step-final' ? 6 : parseInt(el.id.replace('step-', ''), 10);
            T('quiz_step_view', merge(ctx(), { step: n, step_name: STEP_NAMES[n] }));

            if (n === 6) {
                // Conversão: lead gerado (proposta enviada ao WhatsApp do visitante)
                T('generate_lead', merge(ctx(), { step: 6, lead_source: 'quiz_saude' }));
            }
        }).observe(container, { childList: true });
    }

    // --- Localização resolvida ---
    var lastCity = null;
    wrap('updateLocationUI', null, function (city, stateName) {
        if (!city || city === lastCity) return;
        lastCity = city;
        T('quiz_location_resolved', { city: city, state_name: stateName || null });
    });

    // --- Perfil escolhido (CPF / PME / adesão) ---
    wrap('selectProfile', null, function (profile) {
        T('quiz_profile_selected', merge(ctx(), { step: 2, profile: profile }));
    });

    // --- Vidas definidas (clique em continuar do passo 3, com pelo menos 1 vida) ---
    wrap('validateAndProceedStep3', function () {
        if (state.totalLives > 0) T('quiz_lives_defined', merge(ctx(), { step: 3 }));
    });

    // --- Planos carregados ---
    wrap('buscarPlanosAPI', null, function () {
        var n = (state.planos || []).length;
        T('quiz_plans_loaded', merge(ctx(), { step: 4, plans_count: n }));
    });

    // --- Seleção de planos ---
    wrap('togglePlanSelection', null, function (card, id) {
        var selected = state.selectedPlans.indexOf(id) > -1;
        T(selected ? 'quiz_plan_selected' : 'quiz_plan_deselected', merge(ctx(), { step: 4, plan_id: id }));
    });

    // --- Pedido de proposta (clique em continuar do passo 4, com plano marcado) ---
    wrap('validateAndProceedStep4', function () {
        if (state.selectedPlans.length > 0) T('quiz_proposal_requested', merge(ctx(), { step: 4 }));
    });

    // --- Envio do WhatsApp (tentativa válida; o número NUNCA é enviado) ---
    wrap('finishProcess', function () {
        var input = document.getElementById('whatsapp-input');
        var digits = input ? input.value.replace(/\D/g, '') : '';
        if (digits.length >= 10) T('quiz_whatsapp_submit', merge(ctx(), { step: 5 }));
    });

    // --- Erros mostrados ao visitante ---
    wrap('showToast', function (message, type) {
        if (type === 'error') {
            T('quiz_error', merge(ctx(), { step: state.step, message: String(message || '').slice(0, 120) }));
        }
    });
})();
</script>
