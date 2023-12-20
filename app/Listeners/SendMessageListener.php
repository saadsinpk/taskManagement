<?php

namespace App\Listeners;

use App\Events\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Broadcast;

class SendMessageListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SendMessage $event): void
    {
        $user = $event->user;
        $message = $event->message;
        $customData = $event->customData;


        // Broadcast the event to a channel
        Broadcast::channel('chat.' . $user->id, function ($user) use ($message) {
            return $message;
        });
    }
}
