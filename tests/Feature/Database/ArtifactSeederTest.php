<?php

declare(strict_types=1);

use Database\Seeders\ArtifactSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\seed;

beforeEach(function (): void {
    Schema::dropIfExists('artifacts');

    $migration = require database_path('migrations/2025_10_26_223734_create_artifacts_table.php');
    $migration->up();
});

afterEach(function (): void {
    Schema::dropIfExists('artifacts');
});

it('seeds artifacts with expected scopes and effects', function (): void {
    seed(ArtifactSeeder::class);

    $records = DB::table('artifacts')->get();
    expect($records)->toHaveCount(22);

    expect(DB::table('artifacts')->where('treasury_level_req', 10)->count())->toBe(8);
    expect(DB::table('artifacts')->where('treasury_level_req', 20)->count())->toBe(14);
    expect(DB::table('artifacts')->where('size', 'small')->count())->toBe(8);
    expect(DB::table('artifacts')->where('size', 'large')->count())->toBe(7);
    expect(DB::table('artifacts')->where('size', 'unique')->count())->toBe(7);

    $architect = DB::table('artifacts')->where('code', 'architects-secret-small')->first();
    expect($architect)->not->toBeNull();
    $architectEffect = json_decode($architect->effect, true, 512, JSON_THROW_ON_ERROR);
    expect($architectEffect)->toMatchArray([
        'category' => 'architects-secret',
        'size' => 'small',
        'scope' => 'village',
    ]);
    expect($architectEffect['modifiers'][0]['value'])->toEqual(4.0);
    $architectTextEffects = json_decode($architect->text_effects, true, 512, JSON_THROW_ON_ERROR);
    expect($architectTextEffects[0])->toContain('Buildings in this village are 4× harder to destroy');
    expect($architectTextEffects)->toContain('Requires treasury level 10 to capture and activate this small artifact.');

    $diet = DB::table('artifacts')->where('code', 'diet-control-large')->first();
    expect($diet)->not->toBeNull();
    $dietEffect = json_decode($diet->effect, true, 512, JSON_THROW_ON_ERROR);
    expect($dietEffect['modifiers'][0]['value'])->toBe(0.75);
    $dietTextEffects = json_decode($diet->text_effects, true, 512, JSON_THROW_ON_ERROR);
    expect($dietTextEffects)->toContain('Requires treasury level 20 to capture and activate this large artifact.');

    $cranny = DB::table('artifacts')->where('code', 'rivals-confusion-unique')->first();
    expect($cranny)->not->toBeNull();
    $crannyEffect = json_decode($cranny->effect, true, 512, JSON_THROW_ON_ERROR);
    expect($crannyEffect['modifiers'][0]['value'])->toBe(500);
    expect(array_column($crannyEffect['modifiers'], 'operation'))->toContain('randomize');
    $crannyTextEffects = json_decode($cranny->text_effects, true, 512, JSON_THROW_ON_ERROR);
    expect($crannyTextEffects)->toContain('Requires treasury level 20 to capture and activate this unique artifact.');

    $storage = DB::table('artifacts')->where('code', 'storage-masterplan-small')->first();
    expect($storage)->not->toBeNull();
    $storageEffect = json_decode($storage->effect, true, 512, JSON_THROW_ON_ERROR);
    expect($storageEffect['modifiers'][0])->toMatchArray([
        'operation' => 'unlock',
        'applies_to' => 'village',
    ]);
    $storageTextEffects = json_decode($storage->text_effects, true, 512, JSON_THROW_ON_ERROR);
    expect($storageTextEffects)->toContain('Requires treasury level 10 to capture and activate this small artifact.');
});
