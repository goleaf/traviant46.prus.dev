<?php

declare(strict_types=1);

use App\Support\Storefront\StorefrontRepository;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    $this->withoutMiddleware();

    if (class_exists(Bugsnag::class)) {
        Bugsnag::swap(new class
        {
            public function setMetaData(array $meta): void {}
        });
    }
});

it('renders the storefront cart page', function () {
    $this->get(route('storefront.cart'))
        ->assertSuccessful()
        ->assertViewIs('storefront.cart');
});

it('renders the storefront catalogue page', function () {
    $this->get(route('storefront.catalogue'))
        ->assertSuccessful()
        ->assertViewIs('storefront.catalogue');
});

it('renders the storefront checkout page', function () {
    $checkoutData = [
        'cart' => [
            'currency' => 'USD',
            'summary' => [
                'subtotal' => 129.00,
                'tax_amount' => 12.90,
                'total' => 141.90,
            ],
        ],
        'meta' => [
            'og_image' => 'images/storefront/checkout-og.jpg',
        ],
    ];

    $meta = [
        'title' => 'Checkout | Travian Storefront',
        'description' => 'Complete your Travian purchase in a guided, secure flow.',
    ];

    mock(StorefrontRepository::class, function ($mock) use ($checkoutData, $meta) {
        $mock->shouldReceive('checkout')->once()->andReturn($checkoutData + [
            'steps' => [
                'cart_review',
                'billing_details',
                'payment',
                'confirmation',
            ],
            'next_release' => 'New launch bundles arrive next season.',
        ]);
        $mock->shouldReceive('checkoutMeta')->once()->andReturn($meta);
    });

    $response = $this->get(route('storefront.checkout'));

    $response->assertSuccessful();
    $response->assertViewIs('storefront.checkout');
    $response->assertViewHas('checkout', fn (array $checkout) => $checkout['cart'] === $checkoutData['cart']);
    $response->assertViewHas('currency', $checkoutData['cart']['currency']);
    $response->assertViewHas('meta', [
        'title' => $meta['title'],
        'description' => $meta['description'],
        'image' => asset($checkoutData['meta']['og_image']),
    ]);
});

it('renders the storefront product detail page', function () {
    $slug = 'founders-pack';

    $product = [
        'slug' => $slug,
        'name' => 'Founders Pack',
        'summary' => 'Kickstart your village with premium boosts and exclusive cosmetics.',
        'description' => 'The Founders Pack includes pre-launch resources, premium boosts, and a unique chat flair reserved for early supporters.',
        'price' => 149.00,
        'currency' => 'USD',
        'features' => [
            'early_access',
            'resource_boost',
            'exclusive_title',
        ],
        'availability' => 'in_stock',
        'delivery' => 'instant',
        'image' => 'images/storefront/founders-pack.jpg',
        'related' => ['strategist-kit', 'artisan-bundle'],
    ];

    $related = [
        [
            'slug' => 'strategist-kit',
            'name' => 'Strategist Kit',
            'summary' => 'Unlock detailed analytics, pre-built troop templates, and advanced planning tools.',
            'price' => 99.00,
            'image' => 'images/storefront/strategist-kit.jpg',
        ],
    ];

    $meta = [
        'title' => 'Founders Pack | Travian Storefront',
        'description' => 'Kickstart your village with premium boosts and exclusive cosmetics.',
    ];

    mock(StorefrontRepository::class, function ($mock) use ($slug, $product, $related, $meta) {
        $mock->shouldReceive('findProduct')->once()->with($slug)->andReturn($product);
        $mock->shouldReceive('relatedProducts')->once()->with($slug)->andReturn($related);
        $mock->shouldReceive('productMeta')->once()->with($product)->andReturn($meta);
    });

    $response = $this->get(route('storefront.products.show', $slug));

    $response->assertSuccessful();
    $response->assertViewIs('storefront.product');
    $response->assertViewHas('product', $product);
    $response->assertViewHas('relatedProducts', $related);
    $response->assertViewHas('currency', 'USD');
    $response->assertViewHas('meta', [
        'title' => $meta['title'],
        'description' => $meta['description'],
        'image' => asset($product['image']),
    ]);
});
