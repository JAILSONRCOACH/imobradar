<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** Cria (ou promove) o administrador que aprova os cadastros. */
class CriarAdmin extends Command
{
    protected $signature = 'imobradar:admin {email} {--nome=Administrador}';

    protected $description = 'Cria ou promove um administrador e mostra uma senha provisória';

    public function handle(): int
    {
        $email = Str::lower((string) $this->argument('email'));
        $senha = Str::password(16, symbols: false);

        $user = User::firstOrNew(['email' => $email]);
        $novo = ! $user->exists;
        if ($novo) {
            $user->name = (string) $this->option('nome');
            $user->password = $senha;
        }
        $user->forceFill(['status' => 'aprovado', 'is_admin' => true, 'aprovado_em' => $user->aprovado_em ?? now()])->save();

        if ($novo) {
            $this->info("Administrador criado: {$email}");
            $this->line("Senha provisória: {$senha}");
            $this->line('Entre no site e troque em "Esqueci minha senha" se quiser.');
        } else {
            $this->info("{$email} agora é administrador (senha mantida).");
        }

        return self::SUCCESS;
    }
}
