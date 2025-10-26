<?php

namespace App\Livewire\Storefront;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Render the storefront checkout summary within the marketing site.
 */
class Checkout extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $checkout = [];

    public string $currency = 'USD';

    public function mount(array $checkout, ?string $currency = null): void
    {
        $this->checkout = $checkout;
        $this->currency = $currency ?? $checkout['cart']['currency'] ?? config('storefront.currency', 'USD');
    }

    public function render(): View
    {
        return view('livewire.storefront.checkout');
    }
}
