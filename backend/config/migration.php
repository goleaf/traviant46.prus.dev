<?php

return [
    'chunk_size' => env('DATA_MIGRATION_CHUNK_SIZE', 500),

    'features' => [
        // Configure per-feature table migrations in tests or environment-specific configs.
    ],
];
