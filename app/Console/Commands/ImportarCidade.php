<?php

namespace App\Console\Commands;

use App\Models\Busca;
use App\Models\Cidade;
use App\Models\Coleta;
use App\Models\Fonte;
use App\Services\Ingestor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Importa o JSON de uma cidade gerado pela rotina semanal (ramo "dados" do GitHub).
 * Formato: {"cidade_ibge": 2507507, "gerado_em": "2026-10-05", "anuncios": [{url, titulo, fonte, finalidade, ...}]}
 */
class ImportarCidade extends Command
{
    protected $signature = 'imobradar:importar-cidade {cidade : slug do município, ex.: joao-pessoa} {--origem= : caminho ou URL do JSON}';

    protected $description = 'Importa os anúncios de uma cidade gerados pela rotina semanal';

    public function handle(Ingestor $ingestor): int
    {
        $slug = (string) $this->argument('cidade');
        $cidade = Cidade::where('slug', $slug)->first();
        if (! $cidade) {
            $this->error("Município {$slug} não encontrado.");

            return self::FAILURE;
        }

        $origem = $this->option('origem') ?: rtrim((string) config('imobradar.semanal.base_url'), '/')."/{$slug}.json";
        try {
            $bruto = preg_match('#^https?://#', $origem)
                ? Http::timeout(60)->get($origem.'?t='.time())->throw()->body()
                : file_get_contents($origem);
        } catch (\Throwable $e) {
            $this->error("Não consegui baixar {$origem}: {$e->getMessage()}");

            return self::FAILURE;
        }

        $dados = json_decode((string) $bruto, true);
        if (! is_array($dados) || ! isset($dados['anuncios']) || ! is_array($dados['anuncios'])) {
            $this->error('JSON inválido: falta a lista "anuncios".');

            return self::FAILURE;
        }

        $fonte = Fonte::firstOrCreate(['slug' => 'rotina-semanal'], ['nome' => 'Rotina semanal', 'tipo' => 'outro', 'ativa' => true]);
        $coleta = Coleta::create([
            'fonte_id' => $fonte->id,
            'cidade_id' => $cidade->id,
            'status' => 'em_andamento',
            'iniciada_em' => now(),
        ]);

        // A cidade vem do arquivo; cada anúncio traz o próprio portal em "fonte".
        $itens = array_map(fn ($a) => (is_array($a) ? $a : []) + ['cidade_ibge' => $cidade->ibge], $dados['anuncios']);
        $total = ['novos' => 0, 'alterados' => 0, 'voltaram' => 0, 'iguais' => 0, 'rejeitados' => []];
        foreach (array_chunk($itens, 200) as $lote) {
            $r = $ingestor->lote($coleta, $lote);
            foreach (['novos', 'alterados', 'voltaram', 'iguais'] as $k) {
                $total[$k] += $r[$k];
            }
            $total['rejeitados'] = array_merge($total['rejeitados'], $r['rejeitados']);
        }
        $ingestor->finalizar($coleta, completa: false);

        Busca::create([
            'cidade_id' => $cidade->id,
            'finalidade' => 'venda',
            'status' => 'concluida',
            'encontrados' => $total['novos'] + $total['alterados'] + $total['voltaram'] + $total['iguais'],
            'novos' => $total['novos'],
            'concluida_em' => now(),
        ]);
        foreach (['busca:resumo', 'busca:cidades', 'busca:fontes-ativas'] as $chave) {
            Cache::forget($chave);
        }

        $this->info("{$cidade->nome}: ".count($itens).' no arquivo (gerado em '.($dados['gerado_em'] ?? '?').')');
        $this->table(['novos', 'alterados', 'voltaram', 'iguais', 'rejeitados'], [[
            $total['novos'], $total['alterados'], $total['voltaram'], $total['iguais'], count($total['rejeitados']),
        ]]);
        foreach (array_slice($total['rejeitados'], 0, 10) as $r) {
            $this->line("  rejeitado #{$r['indice']}: {$r['motivo']}");
        }

        return self::SUCCESS;
    }
}
