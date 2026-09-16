<?php

namespace App\Mail;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public readonly string $acceptanceUrl;

    public function __construct(
        public readonly OrganizationInvitation $invitation,
        public readonly string $token,
    ) {
        $frontendUrl =
            rtrim(
                (string) config(
                    'conviva.frontend_url'
                ),
                '/'
            );

        $this->acceptanceUrl =
            $frontendUrl
            .'/invitations/accept/'
            .rawurlencode(
                $this->token
            );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Convite para acessar '
                .$this
                    ->invitation
                    ->organization
                    ->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
