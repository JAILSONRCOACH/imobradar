<?php

use App\Http\Controllers\Api\ColetaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.ingestao', 'throttle:120,1'])->group(function () {
    Route::post('/coletas', [ColetaController::class, 'abrir']);
    Route::post('/coletas/{coleta}/anuncios', [ColetaController::class, 'anuncios']);
    Route::post('/coletas/{coleta}/finalizar', [ColetaController::class, 'finalizar']);
});
