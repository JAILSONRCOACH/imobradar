<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/** Grava no .env o endereço do webhook do n8n e cria (ou mostra) o segredo compartilhado. */
class ConfigurarN8n extends Command
{
    protected $signature = 'imobradar:configurar-n8n
        {--webhook=https://n8n.servmjr.site/webhook/imobradar-busca}
        {--novo-segredo : Gera um segredo novo mesmo se já existir}';

    protected $description = 'Configura a busca sob demanda (n8n) e mostra o segredo do webhook';

    public function handle(): int
    {
        $arquivo = base_path('.env');
        $env = file_get_contents($arquivo);

        preg_match('/^IMOBRADAR_N8N_SEGREDO=(.*)$/m', $env, $m);
        $segredo = trim($m[1] ?? '', " \t\"'");
        if ($segredo === '' || $this->option('novo-segredo')) {
            $segredo = bin2hex(random_bytes(24));
        }

        foreach (['IMOBRADAR_N8N_WEBHOOK' => (string) $this->option('webhook'), 'IMOBRADAR_N8N_SEGREDO' => $segredo] as $chave => $valor) {
            $linha = "{$chave}={$valor}";
            $env = preg_match("/^{$chave}=.*$/m", $env)
                ? preg_replace_callback("/^{$chave}=.*$/m", fn () => $linha, $env)
                : rtrim($env)."\n{$linha}\n";
        }
        file_put_contents($arquivo, $env);
        Artisan::call('config:cache');

        $this->info('Webhook: '.$this->option('webhook'));
        $this->line('SEGREDO DO WEBHOOK: '.$segredo);
        $this->line('Cole esse segredo no Value da credencial "IMOBRADAR Webhook" no n8n.');

        return self::SUCCESS;
    }
}
