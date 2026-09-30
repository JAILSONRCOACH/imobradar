<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Models\AnuncioEvento;
use App\Models\Cidade;
use App\Models\Fonte;
use App\Support\Normalizador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BuscaController extends Controller
{
    private const ORDENS = [
        'recentes' => 'Mais recentes',
        'menor_preco' => 'Menor preço',
        'maior_preco' => 'Maior preço',
        'menor_m2' => 'Menor R$/m²',
        'maior_area' => 'Maior área',
    ];

    /**
     * Praias e bairros conhecidos que as pessoas digitam no lugar da cidade.
     * slug da localidade => [slug do município, nome exibido]
     */
    public const LOCALIDADES = [
        'jacuma' => ['conde', 'Jacumã'],
        'carapibus' => ['conde', 'Carapibus'],
        'tabatinga' => ['conde', 'Tabatinga'],
        'coqueirinho' => ['conde', 'Coqueirinho'],
        'tambaba' => ['conde', 'Tambaba'],
        'gurugi' => ['conde', 'Gurugi'],
        'cabo-branco' => ['joao-pessoa', 'Cabo Branco'],
        'tambau' => ['joao-pessoa', 'Tambaú'],
        'manaira' => ['joao-pessoa', 'Manaíra'],
        'bessa' => ['joao-pessoa', 'Bessa'],
        'altiplano' => ['joao-pessoa', 'Altiplano'],
        'intermares' => ['cabedelo', 'Intermares'],
        'camboinha' => ['cabedelo', 'Camboinha'],
        'ponta-de-campina' => ['cabedelo', 'Ponta de Campina'],
        'fagundes' => ['lucena', 'Fagundes'],
        'costinha' => ['lucena', 'Costinha'],
        'camacari' => ['lucena', 'Camaçari'],
        'ponta-de-lucena' => ['lucena', 'Ponta de Lucena'],
    ];

    private const FINALIDADES = ['venda' => 'Comprar', 'aluguel' => 'Alugar', 'temporada' => 'Temporada'];

    public function index(Request $request): View
    {
        $f = $request->validate([
            'finalidade' => ['nullable', Rule::in(Normalizador::FINALIDADES)],
            'cidade' => ['nullable', 'string', 'max:140'],
            'bairro' => ['nullable', 'integer'],
            'tipo' => ['nullable', Rule::in(array_keys(Normalizador::TIPOS))],
            'preco_min' => ['nullable', 'numeric', 'min:0'],
            'preco_max' => ['nullable', 'numeric', 'min:0'],
            'quartos' => ['nullable', 'integer', 'min:1', 'max:10'],
            'piscina' => ['nullable', 'boolean'],
            'praia' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
            'ordem' => ['nullable', Rule::in(array_keys(self::ORDENS))],
            'removidos' => ['nullable', 'boolean'],
            'repetidos' => ['nullable', 'boolean'],
        ]);
        $f['finalidade'] ??= 'venda';
        $f['ordem'] ??= 'recentes';

        $cidades = $this->cidades();

        // Aceita o slug ("joao-pessoa") ou o nome digitado ("João Pessoa", "joao pessoa").
        // Também aceita praias e bairros ("Jacumã" vira Conde, filtrando pelo bairro quando ele existir).
        $cidade = null;
        $cidadeNaoEncontrada = null;
        $localidade = null;
        if (! empty($f['cidade']) && $f['cidade'] !== 'paraiba') {
            // "Fagundes (Lucena)" = praia de Lucena; "Fagundes" sozinho = o município de Fagundes.
            $entreParenteses = preg_match('/^(.*?)\s*\((.+)\)\s*$/u', $f['cidade'], $m) ? Str::slug($m[2]) : null;
            $slug = Str::slug($entreParenteses ? $m[1] : $f['cidade']);
            $pedeLocalidade = $entreParenteses && (self::LOCALIDADES[$slug][0] ?? null) === $entreParenteses;
            $cidade = $pedeLocalidade ? null : $cidades->firstWhere('slug', $slug);
            if (! $cidade && isset(self::LOCALIDADES[$slug])) {
                [$slugCidade, $localidade] = self::LOCALIDADES[$slug];
                $cidade = $cidades->firstWhere('slug', $slugCidade);
                if ($cidade && empty($f['bairro'])) {
                    $f['bairro'] = $cidade->bairros()->where('slug', $slug)->value('id');
                }
            }
            if (! $cidade) {
                $cidadeNaoEncontrada = $f['cidade'];
            }
        }

        $buscou = $request->hasAny(['cidade', 'tipo', 'q', 'preco_min', 'preco_max', 'quartos', 'bairro', 'ordem']);

        $dados = [
            'filtros' => $f,
            'cidadeAtual' => $cidade,
            'cidadeNaoEncontrada' => $cidadeNaoEncontrada,
            'localidade' => $localidade,
            'cidades' => $cidades,
            'finalidades' => self::FINALIDADES,
            'tipos' => Normalizador::TIPOS,
            'ordens' => self::ORDENS,
            'resumo' => $this->resumo(),
            'modo' => $buscou ? 'resultados' : 'inicio',
        ];

        if (! $buscou) {
            $recentes = $this->base($f['finalidade'])
                ->with(['cidade:id,nome,slug', 'bairro:id,nome', 'fonte:id,nome'])
                ->where('status', 'ativo')
                ->orderByDesc('primeira_vez_em')->orderByDesc('id')
                ->limit(8)->get();

            return view('busca.index', $dados + $this->marcadores($recentes) + ['anuncios' => $recentes, 'bairros' => collect()]);
        }

        $query = $this->base($f['finalidade'])->with(['cidade:id,nome,slug', 'bairro:id,nome', 'fonte:id,nome']);
        if ($cidadeNaoEncontrada) {
            $query->whereRaw('1 = 0');
        }
        $this->filtrar($query, $f, $cidade);

        match ($f['ordem']) {
            'menor_preco' => $query->orderByRaw('preco IS NULL')->orderBy('preco'),
            'maior_preco' => $query->orderByRaw('preco IS NULL')->orderByDesc('preco'),
            'menor_m2' => $query->orderByRaw('preco_m2 IS NULL')->orderBy('preco_m2'),
            'maior_area' => $query->orderByRaw('COALESCE(area_construida, area_terreno) IS NULL')
                ->orderByRaw('COALESCE(area_construida, area_terreno) DESC'),
            default => $query->orderByDesc('primeira_vez_em'),
        };
        $query->orderByDesc('id');

        $anuncios = $query->paginate(30)->withQueryString();

        return view('busca.index', $dados + $this->marcadores($anuncios->getCollection()) + [
            'anuncios' => $anuncios,
            'bairros' => $cidade
                ? $cidade->bairros()->whereHas('anuncios', fn ($q) => $q->where('status', 'ativo'))->orderBy('nome')->get(['id', 'nome'])
                : collect(),
        ]);
    }

    /** Anúncios da finalidade, só de fontes ativas. */
    private function base(string $finalidade): Builder
    {
        return Anuncio::query()
            ->where('finalidade', $finalidade)
            ->whereIn('fonte_id', $this->fontesAtivas());
    }

    private function fontesAtivas(): array
    {
        return Cache::remember('busca:fontes-ativas', 600, fn () => Fonte::where('ativa', true)->pluck('id')->all());
    }

    /** Selo de mudança de preço recente e quantidade de repetidos para os anúncios exibidos. */
    private function marcadores(Collection $anuncios): array
    {
        $ids = $anuncios->pluck('id');
        $grupos = $anuncios->pluck('grupo_id')->filter()->unique();

        $repetidos = $grupos->isEmpty() ? collect() : Anuncio::whereIn('grupo_id', $grupos)
            ->where('status', 'ativo')
            ->selectRaw('grupo_id, COUNT(*) as n')
            ->groupBy('grupo_id')
            ->pluck('n', 'grupo_id');

        $ultimaMudanca = $ids->isEmpty() ? collect() : AnuncioEvento::whereIn('anuncio_id', $ids)
            ->where('tipo', 'preco')
            ->where('ocorrido_em', '>=', now()->subDays((int) config('imobradar.dias_novo'))->toDateString())
            ->orderBy('ocorrido_em')->orderBy('id')
            ->get()
            ->keyBy('anuncio_id');

        return ['repetidos' => $repetidos, 'ultimaMudanca' => $ultimaMudanca];
    }

    private function filtrar(Builder $query, array $f, ?Cidade $cidade): void
    {
        if (empty($f['removidos'])) {
            $query->where('status', 'ativo');
        }
        if ($cidade) {
            $query->where('cidade_id', $cidade->id);
        }
        if (! empty($f['bairro'])) {
            $query->where('bairro_id', $f['bairro']);
        }
        if (! empty($f['tipo'])) {
            $query->where('tipo', $f['tipo']);
        }
        if (! empty($f['preco_min'])) {
            $query->where('preco', '>=', $f['preco_min']);
        }
        if (! empty($f['preco_max'])) {
            $query->where('preco', '<=', $f['preco_max']);
        }
        if (! empty($f['quartos'])) {
            $query->where('quartos', '>=', $f['quartos']);
        }
        if (! empty($f['piscina'])) {
            $query->where('piscina', true);
        }
        if (! empty($f['praia'])) {
            $query->where('proximo_praia', true);
        }
        if (! empty($f['q'])) {
            $termo = '%'.str_replace(['%', '_'], ['\%', '\_'], $f['q']).'%';
            $query->where(fn ($q) => $q->where('titulo', 'like', $termo)
                ->orWhere('localizacao_texto', 'like', $termo)
                ->orWhere('condominio_nome', 'like', $termo));
        }

        // Um anúncio por grupo de repetidos: o de menor id entre os ativos do grupo.
        if (empty($f['repetidos'])) {
            $query->where(fn ($q) => $q->whereNull('grupo_id')->orWhereRaw(
                'id = (SELECT MIN(a2.id) FROM anuncios a2 WHERE a2.grupo_id = anuncios.grupo_id AND a2.status = ?)',
                ['ativo'],
            ));
        }
    }

    /** Os 223 municípios, com a quantidade de anúncios ativos de cada um. */
    private function cidades(): Collection
    {
        return Cache::remember('busca:cidades', 600, function () {
            $fontes = $this->fontesAtivas();

            return Cidade::query()
                ->withCount(['anuncios' => fn ($q) => $q->where('status', 'ativo')->whereIn('fonte_id', $fontes)])
                ->orderBy('nome')
                ->get(['id', 'nome', 'slug']);
        });
    }

    private function resumo(): array
    {
        return Cache::remember('busca:resumo', 600, function () {
            $desde = now()->subDays((int) config('imobradar.dias_novo'))->toDateString();
            $fontes = $this->fontesAtivas();
            $ativos = Anuncio::where('status', 'ativo')->whereIn('fonte_id', $fontes);

            return [
                'ativos' => (clone $ativos)->count(),
                'novos' => (clone $ativos)->where('primeira_vez_em', '>=', $desde)->count(),
                'reducoes' => AnuncioEvento::where('tipo', 'preco')->where('ocorrido_em', '>=', $desde)
                    ->whereColumn('preco_novo', '<', 'preco_anterior')->count(),
                'cidades' => (clone $ativos)->distinct()->count('cidade_id'),
                'fontes' => (clone $ativos)->distinct()->count('fonte_id'),
            ];
        });
    }
}
