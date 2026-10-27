<?php

use App\Http\Middleware\ShareLearningActivity;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('payments.{payment}', fn (User $user, Payment $payment): bool => $payment->user_id === $user->id);

// Presence channel of the "Đang học bài" list: every page of a signed-in user joins it with what the page is about,
// encrypted by the layout (see ShareLearningActivity). Each tab is a member of its own (see User::getAuthIdentifierForBroadcasting),
// with its `socket` and the time it joined (`since`), so the list shows a user once, with the activity of their latest tab.
Broadcast::channel('learning', function (User $user): array {
    try {
        $activity = filled(request('activity')) ? decrypt(request('activity')) : null;
    } catch (DecryptException) {
        $activity = null;
    }

    $nameParts = explode(' ', trim($user->name));

    return [
        'id' => $user->id,
        'socket' => (string) request('socket_id'),
        'since' => now()->getTimestampMs(),
        'name' => $user->name,
        'initials' => mb_strtoupper(mb_substr($nameParts[count($nameParts) - 2] ?? '', 0, 1).mb_substr(end($nameParts), 0, 1)),
        'avatarUrl' => $user->avatar_url,
        'activity' => is_string($activity) ? $activity : ShareLearningActivity::BROWSING,
    ];
});
