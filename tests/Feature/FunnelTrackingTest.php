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

test('dashboard fica desligado sem chave configurada', function () {
    config(['services.funnel.dashboard_key' => null]);

    $this->get('/admin/funil?key=qualquer')->assertNotFound();
});

test('dashboard nega chave errada e abre com a chave certa', function () {
    config(['services.funnel.dashboard_key' => 'segredo']);

    $this->get('/admin/funil?key=errada')->assertNotFound();
    $this->get('/admin/funil?key=segredo')->assertOk()->assertSee('Funil por etapas');
});

test('dashboard conta sessoes unicas por etapa', function () {
    config(['services.funnel.dashboard_key' => 'segredo']);

    foreach (['s1', 's1', 's2'] as $sid) {
        FunnelEvent::create(['session_id' => $sid, 'event' => 'quiz_start']);
    }
    FunnelEvent::create(['session_id' => 's1', 'event' => 'generate_lead']);

    $this->get('/admin/funil?key=segredo&period=today')
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
