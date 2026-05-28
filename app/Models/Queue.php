<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    protected $fillable = [
        'vehicle_id',
        'location_id',
        'queue_number',
        'status',
        'arrived_at',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function queueHistory(): HasMany
    {
        return $this->hasMany(QueueHistory::class);
    }
}
