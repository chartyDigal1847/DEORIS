<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}.notifications', function (User $user, int $userId): bool {
    return (int) $user->id === $userId;
});

Broadcast::channel('event-monitoring', function (User $user): bool {
    // Admins and instructors can monitor the event hub.
    return $user->hasRole(User::ROLE_ADMIN, User::ROLE_INSTRUCTOR);
});
