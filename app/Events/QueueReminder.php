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

class QueueReminder implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Queue $queue,
        public int $minutesUntil,
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
        return 'queue.reminder';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->queue->id,
            'queue_number' => $this->queue->queue_number,
            'location_id' => $this->queue->location_id,
            'minutes_until' => $this->minutesUntil,
            'vehicle' => $this->queue->vehicle,
            'location' => $this->queue->location,
        ];
    }

    /**
     * Send WhatsApp reminder notification to driver
     */
    public function sendWhatsAppNotification(): void
    {
        $settings = LocationNotificationSetting::getSettingForLocation($this->queue->location_id);
        
        if (!$settings->send_reminders) {
            return;
        }

        $whatsappService = app(WhatsAppService::class);
        
        if (!$whatsappService->isEnabled()) {
            return;
        }

        $whatsappService->sendQueueReminderNotification($this->queue, $this->minutesUntil);
    }
}
