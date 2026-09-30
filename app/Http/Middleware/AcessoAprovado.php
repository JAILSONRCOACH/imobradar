<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Só deixa passar usuário logado com cadastro aprovado. */
class AcessoAprovado
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('entrar'));
        }
        if (! $user->aprovado()) {
            return redirect()->route('aguardando');
        }

        // Registra o último acesso no máximo uma vez por hora.
        if (! $user->ultimo_acesso_em || $user->ultimo_acesso_em->lt(now()->subHour())) {
            $user->forceFill(['ultimo_acesso_em' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
