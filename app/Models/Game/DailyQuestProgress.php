<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Enums\DailyQuest\RewardFourType;
use App\Enums\DailyQuest\RewardOneType;
use App\Enums\DailyQuest\RewardThreeType;
use App\Enums\DailyQuest\RewardTwoType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyQuestProgress extends Model
{
    protected $table = 'daily_quest_progresses';

    protected $fillable = [
        'user_id',
        'quest_one_progress',
        'quest_two_progress',
        'quest_three_progress',
        'quest_four_progress',
        'quest_five_progress',
        'quest_six_progress',
        'quest_seven_progress',
        'quest_eight_progress',
        'quest_nine_progress',
        'quest_ten_progress',
        'quest_eleven_progress',
        'alliance_contribution_total',
        'reward_one_type',
        'reward_one_claimed',
        'reward_two_type',
        'reward_two_claimed',
        'reward_three_type',
        'reward_three_claimed',
        'reward_four_type',
        'reward_four_claimed',
    ];

    protected $casts = [
        'user_id' => 'int',
        'quest_one_progress' => 'int',
        'quest_two_progress' => 'int',
        'quest_three_progress' => 'int',
        'quest_four_progress' => 'int',
        'quest_five_progress' => 'int',
        'quest_six_progress' => 'int',
        'quest_seven_progress' => 'int',
        'quest_eight_progress' => 'int',
        'quest_nine_progress' => 'int',
        'quest_ten_progress' => 'int',
        'quest_eleven_progress' => 'int',
        'alliance_contribution_total' => 'int',
        'reward_one_type' => RewardOneType::class,
        'reward_one_claimed' => 'bool',
        'reward_two_type' => RewardTwoType::class,
        'reward_two_claimed' => 'bool',
        'reward_three_type' => RewardThreeType::class,
        'reward_three_claimed' => 'bool',
        'reward_four_type' => RewardFourType::class,
        'reward_four_claimed' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
