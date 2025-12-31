<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message->load('user:id,name,email');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->id_conversa_FK),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Kiểm tra null để tránh crash nếu user bị xóa
        if (!$this->message->user) {
            // Fallback nếu user bị xóa
            return [
                'id' => $this->message->getKey(),
                'content' => $this->message->content,
                'sender' => [
                    'id' => null,
                    'name' => 'Người dùng đã xóa',
                    'email' => '',
                ],
                'send_at' => $this->message->send_at,
                'conversation_id' => $this->message->id_conversa_FK,
            ];
        }
        
        return [
            'id' => $this->message->getKey(),
            'content' => $this->message->content,
            'sender' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name ?? 'Người dùng đã xóa',
                'email' => $this->message->user->email ?? '',
            ],
            'send_at' => $this->message->send_at,
            'conversation_id' => $this->message->id_conversa_FK,
        ];
    }
}
