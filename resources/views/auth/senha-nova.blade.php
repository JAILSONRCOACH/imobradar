@extends('layouts.acesso')
@section('titulo', 'Criar nova senha')
@section('conteudo')
<h1>Criar nova senha</h1>
<form method="post" action="{{ route('senha.salvar') }}" class="form-acesso" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label class="campo-form">
        <span>E-mail</span>
        <input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
        @error('email') <small class="erro">{{ $message }}</small> @enderror
    </label>
    <div class="campo-duplo">
        <label class="campo-form">
            <span>Nova senha</span>
            <input type="password" name="password" autocomplete="new-password" required minlength="8">
        </label>
        <label class="campo-form">
            <span>Repita a senha</span>
            <input type="password" name="password_confirmation" autocomplete="new-password" required minlength="8">
        </label>
    </div>
    <small class="dica-campo">Pelo menos 8 caracteres, com letras e números.</small>
    @error('password') <small class="erro">{{ $message }}</small> @enderror
    <button type="submit" class="botao botao-cheio">Salvar nova senha</button>
</form>
@endsection
