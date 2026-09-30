@php
    $mud = $ultimaMudanca[$a->id] ?? null;
    $rep = $a->grupo_id ? max(0, ($repetidos[$a->grupo_id] ?? 1) - 1) : 0;
    $unidade = $a->finalidade === 'venda' ? null : $a->preco_unidade;
    $specs = array_filter([
        $a->area_construida ? \App\Support\Formata::numero($a->area_construida).' m²' : null,
        ! $a->area_construida && $a->area_terreno ? \App\Support\Formata::numero($a->area_terreno).' m² de terreno' : null,
        $a->quartos ? $a->quartos.($a->quartos === 1 ? ' quarto' : ' quartos') : null,
        $a->suites ? $a->suites.($a->suites === 1 ? ' suíte' : ' suítes') : null,
        $a->vagas ? $a->vagas.($a->vagas === 1 ? ' vaga' : ' vagas') : null,
        $a->hospedes ? $a->hospedes.' hóspedes' : null,
        $a->piscina ? 'piscina' : null,
    ]);
@endphp
<li class="linha {{ $a->status === 'removido' ? 'saiu' : '' }}">
    <a href="{{ route('anuncio.show', $a) }}" class="linha-link">
        <div class="linha-foto">
            @if ($a->foto_url)
                <img src="{{ $a->foto_url }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()">
            @endif
            @include('partials.foto-vazia', ['tipo' => $a->tipo])
        </div>
        <div class="linha-corpo">
            <p class="linha-marcas">
                <span class="linha-tipo">{{ $tipos[$a->tipo] ?? 'Imóvel' }}</span>
                @if ($a->status === 'removido')
                    <span class="marca-saiu">Saiu do ar</span>
                @elseif ($a->isNovo())
                    <span class="marca-novo">Novo</span>
                @endif
                @if ($mud && $mud->preco_anterior && $mud->preco_novo < $mud->preco_anterior)
                    <span class="marca-caiu">Baixou {{ ltrim(\App\Support\Formata::variacao($mud->preco_anterior, $mud->preco_novo), '-') }}</span>
                @elseif ($mud && $mud->preco_anterior && $mud->preco_novo > $mud->preco_anterior)
                    <span class="marca-subiu">Subiu {{ ltrim(\App\Support\Formata::variacao($mud->preco_anterior, $mud->preco_novo), '+') }}</span>
                @endif
            </p>
            <p class="linha-preco">
                <strong>{{ \App\Support\Formata::moeda($a->preco, true) }}</strong>
                @if ($a->preco && $unidade) <small>{{ \App\Support\Formata::unidadeCurta($unidade) }}</small> @endif
                @if ($a->preco_m2) <small>R$ {{ \App\Support\Formata::numero($a->preco_m2) }}/m²</small> @endif
            </p>
            <h3 class="linha-titulo">{{ $a->titulo }}</h3>
            <p class="linha-local">{{ $a->bairro?->nome ? $a->bairro->nome.', ' : '' }}{{ $a->cidade->nome }}</p>
            @if ($specs) <p class="linha-specs">{{ implode(', ', $specs) }}</p> @endif
        </div>
        <div class="linha-fonte">
            <span>{{ $a->fonte->nome }}</span>
            @if ($rep > 0) <span class="linha-rep">e mais {{ $rep }} {{ $rep === 1 ? 'site' : 'sites' }}</span> @endif
            <span class="linha-data">desde {{ $a->primeira_vez_em->format('d/m') }}</span>
        </div>
    </a>
</li>
