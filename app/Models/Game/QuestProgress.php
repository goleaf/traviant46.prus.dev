<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestProgress extends Model
{
    /** @use HasFactory<\Database\Factories\Game\QuestProgressFactory> */
    use HasFactory;

    public const STATE_PENDING = 'pending';

    public const STATE_COMPLETED = 'completed';

    protected $table = 'quest_progress';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'quest_id',
        'state',
        'progress',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'quest_id' => 'int',
            'progress' => 'array',
        ];
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function markCompleted(array $payload = []): void
    {
        $progress = is_array($this->progress) ? $this->progress : [];

        $progress['completed_at'] = $payload['completed_at'] ?? now()->toIso8601String();
        $progress['objectives'] = $payload['objectives'] ?? ($progress['objectives'] ?? []);
        $progress['rewards'] = $payload['rewards'] ?? ($progress['rewards'] ?? []);

        $this->forceFill([
            'state' => self::STATE_COMPLETED,
            'progress' => $progress,
        ])->save();
    }
}
