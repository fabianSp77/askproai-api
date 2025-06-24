<?php

namespace App\Jobs;

use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $call;

    /**
     * Create a new job instance.
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Placeholder for AI analysis logic
            // In production, this would integrate with an AI service
            
            $analysis = $this->call->analysis ?? [];
            
            // Simulate analysis update
            $analysis['last_analyzed'] = now();
            $analysis['analysis_version'] = '2.0';
            
            // Update sentiment if not already set
            if (!isset($analysis['sentiment'])) {
                // Simple sentiment analysis based on duration
                if ($this->call->duration_sec > 180) {
                    $analysis['sentiment'] = 'positive';
                } elseif ($this->call->duration_sec < 60) {
                    $analysis['sentiment'] = 'negative';
                } else {
                    $analysis['sentiment'] = 'neutral';
                }
            }
            
            // Update urgency if not already set
            if (!isset($analysis['urgency'])) {
                $analysis['urgency'] = 'normal';
            }
            
            // Generate summary if not already set
            if (!isset($analysis['summary'])) {
                $analysis['summary'] = sprintf(
                    'Anruf von %s am %s, Dauer: %s',
                    $this->call->from_number,
                    $this->call->created_at->format('d.m.Y H:i'),
                    gmdate('i:s', $this->call->duration_sec)
                );
            }
            
            $this->call->update(['analysis' => $analysis]);
            
            Log::info('Call analysis completed', [
                'call_id' => $this->call->id,
                'analysis' => $analysis
            ]);
            
        } catch (\Exception $e) {
            Log::error('Call analysis failed', [
                'call_id' => $this->call->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}