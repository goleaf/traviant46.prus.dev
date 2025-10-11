<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'acting_sitter_id',
        'ip_address',
        'user_agent',
        'via_sitter',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'via_sitter' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actingSitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acting_sitter_id');
    }
}
