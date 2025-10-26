<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    /** @use HasFactory<\Database\Factories\Game\QuestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'quest_code',
        'title',
        'description',
        'is_repeatable',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_repeatable' => 'bool',
        ];
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(QuestProgress::class);
    }

    public function progressFor(User $user): ?QuestProgress
    {
        $relation = $this->relationLoaded('progresses') ? $this->progresses : $this->progresses()->get();

        return $relation->firstWhere('user_id', $user->getKey());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function objectives(): array
    {
        $definitions = (array) config('quests.definitions', []);
        $definition = (array) ($definitions[$this->quest_code] ?? []);

        $objectives = $definition['objectives'] ?? [];

        return is_array($objectives) ? array_values($objectives) : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rewards(): array
    {
        $definitions = (array) config('quests.definitions', []);
        $definition = (array) ($definitions[$this->quest_code] ?? []);

        $rewards = $definition['rewards'] ?? [];

        return is_array($rewards) ? array_values($rewards) : [];
    }

    public function isDaily(): bool
    {
        return $this->is_repeatable;
    }
}
