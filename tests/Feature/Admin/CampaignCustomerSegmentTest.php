<?php

declare(strict_types=1);

use App\Models\CampaignCustomerSegment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

uses(RefreshDatabase::class);

function asAdmin(): User
{
    $user = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
        'email' => 'admin@travian.dev',
    ]);

    actingAs($user);
    actingAs($user, 'admin');

    if (! app()->bound('session')) {
        config()->set('session.driver', 'array');
        app()->register(\Illuminate\Session\SessionServiceProvider::class);
    }

    if (! session()->isStarted()) {
        session()->start();
    }

    if (! app()->bound('bugsnag')) {
        app()->singleton('bugsnag', fn () => new class
        {
            public function __call(string $name, array $arguments): void
            {
                // no-op stub for tests
            }
        });
    }

    return $user;
}

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function campaignSegmentPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Example Segment',
        'slug' => 'example-segment',
        'description' => 'Testing segment creation',
        'is_active' => true,
        'filters' => json_encode([
            ['field' => 'email', 'operator' => 'contains', 'value' => '@example.com'],
        ], JSON_THROW_ON_ERROR),
    ], $overrides);
}

dataset('invalidCampaignSegmentPayloads', [
    'missing name' => [
        fn (): array => tap(campaignSegmentPayload(), static function (array &$payload): void {
            unset($payload['name']);
        }),
        'name',
        'Please enter a segment name.',
    ],
    'name not string' => [
        fn (): array => campaignSegmentPayload(['name' => ['invalid']]),
        'name',
        'The segment name must be a valid string.',
    ],
    'name too long' => [
        fn (): array => campaignSegmentPayload(['name' => str_repeat('A', 256)]),
        'name',
        'The segment name may not be greater than 255 characters.',
    ],
    'slug not string' => [
        fn (): array => campaignSegmentPayload(['slug' => ['invalid']]),
        'slug',
        'The slug must be a valid string.',
    ],
    'slug too long' => [
        fn (): array => campaignSegmentPayload(['slug' => str_repeat('s', 256)]),
        'slug',
        'The slug may not be greater than 255 characters.',
    ],
    'description not string' => [
        fn (): array => campaignSegmentPayload(['description' => ['invalid']]),
        'description',
        'The description must be a valid string.',
    ],
    'is_active not boolean' => [
        fn (): array => campaignSegmentPayload(['is_active' => 'yes']),
        'is_active',
        'The active flag must be true or false.',
    ],
]);

it('validates campaign customer segment input when storing', function (callable $payloadFactory, string $errorField, string $expectedMessage): void {
    asAdmin();

    $payload = $payloadFactory();

    $response = $this->from(route('admin.campaign-customer-segments.create'))
        ->post(route('admin.campaign-customer-segments.store'), $payload);

    $response->assertRedirect(route('admin.campaign-customer-segments.create'));
    $response->assertSessionHasErrors([
        $errorField => fn (array $messages): bool => in_array($expectedMessage, $messages, true),
    ]);
})->with('invalidCampaignSegmentPayloads');

it('creates a campaign customer segment', function () {
    asAdmin();

    $filters = [
        ['field' => 'email', 'operator' => 'contains', 'value' => '@example.com'],
    ];

    $response = $this->post(route('admin.campaign-customer-segments.store'), [
        'name' => 'Example Segment',
        'slug' => 'example-segment',
        'description' => 'Testing segment creation',
        'is_active' => true,
        'filters' => json_encode($filters, JSON_THROW_ON_ERROR),
    ]);

    $segment = CampaignCustomerSegment::first();

    $response->assertRedirect(route('admin.campaign-customer-segments.edit', $segment));

    expect($segment)->not->toBeNull();
    expect($segment->name)->toBe('Example Segment');
    expect($segment->filters)->toBe($filters);
    expect($segment->match_count)->toBeInt();
});

it('validates filters input as JSON', function () {
    asAdmin();

    $response = $this->from(route('admin.campaign-customer-segments.create'))->post(route('admin.campaign-customer-segments.store'), [
        'name' => 'Invalid Filters',
        'slug' => 'invalid-filters',
        'filters' => 'invalid-json',
    ]);

    $response->assertRedirect(route('admin.campaign-customer-segments.create'));
    $response->assertSessionHasErrors('filters');
    assertDatabaseCount('campaign_customer_segments', 0);
});

it('updates a campaign customer segment', function () {
    asAdmin();

    $segment = CampaignCustomerSegment::factory()->create([
        'slug' => 'initial-segment',
        'filters' => [
            ['field' => 'email', 'operator' => 'contains', 'value' => '@example.com'],
        ],
    ]);

    $response = $this->put(route('admin.campaign-customer-segments.update', $segment), [
        'name' => 'Updated Segment',
        'slug' => 'updated-segment',
        'description' => 'Updated description',
        'is_active' => false,
        'filters' => json_encode([
            ['field' => 'username', 'operator' => 'starts_with', 'value' => 'travian'],
        ], JSON_THROW_ON_ERROR),
    ]);

    $response->assertRedirect(route('admin.campaign-customer-segments.edit', $segment));
    $segment->refresh();

    expect($segment->name)->toBe('Updated Segment');
    expect($segment->slug)->toBe('updated-segment');
    expect($segment->is_active)->toBeFalse();
    expect($segment->filters)->toBe([
        ['field' => 'username', 'operator' => 'starts_with', 'value' => 'travian'],
    ]);
});

it('enforces unique slug when updating a campaign customer segment', function (): void {
    asAdmin();

    $segment = CampaignCustomerSegment::factory()->create([
        'slug' => 'initial-segment',
    ]);

    CampaignCustomerSegment::factory()->create([
        'slug' => 'conflicting-segment',
    ]);

    $payload = campaignSegmentPayload([
        'name' => 'Updated Segment',
        'slug' => 'conflicting-segment',
    ]);

    $response = $this->from(route('admin.campaign-customer-segments.edit', $segment))
        ->put(route('admin.campaign-customer-segments.update', $segment), $payload);

    $response->assertRedirect(route('admin.campaign-customer-segments.edit', $segment));
    $response->assertSessionHasErrors([
        'slug' => fn (array $messages): bool => in_array('Another segment is already using this slug.', $messages, true),
    ]);
});

it('recalculates the match count using the filters', function () {
    asAdmin();

    User::factory()->create(['email' => 'alpha@example.com']);
    User::factory()->create(['email' => 'beta@example.com']);
    User::factory()->create(['email' => 'gamma@travian.dev']);

    $segment = CampaignCustomerSegment::factory()->create([
        'filters' => [
            ['field' => 'email', 'operator' => 'contains', 'value' => '@example.com'],
        ],
        'match_count' => 0,
    ]);

    $response = $this->post(route('admin.campaign-customer-segments.recalculate', $segment));

    $response->assertRedirect();
    $segment->refresh();

    expect($segment->match_count)->toBe(2);
    expect($segment->last_calculated_at)->not->toBeNull();
});
