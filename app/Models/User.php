<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const PERFIS = [
        'corretor' => 'Corretor(a)',
        'imobiliaria' => 'Imobiliária',
        'investidor' => 'Investidor(a)',
        'comprador' => 'Procuro imóvel para mim',
        'outro' => 'Outro',
    ];

    protected $fillable = ['name', 'email', 'password', 'whatsapp', 'perfil', 'mensagem'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'aprovado_em' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function aprovado(): bool
    {
        return $this->status === 'aprovado';
    }

    /** E-mail de nova senha em português, pelo mesmo SMTP do site. */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        \Illuminate\Support\Facades\Mail::to($this->email)->send(new \App\Mail\RedefinirSenha($this, $token));
    }

    public function whatsappLink(): ?string
    {
        $n = preg_replace('/\D/', '', (string) $this->whatsapp);
        if ($n === '') {
            return null;
        }

        return 'https://wa.me/'.(strlen($n) <= 11 ? '55'.$n : $n);
    }
}
