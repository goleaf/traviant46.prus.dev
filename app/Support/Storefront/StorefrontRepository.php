<?php

namespace App\Support\Storefront;

class StorefrontRepository
{
    /**
     * @return array{
     *     cart: array{
     *         currency: string,
     *         summary: array{subtotal: float, tax_amount: float, total: float}
     *     },
     *     steps: array<int, string>,
     *     next_release: string,
     *     meta: array{og_image: string}
     * }
     */
    public function checkout(): array
    {
        $currency = config('storefront.currency', 'USD');

        $summary = [
            'subtotal' => 129.00,
            'tax_amount' => 12.90,
        ];

        return [
            'cart' => [
                'currency' => $currency,
                'summary' => $summary + ['total' => $summary['subtotal'] + $summary['tax_amount']],
            ],
            'steps' => [
                'cart_review',
                'billing_details',
                'payment',
                'confirmation',
            ],
            'next_release' => 'New launch bundles arrive next season.',
            'meta' => [
                'og_image' => 'images/storefront/checkout-og.png',
            ],
        ];
    }

    /**
     * @return array{title: string, description: string}
     */
    public function checkoutMeta(): array
    {
        return [
            'title' => 'Checkout | Travian Storefront',
            'description' => 'Complete your Travian purchase in a guided, secure flow.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findProduct(string $slug): ?array
    {
        $products = $this->products();

        return $products[$slug] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function relatedProducts(string $slug): array
    {
        $products = $this->products();
        if (! isset($products[$slug])) {
            return [];
        }

        $related = $products[$slug]['related'] ?? [];
        if ($related === []) {
            return [];
        }

        $items = [];
        foreach ($related as $relatedSlug) {
            if (! isset($products[$relatedSlug])) {
                continue;
            }

            $items[] = $this->productListItem($products[$relatedSlug]);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $product
     * @return array{title: string, description: string}
     */
    public function productMeta(array $product): array
    {
        $name = $product['name'] ?? 'Travian Product';
        $summary = $product['summary'] ?? 'Explore exclusive Travian upgrades.';

        return [
            'title' => $name . ' | Travian Storefront',
            'description' => $summary,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function products(): array
    {
        $currency = config('storefront.currency', 'USD');

        return [
            'founders-pack' => [
                'slug' => 'founders-pack',
                'name' => 'Founders Pack',
                'summary' => 'Kickstart your village with premium boosts and exclusive cosmetics.',
                'description' => 'The Founders Pack includes pre-launch resources, premium boosts, and a unique chat flair reserved for early supporters.',
                'price' => 149.00,
                'currency' => $currency,
                'features' => [
                    'early_access',
                    'resource_boost',
                    'exclusive_title',
                ],
                'availability' => 'in_stock',
                'delivery' => 'instant',
                'image' => 'images/storefront/founders-pack.jpg',
                'related' => [
                    'strategist-kit',
                    'artisan-bundle',
                ],
            ],
            'strategist-kit' => [
                'slug' => 'strategist-kit',
                'name' => 'Strategist Kit',
                'summary' => 'Unlock detailed analytics, pre-built troop templates, and advanced planning tools.',
                'description' => 'Designed for alliance leaders, the Strategist Kit includes dashboards, early access to war reports, and customizable unit presets.',
                'price' => 99.00,
                'currency' => $currency,
                'features' => [
                    'analytics_suite',
                    'troop_presets',
                    'alliance_reports',
                ],
                'availability' => 'in_stock',
                'delivery' => 'instant',
                'image' => 'images/storefront/strategist-kit.jpg',
                'related' => [
                    'founders-pack',
                    'artisan-bundle',
                ],
            ],
            'artisan-bundle' => [
                'slug' => 'artisan-bundle',
                'name' => 'Artisan Bundle',
                'summary' => 'Level up your infrastructure with focused building boosts and queue slots.',
                'description' => 'Great for city planners, the Artisan Bundle speeds up construction, adds queue slots, and unlocks a cosmetic village skin.',
                'price' => 79.00,
                'currency' => $currency,
                'features' => [
                    'build_speed_bonus',
                    'queue_slots',
                    'village_skin',
                ],
                'availability' => 'in_stock',
                'delivery' => 'instant',
                'image' => 'images/storefront/artisan-bundle.jpg',
                'related' => [
                    'founders-pack',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    protected function productListItem(array $product): array
    {
        return [
            'slug' => $product['slug'] ?? '',
            'name' => $product['name'] ?? '',
            'summary' => $product['summary'] ?? '',
            'price' => $product['price'] ?? 0.0,
            'image' => $product['image'] ?? 'images/storefront/product-og.png',
        ];
    }
}
