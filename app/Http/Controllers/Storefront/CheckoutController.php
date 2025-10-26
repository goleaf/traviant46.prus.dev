<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    /**
     * @var string 1200x630 PNG optimized for social sharing stored under public/images/storefront.
     */
    private const DEFAULT_CHECKOUT_SOCIAL_PREVIEW = 'images/storefront/checkout-og.png';

    public function __invoke(Request $request, StorefrontRepository $repository): Response
    {
        $checkout = $repository->checkout();
        $meta = $repository->checkoutMeta();

        return response()->view('storefront.checkout', [
            'checkout' => $checkout,
            'currency' => $checkout['cart']['currency'] ?? config('storefront.currency', 'USD'),
            'meta' => [
                'title' => $meta['title'],
                'description' => $meta['description'],
                'image' => $this->socialPreviewUrl($checkout['meta']['og_image'] ?? null, self::DEFAULT_CHECKOUT_SOCIAL_PREVIEW),
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
