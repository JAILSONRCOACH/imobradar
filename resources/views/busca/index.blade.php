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
            <svg class="hero-radar" viewBox="0 0 400 400" aria-hidden="true">
                <defs>
                    <linearGradient id="varredura" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#0F766E" stop-opacity="0" />
                        <stop offset="1" stop-color="#14A395" stop-opacity=".55" />
                    </linearGradient>
                </defs>
                <circle cx="200" cy="200" r="190" /><circle cx="200" cy="200" r="130" /><circle cx="200" cy="200" r="70" />
                <line x1="10" y1="200" x2="390" y2="200" /><line x1="200" y1="10" x2="200" y2="390" />
                <path class="varredura" d="M200 200 L390 200 A190 190 0 0 0 334 66 Z" fill="url(#varredura)" />
                <circle class="blip" cx="268" cy="142" r="5" /><circle class="blip b2" cx="120" cy="250" r="4" /><circle class="blip b3" cx="238" cy="300" r="3.5" />
            </svg>

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

        <form method="get" action="{{ route('busca') }}" class="filtros">
            <input type="hidden" name="finalidade" value="{{ $filtros['finalidade'] }}">
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
                <label class="opcao"><input type="checkbox" name="piscina" value="1" @checked(! empty($filtros['piscina']))> Com piscina</label>
                <label class="opcao"><input type="checkbox" name="praia" value="1" @checked(! empty($filtros['praia']))> Perto da praia</label>
                <label class="opcao"><input type="checkbox" name="repetidos" value="1" @checked(! empty($filtros['repetidos']))> Mostrar repetidos</label>
                <label class="opcao"><input type="checkbox" name="removidos" value="1" @checked(! empty($filtros['removidos']))> Incluir os que saíram do ar</label>

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
