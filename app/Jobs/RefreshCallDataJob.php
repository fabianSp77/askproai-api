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

    public function __construct(public Call $call) {}

    public function handle(CallDataRefresher $refresher): void
    {
        Log::info('Dispatched RefreshCallDataJob', ['call_db_id' => $this->call->id]);

        $refresher->refresh($this->call);
    }
}
