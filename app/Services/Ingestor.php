<?php

namespace App\Services;

use App\Models\Anuncio;
use App\Models\AnuncioEvento;
use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Coleta;
use App\Support\Normalizador;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Grava anúncios recebidos de uma coleta: cria, atualiza, registra mudança de preço,
 * reativa anúncios que voltaram e, ao finalizar uma coleta completa, marca os que sumiram.
 */
class Ingestor
{
    /** @var array<string, int> cache slug-da-cidade => id */
    private array $cidadesPorNome = [];

    /** @var array<int, int> cache ibge => id */
    private array $cidadesPorIbge = [];

    /** @var array<string, int> cache "cidade_id|slug" => bairro id */
    private array $bairros = [];

    /**
     * Processa um lote. Anúncios inválidos são contados em "rejeitados" e devolvidos com o motivo.
     *
     * @param  array<int, array<string, mixed>>  $itens
     * @return array{novos:int, alterados:int, voltaram:int, iguais:int, rejeitados:array<int, array{indice:int, id_externo:?string, motivo:string}>}
     */
    public function lote(Coleta $coleta, array $itens, ?Carbon $data = null): array
    {
        $data ??= now();
        $res = ['novos' => 0, 'alterados' => 0, 'voltaram' => 0, 'iguais' => 0, 'rejeitados' => []];

        DB::transaction(function () use ($coleta, $itens, $data, &$res) {
            foreach ($itens as $i => $item) {
                try {
                    $r = $this->registrar($coleta, $item, $data);
                } catch (DadoInvalido $e) {
                    $res['rejeitados'][] = [
                        'indice' => $i,
                        'id_externo' => isset($item['id_externo']) ? (string) $item['id_externo'] : null,
                        'motivo' => $e->getMessage(),
                    ];

                    continue;
                }
                $res[$r]++;
            }

            $coleta->increment('recebidos', count($itens));
            $coleta->increment('novos', $res['novos']);
            $coleta->increment('alterados', $res['alterados']);
            $coleta->increment('voltaram', $res['voltaram']);
            $coleta->increment('rejeitados', count($res['rejeitados']));
        });

        return $res;
    }

    /**
     * @return 'novos'|'alterados'|'voltaram'|'iguais'
     *
     * @throws DadoInvalido
     */
    public function registrar(Coleta $coleta, array $item, Carbon $data): string
    {
        $dados = $this->normalizar($coleta, $item);
        $dia = $data->toDateString();

        /** @var Anuncio|null $anuncio */
        $anuncio = Anuncio::query()
            ->where('fonte_id', $coleta->fonte_id)
            ->where('id_externo', $dados['id_externo'])
            ->where('finalidade', $dados['finalidade'])
            ->lockForUpdate()
            ->first();

        if (! $anuncio) {
            $anuncio = Anuncio::create($dados + [
                'fonte_id' => $coleta->fonte_id,
                'status' => 'ativo',
                'primeira_vez_em' => $dia,
                'ultima_vez_em' => $dia,
                'ultima_coleta_id' => $coleta->id,
            ]);
            $this->evento($anuncio, 'novo', null, $anuncio->preco, $dia, $coleta);

            return 'novos';
        }

        $resultado = 'iguais';

        if ($anuncio->status === 'removido') {
            $this->evento($anuncio, 'voltou', null, $dados['preco'], $dia, $coleta);
            $anuncio->status = 'ativo';
            $anuncio->removido_em = null;
            $resultado = 'voltaram';
        }

        $precoAntigo = $anuncio->preco;
        $precoNovo = $dados['preco'];
        if ($precoNovo !== null && ($precoAntigo === null || abs($precoAntigo - $precoNovo) >= 0.01)) {
            $this->evento($anuncio, 'preco', $precoAntigo, $precoNovo, $dia, $coleta);
            $resultado = $resultado === 'iguais' ? 'alterados' : $resultado;
        }

        // Não apaga dado bom com vazio: uma coleta que não trouxe o campo mantém o valor anterior.
        foreach ($dados as $campo => $valor) {
            if ($valor !== null) {
                $anuncio->{$campo} = $valor;
            }
        }

        if ($resultado === 'iguais' && $anuncio->isDirty('hash_conteudo')) {
            $resultado = 'alterados';
        }

        $anuncio->preco_m2 = Normalizador::precoM2(
            $anuncio->finalidade, $anuncio->preco, $anuncio->tipo, $anuncio->area_construida, $anuncio->area_terreno
        );
        $anuncio->ultima_vez_em = $dia;
        $anuncio->ultima_coleta_id = $coleta->id;
        $anuncio->save();

        return $resultado;
    }

    /**
     * Fecha a coleta. Se ela foi completa (varreu todo o escopo fonte+cidade+finalidade),
     * anúncios ativos desse escopo que não apareceram são marcados como removidos.
     * Coleta parcial nunca remove nada — evita sumir com anúncios por falha de raspagem.
     */
    public function finalizar(Coleta $coleta, bool $completa, ?string $erro = null, ?Carbon $data = null): Coleta
    {
        $data ??= now();
        $dia = $data->toDateString();

        DB::transaction(function () use ($coleta, $completa, $erro, $data, $dia) {
            $removidos = 0;

            if ($completa && $erro === null && $coleta->recebidos > 0) {
                $query = Anuncio::query()
                    ->where('fonte_id', $coleta->fonte_id)
                    ->where('status', 'ativo')
                    ->where(fn ($q) => $q->whereNull('ultima_coleta_id')->orWhere('ultima_coleta_id', '!=', $coleta->id));
                if ($coleta->cidade_id) {
                    $query->where('cidade_id', $coleta->cidade_id);
                }
                if ($coleta->finalidade) {
                    $query->where('finalidade', $coleta->finalidade);
                }

                $query->lockForUpdate()->chunkById(500, function ($anuncios) use ($coleta, $dia, &$removidos) {
                    foreach ($anuncios as $anuncio) {
                        $anuncio->update(['status' => 'removido', 'removido_em' => $dia]);
                        $this->evento($anuncio, 'removido', $anuncio->preco, null, $dia, $coleta);
                        $removidos++;
                    }
                });
            }

            $coleta->update([
                'status' => $erro !== null ? 'falhou' : ($completa ? 'concluida' : 'parcial'),
                'finalizada_em' => $data,
                'removidos' => $removidos,
                'erro' => $erro,
            ]);
        });

        return $coleta->refresh();
    }

    /**
     * @throws DadoInvalido
     */
    private function normalizar(Coleta $coleta, array $item): array
    {
        $idExterno = Normalizador::texto($item['id_externo'] ?? null, 191);
        if ($idExterno === null) {
            throw new DadoInvalido('id_externo ausente');
        }

        $url = trim((string) ($item['url'] ?? ''));
        if (! preg_match('#^https?://#i', $url)) {
            throw new DadoInvalido('url ausente ou inválida');
        }

        $titulo = Normalizador::texto($item['titulo'] ?? null, 500);
        if ($titulo === null) {
            throw new DadoInvalido('titulo ausente');
        }

        $finalidade = Normalizador::finalidade($item['finalidade'] ?? $coleta->finalidade);
        if ($finalidade === null) {
            throw new DadoInvalido('finalidade inválida (use venda, aluguel ou temporada)');
        }

        $informouCidade = ! empty($item['cidade_ibge']) || ! empty($item['cidade']);
        $cidadeId = $informouCidade ? $this->cidadeId($item) : $coleta->cidade_id;
        if ($cidadeId === null) {
            throw new DadoInvalido($informouCidade
                ? 'cidade não encontrada na Paraíba'
                : 'cidade ausente (envie cidade_ibge ou cidade, ou abra a coleta com uma cidade)');
        }

        $tipo = Normalizador::tipo($item['tipo'] ?? $titulo);
        $preco = Normalizador::numero($item['preco'] ?? null);
        $unidade = $item['preco_unidade'] ?? null;
        if (! in_array($unidade, Normalizador::UNIDADES_PRECO, true)) {
            $unidade = match ($finalidade) {
                'venda' => 'total',
                'aluguel' => 'mes',
                default => $preco ? 'diaria' : null,
            };
        }

        $areaConstruida = Normalizador::numero($item['area_construida'] ?? null);
        $areaTerreno = Normalizador::numero($item['area_terreno'] ?? null);
        $descricao = Normalizador::texto($item['descricao'] ?? null, 20000);

        $caracteristicas = $item['caracteristicas'] ?? null;
        $caracteristicas = is_array($caracteristicas)
            ? array_values(array_slice(array_filter(array_map(fn ($c) => Normalizador::texto($c, 80), $caracteristicas)), 0, 60))
            : null;

        $outros = $item['outros_links'] ?? null;
        $outros = is_array($outros)
            ? array_values(array_filter(array_map(function ($l) {
                $u = is_array($l) ? ($l['url'] ?? null) : $l;

                return is_string($u) && preg_match('#^https?://#i', $u)
                    ? ['fonte' => is_array($l) ? Normalizador::texto($l['fonte'] ?? null, 80) : null, 'url' => $u]
                    : null;
            }, $outros)))
            : null;

        $foto = $item['foto_url'] ?? null;
        $foto = is_string($foto) && preg_match('#^https?://#i', $foto) ? Str::limit($foto, 1000, '') : null;

        return [
            'id_externo' => $idExterno,
            'finalidade' => $finalidade,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'observacoes' => Normalizador::texto($item['observacoes'] ?? null, 500),
            'url' => $url,
            'outros_links' => $outros ?: null,
            'foto_url' => $foto,
            'cidade_id' => $cidadeId,
            'bairro_id' => $this->bairroId($cidadeId, $item['bairro'] ?? null),
            'localizacao_texto' => Normalizador::texto($item['localizacao'] ?? null, 255),
            'endereco' => Normalizador::texto($item['endereco'] ?? null, 255),
            'condominio_nome' => Normalizador::texto($item['condominio_nome'] ?? null, 150),
            'lat' => $this->coordenada($item['lat'] ?? null, -90, 90),
            'lng' => $this->coordenada($item['lng'] ?? null, -180, 180),
            'preco' => $preco,
            'preco_unidade' => $unidade,
            'preco_texto' => Normalizador::texto($item['preco_texto'] ?? null, 120),
            'valor_condominio' => Normalizador::valorAcessorio($item['valor_condominio'] ?? null),
            'valor_iptu' => Normalizador::valorAcessorio($item['valor_iptu'] ?? null),
            'area_construida' => $areaConstruida,
            'area_terreno' => $areaTerreno,
            'preco_m2' => Normalizador::precoM2($finalidade, $preco, $tipo, $areaConstruida, $areaTerreno),
            'quartos' => Normalizador::inteiro($item['quartos'] ?? null),
            'suites' => Normalizador::inteiro($item['suites'] ?? null),
            'banheiros' => Normalizador::inteiro($item['banheiros'] ?? null),
            'vagas' => Normalizador::inteiro($item['vagas'] ?? null),
            'hospedes' => Normalizador::inteiro($item['hospedes'] ?? null),
            'piscina' => Normalizador::booleano($item['piscina'] ?? null),
            'proximo_praia' => Normalizador::booleano($item['proximo_praia'] ?? null),
            'caracteristicas' => $caracteristicas ?: null,
            'duplicado_obs' => Normalizador::texto($item['duplicado_obs'] ?? null, 255),
            'hash_conteudo' => sha1($titulo.'|'.$descricao),
        ];
    }

    private function cidadeId(array $item): ?int
    {
        if (! empty($item['cidade_ibge'])) {
            $ibge = (int) $item['cidade_ibge'];

            return $this->cidadesPorIbge[$ibge] ??= Cidade::where('ibge', $ibge)->value('id');
        }
        if (! empty($item['cidade'])) {
            $slug = Str::slug((string) $item['cidade']);

            return $this->cidadesPorNome[$slug] ??= Cidade::where('slug', $slug)->value('id');
        }

        return null;
    }

    private function bairroId(int $cidadeId, mixed $nome): ?int
    {
        $nome = Normalizador::texto($nome, 120);
        if ($nome === null) {
            return null;
        }
        $slug = Str::slug($nome);
        if ($slug === '') {
            return null;
        }

        return $this->bairros[$cidadeId.'|'.$slug] ??= Bairro::firstOrCreate(
            ['cidade_id' => $cidadeId, 'slug' => $slug],
            ['nome' => $nome],
        )->id;
    }

    private function coordenada(mixed $v, float $min, float $max): ?float
    {
        if (! is_numeric($v)) {
            return null;
        }
        $f = (float) $v;

        return $f >= $min && $f <= $max && $f != 0.0 ? $f : null;
    }

    private function evento(Anuncio $anuncio, string $tipo, ?float $de, ?float $para, string $dia, ?Coleta $coleta): void
    {
        AnuncioEvento::create([
            'anuncio_id' => $anuncio->id,
            'tipo' => $tipo,
            'preco_anterior' => $de,
            'preco_novo' => $para,
            'ocorrido_em' => $dia,
            'coleta_id' => $coleta?->id,
        ]);
    }
}
