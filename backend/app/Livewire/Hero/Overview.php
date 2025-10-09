<?php

namespace App\Livewire\Hero;

use App\Services\Game\HeroService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Overview extends Component
{
    public array $hero = [];

    public function mount(Request $request, HeroService $heroService): void
    {
        $user = $request->user();

        try {
            $hero = $heroService->resolve($user);

            $this->hero = $heroService->overview($user, $hero);
        } catch (ModelNotFoundException $exception) {
            $this->hero = [
                'name' => __('No hero found'),
                'level' => 0,
                'experience' => 0,
                'health' => 0,
                'is_alive' => false,
                'attributes' => config('game.hero.base_attributes', []),
                'equipment' => [],
                'empty_state' => $exception->getMessage(),
            ];
        }
    }

    public function render()
    {
        return view('livewire.hero.overview');
    }
}
