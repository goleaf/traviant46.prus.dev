<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hero extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'village_id',
        'name',
        'level',
        'experience',
        'health',
        'attributes',
        'equipment',
        'is_alive',
    ];

    protected $casts = [
        'attributes' => 'array',
        'equipment' => 'array',
        'is_alive' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
