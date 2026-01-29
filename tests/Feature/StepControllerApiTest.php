<?php

use App\Services\SimuladorOnlineService;

use function Pest\Laravel\mock;

test('buscar hospitais requires q', function () {
    $response = $this->getJson(route('api.hospitais.buscar'));

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['q']);
});

test('buscar hospitais returns success and hospitais when service returns data', function () {
    mock(SimuladorOnlineService::class)
        ->shouldReceive('searchHospitalsForAutocomplete')
        ->once()
        ->with('santa')
        ->andReturn([
            'hospitais' => [
                ['id' => 1, 'nome' => 'Santa Casa'],
                ['id' => 2, 'nome' => 'Hospital Santa Maria'],
            ],
            'query' => 'santa',
            'region' => 2,
            'raw' => [],
        ]);

    $response = $this->getJson(route('api.hospitais.buscar', ['q' => 'santa']));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'hospitais' => [
            ['id' => 1, 'nome' => 'Santa Casa'],
            ['id' => 2, 'nome' => 'Hospital Santa Maria'],
        ],
    ]);
});

test('buscar hospitais returns 500 when service throws', function () {
    mock(SimuladorOnlineService::class)
        ->shouldReceive('searchHospitalsForAutocomplete')
        ->once()
        ->andThrow(new \Exception('API error'));

    $response = $this->getJson(route('api.hospitais.buscar', ['q' => 'test']));

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'error' => 'API error',
    ]);
});

test('buscar planos requires profile and lives', function () {
    $response = $this->postJson(route('api.planos.buscar'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['profile', 'lives']);
});

test('buscar planos rejects invalid profile', function () {
    $response = $this->postJson(route('api.planos.buscar'), [
        'profile' => 'invalid',
        'lives' => [],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['profile']);
});

test('buscar planos returns success and planos when service returns data', function () {
    mock(SimuladorOnlineService::class)
        ->shouldReceive('searchPlans')
        ->once()
        ->with(\Mockery::on(fn ($v) => isset($v['profile'], $v['lives']) && $v['profile'] === 'cpf'))
        ->andReturn([
            ['id' => 10, 'operadora' => 'Op A', 'nome' => 'Plano X', 'acomodacao' => 'Apartamento', 'acomodacao_sigla' => 'A', 'operadora_logo' => null, 'operadora_descricao' => '', 'vigencia' => '01/2025'],
        ]);

    $response = $this->postJson(route('api.planos.buscar'), [
        'profile' => 'cpf',
        'lives' => ['0-18' => 1, '19-23' => 0],
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'total' => 1,
    ]);
    $response->assertJsonPath('planos.0.id', 10);
    $response->assertJsonPath('planos.0.nome', 'Plano X');
});

test('buscar planos returns 500 when service throws', function () {
    mock(SimuladorOnlineService::class)
        ->shouldReceive('searchPlans')
        ->once()
        ->andThrow(new \Exception('Plans API error'));

    $response = $this->postJson(route('api.planos.buscar'), [
        'profile' => 'cpf',
        'lives' => ['0-18' => 1],
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'error' => 'Plans API error',
    ]);
});
