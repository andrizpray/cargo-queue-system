<?php

namespace App\Listeners;

use App\Events\QueueCreated;
use App\Events\QueueStatusChanged;
use App\Events\QueueReminder;

class SendWhatsAppNotifications
{
    /**
     * Handle QueueCreated events.
     */
    public function handleQueueCreated(QueueCreated $event): void
    {
        $event->sendWhatsAppNotification();
    }

    /**
     * Handle QueueStatusChanged events.
     */
    public function handleQueueStatusChanged(QueueStatusChanged $event): void
    {
        $event->sendWhatsAppNotification();
    }

    /**
     * Handle QueueReminder events.
     */
    public function handleQueueReminder(QueueReminder $event): void
    {
        $event->sendWhatsAppNotification();
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            QueueCreated::class => 'handleQueueCreated',
            QueueStatusChanged::class => 'handleQueueStatusChanged',
            QueueReminder::class => 'handleQueueReminder',
        ];
    }
}
