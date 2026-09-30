@extends('layouts.acesso')
@section('titulo', 'Entrar')
@section('conteudo')
<h1>Entrar</h1>
<p class="acesso-sub">O IMOBRADAR é de acesso restrito. Entre com o e-mail e a senha do seu cadastro.</p>

<form method="post" action="{{ route('entrar') }}" class="form-acesso" novalidate>
    @csrf
    <label class="campo-form">
        <span>E-mail</span>
        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
    </label>
    <label class="campo-form">
        <span>Senha</span>
        <input type="password" name="password" autocomplete="current-password" required>
    </label>
    @error('email') <p class="erro" role="alert">{{ $message }}</p> @enderror
    <label class="opcao"><input type="checkbox" name="lembrar" value="1"> Manter conectado neste aparelho</label>
    <button type="submit" class="botao botao-cheio">Entrar</button>
</form>

<p class="acesso-links">
    <a href="{{ route('senha.pedir') }}">Esqueci minha senha</a>
</p>
<p class="acesso-rodape">Ainda não tem acesso? <a href="{{ route('cadastro') }}">Pedir cadastro</a></p>
@endsection
