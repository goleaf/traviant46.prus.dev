<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Models\Game\MapTile;
use App\Models\Game\Village;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * Renders a compact textual grid of map tiles centred on the active village.
 */
class MapMinigrid extends Component
{
    /**
     * @var array<int, string>
     */
    private const FIELD_TYPE_DISTRIBUTIONS = [
        1 => '3-3-3-9',
        2 => '3-4-5-6',
        3 => '4-4-4-6',
        4 => '4-5-3-6',
        5 => '5-3-4-6',
        6 => '1-1-1-15',
        7 => '4-4-3-7',
        8 => '3-4-4-7',
        9 => '4-3-4-7',
        10 => '3-5-4-6',
        11 => '4-3-5-6',
        12 => '5-4-3-6',
        99 => 'Natars stronghold',
    ];

    /**
     * @var array<int, array<int, int>>
     */
    private const OASIS_RESOURCE_LOOKUP = [
        2 => [1 => 1],
        3 => [1 => 1, 4 => 1],
        4 => [1 => 2],
        6 => [2 => 1],
        7 => [2 => 1, 4 => 1],
        8 => [2 => 2],
        10 => [3 => 1],
        11 => [3 => 1, 4 => 1],
        12 => [3 => 2],
        14 => [4 => 1],
        15 => [4 => 2],
    ];

    private const MIN_RADIUS = 1;

    private const MAX_RADIUS = 4;

    private const DEFAULT_RADIUS = 2;

    public Village $village;

    public int $radius = self::DEFAULT_RADIUS;

    /**
     * @var array<int, array<int, array<string, mixed>>>
     */
    public array $grid = [];

    public bool $sendRouteAvailable = false;

    /**
     * @var list<int>
     */
    public array $radiusOptions = [];

    public function mount(Village $village, ?int $radius = null): void
    {
        $this->village = $village;
        $this->radiusOptions = range(self::MIN_RADIUS, self::MAX_RADIUS);
        $this->radius = $this->normalizeRadius($radius ?? self::DEFAULT_RADIUS);
        $this->sendRouteAvailable = Route::has('game.send');
        $this->refreshGrid();
    }

    public function refreshGrid(): void
    {
        $this->grid = $this->buildGrid();
    }

    public function updatedRadius(int|string $value): void
    {
        $this->radius = $this->normalizeRadius((int) $value);
        $this->refreshGrid();
    }

    public function openSend(int $x, int $y): RedirectResponse|Redirector|null
    {
        if (! $this->sendRouteAvailable) {
            return null;
        }

        return redirect()->route('game.send', [
            'origin' => $this->village->getKey(),
            'target_x' => $x,
            'target_y' => $y,
        ]);
    }

    public function render(): View
    {
        return view('livewire.game.map-minigrid', [
            'columns' => $this->determineColumnCount(),
        ]);
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function buildGrid(): array
    {
        $radius = $this->radius;
        $centerX = (int) $this->village->x_coordinate;
        $centerY = (int) $this->village->y_coordinate;

        $minX = $centerX - $radius;
        $maxX = $centerX + $radius;
        $minY = $centerY - $radius;
        $maxY = $centerY + $radius;

        $tiles = MapTile::query()
            ->whereBetween('x', [$minX, $maxX])
            ->whereBetween('y', [$minY, $maxY])
            ->get();

        $tileIndex = $tiles->keyBy(fn (MapTile $tile): string => $this->tileKey((int) $tile->x, (int) $tile->y));

        $villages = Village::query()
            ->select(['id', 'name', 'x_coordinate', 'y_coordinate'])
            ->whereBetween('x_coordinate', [$minX, $maxX])
            ->whereBetween('y_coordinate', [$minY, $maxY])
            ->get()
            ->keyBy(fn (Village $candidate): string => $this->tileKey((int) $candidate->x_coordinate, (int) $candidate->y_coordinate));

        $grid = [];

        for ($y = $maxY; $y >= $minY; $y--) {
            $row = [];

            for ($x = $minX; $x <= $maxX; $x++) {
                $key = $this->tileKey($x, $y);

                /** @var MapTile|null $tile */
                $tile = $tileIndex->get($key);

                /** @var Village|null $occupyingVillage */
                $occupyingVillage = $villages->get($key);

                $row[] = $this->describeTile(
                    $x,
                    $y,
                    $centerX,
                    $centerY,
                    $tile,
                    $occupyingVillage,
                );
            }

            $grid[] = $row;
        }

        return $grid;
    }

    private function determineColumnCount(): int
    {
        if ($this->grid === []) {
            return 0;
        }

        return count($this->grid[0] ?? []);
    }

    private function tileKey(int $x, int $y): string
    {
        return "{$x}:{$y}";
    }

    private function normalizeRadius(int $radius): int
    {
        if ($radius < self::MIN_RADIUS) {
            return self::MIN_RADIUS;
        }

        if ($radius > self::MAX_RADIUS) {
            return self::MAX_RADIUS;
        }

        return $radius;
    }

    /**
     * @return array<string, mixed>
     */
    private function describeTile(
        int $x,
        int $y,
        int $centerX,
        int $centerY,
        ?MapTile $tile,
        ?Village $occupyingVillage,
    ): array {
        $isCenter = $x === $centerX && $y === $centerY;

        $category = 'unknown';
        $label = __('Unknown tile');
        $details = null;
        $effects = [];
        $badges = [];

        if ($tile instanceof MapTile) {
            $fieldType = (int) $tile->fieldtype;
            $oasisType = (int) $tile->oasistype;

            if ($oasisType > 0) {
                $category = 'oasis';
                $label = __('Oasis');
                $effects = $this->describeOasisEffects($oasisType);
                $badges[] = $tile->occupied ? __('Occupied') : __('Unclaimed');
            } elseif ($fieldType > 0) {
                $distribution = self::FIELD_TYPE_DISTRIBUTIONS[$fieldType] ?? null;

                if ($occupyingVillage instanceof Village) {
                    $category = 'village';
                    $label = __('Village: :name', ['name' => $occupyingVillage->name]);
                    if ($distribution !== null) {
                        $details = __('Fields: :distribution', ['distribution' => $distribution]);
                    }
                } else {
                    $category = 'field';
                    $label = __('Empty field');
                    if ($distribution !== null) {
                        $details = __('Layout: :distribution', ['distribution' => $distribution]);
                    }
                }
            } else {
                $category = 'wilderness';
                $label = __('Wilderness');
                $details = __('No settlement possible');
            }
        }

        if ($isCenter) {
            $badges[] = __('Current village');
        }

        $distance = (int) round(sqrt(pow($x - $centerX, 2) + pow($y - $centerY, 2)));

        return [
            'x' => $x,
            'y' => $y,
            'category' => $category,
            'category_label' => $this->formatCategoryLabel($category),
            'label' => $label,
            'details' => $details,
            'effects' => $effects,
            'badges' => $badges,
            'is_center' => $isCenter,
            'distance' => $distance,
        ];
    }

    private function formatCategoryLabel(string $category): string
    {
        return match ($category) {
            'oasis' => __('Oasis'),
            'village' => __('Village'),
            'field' => __('Field'),
            'wilderness' => __('Wilderness'),
            default => __('Unknown'),
        };
    }

    /**
     * @return list<string>
     */
    private function describeOasisEffects(int $type): array
    {
        $effects = self::OASIS_RESOURCE_LOOKUP[$type] ?? [];

        $labels = [];

        foreach ($effects as $resource => $multiplier) {
            $resourceLabel = match ($resource) {
                1 => __('Wood'),
                2 => __('Clay'),
                3 => __('Iron'),
                4 => __('Crop'),
                default => __('Resource'),
            };

            $labels[] = __('+:percent% :resource', [
                'percent' => $multiplier * 25,
                'resource' => $resourceLabel,
            ]);
        }

        return $labels;
    }
}
