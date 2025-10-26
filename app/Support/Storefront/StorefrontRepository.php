<?php

namespace App\Support\Storefront;

use Illuminate\Support\Arr;

/**
 * Provide storefront data for controllers and Livewire views.
 */
class StorefrontRepository
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $products;

    public function __construct()
    {
        $this->products = [
            'starter-pack' => [
                'slug' => 'starter-pack',
                'name' => 'Starter Pack',
                'summary' => 'Jumpstart a new village with resources and boosts.',
                'description' => 'Includes enough resources, queue boosts, and premium time to accelerate early growth.',
                'price' => 9.99,
                'features' => ['instant_gold', 'queue_boost', 'daily_rewards'],
                'availability' => 'in_stock',
                'delivery' => 'instant',
                'image' => 'images/storefront/products/starter-pack.jpg',
            ],
            'veteran-bundle' => [
                'slug' => 'veteran-bundle',
                'name' => 'Veteran Bundle',
                'summary' => 'Optimise developed villages with advanced bonuses.',
                'description' => 'Curated boosts, instant training vouchers, and production enhancers for mid-game empires.',
                'price' => 19.99,
                'features' => ['vip_support', 'expansion_vouchers', 'advanced_reports'],
                'availability' => 'limited',
                'delivery' => 'scheduled',
                'image' => 'images/storefront/products/veteran-bundle.jpg',
            ],
            'artisan-pack' => [
                'slug' => 'artisan-pack',
                'name' => 'Artisan Pack',
                'summary' => 'Unlock creative cosmetics and alliance enhancements.',
                'description' => 'Cosmetic skins, alliance banner slots, and seasonal emotes for dedicated governors.',
                'price' => 14.99,
                'features' => ['cosmetic_skins', 'alliance_banner_slot', 'seasonal_emotes'],
                'availability' => 'preorder',
                'delivery' => 'instant',
                'image' => 'images/storefront/products/artisan-pack.jpg',
            ],
        ];
    }

    /**
     * Build the checkout payload with pricing summaries and funnel metadata.
     *
     * @return array<string, mixed>
     */
    public function checkout(): array
    {
        $items = [
            [
                'slug' => 'starter-pack',
                'quantity' => 1,
                'price' => Arr::get($this->products, 'starter-pack.price', 0.0),
            ],
        ];

        $subtotal = array_reduce($items, static function (float $carry, array $item): float {
            return $carry + ($item['price'] * $item['quantity']);
        }, 0.0);

        $taxAmount = round($subtotal * 0.2, 2);
        $total = round($subtotal + $taxAmount, 2);

        return [
            'steps' => ['select-package', 'choose-payment', 'confirm-order'],
            'next_release' => 'storefront.checkout.next_release_hint',
            'cart' => [
                'currency' => config('storefront.currency', 'USD'),
                'items' => $items,
                'summary' => [
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                ],
            ],
            'meta' => [
                'og_image' => 'images/storefront/checkout-og.jpg',
            ],
        ];
    }

    /**
     * Describe the checkout page meta tags.
     *
     * @return array<string, string>
     */
    public function checkoutMeta(): array
    {
        return [
            'title' => 'TravianT Checkout',
            'description' => 'Review your TravianT purchase and confirm your order.',
        ];
    }

    /**
     * Locate a storefront product by slug.
     *
     * @return array<string, mixed>|null
     */
    public function findProduct(string $product): ?array
    {
        return $this->products[$product] ?? null;
    }

    /**
     * Determine related products excluding the active selection.
     *
     * @return array<int, array<string, mixed>>
     */
    public function relatedProducts(string $product): array
    {
        return array_values(array_filter($this->products, static function (array $candidate) use ($product): bool {
            return ($candidate['slug'] ?? null) !== $product;
        }));
    }

    /**
     * Build social media metadata for a product detail page.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, string>
     */
    public function productMeta(array $product): array
    {
        $name = $product['name'] ?? Arr::get($product, 'slug', 'TravianT Product');
        $summary = $product['summary'] ?? 'Browse premium bundles to accelerate your TravianT world.';

        return [
            'title' => sprintf('%s – TravianT Storefront', $name),
            'description' => $summary,
        ];
    }
}
