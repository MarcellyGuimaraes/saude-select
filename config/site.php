<?php

/**
 * Dados públicos da empresa exibidos no rodapé e nas páginas legais.
 * Preencha pelo .env; campos vazios simplesmente não aparecem na página.
 * (O Google e a LGPD esperam identificação clara de quem opera o site.)
 */
return [
    'name' => env('COMPANY_NAME', 'BuscarPlanos'),
    'legal_name' => env('COMPANY_LEGAL_NAME'),   // razão social
    'cnpj' => env('COMPANY_CNPJ'),               // 00.000.000/0000-00
    'susep' => env('COMPANY_SUSEP'),             // registro SUSEP do corretor, se houver
    'email' => env('COMPANY_EMAIL', env('ADMIN_NOTIFICATION_EMAIL', 'buscarplanos.oficial@gmail.com')),
    'phone' => env('COMPANY_PHONE'),             // exibido como veio, ex.: (21) 98896-5001
    'address' => env('COMPANY_ADDRESS'),

    'facebook' => 'https://www.facebook.com/buscarplanos',
    'instagram' => 'https://www.instagram.com/buscarplanos',
];
