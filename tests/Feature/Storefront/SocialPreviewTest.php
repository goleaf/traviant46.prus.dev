<?php

declare(strict_types=1);

use App\Http\Middleware\InjectRequestContext;

dataset('storefront-preview-pages', [
    'product page' => ['storefront.products.show', ['founders-pack']],
    'checkout page' => ['storefront.checkout', []],
]);

it('renders social preview meta image pointing to an existing asset', function (string $routeName, array $parameters): void {
    $this->withoutMiddleware([InjectRequestContext::class]);

    $response = $this->get(route($routeName, $parameters));

    $response->assertOk();

    $metaUrl = storefrontOgImageUrl($response->getContent());
    expect($metaUrl)->not->toBeNull();

    $host = parse_url($metaUrl, PHP_URL_HOST);
    $expectedHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
    expect($host)->toBe($expectedHost);

    $path = parse_url($metaUrl, PHP_URL_PATH);
    expect($path)->toBeString();

    $absolutePath = public_path(ltrim((string) $path, '/'));
    expect(is_file($absolutePath))->toBeTrue();
})->with('storefront-preview-pages');

function storefrontOgImageUrl(string $html): ?string
{
    $pattern = '/<meta\s+[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i';
    if (preg_match($pattern, $html, $matches) === 1) {
        return $matches[1];
    }

    $reversePattern = '/<meta\s+[^>]*content=["\']([^"\']+)["\'][^>]*property=["\']og:image["\'][^>]*>/i';
    if (preg_match($reversePattern, $html, $matches) === 1) {
        return $matches[1];
    }

    return null;
}
