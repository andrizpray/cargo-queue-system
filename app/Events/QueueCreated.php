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

class QueueCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Queue $queue)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('queues.all'),
            new Channel('queues.' . $this->queue->location_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'queue.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->queue->id,
            'queue_number' => $this->queue->queue_number,
            'location_id' => $this->queue->location_id,
            'vehicle_id' => $this->queue->vehicle_id,
            'status' => $this->queue->status,
            'arrived_at' => $this->queue->arrived_at,
            'vehicle' => $this->queue->vehicle,
            'location' => $this->queue->location,
        ];
    }
}
