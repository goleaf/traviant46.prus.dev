<?php

use App\Filament\Resources\NewsCategoryResource;
use App\Models\NewsCategory;
use App\Models\User;
use Filament\PanelRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin panel registers the news category resource', function (): void {
    $panel = app(PanelRegistry::class)->get('admin');

    expect($panel)->not->toBeNull();
    expect($panel->getResources())->toContain(NewsCategoryResource::class);
});

test('news categories pages are accessible to administrators', function (): void {
    $user = User::factory()->create([
        'legacy_uid' => 0,
        'email_verified_at' => now(),
        'password' => 'secret-password',
    ]);

    $this->actingAs($user);

    $response = $this->get('/admin/news-categories');
    $response->assertOk();

    $this->get('/admin/news-categories/create')->assertOk();
});

test('existing categories can be edited through the filament resource', function (): void {
    $user = User::factory()->create([
        'legacy_uid' => 0,
        'email_verified_at' => now(),
        'password' => 'secret-password',
    ]);

    $this->actingAs($user);

    $category = NewsCategory::factory()->create();

    $this->get("/admin/news-categories/{$category->getKey()}/edit")
        ->assertOk();
});
