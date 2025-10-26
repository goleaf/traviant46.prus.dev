<?php

use App\Support\Storefront\StorefrontRepository;
use function Pest\Laravel\get;

beforeEach(function (): void {
    // Ensure the placeholder repository is available for every storefront request during testing.
    $this->app->instance(StorefrontRepository::class, new StorefrontRepository());
});

it('renders the storefront catalogue successfully', function (): void {
    // Visit the catalogue route to confirm the name and view wiring.
    get(route('storefront.catalogue'))->assertOk();
});

it('renders the storefront cart successfully', function (): void {
    // Visit the cart route to validate the temporary Blade placeholder configuration.
    get(route('storefront.cart'))->assertOk();
});

it('renders the storefront checkout successfully', function (): void {
    // Visit the checkout route to ensure the controller resolves correctly.
    get(route('storefront.checkout'))->assertOk();
});

it('renders the storefront product detail successfully', function (): void {
    // Visit the dynamic product route to verify the slug parameter resolves without error.
    get(route('storefront.products.show', ['product' => 'starter-kit']))->assertOk();
});
