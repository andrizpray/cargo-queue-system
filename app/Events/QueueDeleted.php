<?php

namespace App\Events;

use App\Models\Queue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $queueId,
        public int $locationId,
        public int $queueNumber,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('queues.all'),
            new Channel('queues.' . $this->locationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'queue.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->queueId,
            'queue_number' => $this->queueNumber,
            'location_id' => $this->locationId,
        ];
    }
}
