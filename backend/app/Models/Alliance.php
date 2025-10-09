<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alliance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tag',
        'description',
        'motd',
        'leader_id',
        'diplomacy',
    ];

    protected $casts = [
        'diplomacy' => 'array',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'alliance_user')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }
}
