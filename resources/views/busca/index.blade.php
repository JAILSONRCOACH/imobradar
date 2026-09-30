@use('App\Support\Formata')
@extends('layouts.app')

@section('titulo', match ($filtros['finalidade']) { 'aluguel' => 'Aluguel', 'temporada' => 'Temporada', default => 'Venda' }.($cidadeAtual ? ' em '.$cidadeAtual->nome : ''))

@section('conteudo')
<section class="hero">
    <h1>Imóveis na Paraíba, de todos os portais, num lugar só.</h1>
    <p class="hero-sub">
        {{ Formata::numero($resumo['ativos']) }} anúncios ativos de {{ $resumo['fontes'] }} fontes
        · {{ Formata::numero($resumo['novos']) }} novos e {{ Formata::numero($resumo['reducoes']) }} reduções de preço nos últimos {{ config('imobradar.dias_novo') }} dias
    </p>
</section>

<form method="get" action="{{ route('busca') }}" class="filtros" id="filtros">
    <div class="abas" role="tablist">
        @foreach (['venda' => 'Comprar', 'aluguel' => 'Alugar', 'temporada' => 'Temporada'] as $valor => $rotulo)
            <label class="aba {{ $filtros['finalidade'] === $valor ? 'ativa' : '' }}">
                <input type="radio" name="finalidade" value="{{ $valor }}" @checked($filtros['finalidade'] === $valor) onchange="this.form.submit()">
                {{ $rotulo }}
            </label>
        @endforeach
    </div>

    <div class="campos">
        <label class="campo">
            <span>Cidade</span>
            <select name="cidade" onchange="this.form.bairro && (this.form.bairro.value=''); this.form.submit()">
                <option value="">Toda a Paraíba</option>
                @foreach ($cidades as $c)
                    <option value="{{ $c->slug }}" @selected($cidadeAtual?->id === $c->id)>{{ $c->nome }} ({{ $c->anuncios_count }})</option>
                @endforeach
            </select>
        </label>

        @if ($bairros->isNotEmpty())
            <label class="campo">
                <span>Bairro / praia</span>
                <select name="bairro">
                    <option value="">Todos</option>
                    @foreach ($bairros as $b)
                        <option value="{{ $b->id }}" @selected((int) ($filtros['bairro'] ?? 0) === $b->id)>{{ $b->nome }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <label class="campo">
            <span>Tipo</span>
            <select name="tipo">
                <option value="">Todos</option>
                @foreach ($tipos as $slug => $rotulo)
                    <option value="{{ $slug }}" @selected(($filtros['tipo'] ?? '') === $slug)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </label>

        <label class="campo campo-curto">
            <span>Preço mín.</span>
            <input type="number" name="preco_min" min="0" step="1000" inputmode="numeric" value="{{ $filtros['preco_min'] ?? '' }}" placeholder="R$">
        </label>
        <label class="campo campo-curto">
            <span>Preço máx.</span>
            <input type="number" name="preco_max" min="0" step="1000" inputmode="numeric" value="{{ $filtros['preco_max'] ?? '' }}" placeholder="R$">
        </label>

        <label class="campo campo-curto">
            <span>Quartos</span>
            <select name="quartos">
                <option value="">Qualquer</option>
                @foreach ([1, 2, 3, 4, 5] as $n)
                    <option value="{{ $n }}" @selected((int) ($filtros['quartos'] ?? 0) === $n)>{{ $n }}+</option>
                @endforeach
            </select>
        </label>

        <label class="campo campo-largo">
            <span>Palavra-chave</span>
            <input type="search" name="q" maxlength="100" value="{{ $filtros['q'] ?? '' }}" placeholder="condomínio, rua, praia…">
        </label>
    </div>

    <div class="linha-opcoes">
        <label class="check"><input type="checkbox" name="piscina" value="1" @checked(! empty($filtros['piscina']))> Com piscina</label>
        <label class="check"><input type="checkbox" name="praia" value="1" @checked(! empty($filtros['praia']))> Perto da praia</label>
        <label class="check"><input type="checkbox" name="repetidos" value="1" @checked(! empty($filtros['repetidos']))> Mostrar repetidos</label>
        <label class="check"><input type="checkbox" name="removidos" value="1" @checked(! empty($filtros['removidos']))> Incluir os que saíram do ar</label>

        <label class="campo-ordem">
            <span>Ordenar</span>
            <select name="ordem" onchange="this.form.submit()">
                @foreach ($ordens as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected($filtros['ordem'] === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="botao">Buscar</button>
    </div>
</form>

<p class="contagem">
    {{ Formata::numero($anuncios->total()) }} {{ $anuncios->total() === 1 ? 'resultado' : 'resultados' }}
    @if (empty($filtros['repetidos'])) <span class="dica">· repetidos entre portais agrupados</span> @endif
</p>

@if ($anuncios->isEmpty())
    <div class="vazio">
        <p>Nenhum imóvel com esses filtros.</p>
        <a href="{{ route('busca', ['finalidade' => $filtros['finalidade']]) }}">Limpar filtros</a>
    </div>
@else
    <ul class="grade">
        @foreach ($anuncios as $a)
            @php
                $mud = $ultimaMudanca[$a->id] ?? null;
                $rep = $a->grupo_id ? max(0, ($repetidos[$a->grupo_id] ?? 1) - 1) : 0;
                $area = $a->area_construida ?: $a->area_terreno;
            @endphp
            <li class="cartao {{ $a->status === 'removido' ? 'saiu' : '' }}">
                <a href="{{ route('anuncio.show', $a) }}" class="cartao-link">
                    <div class="cartao-topo">
                        <span class="tipo">{{ $tipos[$a->tipo] ?? 'Imóvel' }}</span>
                        <span class="selos">
                            @if ($a->status === 'removido') <span class="selo selo-saiu">Saiu do ar</span>
                            @elseif ($a->isNovo()) <span class="selo selo-novo">Novo</span> @endif
                            @if ($mud && $mud->preco_anterior && $mud->preco_novo < $mud->preco_anterior)
                                <span class="selo selo-caiu">Preço caiu {{ Formata::variacao($mud->preco_anterior, $mud->preco_novo) }}</span>
                            @elseif ($mud && $mud->preco_anterior && $mud->preco_novo > $mud->preco_anterior)
                                <span class="selo selo-subiu">Preço subiu</span>
                            @endif
                        </span>
                    </div>
                    <h2 class="titulo">{{ $a->titulo }}</h2>
                    <p class="local">{{ $a->bairro?->nome ? $a->bairro->nome.' · ' : '' }}{{ $a->cidade->nome }}</p>
                    <p class="preco">{{ Formata::preco($a->preco, $a->finalidade === 'venda' ? null : $a->preco_unidade) }}</p>
                    <ul class="specs">
                        @if ($area) <li>{{ Formata::numero($area) }} m²{{ ! $a->area_construida ? ' terreno' : '' }}</li> @endif
                        @if ($a->quartos) <li>{{ $a->quartos }} {{ $a->quartos === 1 ? 'quarto' : 'quartos' }}</li> @endif
                        @if ($a->suites) <li>{{ $a->suites }} {{ $a->suites === 1 ? 'suíte' : 'suítes' }}</li> @endif
                        @if ($a->vagas) <li>{{ $a->vagas }} {{ $a->vagas === 1 ? 'vaga' : 'vagas' }}</li> @endif
                        @if ($a->hospedes) <li>{{ $a->hospedes }} hóspedes</li> @endif
                        @if ($a->piscina) <li>piscina</li> @endif
                        @if ($a->preco_m2) <li>{{ Formata::moeda($a->preco_m2) }}/m²</li> @endif
                    </ul>
                    <p class="fonte">
                        {{ $a->fonte->nome }}
                        @if ($rep > 0) <span class="rep">+{{ $rep }} {{ $rep === 1 ? 'outro anúncio' : 'outros anúncios' }}</span> @endif
                        <span class="desde">desde {{ $a->primeira_vez_em->format('d/m') }}</span>
                    </p>
                </a>
            </li>
        @endforeach
    </ul>

    {{ $anuncios->links('partials.paginacao') }}
@endif
@endsection
