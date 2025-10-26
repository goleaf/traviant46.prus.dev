<?php

declare(strict_types=1);

use App\Services\Security\IpLookupService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

it('performs lookups via the configured provider and caches the result', function (): void {
    Http::fake([
        'http://ip-api.com/*' => Http::response([
            'status' => 'success',
            'query' => '203.0.113.200',
            'country' => 'Example',
            'regionName' => 'Region',
            'city' => 'City',
            'isp' => 'ISP',
            'as' => 'AS65000 Example',
            'timezone' => 'UTC',
            'proxy' => false,
            'mobile' => false,
            'hosting' => false,
        ]),
    ]);

    $cache = new ArrayStore;
    $service = new IpLookupService(new Repository($cache));

    $first = $service->lookup('203.0.113.200');
    $second = $service->lookup('203.0.113.200');

    expect($first['country'])->toBe('Example');

    Http::assertSentCount(1);
    expect($second)->toBe($first);
});

it('rejects invalid ip addresses', function (): void {
    $service = new IpLookupService(cache()->store());

    $this->expectException(InvalidArgumentException::class);
    $service->lookup('not-an-ip');
});
