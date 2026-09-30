@use('App\Support\Formata')
@use('App\Support\Normalizador')
@extends('layouts.app')

@section('titulo', $anuncio->titulo)
@section('descricao', ($anuncio->bairro?->nome ? $anuncio->bairro->nome.', ' : '').$anuncio->cidade->nome.' — '.Formata::preco($anuncio->preco, $anuncio->finalidade === 'venda' ? null : $anuncio->preco_unidade).'. Anúncio encontrado em '.$anuncio->fonte->nome.'.')

@section('conteudo')
@php
    $tipos = Normalizador::TIPOS;
    $unidade = $anuncio->finalidade === 'venda' ? null : $anuncio->preco_unidade;
    $precos = $anuncio->eventos->whereIn('tipo', ['novo', 'preco', 'voltou'])->filter(fn ($e) => $e->preco_novo !== null)->values();
    $primeiro = $precos->first()?->preco_novo;
    $diferenca = ($primeiro && $anuncio->preco) ? $anuncio->preco - $primeiro : null;
@endphp

<nav class="migalhas">
    <a href="{{ route('busca', ['finalidade' => $anuncio->finalidade, 'cidade' => $anuncio->cidade->slug]) }}">‹ {{ $anuncio->cidade->nome }}</a>
</nav>

<article class="ficha">
    <header class="ficha-cab">
        <p class="tipo">{{ $tipos[$anuncio->tipo] ?? 'Imóvel' }} · {{ ['venda' => 'Venda', 'aluguel' => 'Aluguel mensal', 'temporada' => 'Temporada'][$anuncio->finalidade] }}</p>
        <h1>{{ $anuncio->titulo }}</h1>
        <p class="local">{{ collect([$anuncio->endereco, $anuncio->bairro?->nome, $anuncio->cidade->nome.'/PB'])->filter()->implode(' · ') }}</p>
        @if ($anuncio->status === 'removido')
            <p class="aviso">Este anúncio saiu do ar em {{ $anuncio->removido_em?->format('d/m/Y') }}. As informações abaixo são as últimas encontradas.</p>
        @endif
    </header>

    <div class="ficha-corpo">
        <section class="bloco bloco-preco">
            <p class="preco-grande">{{ Formata::preco($anuncio->preco, $unidade) }}</p>
            @if ($anuncio->preco_texto && $anuncio->finalidade !== 'venda')
                <p class="miudo">Como aparece no anúncio: {{ $anuncio->preco_texto }}</p>
            @endif
            @if ($anuncio->preco_m2)
                <p><strong>{{ Formata::moeda($anuncio->preco_m2) }}/m²</strong>
                    @if ($mediana)
                        <span class="miudo">· mediana de {{ $tipos[$anuncio->tipo] ?? 'imóveis' }} em {{ $anuncio->cidade->nome }}: {{ Formata::moeda($mediana) }}/m² ({{ $amostra }} anúncios)</span>
                    @endif
                </p>
                @if ($mediana)
                    @php $dif = ($anuncio->preco_m2 - $mediana) / $mediana * 100; @endphp
                    <p class="comparativo {{ $dif < -10 ? 'abaixo' : ($dif > 10 ? 'acima' : '') }}">
                        {{ number_format(abs($dif), 0, ',', '.') }}% {{ $dif < 0 ? 'abaixo' : 'acima' }} da mediana dos anúncios semelhantes.
                        <span class="miudo">Comparação só entre anúncios; não é avaliação do imóvel.</span>
                    </p>
                @endif
            @endif
            <dl class="acessorios">
                @if ($anuncio->valor_condominio) <div><dt>Condomínio</dt><dd>{{ Formata::moeda($anuncio->valor_condominio) }}</dd></div> @endif
                @if ($anuncio->valor_iptu) <div><dt>IPTU</dt><dd>{{ Formata::moeda($anuncio->valor_iptu) }}</dd></div> @endif
            </dl>
            <a class="botao botao-bloco" href="{{ $anuncio->url }}" target="_blank" rel="noopener nofollow">Ver anúncio original em {{ $anuncio->fonte->nome }} ↗</a>
            @foreach ($anuncio->outros_links ?? [] as $l)
                <a class="link-extra" href="{{ $l['url'] }}" target="_blank" rel="noopener nofollow">Também em {{ $l['fonte'] ?? parse_url($l['url'], PHP_URL_HOST) }} ↗</a>
            @endforeach
        </section>

        <section class="bloco">
            <h2>Características</h2>
            <dl class="grade-dados">
                @php
                    $dados = [
                        'Área construída' => $anuncio->area_construida ? Formata::numero($anuncio->area_construida).' m²' : null,
                        'Área do terreno' => $anuncio->area_terreno ? Formata::numero($anuncio->area_terreno).' m²' : null,
                        'Quartos' => $anuncio->quartos,
                        'Suítes' => $anuncio->suites,
                        'Banheiros' => $anuncio->banheiros,
                        'Vagas' => $anuncio->vagas,
                        'Hóspedes' => $anuncio->hospedes,
                        'Piscina' => $anuncio->piscina ? 'Sim' : null,
                        'Perto da praia' => $anuncio->proximo_praia ? 'Sim' : null,
                        'Condomínio' => $anuncio->condominio_nome,
                    ];
                @endphp
                @foreach (array_filter($dados, fn ($v) => $v !== null && $v !== '') as $rotulo => $valor)
                    <div><dt>{{ $rotulo }}</dt><dd>{{ $valor }}</dd></div>
                @endforeach
            </dl>
            @if ($anuncio->caracteristicas)
                <ul class="tags">@foreach ($anuncio->caracteristicas as $c)<li>{{ $c }}</li>@endforeach</ul>
            @endif
            @if ($anuncio->observacoes)
                <p class="obs">{{ $anuncio->observacoes }}</p>
            @endif
            @if ($anuncio->descricao)
                <h3>Descrição do anunciante</h3>
                <p class="descricao">{{ $anuncio->descricao }}</p>
            @endif
        </section>

        <section class="bloco">
            <h2>Histórico no radar</h2>
            <p class="miudo">No radar desde {{ $anuncio->primeira_vez_em->format('d/m/Y') }} · última verificação em {{ $anuncio->ultima_vez_em->format('d/m/Y') }}</p>
            @if ($diferenca)
                <p class="{{ $diferenca < 0 ? 'caiu' : 'subiu' }}">
                    {{ $diferenca < 0 ? 'Baixou' : 'Subiu' }} {{ Formata::moeda(abs($diferenca)) }} ({{ Formata::variacao($primeiro, $anuncio->preco) }}) desde que entrou no radar.
                </p>
            @endif
            <table class="historico">
                <thead><tr><th>Data</th><th>Evento</th><th>Preço</th></tr></thead>
                <tbody>
                @foreach ($anuncio->eventos as $e)
                    <tr>
                        <td>{{ $e->ocorrido_em->format('d/m/Y') }}</td>
                        <td>{{ ['novo' => 'Entrou no radar', 'preco' => 'Mudou de preço', 'removido' => 'Saiu do ar', 'voltou' => 'Voltou ao ar'][$e->tipo] ?? $e->tipo }}</td>
                        <td>
                            @if ($e->tipo === 'preco' && $e->preco_anterior)
                                {{ Formata::moeda($e->preco_anterior) }} → {{ Formata::moeda($e->preco_novo) }}
                            @elseif ($e->preco_novo)
                                {{ Formata::moeda($e->preco_novo) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        @if ($duplicados->isNotEmpty())
            <section class="bloco">
                <h2>Provavelmente o mesmo imóvel em outros anúncios</h2>
                <ul class="duplicados">
                    @foreach ($duplicados as $d)
                        <li>
                            <a href="{{ route('anuncio.show', $d) }}">{{ $d->fonte->nome }} — {{ Formata::preco($d->preco, $d->finalidade === 'venda' ? null : $d->preco_unidade) }}</a>
                            @if ($d->status === 'removido') <span class="miudo">(saiu do ar)</span> @endif
                        </li>
                    @endforeach
                </ul>
                @if ($anuncio->duplicado_obs) <p class="miudo">{{ $anuncio->duplicado_obs }}</p> @endif
            </section>
        @endif
    </div>
</article>
@endsection
