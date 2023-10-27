<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Chats;

class sendMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $messages;
    public $customData;
    public $userSenderList;
    public $receiver;

    /**
     * Create a new customData instance.
     */
    public function __construct($user, $messages, $customData, $userSender, $receiver)
    {
        $this->messages = $messages;
        $this->sender = $user;
        $this->customData = $customData;
        $this->userSenderList = $userSender;
        $this->receiver = $receiver;
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    // public function broadcastWith(): array
    // {
    //     return [
    //         'id' => 'good',
    //     ];
    // }
}
