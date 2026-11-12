<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Funil | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background: #F3F4F6; }</style>
</head>
<body class="p-4 md:p-8">
@php
    $periods = ['today' => 'Hoje', '7d' => '7 dias', '30d' => '30 dias'];
    $fmt = fn ($v) => $v === null ? '–' : number_format($v, 1, ',', '.') . '%';
    $maxDay = max($perDay ?: [0]);
@endphp
<div class="max-w-5xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Funil por etapas</h1>
            <p class="text-sm text-gray-500">Sessões únicas por etapa · fuso de São Paulo</p>
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-2 text-sm">
            <select name="period" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 bg-white">
                @foreach ($periods as $k => $label)
                    <option value="{{ $k }}" @selected($period === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="source" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 bg-white">
                <option value="">Todas as origens</option>
                @foreach ($options['sources'] as $s)
                    <option value="{{ $s }}" @selected($filters['source'] === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <select name="campaign" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 bg-white">
                <option value="">Todas as campanhas</option>
                @foreach ($options['campaigns'] as $c)
                    <option value="{{ $c }}" @selected($filters['campaign'] === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        @foreach ([
            ['Visitas na presell', number_format($cards['presell'], 0, ',', '.')],
            ['Abriram o quiz', number_format($cards['quiz'], 0, ',', '.')],
            ['Leads', number_format($cards['leads'], 0, ',', '.')],
            ['Quiz → Lead', $fmt($cards['rate'])],
        ] as [$label, $value])
            <div class="bg-white rounded-xl shadow p-4">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Funil --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="font-bold text-gray-900 mb-4">Etapas</h2>
        <div class="space-y-3">
            @foreach ($funnel as $row)
                <div>
                    <div class="flex items-baseline justify-between text-sm mb-1">
                        <span class="font-semibold text-gray-800">{{ $row['label'] }}</span>
                        <span class="text-gray-500">
                            <strong class="text-gray-900">{{ number_format($row['count'], 0, ',', '.') }}</strong>
                            · {{ $fmt($row['pct_top']) }} do topo
                            @if ($row['pct_prev'] !== null)
                                · {{ $fmt($row['pct_prev']) }} da anterior
                                @if ($row['dropped'] > 0)
                                    <span class="text-red-500">(−{{ $row['dropped'] }})</span>
                                @endif
                            @endif
                        </span>
                    </div>
                    <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full" style="width: {{ $row['pct_top'] ?? 0 }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6 mb-6">
        {{-- Origem / campanha --}}
        <div class="bg-white rounded-xl shadow p-5 overflow-x-auto">
            <h2 class="font-bold text-gray-900 mb-3">Origem e campanha</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr><th class="pb-2">Origem / campanha</th><th class="pb-2 text-right">Quiz</th><th class="pb-2 text-right">Leads</th><th class="pb-2 text-right">Conv.</th></tr>
                </thead>
                <tbody>
                @forelse ($bySource as $r)
                    <tr class="border-t">
                        <td class="py-2"><span class="font-semibold">{{ $r['source'] }}</span><br><span class="text-xs text-gray-500">{{ $r['campaign'] }}</span></td>
                        <td class="py-2 text-right">{{ $r['started'] }}</td>
                        <td class="py-2 text-right">{{ $r['leads'] }}</td>
                        <td class="py-2 text-right">{{ $fmt($r['rate']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-400">Sem dados no período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Leads por dia --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-bold text-gray-900 mb-3">Leads por dia</h2>
            @forelse ($perDay as $day => $n)
                <div class="flex items-center gap-3 text-sm mb-2">
                    <span class="w-12 text-gray-500">{{ $day }}</span>
                    <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $maxDay > 0 ? round($n / $maxDay * 100) : 0 }}%"></div>
                    </div>
                    <span class="w-8 text-right font-semibold">{{ $n }}</span>
                </div>
            @empty
                <p class="text-gray-400 text-sm">Sem leads no período.</p>
            @endforelse
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-6">
        @foreach ([['Leads por estado', $breakdown['states']], ['Leads por perfil', $breakdown['profiles']], ['Erros mais frequentes', $errors]] as [$title, $items])
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="font-bold text-gray-900 mb-3">{{ $title }}</h2>
                @forelse ($items as $k => $n)
                    <div class="flex justify-between text-sm py-1 border-t first:border-t-0">
                        <span class="text-gray-700 pr-2">{{ $k }}</span><strong>{{ $n }}</strong>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">Sem dados.</p>
                @endforelse
            </div>
        @endforeach
    </div>
</div>
</body>
</html>
