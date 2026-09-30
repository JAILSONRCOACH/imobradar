<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EntrarController extends Controller
{
    public function form(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('busca') : view('auth.entrar');
    }

    public function entrar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Máximo de 5 tentativas por e-mail + IP a cada minuto.
        $chave = 'entrar:'.Str::lower($dados['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($chave, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Muitas tentativas. Tente de novo em '.RateLimiter::availableIn($chave).' segundos.',
            ]);
        }

        if (! Auth::attempt($dados, $request->boolean('lembrar'))) {
            RateLimiter::hit($chave, 60);
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }

        RateLimiter::clear($chave);
        $request->session()->regenerate();

        return $request->user()->aprovado()
            ? redirect()->intended(route('busca'))
            : redirect()->route('aguardando');
    }

    public function sair(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('entrar');
    }

    public function aguardando(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('entrar');
        }
        if ($user->aprovado()) {
            return redirect()->route('busca');
        }

        return view('auth.aguardando', ['user' => $user]);
    }
}
