<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Acesso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CadastroController extends Controller
{
    public function form(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('busca') : view('auth.cadastro', ['perfis' => User::PERFIS]);
    }

    public function cadastrar(Request $request, Acesso $acesso): RedirectResponse
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'whatsapp' => ['required', 'string', 'regex:/^[\d\s()+-]{10,20}$/'],
            'perfil' => ['required', Rule::in(array_keys(User::PERFIS))],
            'mensagem' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'site' => ['prohibited'], // armadilha para robôs: campo escondido que pessoa não preenche
        ], [
            'email.unique' => 'Já existe um cadastro com esse e-mail. Use "Entrar".',
            'whatsapp.regex' => 'Informe o WhatsApp com DDD, só números.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);
        unset($dados['site']);

        $user = User::create($dados);
        $acesso->avisarAdmin($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('aguardando');
    }
}
