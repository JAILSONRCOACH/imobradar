@extends('layouts.app')
@section('titulo', 'Cadastros')
@section('conteudo')
<div class="pagina">
    <header class="cabecalho-resultados">
        <h1>Cadastros</h1>
        <p>Aprove quem pode usar o IMOBRADAR.</p>
    </header>

    @if (session('ok')) <p class="alerta alerta-ok" role="status">{{ session('ok') }}</p> @endif

    <nav class="abas-admin" aria-label="Situação do cadastro">
        @foreach (['pendente' => 'Aguardando', 'aprovado' => 'Liberados', 'recusado' => 'Recusados'] as $valor => $rotulo)
            <a href="{{ route('admin.usuarios', ['status' => $valor]) }}" @if ($status === $valor) aria-current="page" @endif>{{ $rotulo }} <span>{{ $contagem[$valor] ?? 0 }}</span></a>
        @endforeach
    </nav>

    @if ($usuarios->isEmpty())
        <div class="vazio"><p>Nenhum cadastro nesta situação.</p></div>
    @else
        <ul class="lista">
            @foreach ($usuarios as $u)
                <li class="usuario">
                    <div class="usuario-dados">
                        <strong>{{ $u->name }}</strong>
                        <span>{{ $u->email }}</span>
                        <span>
                            @if ($u->whatsappLink()) <a href="{{ $u->whatsappLink() }}" target="_blank" rel="noopener">{{ $u->whatsapp }}</a> @else {{ $u->whatsapp }} @endif
                            @if ($u->perfil) , {{ $perfis[$u->perfil] ?? $u->perfil }} @endif
                        </span>
                        @if ($u->mensagem) <p class="usuario-msg">{{ $u->mensagem }}</p> @endif
                        <small>Pediu em {{ $u->created_at->format('d/m/Y H:i') }}@if ($u->aprovado_em), liberado em {{ $u->aprovado_em->format('d/m/Y') }}@endif @if ($u->ultimo_acesso_em), último acesso {{ $u->ultimo_acesso_em->format('d/m/Y H:i') }}@endif</small>
                    </div>
                    <div class="usuario-acoes">
                        @if ($u->status !== 'aprovado')
                            <form method="post" action="{{ route('admin.aprovar', $u) }}">@csrf <button type="submit" class="botao">Liberar acesso</button></form>
                        @endif
                        @if ($u->status !== 'recusado' && ! $u->is_admin)
                            <form method="post" action="{{ route('admin.recusar', $u) }}">@csrf <button type="submit" class="botao botao-secundario">{{ $u->status === 'aprovado' ? 'Bloquear' : 'Recusar' }}</button></form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $usuarios->links('partials.paginacao') }}
    @endif
</div>
@endsection
