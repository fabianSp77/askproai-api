<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\MLJobProgress;

class TrainMLModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes
    public $tries = 1;
    
    protected ?MLJobProgress $progress = null;
    public string $jobId;
    public array $filters;

    public function __construct(?string $jobId = null, array $filters = [])
    {
        $this->jobId = $jobId ?? \Str::uuid()->toString();
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting ML model training job', ['job_id' => $this->jobId]);
        
        // Initialize progress tracking
        $this->progress = MLJobProgress::where('job_id', $this->jobId)->first() 
            ?? MLJobProgress::startJob('training', 100);
        
        try {
            // Update progress - Starting
            $this->progress->updateProgress(0, 'Training gestartet...', 'initialization');
            
            // Prepare training data
            $this->prepareTrainingData();
            
            // Run Python training script
            $result = $this->runTrainingScript();
            
            if ($result['success']) {
                // Update progress - Saving model
                $this->progress->updateProgress(90, 'Modell wird gespeichert...', 'saving_model');
                
                // Save model information
                $this->saveModelInfo($result);
                
                // Mark as completed
                $this->progress->markAsCompleted(
                    sprintf('Training erfolgreich! Genauigkeit: %.1f%%', ($result['accuracy'] ?? 0) * 100)
                );
                
                Log::info('ML model training completed successfully', [
                    'accuracy' => $result['accuracy'] ?? null,
                    'samples' => $result['samples'] ?? null,
                ]);
                
                // Notify user
                $this->notifySuccess($result);
            } else {
                throw new \Exception($result['error'] ?? 'Training failed');
            }
            
        } catch (\Exception $e) {
            Log::error('ML model training failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mark progress as failed
            if ($this->progress) {
                $this->progress->markAsFailed($e->getMessage());
            }
            
            $this->notifyFailure($e->getMessage());
            
            throw $e;
        }
    }
    
    /**
     * Prepare training data from database
     */
    protected function prepareTrainingData(): void
    {
        // Update progress
        $this->progress->updateProgress(10, 'Lade Trainingsdaten aus Datenbank...', 'data_preparation');
        
        // Build query with filters
        $query = DB::table('calls')
            ->leftJoin('ml_call_predictions', 'calls.id', '=', 'ml_call_predictions.call_id')
            ->whereNotNull('calls.transcript');
            
        // Apply filters from constructor
        if ($this->filters['require_audio'] ?? false) {
            $query->where(function ($q) {
                $q->whereNotNull('calls.recording_url')
                  ->orWhereRaw("JSON_EXTRACT(calls.webhook_data, '$.recording_url') IS NOT NULL");
            });
        }
        
        if ($this->filters['require_customer'] ?? false) {
            $query->whereNotNull('calls.customer_id');
        }
        
        if ($this->filters['require_appointment'] ?? false) {
            $query->whereNotNull('calls.appointment_id');
        }
        
        if ($this->filters['exclude_test_calls'] ?? false) {
            $query->where('calls.duration_sec', '>=', 15)
                  ->whereNotIn('calls.from_number', ['+49000000000', '+491234567890']);
        }
        
        // Duration filter
        switch ($this->filters['duration_filter'] ?? 'all') {
            case 'min_15':
                $query->where('calls.duration_sec', '>=', 15);
                break;
            case 'min_30':
                $query->where('calls.duration_sec', '>=', 30);
                break;
            case 'min_60':
                $query->where('calls.duration_sec', '>=', 60);
                break;
            case 'range_30_120':
                $query->whereBetween('calls.duration_sec', [30, 120]);
                break;
            case 'range_60_300':
                $query->whereBetween('calls.duration_sec', [60, 300]);
                break;
            case 'range_120_600':
                $query->whereBetween('calls.duration_sec', [120, 600]);
                break;
        }
        
        // Date filters
        if (isset($this->filters['from_date'])) {
            $query->where('calls.created_at', '>=', $this->filters['from_date']);
        }
        if (isset($this->filters['to_date'])) {
            $query->where('calls.created_at', '<=', $this->filters['to_date']);
        }
        
        // Advanced filters
        if (!empty($this->filters['sentiment_filter'])) {
            $query->whereIn('ml_call_predictions.sentiment_label', $this->filters['sentiment_filter']);
        }
        
        if (!empty($this->filters['keyword_filter'])) {
            $keywords = array_map('trim', explode(',', $this->filters['keyword_filter']));
            foreach ($keywords as $keyword) {
                $query->where('calls.transcript', 'LIKE', '%' . $keyword . '%');
            }
        }
        
        if (!empty($this->filters['agent_id'])) {
            $query->where('calls.agent_id', $this->filters['agent_id']);
        }
        
        // Quality filters
        if ($this->filters['require_full_transcript'] ?? false) {
            $query->where('calls.transcript', 'NOT LIKE', '%[inaudible]%')
                  ->where('calls.transcript', 'NOT LIKE', '%[unclear]%');
        }
        
        // Export training data to CSV
        $calls = $query->select([
                'calls.id',
                'calls.transcript',
                'calls.duration_sec',
                'calls.cost',
                'calls.start_timestamp',
                'calls.appointment_id',
                'calls.customer_id',
                'calls.call_successful',
                'calls.disconnection_reason',
                'calls.sentiment',
                'ml_call_predictions.sentiment_score',
                'ml_call_predictions.sentiment_label',
            ])
            ->get();
            
        // Balance classes if requested
        if ($this->filters['balance_classes'] ?? true) {
            $maxPerClass = intval($this->filters['max_samples_per_class'] ?? 1000);
            
            // Group by sentiment
            $grouped = $calls->groupBy(function ($call) {
                if ($call->sentiment_label) {
                    return $call->sentiment_label;
                } elseif ($call->sentiment_score !== null) {
                    if ($call->sentiment_score > 0.3) return 'positive';
                    elseif ($call->sentiment_score < -0.3) return 'negative';
                    else return 'neutral';
                } else {
                    return $call->sentiment ?? 'neutral';
                }
            });
            
            // Balance the classes
            $balanced = collect();
            foreach ($grouped as $sentiment => $items) {
                $balanced = $balanced->concat(
                    $items->shuffle()->take($maxPerClass)
                );
            }
            
            $calls = $balanced->shuffle(); // Final shuffle for randomness
        }
        
        // Create training data file
        $csvPath = storage_path('app/ml/training_data.csv');
        Storage::makeDirectory('ml');
        
        $file = fopen($csvPath, 'w');
        
        // Write header
        fputcsv($file, [
            'id',
            'transcript',
            'duration_sec',
            'cost',
            'has_appointment',
            'has_customer',
            'call_successful',
            'normal_hangup',
            'sentiment_label',
            'sentiment_score'
        ]);
        
        // Write data
        foreach ($calls as $call) {
            // Determine sentiment label
            $sentiment = $call->sentiment ?? 'neutral';
            if ($call->sentiment_score !== null) {
                if ($call->sentiment_score > 0.3) {
                    $sentiment = 'positive';
                } elseif ($call->sentiment_score < -0.3) {
                    $sentiment = 'negative';
                } else {
                    $sentiment = 'neutral';
                }
            }
            
            fputcsv($file, [
                $call->id,
                $call->transcript,
                $call->duration_sec ?? 0,
                $call->cost ?? 0,
                $call->appointment_id ? 1 : 0,
                $call->customer_id ? 1 : 0,
                $call->call_successful ?? 0,
                in_array($call->disconnection_reason, ['agent_hangup', 'user_hangup']) ? 1 : 0,
                $sentiment,
                $call->sentiment_score ?? 0
            ]);
        }
        
        fclose($file);
        
        Log::info('Training data prepared', [
            'samples' => $calls->count(),
            'file' => $csvPath
        ]);
        
        // Update progress
        $this->progress->updateProgress(30, "Trainingsdaten vorbereitet: {$calls->count()} Anrufe", 'data_prepared');
    }
    
    /**
     * Run Python training script
     */
    protected function runTrainingScript(): array
    {
        // Update progress
        $this->progress->updateProgress(40, 'Starte Python ML Training...', 'training');
        $pythonPath = config('ml.python_path', '/usr/bin/python3');
        $scriptPath = base_path('ml/train_model.py');
        $dataPath = storage_path('app/ml/training_data.csv');
        $modelPath = base_path('ml/models');
        
        // Create model directory if not exists
        if (!file_exists($modelPath)) {
            mkdir($modelPath, 0755, true);
        }
        
        // Run training script
        $result = Process::timeout(1800)->run([
            $pythonPath,
            $scriptPath,
            '--data', $dataPath,
            '--output', $modelPath,
            '--model-name', 'sentiment_model_' . now()->format('Y-m-d_H-i-s')
        ]);
        
        if ($result->successful()) {
            // Parse output for metrics
            $output = $result->output();
            $metrics = $this->parseTrainingOutput($output);
            
            return array_merge(['success' => true], $metrics);
        } else {
            return [
                'success' => false,
                'error' => $result->errorOutput()
            ];
        }
    }
    
    /**
     * Parse training output for metrics
     */
    protected function parseTrainingOutput(string $output): array
    {
        $metrics = [];
        
        // Extract accuracy
        if (preg_match('/accuracy:\s*([0-9.]+)/', $output, $matches)) {
            $metrics['accuracy'] = (float) $matches[1];
        }
        
        // Extract sample count
        if (preg_match('/samples:\s*(\d+)/', $output, $matches)) {
            $metrics['samples'] = (int) $matches[1];
        }
        
        // Extract model path
        if (preg_match('/saved to:\s*(.+)/', $output, $matches)) {
            $metrics['model_path'] = trim($matches[1]);
        }
        
        // Extract feature importance
        if (preg_match('/feature_importance:\s*({.+})/', $output, $matches)) {
            $metrics['feature_importance'] = json_decode($matches[1], true);
        }
        
        return $metrics;
    }
    
    /**
     * Save model information to database
     */
    protected function saveModelInfo(array $result): void
    {
        // Deactivate current model
        DB::table('ml_models')
            ->where('model_type', 'sentiment')
            ->where('is_active', true)
            ->update(['is_active' => false]);
        
        // Save new model
        DB::table('ml_models')->insert([
            'model_type' => 'sentiment',
            'version' => '1.0.' . now()->format('YmdHis'),
            'file_path' => $result['model_path'] ?? 'ml/models/sentiment_model.pkl',
            'training_metrics' => json_encode([
                'accuracy' => $result['accuracy'] ?? null,
                'samples' => $result['samples'] ?? null,
                'feature_importance' => $result['feature_importance'] ?? null,
            ]),
            'training_samples' => $result['samples'] ?? 0,
            'is_active' => true,
            'notes' => 'Automatically trained on ' . now()->format('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Notify success
     */
    protected function notifySuccess(array $result): void
    {
        // You could send email notification here
        // For now, just log
        Log::info('ML model training completed', $result);
    }
    
    /**
     * Notify failure
     */
    protected function notifyFailure(string $error): void
    {
        // You could send email notification here
        // For now, just log
        Log::error('ML model training failed: ' . $error);
    }
}