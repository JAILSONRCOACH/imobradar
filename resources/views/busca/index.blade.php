@use('App\Support\Formata')
@extends('layouts.app')

@php
    $acao = ['venda' => 'Comprar', 'aluguel' => 'Alugar', 'temporada' => 'Temporada'][$filtros['finalidade']];
    $lugar = $cidadeAtual ? ($localidade ? $localidade.', '.$cidadeAtual->nome : $cidadeAtual->nome) : ($cidadeNaoEncontrada ?: 'toda a Paraíba');
    $comAnuncios = $cidades->where('anuncios_count', '>', 0)->sortByDesc('anuncios_count')->values();
    $porSlug = $cidades->keyBy('slug');
    $listaCidades = $cidades->map(fn ($c) => ['n' => $c->nome, 's' => $c->slug, 'c' => $c->anuncios_count])
        ->concat(collect(\App\Http\Controllers\Web\BuscaController::LOCALIDADES)->map(fn ($l, $slug) => [
            'n' => $l[1].' ('.($porSlug[$l[0]]->nome ?? '').')', 's' => $slug, 'c' => $porSlug[$l[0]]->anuncios_count ?? 0,
        ]))
        ->values();
@endphp

@section('titulo', $modo === 'inicio' ? 'Imóveis na Paraíba' : $acao.' imóveis em '.$lugar)
@section('corpo', $modo === 'inicio' ? 'pagina-inicio' : 'pagina-resultados')

@if ($modo === 'resultados')
    @section('busca-topo')
        <form method="get" action="{{ route('busca') }}" class="busca-compacta" role="search">
            <label class="sr" for="finalidade-topo">Finalidade</label>
            <select id="finalidade-topo" name="finalidade">
                @foreach ($finalidades as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected($filtros['finalidade'] === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
            @include('partials.campo-cidade', ['id' => 'cidade-topo', 'valor' => $cidadeAtual?->nome ?? $cidadeNaoEncontrada, 'placeholder' => 'Cidade da Paraíba'])
            <button type="submit">Buscar</button>
        </form>
    @endsection
@endif

@section('conteudo')
<script type="application/json" id="lista-cidades">@json($listaCidades)</script>

@if ($modo === 'inicio')
    <section class="hero">
        <div class="hero-in">
            <img class="hero-foto" src="{{ \App\Support\Foto::paisagem(0) }}" alt="" fetchpriority="high" referrerpolicy="no-referrer" onerror="this.remove()">

            <h1>Todos os imóveis da Paraíba numa busca só.</h1>
            <p class="hero-texto">Juntamos anúncios de portais, imobiliárias e classificados, tiramos os repetidos e guardamos o histórico de preço de cada um.</p>

            <form method="get" action="{{ route('busca') }}" class="busca-grande" role="search">
                <fieldset class="finalidade">
                    <legend class="sr">O que você procura</legend>
                    @foreach ($finalidades as $valor => $rotulo)
                        <label>
                            <input type="radio" name="finalidade" value="{{ $valor }}" @checked($filtros['finalidade'] === $valor)>
                            <span>{{ $rotulo }}</span>
                        </label>
                    @endforeach
                </fieldset>
                <div class="busca-linha">
                    @include('partials.campo-cidade', ['id' => 'cidade-inicio', 'valor' => ''])
                    <button type="submit">Buscar imóveis</button>
                </div>
            </form>

            <p class="hero-status">
                Hoje: {{ Formata::numero($resumo['ativos']) }} anúncios ativos,
                {{ Formata::numero($resumo['novos']) }} novos e
                {{ Formata::numero($resumo['reducoes']) }} {{ $resumo['reducoes'] === 1 ? 'redução' : 'reduções' }} de preço na última semana.
            </p>
        </div>
    </section>

    <div class="pagina">
        <section class="bloco-cidades" aria-labelledby="t-cidades">
            <h2 id="t-cidades">Cidades com anúncios no radar</h2>
            @if ($comAnuncios->isEmpty())
                <p class="nota">Nenhuma cidade com anúncios ainda.</p>
            @else
                <ul class="cidades-ativas">
                    @foreach ($comAnuncios as $c)
                        <li><a href="{{ route('busca', ['finalidade' => $filtros['finalidade'], 'cidade' => $c->slug]) }}">
                            <span class="cidade-nome">{{ $c->nome }}</span>
                            <span class="cidade-qtd">{{ Formata::numero($c->anuncios_count) }} {{ $c->anuncios_count === 1 ? 'anúncio' : 'anúncios' }}</span>
                        </a></li>
                    @endforeach
                </ul>
            @endif

            <details class="todas-cidades">
                <summary>Ver os 223 municípios da Paraíba</summary>
                <ul>
                    @foreach ($cidades as $c)
                        <li class="{{ $c->anuncios_count ? '' : 'vazia' }}">
                            <a href="{{ route('busca', ['finalidade' => $filtros['finalidade'], 'cidade' => $c->slug]) }}">{{ $c->nome }}</a>
                            @if ($c->anuncios_count) <span>{{ $c->anuncios_count }}</span> @endif
                        </li>
                    @endforeach
                </ul>
            </details>
        </section>

        @if ($anuncios->isNotEmpty())
            <section aria-labelledby="t-recentes">
                <div class="titulo-secao">
                    <h2 id="t-recentes">Entraram no radar por último</h2>
                    <a href="{{ route('busca', ['finalidade' => $filtros['finalidade'], 'cidade' => 'paraiba', 'ordem' => 'recentes']) }}">Ver todos</a>
                </div>
                <ul class="lista">
                    @foreach ($anuncios as $a)
                        @include('partials.linha', ['a' => $a])
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@else
    <div class="pagina">
        <header class="cabecalho-resultados">
            <h1>{{ $acao }} em {{ $lugar }}</h1>
            <p>{{ Formata::numero($anuncios->total()) }} {{ $anuncios->total() === 1 ? 'imóvel encontrado' : 'imóveis encontrados' }}@if (empty($filtros['repetidos']) && $anuncios->total()), sem contar os repetidos entre sites @endif</p>
        </header>

        @if ($panorama && $panorama['total'])
            @php
                $pg = $panorama['grupos'];
                $rotuloTotal = ['venda' => 'À venda', 'aluguel' => 'Para alugar', 'temporada' => 'Para temporada'][$filtros['finalidade']];
                $partes = collect($grupos)->map(fn ($g, $k) => $pg[$k]['total'] ? $pg[$k]['total'].' '.mb_strtolower($g['rotulo']) : null)->filter()->implode(', ');
                $unidadeMediana = $filtros['finalidade'] === 'venda' ? '' : ($filtros['finalidade'] === 'aluguel' ? ' por mês' : ' por diária');
                $alternar = fn (string $chave) => request()->fullUrlWithQuery([$chave => empty($filtros[$chave]) ? 1 : null, 'page' => null]);
            @endphp
            <section class="panorama" aria-label="Resumo de {{ $lugar }}">
                <div class="numero">
                    <h2>{{ $rotuloTotal }}</h2>
                    <strong>{{ Formata::numero($panorama['total']) }}</strong>
                    <p>{{ $partes }}</p>
                </div>
                <div class="numero">
                    <h2>Casa mediana</h2>
                    <strong>{{ $pg['casas']['mediana'] ? Formata::moeda($pg['casas']['mediana'], true) : 'sem dados' }}</strong>
                    <p>{{ $pg['casas']['com_preco'] }} {{ $pg['casas']['com_preco'] === 1 ? 'casa' : 'casas' }} com preço{{ $unidadeMediana }}</p>
                </div>
                <div class="numero">
                    <h2>Terreno mediano</h2>
                    <strong>{{ $pg['terrenos']['mediana'] ? Formata::moeda($pg['terrenos']['mediana'], true) : 'sem dados' }}</strong>
                    <p>@if ($pg['terrenos']['mediana_m2'])R$ {{ Formata::numero($pg['terrenos']['mediana_m2']) }}/m², @endif @if ($pg['terrenos']['maximo']) até {{ Formata::moeda($pg['terrenos']['maximo'], true) }} @else {{ $pg['terrenos']['com_preco'] }} com preço @endif</p>
                </div>
                <div class="numero">
                    <h2>Apto ou flat</h2>
                    <strong>{{ $pg['aptos']['mediana'] ? Formata::moeda($pg['aptos']['mediana'], true) : 'sem dados' }}</strong>
                    <p>{{ $pg['aptos']['com_preco'] }} com preço{{ $unidadeMediana }}</p>
                </div>
            </section>

            <nav class="chips" aria-label="Tipo de imóvel">
                <a href="{{ request()->fullUrlWithQuery(['grupo' => null, 'tipo' => null, 'page' => null]) }}" @if (empty($filtros['grupo'])) aria-current="true" @endif>Todos <span>{{ $panorama['total'] }}</span></a>
                @foreach ($grupos as $chave => $g)
                    @if ($pg[$chave]['total'])
                        <a href="{{ request()->fullUrlWithQuery(['grupo' => $chave, 'tipo' => null, 'page' => null]) }}" @if (($filtros['grupo'] ?? '') === $chave) aria-current="true" @endif>{{ $g['rotulo'] }} <span>{{ $pg[$chave]['total'] }}</span></a>
                    @endif
                @endforeach
            </nav>
            <nav class="chips chips-leves" aria-label="Filtros rápidos">
                @if ($panorama['m2PorTipo'])
                    <a href="{{ $alternar('abaixo') }}" @if (! empty($filtros['abaixo'])) aria-current="true" @endif title="R$/m² abaixo da mediana de imóveis do mesmo tipo">Abaixo da mediana</a>
                @endif
                <a href="{{ $alternar('piscina') }}" @if (! empty($filtros['piscina'])) aria-current="true" @endif>Piscina</a>
                <a href="{{ $alternar('praia') }}" @if (! empty($filtros['praia'])) aria-current="true" @endif>Beira-mar</a>
                <a href="{{ $alternar('novidades') }}" @if (! empty($filtros['novidades'])) aria-current="true" @endif>Novos ou com preço alterado <span>{{ $panorama['novidades'] }}</span></a>
                <a href="{{ $alternar('repetidos') }}" @if (! empty($filtros['repetidos'])) aria-current="true" @endif>Mostrar repetidos</a>
                <a href="{{ $alternar('removidos') }}" @if (! empty($filtros['removidos'])) aria-current="true" @endif>Mostrar os que saíram</a>
            </nav>
        @endif

        <form method="get" action="{{ route('busca') }}" class="filtros">
            <input type="hidden" name="finalidade" value="{{ $filtros['finalidade'] }}">
            @foreach (['grupo', 'abaixo', 'novidades', 'piscina', 'praia', 'repetidos', 'removidos'] as $chip)
                @if (! empty($filtros[$chip])) <input type="hidden" name="{{ $chip }}" value="{{ $filtros[$chip] }}"> @endif
            @endforeach
            <input type="hidden" name="cidade" value="{{ $cidadeAtual?->slug ?? ($cidadeNaoEncontrada ?: 'paraiba') }}">

            <div class="filtros-linha">
                <label class="filtro">
                    <span>Tipo</span>
                    <select name="tipo">
                        <option value="">Todos</option>
                        @foreach ($tipos as $slug => $rotulo)
                            <option value="{{ $slug }}" @selected(($filtros['tipo'] ?? '') === $slug)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </label>

                @if ($bairros->isNotEmpty())
                    <label class="filtro">
                        <span>Bairro ou praia</span>
                        <select name="bairro">
                            <option value="">Todos</option>
                            @foreach ($bairros as $b)
                                <option value="{{ $b->id }}" @selected((int) ($filtros['bairro'] ?? 0) === $b->id)>{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="filtro filtro-curto">
                    <span>Preço mínimo</span>
                    <input type="number" name="preco_min" min="0" step="1000" inputmode="numeric" value="{{ $filtros['preco_min'] ?? '' }}" placeholder="R$">
                </label>
                <label class="filtro filtro-curto">
                    <span>Preço máximo</span>
                    <input type="number" name="preco_max" min="0" step="1000" inputmode="numeric" value="{{ $filtros['preco_max'] ?? '' }}" placeholder="R$">
                </label>
                <label class="filtro filtro-curto">
                    <span>Quartos</span>
                    <select name="quartos">
                        <option value="">Qualquer</option>
                        @foreach ([1, 2, 3, 4, 5] as $n)
                            <option value="{{ $n }}" @selected((int) ($filtros['quartos'] ?? 0) === $n)>{{ $n }} ou mais</option>
                        @endforeach
                    </select>
                </label>
                <label class="filtro filtro-largo">
                    <span>Palavra-chave</span>
                    <input type="search" name="q" maxlength="100" value="{{ $filtros['q'] ?? '' }}" placeholder="Condomínio, rua, praia">
                </label>
            </div>

            <div class="filtros-linha filtros-extra">

                <label class="ordem">
                    <span>Ordenar por</span>
                    <select name="ordem">
                        @foreach ($ordens as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected($filtros['ordem'] === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="botao">Aplicar filtros</button>
            </div>
        </form>

        @if ($anuncios->isEmpty())
            <div class="vazio">
                @if ($cidadeNaoEncontrada)
                    <h2>Não achamos a cidade “{{ $cidadeNaoEncontrada }}” na Paraíba.</h2>
                    <p>Confira a grafia ou escolha na lista de municípios.</p>
                @elseif ($cidadeAtual && ! $cidadeAtual->anuncios_count)
                    <h2>{{ $cidadeAtual->nome }} ainda não entrou no radar.</h2>
                    <p>Os anúncios desta cidade ainda não estão sendo coletados. Enquanto isso, veja as cidades que já têm anúncios:</p>
                    <ul class="cidades-ativas compacta">
                        @foreach ($comAnuncios->take(6) as $c)
                            <li><a href="{{ route('busca', ['finalidade' => $filtros['finalidade'], 'cidade' => $c->slug]) }}"><span class="cidade-nome">{{ $c->nome }}</span><span class="cidade-qtd">{{ $c->anuncios_count }} anúncios</span></a></li>
                        @endforeach
                    </ul>
                @else
                    <h2>Nenhum imóvel com esses filtros.</h2>
                    <p><a href="{{ route('busca', ['finalidade' => $filtros['finalidade'], 'cidade' => $cidadeAtual?->slug ?? 'paraiba']) }}">Limpar os filtros</a> e ver tudo em {{ $lugar }}.</p>
                @endif
            </div>
        @else
            <ul class="lista">
                @foreach ($anuncios as $a)
                    @include('partials.linha', ['a' => $a])
                @endforeach
            </ul>
            {{ $anuncios->links('partials.paginacao') }}
        @endif
    </div>
@endif
@endsection
