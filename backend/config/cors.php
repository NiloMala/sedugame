<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | O frontend (Next.js) roda em uma origem diferente da API (mesmo em
    | produção: app.SEUDOMINIO vs api.SEUDOMINIO), então toda chamada
    | fetch com credenciais depende desses headers. `supports_credentials`
    | precisa ser true para os cookies de sessão do Sanctum funcionarem, e
    | por isso `allowed_origins` NÃO pode ser '*' — o navegador rejeita
    | wildcard combinado com credentials. Ver docs/01-arquitetura-e-plano.md.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
