<?php

namespace App\Services;

use App\Mail\AcessoLiberado;
use App\Mail\NovoCadastro;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Regras de aprovação de acesso. Falha de e-mail nunca impede o cadastro nem a aprovação. */
class Acesso
{
    public function avisarAdmin(User $user): bool
    {
        return $this->enviar(fn () => Mail::to(config('imobradar.admin_email'))->send(new NovoCadastro($user)), 'aviso de cadastro', $user);
    }

    public function aprovar(User $user): bool
    {
        if ($user->aprovado()) {
            return true;
        }
        $user->forceFill(['status' => 'aprovado', 'aprovado_em' => now()])->save();

        return $this->enviar(fn () => Mail::to($user->email)->send(new AcessoLiberado($user)), 'aviso de acesso liberado', $user);
    }

    public function recusar(User $user): void
    {
        $user->forceFill(['status' => 'recusado', 'aprovado_em' => null])->save();
    }

    private function enviar(callable $envio, string $oque, User $user): bool
    {
        try {
            $envio();

            return true;
        } catch (Throwable $e) {
            Log::error("Falha ao enviar {$oque} (usuário {$user->id}): {$e->getMessage()}");

            return false;
        }
    }
}
