<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class MultihunterAction captures staff interventions taken by multihunter accounts.
 */
class MultihunterAction extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'multihunter_id',
        'target_user_id',
        'action',
        'category',
        'reason',
        'notes',
        'performed_at',
        'ip_address',
        'ip_address_hash',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, MultihunterAction>
     */
    public function multihunter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'multihunter_id');
    }

    /**
     * @return BelongsTo<User, MultihunterAction>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * @return BelongsTo<User, MultihunterAction>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, MultihunterAction>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
