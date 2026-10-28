<?php

use App\Support\Location;

test('formata cidade e estado', function () {
    expect(Location::format('Niterói', 'Rio de Janeiro'))->toBe('Niterói - Rio de Janeiro');
});

test('usa so a cidade quando nao ha estado', function () {
    expect(Location::format('Niterói', null))->toBe('Niterói');
});

test('usa so o estado quando nao ha cidade', function () {
    expect(Location::format('', 'Rio de Janeiro'))->toBe('Rio de Janeiro');
});

test('retorna null sem cidade e sem estado', function () {
    expect(Location::format(null, '  '))->toBeNull();
});
