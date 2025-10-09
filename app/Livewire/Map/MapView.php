<?php

namespace App\Livewire\Map;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class MapView extends Component
{
    public int $centerX;
    public int $centerY;
    public int $zoomLevel;

    protected array $zoomSizes = [
        1 => 5,
        2 => 7,
        3 => 9,
    ];

    public function mount(?int $x = null, ?int $y = null, int $zoom = 1): void
    {
        $this->centerX = $x ?? 0;
        $this->centerY = $y ?? 0;
        $this->zoomLevel = $this->clampZoom($zoom);
    }

    public function zoomIn(): void
    {
        $this->zoomLevel = $this->clampZoom($this->zoomLevel + 1);
    }

    public function zoomOut(): void
    {
        $this->zoomLevel = $this->clampZoom($this->zoomLevel - 1);
    }

    public function pan(string $direction): void
    {
        $steps = $this->panStep();

        switch ($direction) {
            case 'north':
                $this->centerY += $steps;
                break;
            case 'south':
                $this->centerY -= $steps;
                break;
            case 'east':
                $this->centerX += $steps;
                break;
            case 'west':
                $this->centerX -= $steps;
                break;
        }
    }

    public function select(int $x, int $y): void
    {
        $this->centerX = $x;
        $this->centerY = $y;
        $this->dispatch('mapTileSelected', x: $x, y: $y);
    }

    public function render(): View
    {
        $tiles = $this->generateVisibleTiles();

        return view('livewire.map.map-view', [
            'tiles' => $tiles,
            'zoomLevel' => $this->zoomLevel,
            'center' => [
                'x' => $this->centerX,
                'y' => $this->centerY,
            ],
        ]);
    }

    private function generateVisibleTiles(): array
    {
        $size = $this->currentMapSize();
        $radius = (int) floor($size / 2);
        $rows = [];

        for ($y = $this->centerY + $radius; $y >= $this->centerY - $radius; --$y) {
            $row = [];
            for ($x = $this->centerX - $radius; $x <= $this->centerX + $radius; ++$x) {
                $row[] = [
                    'x' => $x,
                    'y' => $y,
                    'isCenter' => $x === $this->centerX && $y === $this->centerY,
                ];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function clampZoom(int $zoom): int
    {
        $min = (int) array_key_first($this->zoomSizes);
        $max = (int) array_key_last($this->zoomSizes);

        return max($min, min($max, $zoom));
    }

    private function panStep(): int
    {
        $size = $this->currentMapSize();

        return max(1, (int) floor($size / 3));
    }

    private function currentMapSize(): int
    {
        $firstKey = array_key_first($this->zoomSizes);

        if ($firstKey === null) {
            return 5;
        }

        return $this->zoomSizes[$this->zoomLevel] ?? $this->zoomSizes[$firstKey];
    }
}
