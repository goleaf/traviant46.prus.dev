<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Village extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'population',
        'x',
        'y',
        'resource_production',
        'resources',
        'warehouse_capacity',
        'granary_capacity',
        'queue_snapshot',
    ];

    protected $casts = [
        'resource_production' => 'array',
        'resources' => 'array',
        'queue_snapshot' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function buildingQueueEntries(): HasMany
    {
        return $this->hasMany(BuildingQueueEntry::class);
    }
}
