<?php

declare(strict_types=1);

use App\Models\SitterDelegation;
use App\Monitoring\Metrics\MetricRecorder;
use App\Observers\SitterDelegationObserver;

it('records metrics for sitter delegation lifecycle events', function (): void {
    $fakeRecorder = new class implements MetricRecorder
    {
        /** @var array<int, array{metric: string, value: float, tags: array<string, string>}> */
        public array $calls = [];

        public function increment(string $metric, float $value = 1.0, array $tags = []): void
        {
            $this->calls[] = compact('metric', 'value', 'tags');
        }

        public function gauge(string $metric, float $value, array $tags = []): void
        {
            // not required for this test
        }
    };

    $observer = new SitterDelegationObserver($fakeRecorder);

    $delegation = SitterDelegation::factory()->make();

    $observer->created($delegation);
    $observer->updated($delegation);
    $observer->deleted($delegation);

    expect($fakeRecorder->calls)
        ->toHaveCount(3)
        ->and(collect($fakeRecorder->calls)->pluck('metric')->unique()->all())
        ->toBe(['sitter.delegation']);

    expect(collect($fakeRecorder->calls)->pluck('tags.operation')->all())
        ->toBe(['created', 'updated', 'deleted']);
});
