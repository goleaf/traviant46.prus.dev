<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'token',
        'password',
        'world_id',
        'used',
    ];

    protected $casts = [
        'used' => 'boolean',
    ];

    public function scopeForWorld(Builder $query, ?string $worldId): Builder
    {
        if ($worldId === null || trim($worldId) === '') {
            return $query->whereNull('world_id');
        }

        return $query->where('world_id', trim($worldId));
    }

    public function scopeUsed(Builder $query): Builder
    {
        return $query->where('used', true);
    }

    public function scopeUnused(Builder $query): Builder
    {
        return $query->where('used', false);
    }

    public function scopeToken(Builder $query, string $token): Builder
    {
        return $query->where('token', $token);
    }
}
