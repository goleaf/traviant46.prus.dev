<?php

namespace App\Support\Storefront;

/**
 * @phpstan-type StorefrontSummary array{subtotal: float|int, tax_amount: float|int, total: float|int}
 * @phpstan-type StorefrontCart array{currency?: string, summary: StorefrontSummary}
 * @phpstan-type StorefrontCheckout array{
 *     steps: array<int, string>,
 *     next_release?: string,
 *     cart: StorefrontCart,
 *     meta?: array{og_image?: string}
 * }
 * @phpstan-type StorefrontMeta array{title: string, description: string}
 * @phpstan-type StorefrontProduct array{
 *     slug: string,
 *     name?: string,
 *     summary?: string,
 *     description?: string,
 *     price: float|int,
 *     features?: array<int, string>,
 *     availability?: string,
 *     delivery?: string,
 *     image?: string,
 *     related?: array<int, string>,
 *     meta?: StorefrontMeta
 * }
 */
final class StorefrontRepository
{
    /**
     * @param array{
     *     checkout?: StorefrontCheckout,
     *     products?: array<string, StorefrontProduct>,
     *     currency?: string
     * } $dataset
     */
    public function __construct(private readonly array $dataset)
    {
    }

    /**
     * Provide the checkout payload consumed by the Livewire checkout view.
     *
     * @return StorefrontCheckout
     */
    public function checkout(): array
    {
        $summaryDefaults = [
            'subtotal' => 0.0,
            'tax_amount' => 0.0,
            'total' => 0.0,
        ];

        $checkout = $this->dataset['checkout'] ?? [];
        $checkout['steps'] = array_values($checkout['steps'] ?? []);

        $cart = $checkout['cart'] ?? [];
        $cart['summary'] = array_merge($summaryDefaults, $cart['summary'] ?? []);
        $checkout['cart'] = $cart;

        return $checkout;
    }

    /**
     * Provide structured checkout metadata for page headers and SEO tags.
     *
     * @return StorefrontMeta
     */
    public function checkoutMeta(): array
    {
        $checkout = $this->checkout();
        $meta = $checkout['meta'] ?? [];

        return [
            'title' => $meta['title'] ?? '',
            'description' => $meta['description'] ?? '',
        ];
    }

    /**
     * Locate a storefront product definition by slug.
     */
    public function findProduct(string $slug): ?array
    {
        $products = $this->dataset['products'] ?? [];

        return $products[$slug] ?? null;
    }

    /**
     * Provide a curated list of related products for cross-selling widgets.
     *
     * @return array<int, StorefrontProduct>
     */
    public function relatedProducts(string $slug): array
    {
        $products = $this->dataset['products'] ?? [];
        $product = $products[$slug] ?? null;

        if ($product === null) {
            return [];
        }

        $relatedSlugs = $product['related'] ?? [];

        if ($relatedSlugs === []) {
            $relatedSlugs = array_values(array_filter(
                array_keys($products),
                static fn (string $candidate): bool => $candidate !== $slug
            ));
        }

        $related = [];

        foreach ($relatedSlugs as $relatedSlug) {
            if (isset($products[$relatedSlug])) {
                $related[] = $products[$relatedSlug];
            }
        }

        return $related;
    }

    /**
     * Build metadata for a given product to reuse across controllers and views.
     *
     * @param StorefrontProduct $product
     *
     * @return StorefrontMeta
     */
    public function productMeta(array $product): array
    {
        $meta = $product['meta'] ?? [];
        $titleFallback = $product['name'] ?? ($product['slug'] ?? '');
        $descriptionFallback = $product['summary'] ?? '';

        return [
            'title' => $meta['title'] ?? $titleFallback,
            'description' => $meta['description'] ?? $descriptionFallback,
        ];
    }
}
