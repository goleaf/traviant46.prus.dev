<?php

namespace Tests\Unit\Migration\Transforms;

use App\Enums\DailyQuest\RewardFourType;
use App\Enums\DailyQuest\RewardOneType;
use App\Enums\DailyQuest\RewardThreeType;
use App\Enums\DailyQuest\RewardTwoType;
use App\Services\Migration\Transforms\DailyQuestProgressRowTransformer;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class DailyQuestProgressRowTransformerTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_transform_maps_daily_quest_row(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2024, 1, 1, 0, 0, 0, 'UTC'));

        $payload = DailyQuestProgressRowTransformer::transform([
            'uid' => 99,
            'qst1' => 2,
            'qst2' => 3,
            'qst3' => 0,
            'qst4' => 1,
            'qst5' => 0,
            'qst6' => 0,
            'qst7' => 0,
            'qst8' => 0,
            'qst9' => 0,
            'qst10' => 0,
            'qst11' => 0,
            'alliance_contribution' => 12345,
            'reward1Type' => RewardOneType::VillageResourceBundle->value,
            'reward1Done' => 1,
            'reward2Type' => RewardTwoType::PlusAccountDay->value,
            'reward2Done' => 0,
            'reward3Type' => RewardThreeType::AdventureScroll->value,
            'reward3Done' => 1,
            'reward4Type' => RewardFourType::HeroExperience->value,
            'reward4Done' => 0,
        ]);

        $this->assertSame(99, $payload['user_id']);
        $this->assertSame(2, $payload['quest_one_progress']);
        $this->assertSame(12345, $payload['alliance_contribution_total']);
        $this->assertInstanceOf(RewardOneType::class, $payload['reward_one_type']);
        $this->assertInstanceOf(RewardThreeType::class, $payload['reward_three_type']);
        $this->assertTrue($payload['reward_one_claimed']);
        $this->assertSame('2024-01-01 00:00:00', $payload['created_at']->toDateTimeString());
    }

    public function test_transform_validates_reward_types(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DailyQuestProgressRowTransformer::transform([
            'uid' => 1,
            'reward1Type' => 99,
        ]);
    }
}
