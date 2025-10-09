<?php

namespace App\Livewire\Map;

use Controller\Ajax\map\tileDetails as LegacyTileDetails;

class TileDetails
{
    public function render(int $x, int $y, bool $forAjax = false): string
    {
        $response = [];
        $controller = new LegacyTileDetails($response);
        return $controller->renderForCoordinates($x, $y, $forAjax);
    }

    public function dispatch(int $x, int $y): array
    {
        $response = [];
        $controller = new LegacyTileDetails($response);
        $response['html'] = $controller->renderForCoordinates($x, $y, true);
        return $response;
    }
}
