<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AuditLog extends Model
{
    use HasFactory;
    use Prunable;

    protected $fillable = [
        'actor_id',
        'actor_username',
        'actor_role',
        'action',
        'target_type',
        'target_id',
        'ip_address',
        'ip_address_hash',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function prunable(): Builder
    {
        $retentionDays = (int) config('privacy.audit.retention_days', 365);
        $threshold = Carbon::now()->subDays(max($retentionDays, 1));

        return static::query()->where('performed_at', '<', $threshold);
    }
}
