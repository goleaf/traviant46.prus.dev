<?php

use App\Services\Game\LegacyModelCatalogue;
use Illuminate\Filesystem\Filesystem;

it('maps every legacy model class to a Laravel service', function () {
    $catalogue = new LegacyModelCatalogue();
    $entries = $catalogue->all();

    $filesystem = new Filesystem();
    $root = base_path('_travian/main_script/include/Model');

    $legacyClasses = collect($filesystem->allFiles($root))
        ->filter(fn ($file) => $file->getExtension() === 'php')
        ->map(function ($file) use ($root) {
            $relative = trim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relative = str_replace(['/', '.php'], ['\\', ''], $relative);

            return $relative;
        })
        ->values();

    expect($entries)->toHaveKeys($legacyClasses->all());

    foreach ($entries as $definition) {
        expect($definition)->toHaveKeys(['responsibility', 'service', 'models']);
        expect(class_exists($definition['service']))->toBeTrue();

        foreach ($definition['models'] as $modelClass) {
            expect(class_exists($modelClass))->toBeTrue();
        }
    }
});
