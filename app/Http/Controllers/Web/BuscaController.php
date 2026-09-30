<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Models\AnuncioEvento;
use App\Models\Cidade;
use App\Support\Normalizador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $cidade = ! empty($f['cidade']) ? Cidade::where('slug', $f['cidade'])->first() : null;

        $query = Anuncio::query()
            ->with(['cidade:id,nome,slug', 'bairro:id,nome', 'fonte:id,nome'])
            ->where('finalidade', $f['finalidade']);

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

        $anuncios = $query->paginate(24)->withQueryString();

        $ids = $anuncios->pluck('id');
        $grupos = $anuncios->pluck('grupo_id')->filter()->unique();
        $repetidos = $grupos->isEmpty() ? collect() : Anuncio::whereIn('grupo_id', $grupos)
            ->where('status', 'ativo')
            ->selectRaw('grupo_id, COUNT(*) as n')
            ->groupBy('grupo_id')
            ->pluck('n', 'grupo_id');

        $ultimaMudanca = AnuncioEvento::whereIn('anuncio_id', $ids)
            ->where('tipo', 'preco')
            ->where('ocorrido_em', '>=', now()->subDays((int) config('imobradar.dias_novo'))->toDateString())
            ->orderBy('ocorrido_em')->orderBy('id')
            ->get()
            ->keyBy('anuncio_id');

        return view('busca.index', [
            'anuncios' => $anuncios,
            'filtros' => $f,
            'cidadeAtual' => $cidade,
            'cidades' => $this->cidadesComAnuncios(),
            'bairros' => $cidade ? $cidade->bairros()->whereHas('anuncios')->orderBy('nome')->get(['id', 'nome']) : collect(),
            'tipos' => Normalizador::TIPOS,
            'ordens' => self::ORDENS,
            'repetidos' => $repetidos,
            'ultimaMudanca' => $ultimaMudanca,
            'resumo' => $this->resumo(),
        ]);
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

    private function cidadesComAnuncios()
    {
        return Cache::remember('busca:cidades', 600, fn () => Cidade::query()
            ->whereHas('anuncios', fn ($q) => $q->where('status', 'ativo'))
            ->withCount(['anuncios' => fn ($q) => $q->where('status', 'ativo')])
            ->orderBy('nome')
            ->get(['id', 'nome', 'slug']));
    }

    private function resumo(): array
    {
        return Cache::remember('busca:resumo', 600, function () {
            $desde = now()->subDays((int) config('imobradar.dias_novo'))->toDateString();

            return [
                'ativos' => Anuncio::where('status', 'ativo')->count(),
                'novos' => Anuncio::where('status', 'ativo')->where('primeira_vez_em', '>=', $desde)->count(),
                'reducoes' => AnuncioEvento::where('tipo', 'preco')->where('ocorrido_em', '>=', $desde)
                    ->whereColumn('preco_novo', '<', 'preco_anterior')->count(),
                'cidades' => Anuncio::where('status', 'ativo')->distinct()->count('cidade_id'),
                'fontes' => Anuncio::where('status', 'ativo')->distinct()->count('fonte_id'),
            ];
        });
    }
}
