<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells the owner that someone signed up, and which affiliate brought them in, if any.
 */
class NewUserRegistered extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Thành viên mới đăng ký: {$this->user->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-user-registered', with: [
            'user' => $this->user,
            'referrer' => $this->user->referrer,
        ]);
    }
}
