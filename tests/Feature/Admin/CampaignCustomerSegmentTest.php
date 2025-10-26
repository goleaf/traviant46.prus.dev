<?php

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

    return $user;
}

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
