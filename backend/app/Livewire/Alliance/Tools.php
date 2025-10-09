<?php

namespace App\Livewire\Alliance;

use App\Services\Game\AllianceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Tools extends Component
{
    public array $data = [];

    public function mount(Request $request, AllianceService $allianceService): void
    {
        $user = $request->user();

        try {
            $alliance = $allianceService->resolve($user);

            $this->data = $allianceService->tools($user, $alliance);
        } catch (ModelNotFoundException $exception) {
            $this->data = [
                'alliance' => [
                    'name' => __('No alliance'),
                    'tag' => '--',
                    'motd' => null,
                    'description' => '',
                ],
                'members' => [],
                'diplomacy' => [
                    'confederacies' => [],
                    'non_aggression_pacts' => [],
                    'wars' => [],
                ],
                'empty_state' => $exception->getMessage(),
            ];
        }
    }

    public function render()
    {
        return view('livewire.alliance.tools');
    }
}
