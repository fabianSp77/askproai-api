<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\ML\SentimentAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeCallSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Call $call;

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
    public function handle(SentimentAnalysisService $sentimentService): void
    {
        try {
            Log::info('Starting sentiment analysis for call', [
                'call_id' => $this->call->id,
                'has_transcript' => !empty($this->call->transcript)
            ]);

            // Analyze sentiment
            $result = $sentimentService->analyzeCall($this->call);

            Log::info('Sentiment analysis completed', [
                'call_id' => $this->call->id,
                'sentiment' => $result['sentiment'] ?? 'unknown',
                'score' => $result['sentiment_score'] ?? 0,
                'method' => $result['method'] ?? 'unknown'
            ]);

            // Update agent performance metrics
            if ($this->call->retell_agent_id && $this->call->company_id) {
                $this->updateAgentMetrics($result);
            }

        } catch (\Exception $e) {
            Log::error('Sentiment analysis job failed', [
                'call_id' => $this->call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Update agent performance metrics
     */
    protected function updateAgentMetrics(array $sentimentResult): void
    {
        try {
            $date = $this->call->created_at->toDateString();
            
            // Get or create metric record for the day
            $metric = \App\Models\AgentPerformanceMetric::firstOrCreate(
                [
                    'agent_id' => $this->call->retell_agent_id,
                    'company_id' => $this->call->company_id,
                    'date' => $date,
                ],
                [
                    'total_calls' => 0,
                    'positive_calls' => 0,
                    'neutral_calls' => 0,
                    'negative_calls' => 0,
                    'converted_calls' => 0,
                    'hourly_metrics' => [],
                ]
            );

            // Update counts
            $metric->increment('total_calls');
            
            // Update sentiment counts
            switch ($sentimentResult['sentiment']) {
                case 'positive':
                    $metric->increment('positive_calls');
                    break;
                case 'negative':
                    $metric->increment('negative_calls');
                    break;
                default:
                    $metric->increment('neutral_calls');
            }

            // Update conversion count if appointment was booked
            if ($this->call->appointment_id) {
                $metric->increment('converted_calls');
            }

            // Update hourly metrics
            $hour = $this->call->created_at->hour;
            $hourlyMetrics = $metric->hourly_metrics ?? [];
            
            if (!isset($hourlyMetrics[$hour])) {
                $hourlyMetrics[$hour] = [
                    'calls' => 0,
                    'positive' => 0,
                    'negative' => 0,
                    'converted' => 0
                ];
            }
            
            $hourlyMetrics[$hour]['calls']++;
            if ($sentimentResult['sentiment'] === 'positive') {
                $hourlyMetrics[$hour]['positive']++;
            } elseif ($sentimentResult['sentiment'] === 'negative') {
                $hourlyMetrics[$hour]['negative']++;
            }
            if ($this->call->appointment_id) {
                $hourlyMetrics[$hour]['converted']++;
            }
            
            $metric->hourly_metrics = $hourlyMetrics;

            // Recalculate averages
            $allCallsToday = Call::where('retell_agent_id', $this->call->retell_agent_id)
                ->whereDate('created_at', $date)
                ->with('mlPrediction')
                ->get();

            $sentimentScores = $allCallsToday
                ->pluck('mlPrediction.sentiment_score')
                ->filter()
                ->values();

            if ($sentimentScores->count() > 0) {
                $metric->avg_sentiment_score = $sentimentScores->avg();
            }

            $metric->conversion_rate = $metric->total_calls > 0 
                ? ($metric->converted_calls / $metric->total_calls) * 100 
                : 0;

            $metric->avg_call_duration_sec = $allCallsToday->avg('duration_sec') ?? 0;

            $metric->save();

            Log::info('Agent metrics updated', [
                'agent_id' => $this->call->retell_agent_id,
                'date' => $date,
                'total_calls' => $metric->total_calls
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update agent metrics', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Sentiment analysis job failed permanently', [
            'call_id' => $this->call->id,
            'error' => $exception->getMessage()
        ]);
    }
}