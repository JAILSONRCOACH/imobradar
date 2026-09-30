@extends('layouts.acesso')
@section('titulo', 'Cadastro em análise')
@section('conteudo')
@if ($user->status === 'recusado')
    <h1>Cadastro não aprovado</h1>
    <p class="acesso-sub">Seu pedido de acesso não foi aprovado. Se achar que é um engano, fale com a gente pelo WhatsApp.</p>
@else
    <h1>Recebemos seu pedido, {{ \Illuminate\Support\Str::before($user->name, ' ') }}.</h1>
    <p class="acesso-sub">Seu cadastro está em análise. Quando o acesso for liberado, você recebe um e-mail em <strong>{{ $user->email }}</strong> e já pode entrar com a senha que criou.</p>
@endif
<a class="botao botao-cheio" href="https://wa.me/{{ config('imobradar.whatsapp') }}?text={{ rawurlencode('Olá! Fiz o cadastro no IMOBRADAR com o e-mail '.$user->email.' e gostaria de liberar o acesso.') }}" target="_blank" rel="noopener">Falar no WhatsApp</a>
<form method="post" action="{{ route('sair') }}" class="acesso-rodape">
    @csrf
    <button type="submit" class="link-botao">Sair</button>
</form>
@endsection
