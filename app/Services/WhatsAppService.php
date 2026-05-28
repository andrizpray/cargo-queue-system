<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private Client $twilioClient;
    private string $fromNumber;
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = config('services.whatsapp.enabled', false);
        $this->fromNumber = config('services.whatsapp.sandbox_from', '');
        
        if ($this->enabled) {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $this->twilioClient = new Client($sid, $token);
        }
    }

    /**
     * Send a WhatsApp message to a phone number
     */
    public function sendMessage(string $toPhone, string $message, array $metadata = []): NotificationLog
    {
        $notificationLog = NotificationLog::create([
            'user_id' => null,
            'phone' => $toPhone,
            'notification_type' => $metadata['type'] ?? 'general',
            'channel' => 'whatsapp',
            'status' => 'pending',
            'message' => $message,
            'metadata' => $metadata,
        ]);

        if (!$this->enabled) {
            Log::info("WhatsApp disabled - Message would be sent to {$toPhone}: {$message}");
            $notificationLog->markAsSent();
            return $notificationLog;
        }

        try {
            $twilioMessage = $this->twilioClient->messages->create(
                "whatsapp:{$toPhone}",
                [
                    'from' => "whatsapp:{$this->fromNumber}",
                    'body' => $message,
                ]
            );

            $notificationLog->update([
                'metadata' => array_merge($notificationLog->metadata ?? [], [
                    'twilio_sid' => $twilioMessage->sid,
                    'twilio_status' => $twilioMessage->status,
                ]),
            ]);

            if ($twilioMessage->status === 'sent' || $twilioMessage->status === 'queued') {
                $notificationLog->markAsSent();
            } else {
                $notificationLog->markAsFailed("Twilio status: {$twilioMessage->status}");
            }

            Log::info("WhatsApp message sent to {$toPhone}", [
                'twilio_sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status,
            ]);

        } catch (\Exception $e) {
            $notificationLog->markAsFailed($e->getMessage());
            Log::error("Failed to send WhatsApp message to {$toPhone}", [
                'error' => $e->getMessage(),
            ]);
        }

        return $notificationLog;
    }

    /**
     * Send notification to a user
     */
    public function sendToUser(User $user, string $message, array $metadata = []): ?NotificationLog
    {
        if (!$user->whatsapp_phone || !$user->whatsapp_notifications_enabled) {
            Log::info("WhatsApp notifications disabled for user {$user->id}");
            return null;
        }

        $metadata['user_id'] = $user->id;
        $notificationLog = $this->sendMessage($user->whatsapp_phone, $message, $metadata);
        $notificationLog->update(['user_id' => $user->id]);
        
        return $notificationLog;
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(array $users, string $message, array $metadata = []): array
    {
        $results = [];
        
        foreach ($users as $user) {
            if ($user instanceof User) {
                $results[] = $this->sendToUser($user, $message, $metadata);
            }
        }

        return array_filter($results);
    }

    /**
     * Send queue created notification to admin
     */
    public function sendQueueCreatedNotification(object $queue): ?NotificationLog
    {
        $message = $this->buildQueueCreatedMessage($queue);
        $metadata = [
            'type' => 'queue_created',
            'queue_id' => $queue->id,
            'queue_number' => $queue->queue_number,
            'location_id' => $queue->location_id,
        ];

        // Get admin users
        $admins = User::where('role', 'admin')
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_notifications_enabled', true)
            ->get();

        if ($admins->isEmpty()) {
            Log::info("No admin users with WhatsApp enabled for queue created notification");
            return null;
        }

        return $this->sendToUsers($admins->all(), $message, $metadata);
    }

    /**
     * Send queue status changed notification to driver
     */
    public function sendQueueStatusChangedNotification(object $queue, string $previousStatus, string $newStatus, ?string $notes = null): ?NotificationLog
    {
        $message = $this->buildStatusChangedMessage($queue, $previousStatus, $newStatus, $notes);
        $metadata = [
            'type' => 'queue_status_changed',
            'queue_id' => $queue->id,
            'queue_number' => $queue->queue_number,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
        ];

        // Get the driver (user who created the queue)
        $driver = User::find($queue->user_id ?? $queue->created_by);
        
        if (!$driver || !$driver->whatsapp_phone || !$driver->whatsapp_notifications_enabled) {
            Log::info("Cannot send status notification - no valid driver with WhatsApp");
            return null;
        }

        return $this->sendToUser($driver, $message, $metadata);
    }

    /**
     * Send queue reminder notification
     */
    public function sendQueueReminderNotification(object $queue, int $minutesUntil): ?NotificationLog
    {
        $message = $this->buildReminderMessage($queue, $minutesUntil);
        $metadata = [
            'type' => 'queue_reminder',
            'queue_id' => $queue->id,
            'queue_number' => $queue->queue_number,
            'minutes_until' => $minutesUntil,
        ];

        $driver = User::find($queue->user_id ?? $queue->created_by);
        
        if (!$driver || !$driver->whatsapp_phone || !$driver->whatsapp_notifications_enabled) {
            Log::info("Cannot send reminder - no valid driver with WhatsApp");
            return null;
        }

        return $this->sendToUser($driver, $message, $metadata);
    }

    /**
     * Build queue created message
     */
    private function buildQueueCreatedMessage(object $queue): string
    {
        $vehicle = $queue->vehicle ?? null;
        $location = $queue->location ?? null;
        
        return sprintf(
            "🚛 *New Queue Created*\n\n" .
            "Queue #: %s\n" .
            "Location: %s\n" .
            "Vehicle: %s\n" .
            "Status: %s\n" .
            "Arrived at: %s",
            $queue->queue_number,
            $location->name ?? 'N/A',
            $vehicle->plate_number ?? 'N/A',
            ucfirst($queue->status),
            $queue->arrived_at ? now()->parse($queue->arrived_at)->format('H:i') : 'N/A'
        );
    }

    /**
     * Build status changed message
     */
    private function buildStatusChangedMessage(object $queue, string $previousStatus, string $newStatus, ?string $notes): string
    {
        $vehicle = $queue->vehicle ?? null;
        
        $statusEmoji = match ($newStatus) {
            'waiting' => '⏳',
            'loading' => '🔄',
            'done' => '✅',
            'cancelled' => '❌',
            default => '📋',
        };

        $message = sprintf(
            "%s *Queue Status Updated*\n\n" .
            "Queue #: %s\n" .
            "Vehicle: %s\n" .
            "Status: %s → %s",
            $statusEmoji,
            $queue->queue_number,
            $vehicle->plate_number ?? 'N/A',
            ucfirst($previousStatus),
            ucfirst($newStatus)
        );

        if ($notes) {
            $message .= sprintf("\n\n📝 Notes: %s", $notes);
        }

        if ($newStatus === 'loading') {
            $message .= "\n\n⚡ Your turn is now! Please proceed to the loading area.";
        } elseif ($newStatus === 'done') {
            $message .= "\n\n✔️ Your queue is complete. Thank you!";
        } elseif ($newStatus === 'cancelled') {
            $message .= "\n\n⚠️ Your queue has been cancelled.";
        }

        return $message;
    }

    /**
     * Build reminder message
     */
    private function buildReminderMessage(object $queue, int $minutesUntil): string
    {
        $vehicle = $queue->vehicle ?? null;
        
        return sprintf(
            "⏰ *Queue Reminder*\n\n" .
            "Queue #: %s\n" .
            "Vehicle: %s\n\n" .
            "Your queue is approaching in approximately *%d minutes*.\n" .
            "Please be ready at the loading area.",
            $queue->queue_number,
            $vehicle->plate_number ?? 'N/A',
            $minutesUntil
        );
    }

    /**
     * Check if WhatsApp is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
