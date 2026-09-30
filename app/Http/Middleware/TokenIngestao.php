<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Protege a API de ingestão com um token fixo (Authorization: Bearer <token>). */
class TokenIngestao
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = (string) config('imobradar.ingest_token');

        if ($esperado === '' || strlen($esperado) < 32) {
            return response()->json(['erro' => 'API de ingestão desativada: defina IMOBRADAR_INGEST_TOKEN (32+ caracteres).'], 503);
        }

        $recebido = (string) $request->bearerToken();
        if ($recebido === '' || ! hash_equals($esperado, $recebido)) {
            return response()->json(['erro' => 'Token inválido.'], 401);
        }

        return $next($request);
    }
}
