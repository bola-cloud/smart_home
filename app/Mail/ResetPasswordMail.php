<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetCode; // Store the reset code

    /**
     * Create a new message instance.
     */
    public function __construct($resetCode)
    {
        $this->resetCode = $resetCode;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Password Reset Code')
                    ->text('emails.reset_password_plain') // Use raw text for the email
                    ->with(['resetCode' => $this->resetCode]);
    }
}
