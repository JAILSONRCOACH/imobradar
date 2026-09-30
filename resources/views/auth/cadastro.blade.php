@extends('layouts.acesso')
@section('titulo', 'Pedir cadastro')
@section('conteudo')
<h1>Pedir cadastro</h1>
<p class="acesso-sub">Preencha seus dados. Cada pedido é analisado e você recebe um e-mail quando o acesso for liberado.</p>

<form method="post" action="{{ route('cadastro') }}" class="form-acesso" novalidate>
    @csrf
    <label class="campo-form">
        <span>Nome completo</span>
        <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="120">
        @error('name') <small class="erro">{{ $message }}</small> @enderror
    </label>
    <label class="campo-form">
        <span>E-mail</span>
        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
        @error('email') <small class="erro">{{ $message }}</small> @enderror
    </label>
    <label class="campo-form">
        <span>WhatsApp com DDD</span>
        <input type="tel" name="whatsapp" value="{{ old('whatsapp') }}" autocomplete="tel" inputmode="tel" placeholder="(83) 90000-0000" required>
        @error('whatsapp') <small class="erro">{{ $message }}</small> @enderror
    </label>
    <label class="campo-form">
        <span>Você é</span>
        <select name="perfil" required>
            <option value="">Escolha</option>
            @foreach ($perfis as $valor => $rotulo)
                <option value="{{ $valor }}" @selected(old('perfil') === $valor)>{{ $rotulo }}</option>
            @endforeach
        </select>
        @error('perfil') <small class="erro">{{ $message }}</small> @enderror
    </label>
    <label class="campo-form">
        <span>O que você procura? <em>(opcional)</em></span>
        <textarea name="mensagem" rows="3" maxlength="500" placeholder="Ex.: casas em Lucena e Cabedelo até R$ 500 mil">{{ old('mensagem') }}</textarea>
    </label>
    <div class="campo-duplo">
        <label class="campo-form">
            <span>Senha</span>
            <input type="password" name="password" autocomplete="new-password" required minlength="8">
        </label>
        <label class="campo-form">
            <span>Repita a senha</span>
            <input type="password" name="password_confirmation" autocomplete="new-password" required minlength="8">
        </label>
    </div>
    <small class="dica-campo">Pelo menos 8 caracteres, com letras e números.</small>
    @error('password') <small class="erro">{{ $message }}</small> @enderror
    <label class="armadilha" aria-hidden="true">Site <input type="text" name="site" tabindex="-1" autocomplete="off"></label>
    <button type="submit" class="botao botao-cheio">Enviar pedido de cadastro</button>
</form>

<p class="acesso-rodape">Já tem cadastro? <a href="{{ route('entrar') }}">Entrar</a></p>
@endsection
