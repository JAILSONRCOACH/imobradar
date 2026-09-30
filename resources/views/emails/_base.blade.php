<!doctype html>
<html lang="pt-BR">
<body style="margin:0;background:#F1F5F9;font-family:Arial,Helvetica,sans-serif;color:#0F172A;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#FFFFFF;border-radius:12px;border:1px solid #E2E8F0;">
<tr><td style="padding:24px 28px 8px;"><img src="{{ asset('img/logo.png') }}" alt="IMOBRADAR" width="180" style="display:block;height:auto;"></td></tr>
<tr><td style="padding:8px 28px 28px;font-size:15px;line-height:1.55;">
@yield('corpo')
</td></tr>
</table>
<p style="font-size:12px;color:#64748B;margin:16px 0 0;">IMOBRADAR · {{ config('imobradar.admin_email') }} · WhatsApp {{ config('imobradar.whatsapp_exibicao') }}</p>
</td></tr>
</table>
</body>
</html>
