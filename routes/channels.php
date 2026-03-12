<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{conversation_id}', function ($user, $conversation_id) {
    $conversation = \App\Models\Conversation::find($conversation_id);
    if (!$conversation) return false;
    return $user->id === $conversation->participant_1_id || $user->id === $conversation->participant_2_id;
});

Broadcast::channel('tracking.{shipment_id}', function ($user, $shipment_id) {
    $shipment = \App\Models\Shipment::find($shipment_id);
    if (!$shipment) return false;
    
    // Only sender or assigned transporter
    $isSender = $user->id === $shipment->user_id;
    $isTransporter = $shipment->matches()->where('transporter_id', $user->id)->where('status', 'accepted')->exists();
    
    return $isSender || $isTransporter;
});
