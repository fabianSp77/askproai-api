<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\CallDataRefresher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshCallDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 120];

    public function __construct(public Call $call) {}

    public function handle(CallDataRefresher $refresher): void
    {
        Log::info('Dispatched RefreshCallDataJob', ['call_db_id' => $this->call->id]);

        $refresher->refresh($this->call);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[RefreshCallDataJob] permanently failed', [
            'call_id' => $this->call->id,
            'call_db_id' => $this->call->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
