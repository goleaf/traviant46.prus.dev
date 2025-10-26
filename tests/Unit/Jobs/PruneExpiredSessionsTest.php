<?php

declare(strict_types=1);

use App\Jobs\PruneExpiredSessions;
use Illuminate\Support\Facades\Session;

it('invokes the session handler garbage collector with the configured lifetime', function (): void {
    $store = Session::driver();
    $originalHandler = $store->getHandler();

    $fakeHandler = new class implements \SessionHandlerInterface
    {
        /** @var list<int> */
        public array $gcInvocations = [];

        public function open(string $savePath, string $sessionName): bool
        {
            return true;
        }

        public function close(): bool
        {
            return true;
        }

        public function read(string $id): string|false
        {
            return '';
        }

        public function write(string $id, string $data): bool
        {
            return true;
        }

        public function destroy(string $id): bool
        {
            return true;
        }

        public function gc(int $max_lifetime): int|false
        {
            $this->gcInvocations[] = $max_lifetime;

            return 0;
        }
    };

    $store->setHandler($fakeHandler);

    try {
        (new PruneExpiredSessions)->handle();

        $expectedLifetime = (int) config('session.lifetime', 120) * 60;

        expect($fakeHandler->gcInvocations)->toHaveCount(1)
            ->and($fakeHandler->gcInvocations[0])->toBe($expectedLifetime);
    } finally {
        $store->setHandler($originalHandler);
    }
});
