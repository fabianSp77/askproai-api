<?php

namespace App\Services\ML;

use App\Models\Call;
use App\Models\MLCallPrediction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class SentimentAnalysisService
{
    protected string $mlServiceUrl;
    protected string $pythonPath;
    protected string $modelPath;
    
    public function __construct()
    {
        $this->mlServiceUrl = config('ml.sentiment_service_url', 'http://localhost:5000');
        $this->pythonPath = config('ml.python_path', '/usr/bin/python3');
        $this->modelPath = base_path('ml/models/sentiment_model.pkl');
    }
    
    /**
     * Analyze sentiment for a call
     */
    public function analyzeCall(Call $call): array
    {
        try {
            // Prepare call data for ML analysis
            $callData = $this->prepareCallData($call);
            
            // Try ML service first, fallback to direct Python execution
            $result = $this->callMLService($callData);
            
            if (!$result) {
                $result = $this->runPythonDirectly($callData);
            }
            
            if (!$result) {
                // Final fallback to rule-based analysis
                $result = $this->ruleBasedAnalysis($call);
            }
            
            // Store prediction in database
            $this->storePrediction($call, $result);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Sentiment analysis failed', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            
            // Return fallback analysis
            return $this->ruleBasedAnalysis($call);
        }
    }
    
    /**
     * Prepare call data for ML analysis
     */
    protected function prepareCallData(Call $call): array
    {
        // Check if customer is repeat customer
        $isRepeatCustomer = false;
        if ($call->customer_id) {
            $previousCalls = Call::where('customer_id', $call->customer_id)
                ->where('id', '<', $call->id)
                ->count();
            $isRepeatCustomer = $previousCalls > 0;
        }
        
        return [
            'call_id' => $call->id,
            'transcript' => $call->transcript ?? '',
            'duration_sec' => $call->duration_sec ?? 0,
            'cost' => $call->cost ?? 0,
            'start_timestamp' => $call->start_timestamp ?? $call->created_at,
            'appointment_id' => $call->appointment_id,
            'customer_id' => $call->customer_id,
            'is_repeat_customer' => $isRepeatCustomer ? 1 : 0,
            'call_successful' => $call->call_successful ?? 0,
            'disconnection_reason' => $call->disconnection_reason ?? '',
        ];
    }
    
    /**
     * Call ML service API
     */
    protected function callMLService(array $callData): ?array
    {
        try {
            $response = Http::timeout(10)
                ->post($this->mlServiceUrl . '/analyze', $callData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::warning('ML service returned error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::debug('ML service not available, falling back to direct execution', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Run Python script directly
     */
    protected function runPythonDirectly(array $callData): ?array
    {
        try {
            $scriptPath = base_path('ml/analyze_call.py');
            $inputJson = json_encode($callData);
            
            // Create temporary file for input
            $tempFile = tempnam(sys_get_temp_dir(), 'call_data_');
            file_put_contents($tempFile, $inputJson);
            
            // Run Python script
            $result = Process::run([
                $this->pythonPath,
                $scriptPath,
                $tempFile
            ]);
            
            // Clean up temp file
            unlink($tempFile);
            
            if ($result->successful()) {
                $output = $result->output();
                return json_decode($output, true);
            }
            
            Log::error('Python script failed', [
                'error' => $result->errorOutput()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Failed to run Python directly', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Rule-based sentiment analysis fallback
     */
    protected function ruleBasedAnalysis(Call $call): array
    {
        $transcript = strtolower($call->transcript ?? '');
        
        // Sentiment keywords
        $positiveWords = ['danke', 'super', 'toll', 'perfekt', 'gut', 'gerne', 
                         'freue', 'klasse', 'wunderbar', 'ja', 'prima', 'schön'];
        $negativeWords = ['problem', 'schlecht', 'nein', 'nicht', 'leider', 
                         'schwierig', 'ärger', 'beschwerde', 'unzufrieden', 'falsch'];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($transcript, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($transcript, $word);
        }
        
        // Calculate sentiment
        $totalWords = str_word_count($transcript) ?: 1;
        $positiveRatio = $positiveCount / $totalWords;
        $negativeRatio = $negativeCount / $totalWords;
        
        // Boost based on outcomes
        if ($call->appointment_id) {
            $positiveRatio += 0.1;
        }
        
        // Determine sentiment
        if ($positiveRatio > $negativeRatio * 1.5) {
            $sentiment = 'positive';
            $score = min($positiveRatio * 5, 1);
        } elseif ($negativeRatio > $positiveRatio * 1.5) {
            $sentiment = 'negative';
            $score = max(-$negativeRatio * 5, -1);
        } else {
            $sentiment = 'neutral';
            $score = 0;
        }
        
        return [
            'sentiment' => $sentiment,
            'sentiment_score' => $score,
            'confidence' => 0.5,
            'positive_probability' => $positiveRatio,
            'neutral_probability' => 1 - $positiveRatio - $negativeRatio,
            'negative_probability' => $negativeRatio,
            'method' => 'rule_based',
            'sentence_sentiments' => $this->analyzeSentencesRuleBased($transcript)
        ];
    }
    
    /**
     * Analyze sentences with rule-based approach
     */
    protected function analyzeSentencesRuleBased(string $transcript): array
    {
        if (empty($transcript)) {
            return [];
        }
        
        // Split into sentences
        $sentences = preg_split('/[.!?]+/', $transcript, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceSentiments = [];
        
        $positiveWords = ['danke', 'super', 'gut', 'gerne', 'ja'];
        $negativeWords = ['problem', 'nein', 'nicht', 'leider'];
        
        foreach ($sentences as $index => $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            $sentenceLower = strtolower($sentence);
            $posCount = 0;
            $negCount = 0;
            
            foreach ($positiveWords as $word) {
                if (str_contains($sentenceLower, $word)) {
                    $posCount++;
                }
            }
            
            foreach ($negativeWords as $word) {
                if (str_contains($sentenceLower, $word)) {
                    $negCount++;
                }
            }
            
            if ($posCount > $negCount) {
                $sentiment = 'positive';
                $score = 0.5;
            } elseif ($negCount > $posCount) {
                $sentiment = 'negative';
                $score = -0.5;
            } else {
                $sentiment = 'neutral';
                $score = 0.0;
            }
            
            $sentenceSentiments[] = [
                'text' => $sentence,
                'sentiment' => $sentiment,
                'score' => $score,
                'index' => $index
            ];
        }
        
        return $sentenceSentiments;
    }
    
    /**
     * Store ML prediction in database
     */
    protected function storePrediction(Call $call, array $result): void
    {
        try {
            MLCallPrediction::updateOrCreate(
                ['call_id' => $call->id],
                [
                    'model_version' => $result['model_version'] ?? '1.0.0',
                    'sentiment_score' => $result['sentiment_score'] ?? 0,
                    'satisfaction_score' => $result['satisfaction_score'] ?? null,
                    'goal_achievement_score' => $result['goal_achievement_score'] ?? null,
                    'sentence_sentiments' => json_encode($result['sentence_sentiments'] ?? []),
                    'feature_contributions' => json_encode($result['features'] ?? []),
                    'prediction_confidence' => $result['confidence'] ?? 0.5,
                    'processing_time_ms' => $result['processing_time_ms'] ?? 0,
                ]
            );
            
            // Update call sentiment
            $call->update([
                'sentiment' => $result['sentiment'],
                'sentiment_score' => $result['sentiment_score']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to store ML prediction', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get agent performance metrics
     */
    public function getAgentPerformanceMetrics(string $agentId, Carbon $startDate, Carbon $endDate): array
    {
        $metrics = DB::table('calls')
            ->leftJoin('ml_call_predictions', 'calls.id', '=', 'ml_call_predictions.call_id')
            ->where('calls.retell_agent_id', $agentId)
            ->whereBetween('calls.created_at', [$startDate, $endDate])
            ->select([
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('AVG(ml_call_predictions.sentiment_score) as avg_sentiment_score'),
                DB::raw('AVG(ml_call_predictions.satisfaction_score) as avg_satisfaction_score'),
                DB::raw('SUM(CASE WHEN calls.appointment_id IS NOT NULL THEN 1 ELSE 0 END) as converted_calls'),
                DB::raw('AVG(calls.duration_sec) as avg_duration'),
                DB::raw('SUM(CASE WHEN ml_call_predictions.sentiment_score > 0.3 THEN 1 ELSE 0 END) as positive_calls'),
                DB::raw('SUM(CASE WHEN ml_call_predictions.sentiment_score < -0.3 THEN 1 ELSE 0 END) as negative_calls'),
            ])
            ->first();
        
        $conversionRate = $metrics->total_calls > 0 
            ? ($metrics->converted_calls / $metrics->total_calls) * 100 
            : 0;
        
        return [
            'agent_id' => $agentId,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'total_calls' => (int) $metrics->total_calls,
            'avg_sentiment_score' => round((float) $metrics->avg_sentiment_score, 2),
            'avg_satisfaction_score' => round((float) $metrics->avg_satisfaction_score, 2),
            'conversion_rate' => round($conversionRate, 1),
            'avg_call_duration_sec' => (int) $metrics->avg_duration,
            'positive_calls' => (int) $metrics->positive_calls,
            'negative_calls' => (int) $metrics->negative_calls,
            'neutral_calls' => $metrics->total_calls - $metrics->positive_calls - $metrics->negative_calls,
        ];
    }
    
    /**
     * Calculate correlation between metrics
     */
    public function calculateCorrelations(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('calls')
            ->leftJoin('ml_call_predictions', 'calls.id', '=', 'ml_call_predictions.call_id')
            ->where('calls.company_id', $companyId)
            ->whereBetween('calls.created_at', [$startDate, $endDate])
            ->whereNotNull('ml_call_predictions.sentiment_score')
            ->select([
                'ml_call_predictions.sentiment_score',
                'calls.duration_sec',
                DB::raw('CASE WHEN calls.appointment_id IS NOT NULL THEN 1 ELSE 0 END as has_appointment'),
                DB::raw('HOUR(calls.created_at) as hour_of_day'),
            ])
            ->get();
        
        if ($data->count() < 10) {
            return [
                'message' => 'Insufficient data for correlation analysis',
                'data_points' => $data->count()
            ];
        }
        
        // Calculate correlations using simple Pearson correlation
        $sentiments = $data->pluck('sentiment_score')->toArray();
        $durations = $data->pluck('duration_sec')->toArray();
        $appointments = $data->pluck('has_appointment')->toArray();
        $hours = $data->pluck('hour_of_day')->toArray();
        
        return [
            'sentiment_vs_conversion' => $this->pearsonCorrelation($sentiments, $appointments),
            'duration_vs_conversion' => $this->pearsonCorrelation($durations, $appointments),
            'duration_vs_sentiment' => $this->pearsonCorrelation($durations, $sentiments),
            'hour_vs_sentiment' => $this->pearsonCorrelation($hours, $sentiments),
            'data_points' => $data->count()
        ];
    }
    
    /**
     * Calculate Pearson correlation coefficient
     */
    protected function pearsonCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y) || $n < 2) {
            return 0;
        }
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }
        
        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        
        if ($denominator == 0) {
            return 0;
        }
        
        return round($numerator / $denominator, 3);
    }
}