<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Acesso;
use Illuminate\View\View;

/**
 * Link assinado enviado no e-mail do administrador.
 * O GET só mostra a confirmação (leitores de e-mail abrem links sozinhos); quem aprova é o POST.
 */
class AprovacaoLinkController extends Controller
{
    public function mostrar(User $user): View
    {
        return view('admin.aprovar-link', ['user' => $user, 'feito' => null]);
    }

    public function aprovar(User $user, Acesso $acesso): View
    {
        $enviado = $acesso->aprovar($user);

        return view('admin.aprovar-link', ['user' => $user->refresh(), 'feito' => $enviado ? 'aprovado' : 'aprovado-sem-email']);
    }
}
