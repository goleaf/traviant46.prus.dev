<?php

use App\Support\Storefront\StorefrontRepository;
use Illuminate\Testing\TestResponse;

/**
 * Feature coverage for the storefront entry points.
 */
it('renders the checkout page with the expected Livewire component', function (): void {
    /** @var TestResponse $response */
    $response = $this->get(route('storefront.checkout'));

    $response->assertOk();
    $response->assertSee('livewire:storefront.checkout', false);
});

it('renders the product detail page with the expected Livewire component', function (): void {
    /** @var StorefrontRepository $repository */
    $repository = app(StorefrontRepository::class);
    $product = $repository->findProduct('starter-pack');

    expect($product)->not->toBeNull();

    /** @var TestResponse $response */
    $response = $this->get(route('storefront.products.show', $product['slug']));

    $response->assertOk();
    $response->assertSee('livewire:storefront.product', false);
});
