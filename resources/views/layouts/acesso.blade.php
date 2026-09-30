<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('titulo') | IMOBRADAR</title>
    <meta name="theme-color" content="#0F172A">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon-64.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="pagina-acesso">
<div class="acesso">
    <aside class="acesso-lado">
        <img class="acesso-logo" src="{{ asset('img/logo.png') }}" alt="IMOBRADAR" width="240" height="72">
        <p class="acesso-frase">Imóveis de toda a Paraíba numa busca só, com histórico de preço e sem anúncios repetidos.</p>
        <p class="acesso-contato">
            Dúvidas? <a href="https://wa.me/{{ config('imobradar.whatsapp') }}" target="_blank" rel="noopener">WhatsApp {{ config('imobradar.whatsapp_exibicao') }}</a>
            ou <a href="mailto:{{ config('imobradar.admin_email') }}">{{ config('imobradar.admin_email') }}</a>
        </p>
    </aside>
    <main class="acesso-caixa">
        @if (session('ok')) <p class="alerta alerta-ok" role="status">{{ session('ok') }}</p> @endif
        @yield('conteudo')
    </main>
</div>
<script src="{{ asset('js/acesso.js') }}?v={{ @filemtime(public_path('js/acesso.js')) }}" defer></script>
</body>
</html>
