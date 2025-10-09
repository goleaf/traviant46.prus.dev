<?php

namespace App\Livewire;

use resources\View\FluxView;

abstract class Component
{
    protected array $state = [];

    public function __construct(array $state = [])
    {
        $this->with($state);
    }

    public function with(array $state): static
    {
        $this->state = array_merge($this->state, $state);

        return $this;
    }

    public function render(): string
    {
        return FluxView::render($this->view(), $this->state);
    }

    public function output(): string
    {
        return $this->render();
    }

    abstract protected function view(): string;
}
