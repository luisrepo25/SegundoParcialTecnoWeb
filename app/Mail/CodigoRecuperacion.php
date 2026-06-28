<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CodigoRecuperacion extends Mailable
{
    public function __construct(
        public string $codigo,
        public string $nombre,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Código de recuperación — Instituto San Pablo');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo_recuperacion',
            with: ['codigo' => $this->codigo, 'nombre' => $this->nombre],
        );
    }
}
