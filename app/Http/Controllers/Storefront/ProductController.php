<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * @var string 1200x630 PNG optimized for social sharing stored under public/images/storefront.
     */
    private const DEFAULT_PRODUCT_SOCIAL_PREVIEW = 'images/storefront/product-og.png';

    public function __invoke(Request $request, StorefrontRepository $repository, string $product): Response
    {
        $productData = $repository->findProduct($product);

        if ($productData === null) {
            abort(404);
        }

        $related = $repository->relatedProducts($product);
        $meta = $repository->productMeta($productData);

        return response()->view('storefront.product', [
            'product' => $productData,
            'relatedProducts' => $related,
            'currency' => config('storefront.currency', 'USD'),
            'meta' => [
                'title' => $meta['title'],
                'description' => $meta['description'],
                'image' => $this->socialPreviewUrl($productData['image'] ?? null, self::DEFAULT_PRODUCT_SOCIAL_PREVIEW),
            ],
        ]);
    }

    private function socialPreviewUrl(?string $candidate, string $fallbackPath): string
    {
        if ($candidate === null || $candidate === '') {
            return asset($fallbackPath);
        }

        if ($this->isExternalUrl($candidate)) {
            return $candidate;
        }

        if (! file_exists(public_path($candidate))) {
            return asset($fallbackPath);
        }

        return asset($candidate);
    }

    private function isExternalUrl(string $path): bool
    {
        return str_starts_with($path, '//') || filter_var($path, FILTER_VALIDATE_URL) !== false;
    }
}
