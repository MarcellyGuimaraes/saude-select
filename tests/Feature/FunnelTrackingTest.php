<?php

use App\Models\FunnelEvent;

test('track grava evento valido sem dados pessoais', function () {
    $this->postJson('/api/track', [
        'event' => 'quiz_step_view',
        'session_id' => 'abc-123',
        'page' => '/',
        'step' => 2,
        'utm' => ['utm_source' => 'meta', 'utm_campaign' => 'saude1'],
        'props' => ['profile' => 'cpf', 'state_name' => 'Rio de Janeiro', 'phone' => '21999998888'],
    ])->assertNoContent();

    $event = FunnelEvent::first();
    expect($event->event)->toBe('quiz_step_view')
        ->and($event->step)->toBe(2)
        ->and($event->utm_source)->toBe('meta')
        ->and($event->profile)->toBe('cpf')
        ->and($event->props)->not->toHaveKey('phone');
});

test('track rejeita evento desconhecido', function () {
    $this->postJson('/api/track', ['event' => 'hack', 'session_id' => 'x'])
        ->assertUnprocessable();

    expect(FunnelEvent::count())->toBe(0);
});

/** Cabecalho de autenticacao basica com a senha de teste configurada no test. */
function dashboardLogin(string $user = 'trafego', string $senha = 'senha-de-teste'): array
{
    config(['services.funnel.user' => 'trafego', 'services.funnel.password_hash' => password_hash('senha-de-teste', PASSWORD_BCRYPT)]);

    return ['Authorization' => 'Basic '.base64_encode($user.':'.$senha)];
}

test('dashboard pede login quando nao ha credenciais', function () {
    dashboardLogin();

    $this->get('/admin/funil')
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');
});

test('dashboard nega usuario ou senha errados e abre com o login certo', function () {
    $this->withHeaders(dashboardLogin('trafego', 'senha-errada'))->get('/admin/funil')->assertUnauthorized();
    $this->withHeaders(dashboardLogin('outro', 'senha-de-teste'))->get('/admin/funil')->assertUnauthorized();
    $this->withHeaders(dashboardLogin())->get('/admin/funil')->assertOk()->assertSee('Funil por etapas');
});

test('dashboard mostra so periodo, metricas e etapas', function () {
    $this->withHeaders(dashboardLogin())->get('/admin/funil')
        ->assertOk()
        ->assertSee('Funil por etapas')
        ->assertSee('Abriram o quiz')
        ->assertSee('Passo 5: WhatsApp')
        ->assertDontSee('Leads por estado')
        ->assertDontSee('Erros mais frequentes')
        ->assertDontSee('Origem e campanha')
        ->assertDontSee('Leads por dia');
});

test('dashboard aceita periodo personalizado e ignora datas invalidas', function () {
    FunnelEvent::create(['session_id' => 'a', 'event' => 'quiz_start', 'created_at' => '2026-11-10 15:00:00']);
    FunnelEvent::create(['session_id' => 'b', 'event' => 'quiz_start', 'created_at' => '2026-11-12 15:00:00']);
    $h = dashboardLogin();

    // so o dia 10 (um quiz aberto)
    $this->withHeaders($h)->get('/admin/funil?period=custom&from=2026-11-10&to=2026-11-10')
        ->assertOk()->assertViewHas('cards', fn ($c) => $c['quiz'] === 1)->assertViewHas('period', 'custom');

    // intervalo invertido ou texto qualquer: cai nos ultimos 7 dias
    $this->withHeaders($h)->get('/admin/funil?period=custom&from=2026-11-20&to=2026-11-10')
        ->assertOk()->assertViewHas('period', '7d');
    $this->withHeaders($h)->get('/admin/funil?period=custom&from=abc&to=xyz')
        ->assertOk()->assertViewHas('period', '7d');
});

test('dashboard tem os periodos pre-definidos no menu, na ordem pedida', function () {
    $this->withHeaders(dashboardLogin())->get('/admin/funil')
        ->assertOk()
        ->assertSeeInOrder(['Hoje', 'Ontem', 'Hoje e ontem', '3 dias', '7 dias', '14 dias', 'Este mês', 'Mês passado', 'Personalizado'])
        ->assertSee('family=Sora', false);
});

test('cada periodo pre-definido filtra o intervalo certo (fuso de Sao Paulo)', function () {
    // "agora": 19/11/2026 12:00 em Sao Paulo (15:00 UTC)
    $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-19 15:00:00', 'UTC'));
    $h = dashboardLogin();

    foreach (['2026-11-19 14:00:00' => 'd19', '2026-11-18 15:00:00' => 'd18', '2026-11-17 15:00:00' => 'd17',
              '2026-11-13 15:00:00' => 'd13', '2026-11-06 15:00:00' => 'd06', '2026-11-02 15:00:00' => 'd02',
              '2026-10-20 15:00:00' => 'o20'] as $quando => $sid) {
        FunnelEvent::create(['session_id' => $sid, 'event' => 'quiz_start', 'created_at' => $quando]);
    }

    $quiz = fn (string $periodo) => $this->withHeaders($h)->get("/admin/funil?period=$periodo")->viewData('cards')['quiz'];

    expect($quiz('today'))->toBe(1)             // 19
        ->and($quiz('yesterday'))->toBe(1)      // 18
        ->and($quiz('today_yesterday'))->toBe(2) // 18 e 19
        ->and($quiz('3d'))->toBe(3)             // 17, 18, 19
        ->and($quiz('7d'))->toBe(4)             // + 13
        ->and($quiz('14d'))->toBe(5)            // + 06
        ->and($quiz('month'))->toBe(6)          // + 02 (novembro inteiro ate agora)
        ->and($quiz('last_month'))->toBe(1);    // outubro (20)
});

test('dashboard destaca a maior perda entre etapas e nao passa de 100%', function () {
    $h = dashboardLogin();
    // 4 sessoes passam pela presell e clicam; 10 abrem o quiz (6 chegaram direto);
    // 8 veem o passo 1; 2 veem o passo 2 (a maior perda: 75%) e essas 2 seguem ate virar lead.
    foreach (range(1, 4) as $i) {
        FunnelEvent::create(['session_id' => "p$i", 'event' => 'presell_view']);
        FunnelEvent::create(['session_id' => "p$i", 'event' => 'presell_cta_click']);
    }
    foreach (range(1, 10) as $i) { FunnelEvent::create(['session_id' => "s$i", 'event' => 'quiz_start']); }
    foreach (range(1, 8) as $i) { FunnelEvent::create(['session_id' => "s$i", 'event' => 'quiz_step_view', 'step' => 1]); }
    foreach (range(1, 2) as $i) {
        foreach ([2, 3, 4, 5] as $step) { FunnelEvent::create(['session_id' => "s$i", 'event' => 'quiz_step_view', 'step' => $step]); }
        FunnelEvent::create(['session_id' => "s$i", 'event' => 'generate_lead', 'step' => 6]);
    }

    $this->withHeaders($h)->get('/admin/funil?period=today')
        ->assertOk()
        ->assertSee('Maior perda')
        ->assertSee('chegaram direto')
        ->assertViewHas('funnel', fn ($f) => $f[2]['pct_prev'] === null && $f[2]['direct'] === 6 && $f[4]['lost_pct'] === 75.0)
        ->assertViewHas('worst', 4);
});

test('dashboard fica desligado se nao houver hash configurado', function () {
    config(['services.funnel.user' => 'trafego', 'services.funnel.password_hash' => '']);

    $this->withHeaders(['Authorization' => 'Basic '.base64_encode('trafego:')])->get('/admin/funil')->assertUnauthorized();
});

test('dashboard conta sessoes unicas por etapa', function () {
    $headers = dashboardLogin();

    foreach (['s1', 's1', 's2'] as $sid) {
        FunnelEvent::create(['session_id' => $sid, 'event' => 'quiz_start']);
    }
    FunnelEvent::create(['session_id' => 's1', 'event' => 'generate_lead']);

    $this->withHeaders($headers)->get('/admin/funil?period=today')
        ->assertOk()
        ->assertViewHas('cards', fn ($c) => $c['quiz'] === 2 && $c['leads'] === 1 && $c['rate'] === 50.0);
});

test('presell abre e o funil continua com o snippet de rastreamento', function () {
    $this->get('/presell')->assertOk()->assertSee('presell_view');
    $this->get('/')->assertOk()->assertSee('fsTrack', false);
});

test('gtm so aparece quando GTM_ID esta configurado', function () {
    config(['services.gtm.id' => null]);
    $this->get('/')->assertDontSee('googletagmanager.com', false);

    config(['services.gtm.id' => 'GTM-TEST123']);
    $this->get('/')->assertSee('GTM-TEST123', false);
});
