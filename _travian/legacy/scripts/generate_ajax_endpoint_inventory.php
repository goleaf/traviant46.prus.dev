#!/usr/bin/env php
<?php
declare(strict_types=1);

$rootDir = realpath(__DIR__ . '/../_travian/controllers/Ajax');
if ($rootDir === false) {
    fwrite(STDERR, "Unable to locate legacy Ajax controllers directory\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS)
);

$rows = [];
foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $baseName = $file->getBasename('.php');
    if ($baseName === 'AjaxBase' || $baseName === 'Ajax') {
        continue;
    }
    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($rootDir) + 1));
    $segments = explode('/', $relativePath);
    $segments[count($segments) - 1] = $baseName;
    $legacyClass = 'Controller\\Ajax\\' . implode('\\', $segments);

    $uriSegments = array_map(static function (string $segment): string {
        $segment = str_replace(['_', ' '], '-', $segment);
        $segment = preg_replace('/(?<!^)([A-Z])/', '-$1', $segment);
        $segment = strtolower($segment ?? '');
        $segment = preg_replace('/-+/', '-', $segment);
        return trim($segment, '-');
    }, $segments);

    $rows[] = [
        'cmd' => $baseName,
        'legacyFile' => $relativePath,
        'legacyClass' => $legacyClass,
        'suggestedUri' => '/ajax/' . implode('/', $uriSegments),
    ];
}

usort($rows, static function (array $a, array $b): int {
    return $a['cmd'] <=> $b['cmd'];
});

$target = __DIR__ . '/../docs/modernization/ajax-endpoint-inventory.md';

$header = "# Legacy AJAX endpoint inventory\n\n" .
    "This file is generated to support the API routing migration. Each entry maps the legacy `cmd` value to the owning class and a suggested Laravel route.\n\n" .
    "| cmd | legacy file | legacy class | suggested API URI |\n" .
    "| --- | --- | --- | --- |\n";

$content = $header;
foreach ($rows as $row) {
    $content .= sprintf(
        "| `%s` | `%s` | `%s` | `%s` |\n",
        $row['cmd'],
        $row['legacyFile'],
        $row['legacyClass'],
        $row['suggestedUri']
    );
}

file_put_contents($target, $content);

echo "Wrote {$target}\n";
