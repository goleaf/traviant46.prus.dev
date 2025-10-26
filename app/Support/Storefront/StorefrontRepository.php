<?php

namespace App\Support\Storefront;

/**
 * This repository provides temporary storefront data so the placeholder routes can render while the
 * production integrations are still under construction.
 */
class StorefrontRepository
{
    /**
     * Build a placeholder checkout payload that mirrors the structure required by the checkout template.
     *
     * @return array<string, mixed>
     */
    public function checkout(): array
    {
        return [
            'steps' => ['cart', 'details', 'confirmation'],
            'next_release' => 'storefront.checkout.next_release',
            'cart' => [
                'currency' => config('storefront.currency', 'USD'),
                'summary' => [
                    'subtotal' => 49.00,
                    'tax_amount' => 4.90,
                    'total' => 53.90,
                ],
            ],
        ];
    }

    /**
     * Provide meta tags for the checkout template until dynamic content is delivered by the product API.
     *
     * @return array{title: string, description: string}
     */
    public function checkoutMeta(): array
    {
        return [
            'title' => __('storefront.checkout.meta.title', ['default' => 'Checkout Preview']),
            'description' => __('storefront.checkout.meta.description', ['default' => 'Review your order details before completing checkout.']),
        ];
    }

    /**
     * Retrieve a storefront product by slug using an in-memory catalogue suitable for early development.
     *
     * @param string $slug
     * @return array<string, mixed>|null
     */
    public function findProduct(string $slug): ?array
    {
        $products = $this->products();

        return $products[$slug] ?? null;
    }

    /**
     * Return related product suggestions while the production recommendation service is unavailable.
     *
     * @param string $slug
     * @return array<int, array<string, mixed>>
     */
    public function relatedProducts(string $slug): array
    {
        return collect($this->products())
            ->reject(static fn (array $product) => $product['slug'] === $slug)
            ->values()
            ->map(static function (array $product): array {
                return [
                    'slug' => $product['slug'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                ];
            })
            ->all();
    }

    /**
     * Generate social meta tags for a storefront product using the placeholder information we have available.
     *
     * @param array<string, mixed> $product
     * @return array{title: string, description: string, image: string}
     */
    public function productMeta(array $product): array
    {
        return [
            'title' => $product['name'] ?? __('storefront.product.meta.title'),
            'description' => $product['summary'] ?? __('storefront.product.meta.description'),
            'image' => asset($product['image'] ?? 'images/storefront/product-og.jpg'),
        ];
    }

    /**
     * Expose a trimmed catalogue for the storefront listing placeholder so the Blade template has data to render.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalogue(): array
    {
        return array_values($this->products());
    }

    /**
     * Define the temporary product catalogue as an associative array keyed by slug.
     *
     * @return array<string, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            'starter-kit' => [
                'slug' => 'starter-kit',
                'name' => 'Starter Kit',
                'summary' => 'Everything required to launch your Travian-inspired world in minutes.',
                'description' => 'Includes default server presets, onboarding tutorials, and automation helpers so teams can iterate quickly.',
                'price' => 29.00,
                'features' => ['instant-setup', 'priority-support'],
                'availability' => 'in_stock',
                'delivery' => 'instant',
                'image' => 'images/storefront/products/starter-kit.jpg',
            ],
            'expansion-pack' => [
                'slug' => 'expansion-pack',
                'name' => 'Expansion Pack',
                'summary' => 'Unlock advanced artefacts, hero cosmetics, and alliance dashboards.',
                'description' => 'Ships with curated Livewire components, map overlays, and quest templates aligned with the migration roadmap.',
                'price' => 59.00,
                'features' => ['hero-upgrades', 'alliance-tools', 'map-enhancements'],
                'availability' => 'limited',
                'delivery' => 'scheduled',
                'image' => 'images/storefront/products/expansion-pack.jpg',
            ],
        ];
    }
}
