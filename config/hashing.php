<?php

declare(strict_types=1);

return [

    'driver' => env('HASH_DRIVER', 'argon2id'),

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
    ],

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
    ],

    'argon2id' => [
        'memory' => (int) env('ARGON2_MEMORY', 131072),
        'threads' => (int) env('ARGON2_THREADS', 1),
        'time' => (int) env('ARGON2_TIME', 4),
    ],

];
