@extends('emails._base')
@section('corpo')
<h1 style="font-size:20px;margin:0 0 12px;">Criar nova senha</h1>
<p style="margin:0 0 20px;">Recebemos um pedido para criar uma nova senha para <strong>{{ $user->email }}</strong>.</p>
<p style="margin:0 0 20px;"><a href="{{ $link }}" style="display:inline-block;background:#0F766E;color:#FFFFFF;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:8px;">Criar nova senha</a></p>
<p style="font-size:13px;color:#64748B;margin:0;">O link vale por {{ $minutos }} minutos. Se não foi você que pediu, ignore este e-mail: sua senha continua a mesma.</p>
@endsection
