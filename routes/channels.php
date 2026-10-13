<?php

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('payments.{payment}', fn (User $user, Payment $payment): bool => $payment->user_id === $user->id);
