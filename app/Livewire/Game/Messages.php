<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Messages extends Component
{
    /**
     * @var array<int, string>
     */
    private const TABS = ['inbox', 'sent', 'compose'];

    #[Url(as: 'tab')]
    public string $currentTab = 'inbox';

    public ?string $flashMessage = null;

    public function mount(): void
    {
        if (! in_array($this->currentTab, self::TABS, true)) {
            $this->currentTab = 'inbox';
        }
    }

    public function switchTab(string $name): void
    {
        if (! in_array($name, self::TABS, true)) {
            return;
        }

        $this->currentTab = $name;
    }

    #[On('message-sent')]
    public function handleMessageSent(?string $status = null): void
    {
        $this->flashMessage = $status;
    }

    public function dismissFlash(): void
    {
        $this->flashMessage = null;
    }

    public function render(): View
    {
        return view('livewire.game.messages', [
            'tabs' => self::TABS,
            'currentTab' => $this->currentTab,
            'flashMessage' => $this->flashMessage,
        ]);
    }
}
