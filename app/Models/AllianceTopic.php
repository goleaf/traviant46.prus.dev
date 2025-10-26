<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllianceTopic extends Model
{
    /** @use HasFactory<\Database\Factories\AllianceTopicFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'forum_id',
        'alliance_id',
        'author_id',
        'acting_sitter_id',
        'title',
        'is_locked',
        'is_pinned',
        'last_posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'is_pinned' => 'boolean',
            'last_posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (self $topic): void {
            if ($topic->alliance_id === null && $topic->forum instanceof AllianceForum) {
                $topic->alliance_id = $topic->forum->alliance_id;
            }
        });
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(AllianceForum::class, 'forum_id');
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function actingSitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acting_sitter_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(AlliancePost::class, 'topic_id')->orderBy('created_at');
    }

    public function touchLastActivity(): void
    {
        $this->forceFill(['last_posted_at' => now()])->save();
    }
}
