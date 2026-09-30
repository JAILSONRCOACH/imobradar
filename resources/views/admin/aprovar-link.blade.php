@extends('layouts.acesso')
@section('titulo', 'Liberar acesso')
@section('conteudo')
@if ($feito)
    <h1>Acesso liberado.</h1>
    <p class="acesso-sub">{{ $user->name }} já pode entrar no IMOBRADAR.
        @if ($feito === 'aprovado') Enviamos um e-mail avisando. @else O e-mail de aviso falhou: avise pelo WhatsApp. @endif
    </p>
    @if ($user->whatsappLink()) <a class="botao botao-cheio" href="{{ $user->whatsappLink() }}" target="_blank" rel="noopener">Abrir WhatsApp de {{ \Illuminate\Support\Str::before($user->name, ' ') }}</a> @endif
@elseif ($user->aprovado())
    <h1>{{ $user->name }} já tem acesso.</h1>
    <p class="acesso-sub">Este cadastro foi liberado em {{ $user->aprovado_em?->format('d/m/Y') }}.</p>
@else
    <h1>Liberar acesso?</h1>
    <dl class="dados-pedido">
        <div><dt>Nome</dt><dd>{{ $user->name }}</dd></div>
        <div><dt>E-mail</dt><dd>{{ $user->email }}</dd></div>
        <div><dt>WhatsApp</dt><dd>{{ $user->whatsapp }}</dd></div>
        <div><dt>Perfil</dt><dd>{{ \App\Models\User::PERFIS[$user->perfil] ?? $user->perfil }}</dd></div>
        @if ($user->mensagem) <div><dt>Procura</dt><dd>{{ $user->mensagem }}</dd></div> @endif
    </dl>
    <form method="post" action="{{ request()->fullUrl() }}">
        @csrf
        <button type="submit" class="botao botao-cheio">Liberar acesso de {{ \Illuminate\Support\Str::before($user->name, ' ') }}</button>
    </form>
@endif
@endsection
