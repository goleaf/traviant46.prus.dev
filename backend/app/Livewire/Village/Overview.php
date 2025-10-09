<?php

namespace App\Livewire\Village;

use App\Models\Village;
use App\Services\Game\VillageService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Overview extends Component
{
    public array $overview = [];

    public function mount(Request $request, VillageService $service, ?Village $village = null): void
    {
        /** @var Authenticatable $user */
        $user = $request->user();

        try {
            $activeVillage = $service->resolveActiveVillage($user, $village);

            $this->overview = $service->overview($user, $activeVillage);
        } catch (ModelNotFoundException $exception) {
            $this->overview = [
                'village' => [
                    'id' => null,
                    'name' => __('No villages'),
                    'population' => 0,
                    'coordinates' => ['x' => 0, 'y' => 0],
                ],
                'production' => config('game.default_production'),
                'storage' => [
                    'warehouse' => 0,
                    'granary' => 0,
                    'resources' => config('game.default_production'),
                ],
                'queues' => [],
                'empty_state' => $exception->getMessage(),
            ];
        }
    }

    public function render()
    {
        return view('livewire.village.overview');
    }
}
