<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class ExportCallDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $exportId,
        public array $filters,
        public User $user
    ) {}

    public function handle(): void
    {
        try {
            // Build query with filters
            $query = Call::with(['customer', 'appointment', 'branch', 'company']);
            
            // Apply filters
            $this->applyFilters($query);
            
            // Generate CSV
            $csv = Writer::createFromString('');
            
            // Add headers
            $csv->insertOne([
                'Call ID',
                'Date/Time',
                'Customer Name',
                'Customer Email',
                'Customer Phone',
                'From Number',
                'Duration (seconds)',
                'Duration (formatted)',
                'Status',
                'Priority',
                'Appointment Made',
                'Branch',
                'Company',
                'Recording URL',
                'Created At',
                'Updated At',
            ]);
            
            // Add data in chunks to avoid memory issues
            $query->chunk(1000, function ($calls) use ($csv) {
                foreach ($calls as $call) {
                    $csv->insertOne([
                        $call->call_id,
                        $call->created_at->format('Y-m-d H:i:s'),
                        $call->customer?->name ?? '',
                        $call->customer?->email ?? '',
                        $call->customer?->phone ?? '',
                        $call->from_number ?? '',
                        $call->duration_sec ?? 0,
                        $this->formatDuration($call->duration_sec),
                        $call->call_status,
                        $call->priority ?? 'normal',
                        $call->appointment_made ? 'Yes' : 'No',
                        $call->branch?->name ?? '',
                        $call->company?->name ?? '',
                        $call->recording_url ?? $call->audio_url ?? '',
                        $call->created_at->format('Y-m-d H:i:s'),
                        $call->updated_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
            
            // Save to storage
            $filename = "call-exports/{$this->exportId}.csv";
            Storage::put($filename, $csv->toString());
            
            // Send email with download link
            $this->sendDownloadEmail($filename);
            
        } catch (\Exception $e) {
            \Log::error('Call export failed', [
                'export_id' => $this->exportId,
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendErrorEmail($e->getMessage());
        }
    }
    
    private function applyFilters($query): void
    {
        foreach ($this->filters as $filter => $value) {
            switch ($filter) {
                case 'time_range':
                    $this->applyTimeRangeFilter($query, $value);
                    break;
                case 'appointment_made':
                    $query->where('appointment_made', $value);
                    break;
                case 'call_status':
                    $query->where('call_status', $value);
                    break;
                case 'priority':
                    $query->where('priority', $value);
                    break;
                case 'call_ids':
                    if (is_array($value)) {
                        $query->whereIn('id', $value);
                    }
                    break;
            }
        }
    }
    
    private function applyTimeRangeFilter($query, $range): void
    {
        switch ($range) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'yesterday':
                $query->whereDate('created_at', today()->subDay());
                break;
            case 'this_week':
                $query->whereBetween('created_at', [
                    now()->startOfWeek(), 
                    now()->endOfWeek()
                ]);
                break;
            case 'this_month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
        }
    }
    
    private function formatDuration(?int $seconds): string
    {
        if (!$seconds) return '00:00';
        
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
    
    private function sendDownloadEmail(string $filename): void
    {
        $downloadUrl = url('storage/' . $filename);
        
        Mail::raw(
            "Your call data export is ready!\n\n" .
            "Download: {$downloadUrl}\n\n" .
            "This link will expire in 24 hours.\n\n" .
            "Export ID: {$this->exportId}",
            function ($message) {
                $message->to($this->user->email)
                        ->subject('Call Data Export Ready - ' . $this->exportId);
            }
        );
    }
    
    private function sendErrorEmail(string $error): void
    {
        Mail::raw(
            "Your call data export failed.\n\n" .
            "Error: {$error}\n\n" .
            "Export ID: {$this->exportId}\n\n" .
            "Please try again or contact support.",
            function ($message) {
                $message->to($this->user->email)
                        ->subject('Call Data Export Failed - ' . $this->exportId);
            }
        );
    }
}