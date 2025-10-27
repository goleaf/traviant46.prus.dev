<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a configurable alliance role with JSON encoded permissions.
 */
class AllianceRole extends Model
{
    /** @use HasFactory<\Database\Factories\AllianceRoleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'alliance_id',
        'name',
        'permissions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(AllianceMember::class);
    }
}
