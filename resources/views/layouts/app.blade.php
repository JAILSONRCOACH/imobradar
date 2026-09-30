<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Imóveis na Paraíba') | IMOBRADAR</title>
    <meta name="description" content="@yield('descricao', 'Busque imóveis à venda e para alugar em qualquer cidade da Paraíba. Anúncios de vários portais e imobiliárias, atualizados todos os dias.')">
    <meta name="theme-color" content="#0F2A3A">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%230F2A3A'/%3E%3Ccircle cx='16' cy='16' r='9' fill='none' stroke='%23F4B400' stroke-width='2.5'/%3E%3Ccircle cx='16' cy='16' r='3' fill='%23F4B400'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,400..800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="@yield('corpo')">
<header class="topo">
    <div class="topo-in">
        <a href="{{ route('busca') }}" class="marca" aria-label="IMOBRADAR, página inicial">
            <svg class="marca-radar" viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="13" /><circle cx="16" cy="16" r="7.5" /><circle class="ponto" cx="16" cy="16" r="2.6" /></svg>
            <span>IMOBRADAR</span>
        </a>
        @hasSection('busca-topo')
            <div class="topo-busca">@yield('busca-topo')</div>
        @endif
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
<script src="{{ asset('js/cidades.js') }}?v={{ @filemtime(public_path('js/cidades.js')) }}" defer></script>
</body>
</html>
