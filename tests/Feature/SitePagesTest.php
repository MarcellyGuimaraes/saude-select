<?php

test('presell tem rodape com links legais, aviso de nao-operadora e e indexavel', function () {
    $this->get('/presell')
        ->assertOk()
        ->assertSee('Política de Privacidade')
        ->assertSee('Termos de Uso')
        ->assertSee('Não somos operadora de plano de saúde')
        ->assertSee('index, follow', false)
        ->assertDontSee('noindex', false)
        ->assertSee('FAQPage', false)
        ->assertSee('presell_view', false);
});

test('presell mantem o CTA para o quiz', function () {
    $this->get('/presell')->assertOk()->assertSee('data-cta="hero"', false);
});

test('paginas de privacidade e termos abrem', function () {
    $this->get('/privacidade')->assertOk()->assertSee('LGPD');
    $this->get('/termos')->assertOk()->assertSee('Não somos operadora de plano de saúde');
});

test('rodape mostra CNPJ e razao social so quando configurados', function () {
    config(['site.cnpj' => null, 'site.legal_name' => null]);
    $this->get('/presell')->assertDontSee('· CNPJ', false);

    config(['site.cnpj' => '12.345.678/0001-90', 'site.legal_name' => 'Empresa Teste LTDA']);
    $this->get('/presell')->assertSee('CNPJ 12.345.678/0001-90')->assertSee('Empresa Teste LTDA');
});
