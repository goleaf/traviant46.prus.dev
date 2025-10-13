<?php

namespace App\Services\Game;

use App\Models\Game\AvailableVillage;
use App\Models\Game\UserAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use RuntimeException;

class RegistrationService
{
    public function __construct(
        private readonly HeroService $heroService,
        private readonly VillageSetupService $villageSetupService,
        private readonly DailyQuestService $dailyQuestService,
        private readonly InfoBoxService $infoBoxService,
        private readonly SummaryService $summaryService,
    ) {
    }

    public function register(string $username, string $password, string $email, int $tribe, string $sector = 'rand', bool $addHero = true): UserAccount
    {
        $spawn = $this->pickSpawnLocation($sector, 3);

        if (!$spawn) {
            throw new RuntimeException('No spawn location available for new account.');
        }

        $signupTime = now()->timestamp;
        $protectionEndsAt = now()->addHours(Config::get('game.protection.basic_hours', 72))->timestamp;
        $culturePoints = 500;

        return DB::transaction(function () use ($username, $password, $email, $tribe, $spawn, $signupTime, $protectionEndsAt, $culturePoints, $addHero) {
            $user = UserAccount::create([
                'uuid' => (string) Str::uuid(),
                'name' => Str::of($username)->limit(15, '')->toString(),
                'password' => Hash::make($password),
                'email' => $email,
                'access' => 1,
                'gift_gold' => 0,
                'signupTime' => $signupTime,
                'protection' => $protectionEndsAt,
                'race' => $tribe,
                'kid' => $spawn->kid,
                'cp' => $culturePoints,
                'lastupdate' => $signupTime,
                'last_adventure_time' => $signupTime,
                'location' => '',
                'desc1' => '',
                'desc2' => '',
                'note' => '',
                'last_login_time' => $signupTime,
                'countryFlag' => '',
                'lastCountryFlagCheck' => $signupTime,
                'profileCacheVersion' => 1,
                'total_villages' => 0,
                'total_pop' => 0,
                'cp_prod' => 0,
            ]);

            $village = $this->villageSetupService->createStartingVillage($user, $spawn, $tribe, true, 0, $addHero);

            if ($addHero) {
                $this->heroService->createStarterHero($user->getKey(), $village->getKey());
            }

            $this->dailyQuestService->createForUser($user->getKey());
            $this->infoBoxService->addProtectionNotice($user->getKey(), $protectionEndsAt);
            $this->summaryService->incrementTribePopulation($tribe);

            return $user->fresh();
        });
    }

    private function pickSpawnLocation(string $sector, int $fieldType, int $attempt = 0): ?AvailableVillage
    {
        $attemptLimit = Config::get('game.spawn.retry_limit', 16);

        if ($attempt >= $attemptLimit) {
            return null;
        }

        $minDistance = Config::get('game.spawn.min_radius', 25);
        $maxDistance = $minDistance + Config::get('game.spawn.max_radius_padding', 40) + ($attempt * 10);

        $query = AvailableVillage::query()
            ->unoccupied()
            ->where('fieldtype', $fieldType)
            ->where('r', '>', 25)
            ->whereBetween('r', [$minDistance, $maxDistance]);

        if ($angles = $this->resolveSectorAngles($sector)) {
            $query->whereBetween('angle', $angles);
        }

        $candidate = $query->inRandomOrder()->first();

        if ($candidate) {
            return $candidate;
        }

        return $this->pickSpawnLocation($sector, $fieldType, $attempt + 1);
    }

    private function resolveSectorAngles(string $sector): ?array
    {
        return match (strtolower($sector)) {
            'ne', 'no' => [0, 90],
            'se', 'so' => [270, 360],
            'nw' => [90, 180],
            'sw' => [180, 270],
            default => null,
        };
    }
}
