<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Aviso para o usuário: o acesso foi liberado. */
class AcessoLiberado extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seu acesso ao IMOBRADAR foi liberado');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.acesso-liberado');
    }
}
