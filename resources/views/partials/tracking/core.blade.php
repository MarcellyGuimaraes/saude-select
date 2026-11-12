{{-- Núcleo do rastreamento: window.fsTrack(evento, props). Nunca lança erro. --}}
<script>
(function () {
    var UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
    var IDLE_MS = 30 * 60 * 1000;

    function lsGet(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } }
    function lsSet(k, v) { try { window.localStorage.setItem(k, v); } catch (e) {} }

    var memSid = null;
    function uid() {
        try { if (window.crypto && crypto.randomUUID) return crypto.randomUUID(); } catch (e) {}
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 3 | 8)).toString(16);
        });
    }

    // session_id compartilhado entre presell e quiz (mesma origem); renova após 30 min parado.
    function sessionId() {
        var now = Date.now();
        var sid = lsGet('fs_sid') || memSid;
        var last = parseInt(lsGet('fs_sid_ts'), 10) || 0;
        if (!sid || (last && now - last > IDLE_MS)) { sid = uid(); }
        memSid = sid;
        lsSet('fs_sid', sid);
        lsSet('fs_sid_ts', String(now));
        return sid;
    }

    // UTMs: o último toque com UTM na URL vence; sem UTM na URL, reaproveita o guardado.
    function utms() {
        var found = {}, any = false;
        try {
            var p = new URLSearchParams(window.location.search);
            UTM_KEYS.forEach(function (k) { if (p.get(k)) { found[k] = p.get(k); any = true; } });
        } catch (e) {}
        if (any) { lsSet('fs_utm', JSON.stringify(found)); return found; }
        try { return JSON.parse(lsGet('fs_utm') || '{}') || {}; } catch (e) { return {}; }
    }

    window.fsTrack = function (name, props) {
        try {
            props = props || {};
            var sid = sessionId();
            var utm = utms();

            // 1) GTM / dataLayer
            window.dataLayer = window.dataLayer || [];
            var push = { event: name, fs_session_id: sid, fs_page: window.location.pathname };
            Object.keys(utm).forEach(function (k) { push[k] = utm[k]; });
            Object.keys(props).forEach(function (k) { push[k] = props[k]; });
            window.dataLayer.push(push);

            // 2) Dashboard interno
            var meta = document.querySelector('meta[name="csrf-token"]');
            fetch('/api/track', {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': meta ? meta.getAttribute('content') : ''
                },
                body: JSON.stringify({
                    event: name,
                    session_id: sid,
                    page: window.location.pathname,
                    step: props.step || null,
                    utm: utm,
                    props: props
                })
            }).catch(function () {});
        } catch (e) { /* rastreamento nunca pode quebrar a página */ }
    };
})();
</script>
