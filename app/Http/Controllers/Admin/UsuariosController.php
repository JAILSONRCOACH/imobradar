<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Acesso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuariosController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['pendente', 'aprovado', 'recusado'], true) ? $request->query('status') : 'pendente';

        return view('admin.usuarios', [
            'usuarios' => User::where('status', $status)->orderByDesc('created_at')->paginate(50)->withQueryString(),
            'status' => $status,
            'contagem' => User::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
            'perfis' => User::PERFIS,
        ]);
    }

    public function aprovar(User $user, Acesso $acesso): RedirectResponse
    {
        $enviado = $acesso->aprovar($user);

        return back()->with('ok', "Acesso de {$user->name} liberado.".($enviado ? ' Enviamos um e-mail avisando.' : ' O e-mail de aviso falhou: avise pelo WhatsApp.'));
    }

    public function recusar(User $user, Acesso $acesso): RedirectResponse
    {
        abort_if($user->is_admin, 422, 'Não é possível recusar um administrador.');
        $acesso->recusar($user);

        return back()->with('ok', "Cadastro de {$user->name} recusado.");
    }
}
