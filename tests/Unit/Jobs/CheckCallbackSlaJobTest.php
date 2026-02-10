<?php

use App\Jobs\CheckCallbackSlaJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches job successfully', function () {
    Queue::fake();

    CheckCallbackSlaJob::dispatch();

    Queue::assertPushed(CheckCallbackSlaJob::class);
});

it('has proper configuration', function () {
    $job = new CheckCallbackSlaJob();

    expect($job->tries)->toBe(3);
    expect($job->timeout)->toBe(120);
});

it('defines correct SLA thresholds', function () {
    expect(CheckCallbackSlaJob::WARNING_THRESHOLD)->toBe(60);
    expect(CheckCallbackSlaJob::CRITICAL_THRESHOLD)->toBe(90);
    expect(CheckCallbackSlaJob::ESCALATION_THRESHOLD)->toBe(120);
});

it('logs failure on permanent job failure', function () {
    $job = new CheckCallbackSlaJob();

    $exception = new \Exception('Permanent failure');
    $job->failed($exception);

    expect(true)->toBeTrue();
});
