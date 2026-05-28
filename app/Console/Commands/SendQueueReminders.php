<?php

namespace App\Console\Commands;

use App\Events\QueueReminder;
use App\Models\LocationNotificationSetting;
use App\Models\Queue;
use Illuminate\Console\Command;

class SendQueueReminders extends Command
{
    protected $signature = 'queues:send-reminders {--location= : Specific location ID}';

    protected $description = 'Send WhatsApp reminders to drivers whose queues are approaching';

    public function handle(): int
    {
        $locationId = $this->option('location');

        $query = Queue::with('vehicle', 'location')
            ->where('status', 'waiting');

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $queues = $query->get();

        $sentCount = 0;

        foreach ($queues as $queue) {
            $settings = LocationNotificationSetting::getSettingForLocation($queue->location_id);

            if (!$settings->send_reminders) {
                continue;
            }

            // Calculate estimated wait time (simplified: 5 minutes per queue ahead)
            $queuesAhead = Queue::where('location_id', $queue->location_id)
                ->where('status', 'waiting')
                ->where('queue_number', '<', $queue->queue_number)
                ->count();

            $estimatedMinutesUntil = $queuesAhead * 5;

            // Only send reminder if within the reminder window
            if ($estimatedMinutesUntil <= $settings->reminder_minutes_before && $estimatedMinutesUntil > 0) {
                QueueReminder::dispatch($queue, $estimatedMinutesUntil);
                $sentCount++;

                $this->info("Sent reminder for Queue #{$queue->queue_number} at {$queue->location->name} (estimated {$estimatedMinutesUntil} min)");
            }
        }

        $this->info("Sent {$sentCount} queue reminders.");

        return Command::SUCCESS;
    }
}
