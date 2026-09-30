<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'IMOBRADAR') · Imóveis na Paraíba</title>
    <meta name="description" content="@yield('descricao', 'Imóveis à venda, aluguel e temporada na Paraíba, reunidos de vários portais e atualizados todos os dias.')">
    <meta name="theme-color" content="#0E7C74">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%230E7C74'/%3E%3Ccircle cx='12' cy='12' r='6' fill='none' stroke='white' stroke-width='2'/%3E%3Cpath d='M12 12l4-4' stroke='white' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Manrope:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<header class="topo">
    <div class="topo-in">
        <a href="{{ route('busca') }}" class="marca" aria-label="IMOBRADAR, início">
            <span class="logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2.5" fill="currentColor"/><path d="M12 12l5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <span><b>IMOBRADAR</b><small>Pesquise menos. Encontre mais.</small></span>
        </a>
    </div>
</header>

<main class="conteudo">
    @yield('conteudo')
</main>

<footer class="rodape">
    <div class="rodape-in">
        <p>O IMOBRADAR reúne anúncios públicos de portais, imobiliárias e classificados. Cada anúncio mantém o link da fonte original: confira sempre lá antes de negociar.</p>
        <p>&copy; {{ date('Y') }} IMOBRADAR</p>
    </div>
</footer>
</body>
</html>
