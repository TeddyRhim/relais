<?php

namespace App\Mail;

use App\Models\Invitation;
use App\Models\Organization;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvitationMail extends Mailable
{
    public function __construct(
        public Invitation $invitation,
        public Organization $organization,
        public string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation à rejoindre '.$this->organization->name.' sur Relais');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.invitation');
    }
}
