@extends('layouts.acesso')
@section('titulo', 'Esqueci minha senha')
@section('conteudo')
<h1>Esqueci minha senha</h1>
<p class="acesso-sub">Informe o e-mail do cadastro. Enviamos um link para você criar uma nova senha.</p>
<form method="post" action="{{ route('senha.enviar') }}" class="form-acesso" novalidate>
    @csrf
    <label class="campo-form">
        <span>E-mail</span>
        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        @error('email') <small class="erro">{{ $message }}</small> @enderror
    </label>
    <button type="submit" class="botao botao-cheio">Enviar link</button>
</form>
<p class="acesso-rodape"><a href="{{ route('entrar') }}">Voltar para entrar</a></p>
@endsection
