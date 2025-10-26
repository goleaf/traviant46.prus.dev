<?php

declare(strict_types=1);

namespace Tests\Unit\Migration\Transforms;

use App\Services\Migration\Transforms\LoginIpLogRowTransformer;
use InvalidArgumentException;
use Tests\TestCase;

class LoginIpLogRowTransformerTest extends TestCase
{
    public function test_transform_maps_login_ip_row(): void
    {
        $payload = LoginIpLogRowTransformer::transform([
            'uid' => 7,
            'ip' => ip2long('203.0.113.10'),
            'time' => 1_700_000_123,
        ]);

        $this->assertSame(7, $payload['user_id']);
        $this->assertSame('203.0.113.10', $payload['ip_address']);
        $this->assertSame(1_700_000_123, $payload['recorded_at']->getTimestamp());
    }

    public function test_transform_rejects_invalid_ip(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LoginIpLogRowTransformer::transform([
            'uid' => 7,
            'ip' => 0,
            'time' => 1,
        ]);
    }
}
