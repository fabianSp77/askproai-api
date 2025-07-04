<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class HeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('💓 Heartbeat Job läuft – '.now());
        
        // Set heartbeat timestamp in Redis
        Redis::set('askproai:heartbeat:last', time());
        Redis::expire('askproai:heartbeat:last', 300); // Expire after 5 minutes
    }
}
