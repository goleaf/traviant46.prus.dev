<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;

trait CreatesApplication
{
    public function createApplication()
    {
        putenv('CACHE_STORE=array');
        putenv('TRAVIAN_CACHE_STORE=array');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['CACHE_STORE'] = 'array';
        $_ENV['TRAVIAN_CACHE_STORE'] = 'array';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if (! Blueprint::hasMacro('unsignedDecimal')) {
            Blueprint::macro('unsignedDecimal', function (string $column, int $total, int $places) {
                /** @var Blueprint $this */
                return $this->decimal($column, $total, $places)->unsigned();
            });
        }

        if (empty(config('app.key'))) {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        }

        return $app;
    }
}
