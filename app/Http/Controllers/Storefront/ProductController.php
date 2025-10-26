<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
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
                'image' => asset($productData['image'] ?? 'images/storefront/product-og.jpg'),
            ],
        ]);
    }
}
