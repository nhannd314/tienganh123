<?php

namespace App\Listeners;

use App\Mail\NewUserRegistered;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

/**
 * Mails the owner when a learner signs up. A mail that cannot be sent is only reported: the sign-up still goes through.
 */
class SendNewUserNotice
{
    public function handle(Registered $event): void
    {
        $to = config('payment.notify_email');

        if (blank($to) || ! $event->user instanceof User) {
            return;
        }

        rescue(fn () => Mail::to($to)->send(new NewUserRegistered($event->user)));
    }
}
