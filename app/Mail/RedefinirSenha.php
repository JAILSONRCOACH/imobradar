<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RedefinirSenha extends Mailable
{
    public function __construct(public User $user, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Crie uma nova senha no IMOBRADAR');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.redefinir-senha', with: [
            'link' => route('password.reset', ['token' => $this->token, 'email' => $this->user->email]),
            'minutos' => config('auth.passwords.users.expire', 60),
        ]);
    }
}
