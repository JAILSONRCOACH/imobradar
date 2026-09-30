<?php

use App\Http\Controllers\Admin\AprovacaoLinkController;
use App\Http\Controllers\Admin\UsuariosController;
use App\Http\Controllers\Auth\CadastroController;
use App\Http\Controllers\Auth\EntrarController;
use App\Http\Controllers\Auth\SenhaController;
use App\Http\Controllers\Web\AnuncioController;
use App\Http\Controllers\Web\BuscaController;
use Illuminate\Support\Facades\Route;

// Acesso (público)
Route::get('/entrar', [EntrarController::class, 'form'])->name('entrar');
Route::post('/entrar', [EntrarController::class, 'entrar'])->middleware('throttle:20,1');
Route::post('/sair', [EntrarController::class, 'sair'])->name('sair');
Route::get('/cadastro', [CadastroController::class, 'form'])->name('cadastro');
Route::post('/cadastro', [CadastroController::class, 'cadastrar'])->middleware('throttle:5,10');
Route::get('/aguardando', [EntrarController::class, 'aguardando'])->name('aguardando');

Route::get('/esqueci-a-senha', [SenhaController::class, 'pedir'])->name('senha.pedir');
Route::post('/esqueci-a-senha', [SenhaController::class, 'enviar'])->middleware('throttle:5,10')->name('senha.enviar');
Route::get('/nova-senha/{token}', [SenhaController::class, 'form'])->name('password.reset');
Route::post('/nova-senha', [SenhaController::class, 'salvar'])->middleware('throttle:10,10')->name('senha.salvar');

// Link de aprovação enviado por e-mail ao administrador (assinado e com validade)
Route::get('/aprovar/{user}', [AprovacaoLinkController::class, 'mostrar'])->middleware('signed')->name('aprovacao.mostrar');
Route::post('/aprovar/{user}', [AprovacaoLinkController::class, 'aprovar'])->middleware('signed')->name('aprovacao.aprovar');

// Área privada: só usuários aprovados
Route::middleware('aprovado')->group(function () {
    Route::get('/', [BuscaController::class, 'index'])->name('busca');
    Route::get('/imovel/{anuncio}', [AnuncioController::class, 'show'])->whereNumber('anuncio')->name('anuncio.show');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/usuarios', [UsuariosController::class, 'index'])->name('admin.usuarios');
        Route::post('/usuarios/{user}/aprovar', [UsuariosController::class, 'aprovar'])->name('admin.aprovar');
        Route::post('/usuarios/{user}/recusar', [UsuariosController::class, 'recusar'])->name('admin.recusar');
    });
});
