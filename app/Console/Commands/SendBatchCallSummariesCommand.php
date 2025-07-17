<?php

namespace App\Console\Commands;

use App\Mail\CallSummaryBatchEmail;
use App\Models\Call;
use App\Models\Company;
use App\Services\CallExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendBatchCallSummariesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:send-batch-summaries {--frequency=hourly : The frequency to process (hourly|daily)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send batch call summaries based on company frequency settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $frequency = $this->option('frequency');
        
        if (!in_array($frequency, ['hourly', 'daily'])) {
            $this->error('Invalid frequency. Must be hourly or daily.');
            return 1;
        }
        
        $this->info("Processing {$frequency} call summaries...");
        
        // Get companies with matching frequency
        $companies = Company::where('send_call_summaries', true)
            ->where('summary_email_frequency', $frequency)
            ->get();
            
        $this->info("Found {$companies->count()} companies with {$frequency} summaries enabled.");
        
        foreach ($companies as $company) {
            try {
                $this->processCompany($company, $frequency);
            } catch (\Exception $e) {
                Log::error('Failed to process batch summaries for company', [
                    'company_id' => $company->id,
                    'frequency' => $frequency,
                    'error' => $e->getMessage()
                ]);
                $this->error("Failed for company {$company->name}: {$e->getMessage()}");
            }
        }
        
        $this->info('Batch summaries processing completed.');
        return 0;
    }
    
    /**
     * Process batch summaries for a company
     */
    protected function processCompany(Company $company, string $frequency): void
    {
        $this->info("Processing company: {$company->name}");
        
        // Determine time range
        $endTime = now();
        $startTime = match($frequency) {
            'hourly' => now()->subHour(),
            'daily' => now()->subDay()->startOfDay(),
            default => now()->subHour()
        };
        
        // Get calls in the time range
        $calls = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$startTime, $endTime])
            ->with(['customer', 'branch', 'appointment'])
            ->get();
            
        if ($calls->isEmpty()) {
            $this->info("No calls found for {$company->name} in the specified time range.");
            return;
        }
        
        $this->info("Found {$calls->count()} calls for {$company->name}");
        
        // Get recipients
        $recipients = $this->getRecipients($company);
        
        if (empty($recipients)) {
            $this->warn("No recipients configured for {$company->name}");
            return;
        }
        
        // Prepare summary data
        $summaryData = [
            'company' => $company,
            'calls' => $calls,
            'frequency' => $frequency,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'totalCalls' => $calls->count(),
            'totalDuration' => $calls->sum('duration_sec'),
            'appointmentsBooked' => $calls->whereNotNull('appointment_id')->count(),
            'urgentCalls' => $calls->where('urgency_level', 'urgent')->count(),
            'includeTranscript' => $company->include_transcript_in_summary ?? false,
            'includeCsv' => $company->include_csv_export ?? true,
        ];
        
        // Generate CSV if needed
        $csvAttachment = null;
        if ($summaryData['includeCsv']) {
            $exportService = new CallExportService();
            $csvContent = $exportService->exportMultipleCalls($calls);
            $csvAttachment = [
                'content' => $csvContent,
                'filename' => "anruf_zusammenfassung_{$frequency}_" . now()->format('Y-m-d_His') . '.csv'
            ];
        }
        
        // Send email to each recipient
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)
                    ->send(new CallSummaryBatchEmail($summaryData, $csvAttachment));
                    
                $this->info("Sent batch summary to {$recipient}");
                
                Log::info('Batch call summary sent', [
                    'company_id' => $company->id,
                    'recipient' => $recipient,
                    'frequency' => $frequency,
                    'call_count' => $calls->count()
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to send batch summary email', [
                    'company_id' => $company->id,
                    'recipient' => $recipient,
                    'error' => $e->getMessage()
                ]);
                $this->error("Failed to send to {$recipient}: {$e->getMessage()}");
            }
        }
        
        // Mark calls as having summaries sent
        $calls->each(function ($call) use ($frequency) {
            $metadata = $call->metadata ?? [];
            $metadata['batch_summary_sent'] = [
                'frequency' => $frequency,
                'sent_at' => now()->toIso8601String()
            ];
            $call->update(['metadata' => $metadata]);
        });
    }
    
    /**
     * Get recipients for a company
     */
    protected function getRecipients(Company $company): array
    {
        $recipients = [];
        
        // Add company-configured recipients
        if ($company->call_summary_recipients) {
            $recipients = array_merge($recipients, $company->call_summary_recipients);
        }
        
        // Add users who opted in
        $users = $company->portalUsers()
            ->where('is_active', true)
            ->get();
            
        foreach ($users as $user) {
            $prefs = $user->call_notification_preferences ?? [];
            if ($prefs['receive_summaries'] ?? false) {
                $recipients[] = $user->email;
            }
        }
        
        // Remove duplicates
        return array_unique($recipients);
    }
}