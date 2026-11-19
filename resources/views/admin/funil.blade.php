<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Funil por etapas | BuscarPlanos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #F6F3EC; --card: #FFFDF9; --ink: #10233F; --muted: #5A6B82; --line: #DDD6C8;
            --brand: #1B66D1; --brand-deep: #154FA3; --brand-soft: #8FB4EC;
            --mint: #E1F2EA; --mint-ink: #1B6A4D; --green: #1F9D6B;
            --amber-bg: #FFF1D6; --amber-ink: #8A4B00; --amber: #E8890C;
            --serif: 'Fraunces', Georgia, serif; --sans: 'Instrument Sans', system-ui, sans-serif;
            --ease: cubic-bezier(.16, 1, .3, 1);
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font: 400 1rem/1.5 var(--sans); -webkit-font-smoothing: antialiased; }
        .wrap { width: min(920px, 100% - 2rem); margin: 0 auto; padding: 1.5rem 0 3rem; }
        h1, h2 { font-family: var(--serif); font-weight: 600; margin: 0; letter-spacing: -.015em; }
        h1 { font-size: clamp(1.6rem, 5vw, 2.1rem); line-height: 1.1; }
        .num { font-variant-numeric: tabular-nums; }

        /* ---------- topo e filtro de datas ---------- */
        .top { display: flex; flex-wrap: wrap; gap: 1rem 1.5rem; align-items: flex-end; justify-content: space-between; margin-bottom: 1.4rem; }
        .sub { margin: .35rem 0 0; font-size: .9rem; color: var(--muted); }
        .range { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
        .seg { display: inline-flex; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 3px; }
        .seg a { padding: .45rem .95rem; border-radius: 999px; color: var(--muted); font-size: .9rem; font-weight: 500; text-decoration: none; min-height: 36px; display: inline-flex; align-items: center; transition: background .2s, color .2s; }
        .seg a:hover { color: var(--ink); }
        .seg a.on { background: var(--ink); color: #fff; }
        .custom { display: inline-flex; flex-wrap: wrap; gap: .5rem; align-items: center; padding: 3px 3px 3px .9rem; background: var(--card); border: 1px solid var(--line); border-radius: 999px; font-size: .85rem; color: var(--muted); }
        .custom.on { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(27,102,209,.12); }
        .custom label { display: inline-flex; gap: .35rem; align-items: center; }
        .custom input[type=date] { font: inherit; color: var(--ink); border: 0; background: transparent; padding: .3rem .1rem; min-height: 32px; }
        .custom button { font: 600 .85rem var(--sans); border: 0; border-radius: 999px; background: var(--brand); color: #fff; padding: .45rem 1rem; min-height: 36px; cursor: pointer; }
        .custom button:hover { background: var(--brand-deep); }
        :focus-visible { outline: 3px solid var(--brand); outline-offset: 2px; border-radius: 8px; }

        /* ---------- metricas ---------- */
        .cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; margin-bottom: 1.25rem; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 1rem 1.1rem; }
        .card .label { font-size: .8rem; color: var(--muted); }
        .card .value { font: 600 clamp(1.7rem, 5vw, 2.2rem)/1.1 var(--serif); margin-top: .3rem; font-variant-numeric: tabular-nums; }
        .card .hint { font-size: .75rem; color: var(--muted); margin-top: .2rem; }
        .card.conv { background: var(--mint); border-color: #BFE3D2; }
        .card.conv .value { color: var(--mint-ink); }

        /* ---------- funil ---------- */
        .panel { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 1.25rem 1.1rem 1.4rem; }
        .panel-head { display: flex; flex-wrap: wrap; gap: .4rem 1rem; justify-content: space-between; align-items: baseline; margin-bottom: 1rem; }
        .panel-head h2 { font-size: 1.25rem; }
        .insight { font-size: .85rem; color: var(--amber-ink); background: var(--amber-bg); border-radius: 10px; padding: .5rem .75rem; margin: 0 0 1rem; line-height: 1.4; }
        .insight strong { font-weight: 600; }
        .empty { font-size: .9rem; color: var(--muted); background: var(--paper); border-radius: 10px; padding: .6rem .8rem; margin: 0 0 1rem; }

        .funnel { list-style: none; margin: 0; padding: 0; }
        .group { font-size: .7rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin: 1.1rem 0 .5rem; display: flex; align-items: center; gap: .6rem; }
        .group:first-child { margin-top: 0; }
        .group::after { content: ""; flex: 1; height: 1px; background: var(--line); }

        .stage { padding: .15rem 0; }
        .stage .head { display: flex; align-items: baseline; gap: .6rem; }
        .stage .idx { flex: none; width: 22px; height: 22px; border-radius: 50%; background: var(--paper); border: 1px solid var(--line); color: var(--muted); font-size: .72rem; font-weight: 600; display: grid; place-items: center; align-self: center; }
        .stage .name { flex: 1; font-weight: 500; font-size: .95rem; }
        .stage .count { font: 600 1.25rem/1 var(--serif); font-variant-numeric: tabular-nums; }
        .bar { height: 14px; background: var(--paper); border-radius: 999px; overflow: hidden; margin: .45rem 0 .3rem 30px; }
        .bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--brand-soft), var(--brand)); transform-origin: left; animation: grow .9s var(--ease) both; animation-delay: calc(var(--i) * 70ms); }
        .stage.pre .bar span { background: linear-gradient(90deg, #BBD1F3, var(--brand-soft)); }
        .stage.lead .bar span { background: linear-gradient(90deg, #6FD1A6, var(--green)); }
        .stage.lead .idx { background: var(--green); border-color: var(--green); color: #fff; }
        .stage.lead .count { color: var(--mint-ink); }
        .stage .meta { margin-left: 30px; font-size: .78rem; color: var(--muted); }
        .stage .meta b { color: var(--ink); font-weight: 600; }

        .link { margin: .25rem 0 .25rem 8px; padding-left: 22px; border-left: 2px dashed var(--line); min-height: 28px; display: flex; align-items: center; }
        .chip { display: inline-flex; align-items: center; gap: .35rem; font-size: .76rem; font-weight: 500; padding: .2rem .6rem; border-radius: 999px; background: var(--paper); color: var(--muted); }
        .chip.loss { background: #FDECEA; color: #A33A2E; }
        .chip.worst { background: var(--amber-bg); color: var(--amber-ink); box-shadow: 0 0 0 1.5px var(--amber) inset; }
        .chip.direct { background: #E8EEF9; color: var(--brand-deep); }

        @keyframes grow { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        @media (min-width: 720px) { .cards { grid-template-columns: repeat(4, 1fr); } .panel { padding: 1.5rem 1.6rem 1.7rem; } .stage .name { font-size: 1rem; } }
        @media (prefers-reduced-motion: reduce) { .bar span { animation: none; } .seg a { transition: none; } }
    </style>
</head>
<body>
@php
    $n = fn ($v) => number_format($v, 0, ',', '.');
    $pct = fn ($v) => $v === null ? '–' : number_format($v, 1, ',', '.').'%';
    $periods = ['today' => 'Hoje', '7d' => '7 dias', '30d' => '30 dias'];
    $groups = [0 => 'Presell', 2 => 'Quiz', 8 => 'Resultado'];
    $today = now('America/Sao_Paulo')->format('Y-m-d');
@endphp
<div class="wrap">
    <header class="top">
        <div>
            <h1>Funil por etapas</h1>
            <p class="sub">{{ $rangeLabel }} · sessões únicas · horário de São Paulo</p>
        </div>
        <div class="range">
            <nav class="seg" aria-label="Período">
                @foreach ($periods as $key => $label)
                    <a href="?period={{ $key }}" class="{{ $period === $key ? 'on' : '' }}" @if ($period === $key) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <form method="GET" class="custom {{ $period === 'custom' ? 'on' : '' }}">
                <input type="hidden" name="period" value="custom">
                <label>De <input type="date" name="from" value="{{ $from }}" max="{{ $today }}" required></label>
                <label>até <input type="date" name="to" value="{{ $to }}" max="{{ $today }}" required></label>
                <button type="submit">Aplicar</button>
            </form>
        </div>
    </header>

    <section class="cards" aria-label="Métricas principais">
        <div class="card"><div class="label">Visitas na presell</div><div class="value">{{ $n($cards['presell']) }}</div><div class="hint">sessões únicas</div></div>
        <div class="card"><div class="label">Abriram o quiz</div><div class="value">{{ $n($cards['quiz']) }}</div><div class="hint">com ou sem presell</div></div>
        <div class="card"><div class="label">Leads</div><div class="value">{{ $n($cards['leads']) }}</div><div class="hint">enviaram o WhatsApp</div></div>
        <div class="card conv"><div class="label">Quiz → Lead</div><div class="value">{{ $pct($cards['rate']) }}</div><div class="hint">conversão do quiz</div></div>
    </section>

    <section class="panel" aria-label="Etapas do funil">
        <div class="panel-head">
            <h2>Etapas</h2>
        </div>

        @if (! $hasData)
            <p class="empty">Ainda não há acessos neste período. Assim que o site receber visitas, as etapas aparecem aqui.</p>
        @elseif ($worst !== null)
            <p class="insight">
                <strong>Maior perda:</strong> entre “{{ $funnel[$worst - 1]['label'] }}” e “{{ $funnel[$worst]['label'] }}”,
                {{ $pct($funnel[$worst]['lost_pct']) }} saíram ({{ $n($funnel[$worst]['dropped']) }} sessões).
            </p>
        @endif

        <ol class="funnel">
            @foreach ($funnel as $i => $row)
                @if (isset($groups[$i]))
                    <li class="group" aria-hidden="true">{{ $groups[$i] }}</li>
                @endif

                @if ($i > 0)
                    <li class="link" aria-hidden="true">
                        @if ($row['direct'] > 0)
                            <span class="chip direct">+ {{ $n($row['direct']) }} chegaram direto</span>
                        @elseif ($row['dropped'] > 0)
                            <span class="chip {{ $i === $worst ? 'worst' : 'loss' }}">▼ {{ $n($row['dropped']) }} saíram · {{ $pct($row['lost_pct']) }}@if ($i === $worst) · maior perda @endif</span>
                        @else
                            <span class="chip">sem perda</span>
                        @endif
                    </li>
                @endif

                <li class="stage {{ $i < 2 ? 'pre' : '' }} {{ $i === 8 ? 'lead' : '' }}" style="--i: {{ $i }}">
                    <div class="head">
                        <span class="idx" aria-hidden="true">{{ $i + 1 }}</span>
                        <span class="name">{{ $row['label'] }}</span>
                        <span class="count">{{ $n($row['count']) }}</span>
                    </div>
                    <div class="bar" role="img" aria-label="{{ $row['label'] }}: {{ $n($row['count']) }} sessões, {{ $pct($row['pct_top']) }} do topo">
                        <span style="width: {{ max($row['pct_top'] ?? 0, $row['count'] > 0 ? 1.5 : 0) }}%"></span>
                    </div>
                    <div class="meta"><b>{{ $pct($row['pct_top']) }}</b> do topo @if ($row['pct_prev'] !== null) · <b>{{ $pct($row['pct_prev']) }}</b> da etapa anterior @endif</div>
                </li>
            @endforeach
        </ol>
    </section>
</div>
</body>
</html>
