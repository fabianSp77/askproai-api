<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class QueueStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.queue-status-widget';

    public function getQueueStatus(): array
    {
        $queueConn = config('queue.default');
        $jobs = null;
        $failed = null;
        if (DB::getSchemaBuilder()->hasTable('jobs')) {
            $jobs = DB::table('jobs')->count();
        }
        if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')->count();
        }
        return [
            'connection' => $queueConn,
            'jobs' => $jobs,
            'failed' => $failed,
        ];
    }
}
