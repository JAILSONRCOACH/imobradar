<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RegraSenha;
use Illuminate\View\View;

/** "Esqueci minha senha": envia link por e-mail e permite criar uma nova. */
class SenhaController extends Controller
{
    public function pedir(): View
    {
        return view('auth.senha-pedir');
    }

    public function enviar(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        // A resposta é a mesma exista ou não o e-mail, para não revelar quem tem cadastro.
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('ok', 'Se esse e-mail tiver cadastro, enviamos um link para criar uma nova senha. Confira também o spam.');
    }

    public function form(Request $request, string $token): View
    {
        return view('auth.senha-nova', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function salvar(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', RegraSenha::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $senha) {
                $user->forceFill(['password' => $senha, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('entrar')->with('ok', 'Senha alterada. Entre com a nova senha.')
            : back()->withErrors(['email' => 'Link inválido ou expirado. Peça um novo.']);
    }
}
