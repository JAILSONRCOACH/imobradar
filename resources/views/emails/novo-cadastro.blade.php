@extends('emails._base')
@section('corpo')
<h1 style="font-size:20px;margin:0 0 12px;">Novo pedido de acesso</h1>
<table role="presentation" cellpadding="0" cellspacing="0" style="font-size:15px;margin:0 0 20px;">
    <tr><td style="color:#64748B;padding:3px 16px 3px 0;">Nome</td><td><strong>{{ $user->name }}</strong></td></tr>
    <tr><td style="color:#64748B;padding:3px 16px 3px 0;">E-mail</td><td>{{ $user->email }}</td></tr>
    <tr><td style="color:#64748B;padding:3px 16px 3px 0;">WhatsApp</td><td>@if ($user->whatsappLink())<a href="{{ $user->whatsappLink() }}" style="color:#0F766E;">{{ $user->whatsapp }}</a>@else{{ $user->whatsapp }}@endif</td></tr>
    <tr><td style="color:#64748B;padding:3px 16px 3px 0;">Perfil</td><td>{{ \App\Models\User::PERFIS[$user->perfil] ?? $user->perfil }}</td></tr>
    @if ($user->mensagem)
    <tr><td style="color:#64748B;padding:3px 16px 3px 0;vertical-align:top;">Procura</td><td>{{ $user->mensagem }}</td></tr>
    @endif
</table>
<p style="margin:0 0 20px;"><a href="{{ $linkAprovar }}" style="display:inline-block;background:#0F766E;color:#FFFFFF;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:8px;">Revisar e liberar acesso</a></p>
<p style="font-size:13px;color:#64748B;margin:0;">O link vale por {{ config('imobradar.dias_link_aprovacao') }} dias. Você também pode aprovar pelo painel: {{ route('admin.usuarios') }}</p>
@endsection
