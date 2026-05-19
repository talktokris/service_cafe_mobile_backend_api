<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public int $expiresMinutes = 15,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Serve Cafe — Password Reset Code');
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Your Serve Cafe password reset code is:</p>'
                .'<p style="font-size:28px;font-weight:bold;letter-spacing:4px;">'.$this->code.'</p>'
                .'<p>This code expires in '.$this->expiresMinutes.' minutes.</p>'
                .'<p>If you did not request this, ignore this email.</p>',
        );
    }
}
