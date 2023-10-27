<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\NewNotificationEvent;
use App\Notifications\MyNotification;

class SendNotificationListener
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
    public function handle(NewNotificationEvent $event)
    {
        $event->notifiable->notify(new MyNotification($event->notificationData));
    }
}
