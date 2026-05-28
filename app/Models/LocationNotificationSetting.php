<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationNotificationSetting extends Model
{
    protected $fillable = [
        'location_id',
        'notify_admin_on_queue_created',
        'notify_driver_on_status_changed',
        'send_reminders',
        'reminder_minutes_before',
        'admin_phone',
        'driver_notification_preferences',
    ];

    protected $casts = [
        'notify_admin_on_queue_created' => 'boolean',
        'notify_driver_on_status_changed' => 'boolean',
        'send_reminders' => 'boolean',
        'reminder_minutes_before' => 'integer',
        'driver_notification_preferences' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public static function getSettingForLocation(int $locationId): self
    {
        return self::firstOrCreate(
            ['location_id' => $locationId],
            [
                'notify_admin_on_queue_created' => true,
                'notify_driver_on_status_changed' => true,
                'send_reminders' => true,
                'reminder_minutes_before' => 10,
            ]
        );
    }
}
