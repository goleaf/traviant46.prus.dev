<?php

declare(strict_types=1);

namespace App\Services\Migration\Transforms;

use App\Enums\DailyQuest\RewardFourType;
use App\Enums\DailyQuest\RewardOneType;
use App\Enums\DailyQuest\RewardThreeType;
use App\Enums\DailyQuest\RewardTwoType;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class DailyQuestProgressRowTransformer
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function transform(array $row): array
    {
        self::assertHasKeys($row, ['uid']);

        $userId = (int) ($row['uid'] ?? 0);

        if ($userId <= 0) {
            throw new InvalidArgumentException('Daily quest progress rows must reference a valid user.');
        }

        $now = CarbonImmutable::now();

        return [
            'user_id' => $userId,
            'quest_one_progress' => self::normalizeProgress($row['qst1'] ?? 0),
            'quest_two_progress' => self::normalizeProgress($row['qst2'] ?? 0),
            'quest_three_progress' => self::normalizeProgress($row['qst3'] ?? 0),
            'quest_four_progress' => self::normalizeProgress($row['qst4'] ?? 0),
            'quest_five_progress' => self::normalizeProgress($row['qst5'] ?? 0),
            'quest_six_progress' => self::normalizeProgress($row['qst6'] ?? 0),
            'quest_seven_progress' => self::normalizeProgress($row['qst7'] ?? 0),
            'quest_eight_progress' => self::normalizeProgress($row['qst8'] ?? 0),
            'quest_nine_progress' => self::normalizeProgress($row['qst9'] ?? 0),
            'quest_ten_progress' => self::normalizeProgress($row['qst10'] ?? 0),
            'quest_eleven_progress' => self::normalizeProgress($row['qst11'] ?? 0),
            'alliance_contribution_total' => self::normalizeContribution($row['alliance_contribution'] ?? 0),
            'reward_one_type' => self::mapReward(RewardOneType::class, $row['reward1Type'] ?? 0),
            'reward_one_claimed' => self::normalizeBool($row['reward1Done'] ?? 0),
            'reward_two_type' => self::mapReward(RewardTwoType::class, $row['reward2Type'] ?? 0),
            'reward_two_claimed' => self::normalizeBool($row['reward2Done'] ?? 0),
            'reward_three_type' => self::mapReward(RewardThreeType::class, $row['reward3Type'] ?? 0),
            'reward_three_claimed' => self::normalizeBool($row['reward3Done'] ?? 0),
            'reward_four_type' => self::mapReward(RewardFourType::class, $row['reward4Type'] ?? 0),
            'reward_four_claimed' => self::normalizeBool($row['reward4Done'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected static function assertHasKeys(array $row, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                throw new InvalidArgumentException(sprintf('Missing required column [%s] in daily quest row.', $key));
            }
        }
    }

    protected static function normalizeProgress(mixed $value): int
    {
        $progress = (int) ($value ?? 0);

        if ($progress < 0) {
            throw new InvalidArgumentException('Quest progress cannot be negative.');
        }

        return $progress;
    }

    protected static function normalizeContribution(mixed $value): int
    {
        $contribution = (int) ($value ?? 0);

        if ($contribution < 0) {
            throw new InvalidArgumentException('Alliance contribution cannot be negative.');
        }

        return $contribution;
    }

    /**
     * @param class-string<RewardOneType|RewardTwoType|RewardThreeType|RewardFourType> $enum
     */
    protected static function mapReward(string $enum, mixed $value): RewardOneType|RewardTwoType|RewardThreeType|RewardFourType
    {
        $reward = $enum::tryFrom((int) ($value ?? 0));

        if ($reward === null) {
            throw new InvalidArgumentException(sprintf('Invalid reward type [%s] for enum [%s].', (string) $value, $enum));
        }

        return $reward;
    }

    protected static function normalizeBool(mixed $value): bool
    {
        return (int) ($value ?? 0) > 0;
    }
}
