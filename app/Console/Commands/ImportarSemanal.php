<?php

namespace App\Console\Commands;

use App\Models\Anuncio;
use App\Models\AnuncioEvento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Importa todas as cidades da atualização semanal e tira do ar o que não aparece há semanas. */
class ImportarSemanal extends Command
{
    protected $signature = 'imobradar:importar-semanal';

    protected $description = 'Importa as cidades da rotina semanal e expira anúncios antigos';

    public function handle(): int
    {
        foreach (array_keys(config('imobradar.semanal.cidades', [])) as $slug) {
            $this->call('imobradar:importar-cidade', ['cidade' => $slug]);
        }

        $limite = now()->subDays((int) config('imobradar.semanal.dias_expirar'))->toDateString();
        $hoje = now()->toDateString();
        $expirados = 0;
        Anuncio::where('status', 'ativo')->where('ultima_vez_em', '<', $limite)
            ->chunkById(500, function ($anuncios) use ($hoje, &$expirados) {
                DB::transaction(function () use ($anuncios, $hoje, &$expirados) {
                    foreach ($anuncios as $a) {
                        $a->update(['status' => 'removido', 'removido_em' => $hoje]);
                        AnuncioEvento::create(['anuncio_id' => $a->id, 'tipo' => 'removido', 'preco_anterior' => $a->preco, 'ocorrido_em' => $hoje]);
                        $expirados++;
                    }
                });
            });
        $this->info("Anúncios sem aparecer há mais de ".config('imobradar.semanal.dias_expirar')." dias, marcados como fora do ar: {$expirados}");

        return self::SUCCESS;
    }
}
