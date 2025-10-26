<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

it('renders translated checkout headline', function (): void {
    Route::name('storefront.cart')->get('/storefront/cart', static fn () => 'cart');

    /**
     * @var array<string, mixed> $checkout Example checkout payload used to render the component.
     */
    $checkout = [
        'steps' => ['select_bundle', 'confirm_account', 'review_order', 'secure_payment'],
        'cart' => [
            'summary' => [
                'subtotal' => 49.99,
                'tax_amount' => 9.5,
                'total' => 59.49,
            ],
            'currency' => 'USD',
        ],
    ];

    $component = Livewire::test('storefront.checkout', [
        'checkout' => $checkout,
        'currency' => 'USD',
    ]);

    $component->assertSee(__('storefront.checkout.headline'));
});
