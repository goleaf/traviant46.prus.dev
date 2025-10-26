<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlliancePost extends Model
{
    /** @use HasFactory<\Database\Factories\AlliancePostFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'topic_id',
        'alliance_id',
        'author_id',
        'acting_sitter_id',
        'body',
        'edited_by',
        'edited_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (self $post): void {
            if ($post->alliance_id === null && $post->topic instanceof AllianceTopic) {
                $post->alliance_id = $post->topic->alliance_id;
            }
        });

        static::created(static function (self $post): void {
            if ($post->relationLoaded('topic')) {
                $post->topic->touchLastActivity();
            } else {
                $post->topic()->first()?->touchLastActivity();
            }
        });
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(AllianceTopic::class, 'topic_id');
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

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function markEdited(User $editor): void
    {
        $this->forceFill([
            'edited_by' => $editor->getKey(),
            'edited_at' => now(),
        ])->save();
    }
}
