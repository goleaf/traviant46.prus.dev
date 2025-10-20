<?php

use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it generates slug from the name when saving', function (): void {
    $category = NewsCategory::create([
        'name' => 'Server Updates',
    ]);

    expect($category->slug)->toBe('server-updates');
});

test('it normalizes provided slug before saving', function (): void {
    $category = NewsCategory::create([
        'name' => 'Weekly Roundup',
        'slug' => 'Weekly Roundup 2025',
    ]);

    expect($category->slug)->toBe('weekly-roundup-2025');
});
