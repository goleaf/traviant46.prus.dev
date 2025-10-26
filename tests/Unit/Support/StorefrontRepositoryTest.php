<?php

use App\Support\Storefront\StorefrontRepository;

beforeEach(function (): void {
    $this->repository = app(StorefrontRepository::class);
});

it('returns checkout data with required structure', function (): void {
    $checkout = $this->repository->checkout();

    expect($checkout)
        ->toHaveKey('steps')
        ->and($checkout['steps'])->toBeArray()->not->toBeEmpty()
        ->and($checkout['cart'])->toHaveKey('summary')
        ->and($checkout['cart']['summary'])
        ->toHaveKeys(['subtotal', 'tax_amount', 'total']);

    expect($checkout['cart']['summary']['total'])
        ->toBeFloat()
        ->toBeGreaterThan(0.0);
});

it('returns checkout metadata for seo integration', function (): void {
    $meta = $this->repository->checkoutMeta();

    expect($meta)
        ->toHaveKeys(['title', 'description'])
        ->and($meta['title'])->not->toBeEmpty();
});

it('finds a product and exposes its attributes', function (): void {
    $product = $this->repository->findProduct('starter-pack');

    expect($product)
        ->not->toBeNull()
        ->and($product)
        ->toHaveKeys(['slug', 'price', 'features']);

    expect($product['features'])
        ->toBeArray()
        ->not->toBeEmpty();
});

it('resolves related products for cross selling', function (): void {
    $related = $this->repository->relatedProducts('village-expansion');

    expect($related)
        ->toBeArray()
        ->not->toBeEmpty();

    $slugs = array_column($related, 'slug');

    expect($slugs)
        ->toContain('starter-pack')
        ->toContain('hero-bundle')
        ->not->toContain('village-expansion');
});

it('creates product metadata with graceful fallbacks', function (): void {
    $product = $this->repository->findProduct('hero-bundle');

    expect($product)->not->toBeNull();

    $meta = $this->repository->productMeta($product);

    expect($meta)
        ->toHaveKeys(['title', 'description'])
        ->and($meta['title'])->toContain('Hero Mastery Bundle');

    $fallbackMeta = $this->repository->productMeta([
        'slug' => 'custom-item',
        'name' => 'Custom Item',
        'summary' => 'Limited edition drop.',
        'price' => 1,
    ]);

    expect($fallbackMeta)
        ->toMatchArray([
            'title' => 'Custom Item',
            'description' => 'Limited edition drop.',
        ]);
});
