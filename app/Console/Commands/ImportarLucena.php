<?php

namespace App\Console\Commands;

use App\Models\Anuncio;
use App\Models\Cidade;
use App\Models\Coleta;
use App\Models\Fonte;
use App\Services\Agrupador;
use App\Services\DadoInvalido;
use App\Services\Ingestor;
use App\Support\Normalizador;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Importa a base do monitoramento de Lucena (repositório imoveis-lucena), preservando
 * a data em que cada anúncio apareceu e o histórico de preço. Pode ser rodado de novo:
 * anúncios já importados são atualizados, sem duplicar.
 */
class ImportarLucena extends Command
{
    protected $signature = 'imobradar:importar-lucena
        {origem=https://raw.githubusercontent.com/JAILSONRCOACH/imoveis-lucena/main/index.html : Caminho ou URL do index.html}';

    protected $description = 'Importa os anúncios de Lucena do index.html do monitoramento diário';

    /** prefixo do id no index.html => slug da fonte */
    private const PREFIXOS = [
        'mgf' => 'mgf', 'olx' => 'olx', 'zap' => 'zap-vivareal', 'cnm' => 'chaves-na-mao',
        'iw' => 'imovelweb', 'bkz' => 'buskaza', 'cm' => 'casa-mineira', 'pp' => 'paraiba-property',
        'cf' => 'casa-forte', 'caixa' => 'caixa', 'pgfn' => 'pgfn', 'airbnb' => 'airbnb',
        'vrbo' => 'vrbo', 'tl' => 'temporada-livre',
    ];

    private const IBGE_LUCENA = 2508604;

    /** @var array<string, Coleta> */
    private array $coletas = [];

    public function handle(Ingestor $ingestor, Agrupador $agrupador): int
    {
        $origem = (string) $this->argument('origem');
        $html = preg_match('#^https?://#', $origem)
            ? Http::timeout(60)->get($origem)->throw()->body()
            : @file_get_contents($origem);

        if (! $html) {
            $this->error("Não consegui ler {$origem}");

            return self::FAILURE;
        }

        $venda = $this->json($html, 'dados-venda')['imoveis'] ?? null;
        $aluguel = $this->json($html, 'dados-aluguel')['casas'] ?? null;
        if (! is_array($venda) || ! is_array($aluguel)) {
            $this->error('Blocos dados-venda / dados-aluguel não encontrados no HTML.');

            return self::FAILURE;
        }

        if (! Cidade::where('ibge', self::IBGE_LUCENA)->exists()) {
            $this->error('Cidades não cadastradas. Rode antes: php artisan db:seed --force');

            return self::FAILURE;
        }

        $this->info(sprintf('Venda: %d | Aluguel/temporada: %d', count($venda), count($aluguel)));

        $contagem = ['novos' => 0, 'alterados' => 0, 'voltaram' => 0, 'iguais' => 0, 'rejeitados' => 0];
        $refs = [];

        $itens = array_merge(
            array_map(fn ($i) => ['venda', $i], $venda),
            array_map(fn ($i) => ['aluguel', $i], $aluguel),
        );

        $barra = $this->output->createProgressBar(count($itens));
        foreach ($itens as [$lista, $i]) {
            $barra->advance();
            [$item, $fonteSlug] = $lista === 'venda' ? $this->mapearVenda($i) : $this->mapearAluguel($i);
            if ($fonteSlug === null) {
                $contagem['rejeitados']++;
                $this->newLine();
                $this->warn("Prefixo desconhecido: {$i['id']}");

                continue;
            }

            $coleta = $this->coleta($fonteSlug);
            $historico = $this->historico($i, $item['preco']);

            $jaExiste = Anuncio::where('fonte_id', $coleta->fonte_id)
                ->where('id_externo', $item['id_externo'])
                ->where('finalidade', $item['finalidade'])
                ->exists();
            // Numa reimportação só o último preço interessa; repetir o histórico criaria mudanças falsas.
            if ($jaExiste) {
                $historico = [end($historico)];
            }

            try {
                $r = null;
                foreach ($historico as [$data, $preco]) {
                    $res = $ingestor->registrar($coleta, ['preco' => $preco] + $item, $data);
                    $r ??= $res;
                }
            } catch (DadoInvalido $e) {
                $contagem['rejeitados']++;
                $this->newLine();
                $this->warn("{$i['id']}: {$e->getMessage()}");

                continue;
            }
            $contagem[$r]++;

            $anuncio = Anuncio::where('fonte_id', $coleta->fonte_id)
                ->where('id_externo', $item['id_externo'])
                ->where('finalidade', $item['finalidade'])
                ->first();

            // Datas reais do monitoramento, não a data da importação.
            $ultima = $this->data($i['lastSeen'] ?? null) ?? end($historico)[0];
            $anuncio->forceFill([
                'primeira_vez_em' => $this->data($i['firstSeen'] ?? null)?->toDateString() ?? $anuncio->primeira_vez_em,
                'ultima_vez_em' => $ultima->toDateString(),
                'status' => ($i['status'] ?? 'ativo') === 'ativo' ? 'ativo' : 'removido',
            ])->save();

            if (preg_match_all('/\b(?:'.implode('|', array_keys(self::PREFIXOS)).')-[\w-]+/', (string) ($i['dup'] ?? ''), $m)) {
                foreach ($m[0] as $ref) {
                    $refs[] = [$anuncio->id, $ref];
                }
            }
        }
        $barra->finish();
        $this->newLine(2);

        foreach ($this->coletas as $c) {
            $ingestor->finalizar($c, completa: false);
        }

        // Liga os prováveis duplicados indicados no campo "dup".
        $mapa = Anuncio::whereIn('id_externo', array_unique(array_column($refs, 1)))->pluck('id', 'id_externo');
        $pares = [];
        foreach ($refs as [$id, $ref]) {
            if (isset($mapa[$ref])) {
                $pares[] = [$id, (int) $mapa[$ref]];
            }
        }
        $agrupados = $agrupador->agrupar($pares);

        $this->table(
            ['novos', 'alterados', 'voltaram', 'iguais', 'rejeitados', 'agrupados'],
            [[...array_values($contagem), $agrupados]],
        );

        return self::SUCCESS;
    }

    private function json(string $html, string $id): ?array
    {
        if (! preg_match('#<script id="'.preg_quote($id, '#').'" type="application/json">(.*?)</script>#s', $html, $m)) {
            return null;
        }

        return json_decode($m[1], true);
    }

    private function coleta(string $fonteSlug): Coleta
    {
        if (! isset($this->coletas[$fonteSlug])) {
            $fonte = Fonte::firstOrCreate(['slug' => $fonteSlug], ['nome' => $fonteSlug]);
            $this->coletas[$fonteSlug] = Coleta::create([
                'fonte_id' => $fonte->id,
                'cidade_id' => Cidade::where('ibge', self::IBGE_LUCENA)->value('id'),
                'status' => 'em_andamento',
                'iniciada_em' => now(),
            ]);
        }

        return $this->coletas[$fonteSlug];
    }

    /** @return array<int, array{0: Carbon, 1: mixed}> lista [data, preço] em ordem cronológica */
    private function historico(array $i, mixed $precoAtual): array
    {
        $h = [];
        foreach ($i['priceHistory'] ?? [] as $p) {
            if ($d = $this->data($p['d'] ?? null)) {
                $h[] = [$d, $p['p'] ?? null];
            }
        }
        usort($h, fn ($a, $b) => $a[0] <=> $b[0]);

        if ($h === []) {
            $h[] = [$this->data($i['firstSeen'] ?? null) ?? now(), $precoAtual];
        }

        return $h;
    }

    private function data(?string $s): ?Carbon
    {
        return $s && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? Carbon::parse($s)->startOfDay() : null;
    }

    /** Anúncios de temporada quase sempre são casas; só muda se o título disser outra coisa. */
    private function tipoTemporada(array $i): string
    {
        $tipo = Normalizador::tipo($i['tipo'] ?? ($i['t'] ?? null));

        return $tipo === 'outro' ? 'casa' : $tipo;
    }

    private function fonteDoId(string $id): ?string
    {
        $prefixo = explode('-', $id, 2)[0];

        return self::PREFIXOS[$prefixo] ?? null;
    }

    private function base(array $i): array
    {
        $links = $i['links'] ?? [];
        $bairro = (string) ($i['locg'] ?? '');
        if (str_starts_with($bairro, 'Lucena (')) {
            $bairro = '';
        }

        return [
            'id_externo' => $i['id'],
            'titulo' => $i['t'] ?? null,
            'url' => $links[0]['u'] ?? null,
            'outros_links' => array_map(fn ($l) => ['fonte' => $l['n'] ?? null, 'url' => $l['u'] ?? null], array_slice($links, 1)),
            'cidade_ibge' => self::IBGE_LUCENA,
            'bairro' => $bairro ?: null,
            'localizacao' => $i['loc'] ?? null,
            'observacoes' => $i['dest'] ?? null,
            'duplicado_obs' => $i['dup'] ?? null,
            'quartos' => $i['q'] ?? null,
            'suites' => $i['s'] ?? null,
            'banheiros' => $i['b'] ?? null,
            'piscina' => ! empty($i['pool']) ? true : null,
            'proximo_praia' => ! empty($i['beach']) ? true : null,
        ];
    }

    /** @return array{0: array, 1: ?string} */
    private function mapearVenda(array $i): array
    {
        $areaTerreno = ($i['at'] ?? '') === 'terreno';

        return [$this->base($i) + [
            'finalidade' => 'venda',
            'tipo' => $i['tipo'] ?? null,
            'preco' => $i['p'] ?? null,
            'preco_unidade' => 'total',
            'area_construida' => $areaTerreno ? null : ($i['a'] ?? null),
            'area_terreno' => $areaTerreno ? ($i['a'] ?? null) : null,
            'vagas' => $i['v'] ?? null,
            'valor_condominio' => $i['cond'] ?? null,
            'valor_iptu' => $i['iptu'] ?? null,
        ], $this->fonteDoId($i['id'])];
    }

    /** @return array{0: array, 1: ?string} */
    private function mapearAluguel(array $i): array
    {
        $unidadeTxt = mb_strtolower((string) ($i['unit'] ?? ''));
        $mensal = ($i['cat'] ?? '') === 'Mensal';

        $unidade = match (true) {
            $mensal || $unidadeTxt === 'mês' => 'mes',
            str_contains($unidadeTxt, 'não confirmado') || ($i['cat'] ?? '') === 'A confirmar' => 'a_confirmar',
            str_contains($unidadeTxt, 'pacote') => 'pacote',
            str_contains($unidadeTxt, 'diária') => 'diaria',
            default => null,
        };

        $precoTexto = trim(($i['ptxt'] ?? '').' '.($i['unit'] ?? ''));

        return [$this->base($i) + [
            'finalidade' => $mensal ? 'aluguel' : 'temporada',
            'tipo' => $this->tipoTemporada($i),
            'preco' => $i['p'] ?? null,
            'preco_unidade' => $unidade,
            'preco_texto' => $precoTexto !== '' ? $precoTexto : null,
            'area_construida' => $i['a'] ?? null,
            'hospedes' => $i['h'] ?? null,
        ], $this->fonteDoId($i['id'])];
    }
}
