<?php

namespace App\Events;

use App\Models\Queue;
use App\Models\LocationNotificationSetting;
use App\Services\WhatsAppService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Queue $queue,
        public string $previousStatus,
        public string $newStatus,
        public ?string $notes = null,
    ) {
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
        return 'queue.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->queue->id,
            'queue_number' => $this->queue->queue_number,
            'location_id' => $this->queue->location_id,
            'vehicle_id' => $this->queue->vehicle_id,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'status' => $this->newStatus,
            'started_at' => $this->queue->started_at,
            'completed_at' => $this->queue->completed_at,
            'notes' => $this->notes,
            'vehicle' => $this->queue->vehicle,
            'location' => $this->queue->location,
        ];
    }

    /**
     * Send WhatsApp notification to driver when queue status changes
     */
    public function sendWhatsAppNotification(): void
    {
        $settings = LocationNotificationSetting::getSettingForLocation($this->queue->location_id);
        
        if (!$settings->notify_driver_on_status_changed) {
            return;
        }

        $whatsappService = app(WhatsAppService::class);
        
        if (!$whatsappService->isEnabled()) {
            return;
        }

        $whatsappService->sendQueueStatusChangedNotification(
            $this->queue,
            $this->previousStatus,
            $this->newStatus,
            $this->notes
        );
    }
}
