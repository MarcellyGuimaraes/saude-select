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
