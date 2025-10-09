<?php

namespace App\Livewire\Map;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class TileDetails extends Component
{
    public int $x;
    public int $y;
    public array $details = [];

    protected $listeners = [
        'mapTileSelected' => 'updateSelection',
    ];

    public function updateSelection(int $x, int $y): void
    {
        $this->setTile($x, $y);
    }

    public function mount(int $x = 0, int $y = 0): void
    {
        $this->setTile($x, $y);
    }

    public function setTile(int $x, int $y): void
    {
        $this->x = $x;
        $this->y = $y;
        $this->details = $this->buildDetails();
    }

    public function render(): View
    {
        return view('livewire.map.tile-details', [
            'coordinates' => ['x' => $this->x, 'y' => $this->y],
            'details' => $this->details,
        ]);
    }

    private function buildDetails(): array
    {
        $terrain = $this->determineTerrainType();

        return [
            'terrain' => $terrain,
            'description' => $this->describeTerrain($terrain),
            'abundance' => $this->resourceAbundance(),
        ];
    }

    private function determineTerrainType(): string
    {
        $types = ['grassland', 'forest', 'hill', 'mountain', 'desert'];
        $index = (abs($this->x) + abs($this->y)) % count($types);

        return $types[$index];
    }

    private function describeTerrain(string $terrain): string
    {
        return match ($terrain) {
            'grassland' => 'Open fields that are perfect for early settlements.',
            'forest' => 'Dense woodland offering protection and timber.',
            'hill' => 'Gently rolling hills with a balanced resource mix.',
            'mountain' => 'Rocky cliffs rich in ore but hard to cultivate.',
            'desert' => 'Arid sands that demand careful planning.',
            default => 'An unexplored stretch of land.',
        };
    }

    private function resourceAbundance(): array
    {
        $base = abs(($this->x * 3) + ($this->y * 2));

        return [
            'wood' => ($base + 15) % 100,
            'clay' => ($base + 35) % 100,
            'iron' => ($base + 55) % 100,
            'crop' => ($base + 75) % 100,
        ];
    }
}
