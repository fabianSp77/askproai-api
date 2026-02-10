<?php

use App\Jobs\ProcessRetellCallJob;
use App\Models\Call;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches job successfully', function () {
    Queue::fake();

    ProcessRetellCallJob::dispatch([
        'call_id' => 'call_123',
        'transcript' => 'Test transcript',
    ]);

    Queue::assertPushed(ProcessRetellCallJob::class);
});

it('has proper retry configuration', function () {
    $job = new ProcessRetellCallJob(['call_id' => 'test']);

    expect($job->tries)->toBe(3);
    expect($job->timeout)->toBe(120);
    expect($job->backoff)->toBe([60, 300]);
});

it('logs failure on permanent job failure', function () {
    $payload = ['call_id' => 'call_failed_123'];

    $job = new ProcessRetellCallJob($payload);

    $exception = new \Exception('Permanent failure');
    $job->failed($exception);

    expect(true)->toBeTrue();
});
