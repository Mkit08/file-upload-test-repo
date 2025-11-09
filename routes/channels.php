<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Register the event broadcasting channels your application supports.
|
*/

Broadcast::channel('file-uploads.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
