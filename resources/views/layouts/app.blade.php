<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Imóveis na Paraíba') | IMOBRADAR</title>
    <meta name="description" content="@yield('descricao', 'Busque imóveis à venda e para alugar em qualquer cidade da Paraíba. Anúncios de vários portais e imobiliárias, atualizados todos os dias.')">
    <meta name="theme-color" content="#0F172A">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/icone-180.png') }}">
    <meta property="og:image" content="{{ asset('img/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="@yield('corpo')">
<header class="topo">
    <div class="topo-in">
        <a href="{{ route('busca') }}" class="marca" aria-label="IMOBRADAR, página inicial">
            <img src="{{ asset('img/logo.png') }}" alt="IMOBRADAR" width="194" height="58">
        </a>
        @hasSection('busca-topo')
            <div class="topo-busca">@yield('busca-topo')</div>
        @endif
        @auth
            <nav class="topo-conta" aria-label="Sua conta">
                @if (auth()->user()->is_admin)
                    @php $pendentes = \App\Models\User::where('status', 'pendente')->count(); @endphp
                    <a href="{{ route('admin.usuarios') }}">Cadastros @if ($pendentes)<span class="contador">{{ $pendentes }}</span>@endif</a>
                @endif
                <form method="post" action="{{ route('sair') }}">@csrf <button type="submit" class="link-botao">Sair</button></form>
            </nav>
        @endauth
    </div>
</header>

<main>
    @yield('conteudo')
</main>

<footer class="rodape">
    <div class="rodape-in">
        <p>O IMOBRADAR reúne anúncios públicos de portais, imobiliárias e classificados da Paraíba. Cada anúncio leva ao site onde foi publicado: confirme preço e disponibilidade lá antes de negociar.</p>
    </div>
</footer>
<script src="{{ asset('js/busca.js') }}?v={{ @filemtime(public_path('js/busca.js')) }}" defer></script>
<script src="{{ asset('js/cidades.js') }}?v={{ @filemtime(public_path('js/cidades.js')) }}" defer></script>
</body>
</html>
