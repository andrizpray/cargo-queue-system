<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Queue broadcasting channels
Broadcast::channel('queues.all', function ($user) {
    return true;
});

Broadcast::channel('queues.{location_id}', function ($user, $location_id) {
    return true;
});
