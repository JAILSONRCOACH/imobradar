<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

/** Aviso para o administrador: alguém pediu acesso. */
class NovoCadastro extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido de acesso ao IMOBRADAR: '.$this->user->name,
            replyTo: [$this->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.novo-cadastro', with: [
            'linkAprovar' => URL::temporarySignedRoute(
                'aprovacao.mostrar',
                now()->addDays((int) config('imobradar.dias_link_aprovacao')),
                ['user' => $this->user->id],
            ),
        ]);
    }
}
