<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Alguns proxies reversos (ex: um Context do OpenLiteSpeed montando este app
// sob um sub-caminho como /api ou /sanctum, dentro de um vhost cujo domínio
// principal serve outra aplicação) alteram SCRIPT_NAME/PHP_SELF para refletir
// esse sub-caminho. O Symfony usa esses valores pra calcular a "base URL" do
// app e subtrai isso da URI antes de rotear — o que faz o Laravel achar que
// /api/campaigns é só /campaigns, e nenhuma rota bate. Como este index.php
// SEMPRE está fisicamente na raiz de public/, forçamos os dois de volta pro
// valor correto, ignorando o que o servidor tentou definir.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
