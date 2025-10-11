<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitterAssignment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'sitter_id',
        'permissions',
        'expires_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    public function sitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sitter_id');
    }
}
