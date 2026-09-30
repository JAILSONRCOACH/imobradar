<?php

use App\Http\Controllers\Web\AnuncioController;
use App\Http\Controllers\Web\BuscaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BuscaController::class, 'index'])->name('busca');
Route::get('/imovel/{anuncio}', [AnuncioController::class, 'show'])->whereNumber('anuncio')->name('anuncio.show');
