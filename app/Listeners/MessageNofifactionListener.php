<?php

namespace App\Listeners;

use App\Events\MessageNotifaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Broadcast;

class MessageNofifactionListener
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
    public function handle(MessageNotifaction $event): void
    {
        $user = $event->user;
        $notifaction = $event->notifaction;

        // Broadcast the event to a channel
        Broadcast::channel('notifaction.' . $user->id, function ($user) use ($notifaction) {
            return $notifaction;
        });
    }
}
