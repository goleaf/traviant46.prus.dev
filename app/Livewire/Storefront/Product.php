<?php

namespace App\Livewire\Storefront;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Present a storefront product with related bundles and pricing details.
 */
class Product extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $product = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $relatedProducts = [];

    public string $currency = 'USD';

    public string $productName = '';

    public string $productSummary = '';

    public string $productDescription = '';

    public function mount(array $product, array $relatedProducts = [], ?string $currency = null): void
    {
        $this->product = $product;
        $this->relatedProducts = $relatedProducts;
        $this->currency = $currency ?? config('storefront.currency', 'USD');

        $slug = $product['slug'] ?? '';
        $translatedName = __('storefront.products.' . $slug . '.name');
        $translatedSummary = __('storefront.products.' . $slug . '.summary');
        $translatedDescription = __('storefront.products.' . $slug . '.description');

        if ($translatedName === 'storefront.products.' . $slug . '.name') {
            $translatedName = $product['name'] ?? Str::headline($slug);
        }

        if ($translatedSummary === 'storefront.products.' . $slug . '.summary') {
            $translatedSummary = $product['summary'] ?? '';
        }

        if ($translatedDescription === 'storefront.products.' . $slug . '.description') {
            $translatedDescription = $product['description'] ?? '';
        }

        $this->productName = $translatedName;
        $this->productSummary = $translatedSummary;
        $this->productDescription = $translatedDescription;
    }

    public function render(): View
    {
        return view('livewire.storefront.product');
    }
}
