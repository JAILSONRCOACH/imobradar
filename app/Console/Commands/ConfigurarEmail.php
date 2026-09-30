<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Grava no .env o SMTP da caixa contato@imobradar.com.br (Hostinger) e manda um e-mail de teste.
 * A senha é pedida sem aparecer na tela e sem ficar no histórico do terminal.
 */
class ConfigurarEmail extends Command
{
    protected $signature = 'imobradar:configurar-email
        {--email=contato@imobradar.com.br : Caixa de e-mail que envia}
        {--host=smtp.hostinger.com}
        {--porta=465}
        {--testar-para= : Para onde mandar o teste (padrão: a própria caixa)}';

    protected $description = 'Configura o envio de e-mails do site e envia um teste';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $senha = trim((string) $this->secret("Senha da caixa {$email}"));
        if ($senha === '') {
            $this->error('Senha vazia. Nada foi alterado.');

            return self::FAILURE;
        }

        $valores = [
            'MAIL_MAILER' => 'smtp',
            'MAIL_SCHEME' => (int) $this->option('porta') === 465 ? 'smtps' : 'smtp',
            'MAIL_HOST' => (string) $this->option('host'),
            'MAIL_PORT' => (string) $this->option('porta'),
            'MAIL_USERNAME' => $email,
            'MAIL_PASSWORD' => $senha,
            'MAIL_FROM_ADDRESS' => $email,
            'MAIL_FROM_NAME' => 'IMOBRADAR',
            'IMOBRADAR_ADMIN_EMAIL' => $email,
        ];

        $arquivo = base_path('.env');
        $env = file_get_contents($arquivo);
        foreach ($valores as $chave => $valor) {
            $linha = $chave.'="'.addcslashes($valor, '"\\$').'"';
            $env = preg_match("/^{$chave}=.*$/m", $env)
                ? preg_replace_callback("/^{$chave}=.*$/m", fn () => $linha, $env)
                : rtrim($env)."\n{$linha}\n";
        }
        file_put_contents($arquivo, $env);
        $this->info('Configuração gravada no .env.');

        // Recarrega a configuração com os valores novos antes do teste.
        Artisan::call('config:clear');
        foreach ($valores as $chave => $valor) {
            putenv("{$chave}={$valor}");
            $_ENV[$chave] = $_SERVER[$chave] = $valor;
        }
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $valores['MAIL_SCHEME'],
            'mail.mailers.smtp.host' => $valores['MAIL_HOST'],
            'mail.mailers.smtp.port' => (int) $valores['MAIL_PORT'],
            'mail.mailers.smtp.username' => $email,
            'mail.mailers.smtp.password' => $senha,
            'mail.from.address' => $email,
            'mail.from.name' => 'IMOBRADAR',
            'imobradar.admin_email' => $email,
        ]);

        $destino = (string) ($this->option('testar-para') ?: $email);
        try {
            Mail::raw('Teste de envio do IMOBRADAR. Se você recebeu, o e-mail do site está funcionando.', function ($m) use ($destino) {
                $m->to($destino)->subject('Teste de e-mail do IMOBRADAR');
            });
            $this->info("E-mail de teste enviado para {$destino}. Confira a caixa de entrada e o spam.");
        } catch (Throwable $e) {
            $this->error('O envio falhou: '.$e->getMessage());
            $this->line('Confira a senha da caixa e se ela existe no hPanel (E-mails).');

            return self::FAILURE;
        } finally {
            Artisan::call('config:cache');
        }

        return self::SUCCESS;
    }
}
