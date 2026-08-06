<?php

use Illuminate\Support\Facades\Route;

// Este projeto é uma API pura (ver docs/01-arquitetura-e-plano.md) — o frontend
// vive em /frontend (Next.js), consumindo /api/*. Esta rota é só um landing
// mínimo pra confirmar que o backend está no ar.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'status' => 'ok',
]));
