<?php

namespace App\Support;

class Location
{
    /**
     * Monta a localização exibida ao lead: "Cidade - Estado", ou só o valor conhecido.
     */
    public static function format(?string $city, ?string $state): ?string
    {
        $parts = array_filter([trim((string) $city), trim((string) $state)], fn ($part) => $part !== '');

        return $parts ? implode(' - ', $parts) : null;
    }
}
