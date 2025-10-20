<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
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
                'image' => asset($checkout['meta']['og_image'] ?? 'images/storefront/checkout-og.jpg'),
            ],
        ]);
    }
}
