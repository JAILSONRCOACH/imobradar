@extends('emails._base')
@section('corpo')
<h1 style="font-size:20px;margin:0 0 12px;">Seu acesso foi liberado, {{ \Illuminate\Support\Str::before($user->name, ' ') }}.</h1>
<p style="margin:0 0 20px;">Você já pode entrar no IMOBRADAR com o e-mail <strong>{{ $user->email }}</strong> e a senha que criou no cadastro.</p>
<p style="margin:0 0 20px;"><a href="{{ route('entrar') }}" style="display:inline-block;background:#0F766E;color:#FFFFFF;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:8px;">Entrar no IMOBRADAR</a></p>
<p style="font-size:13px;color:#64748B;margin:0;">Esqueceu a senha? Use "Esqueci minha senha" na tela de entrada.</p>
@endsection
