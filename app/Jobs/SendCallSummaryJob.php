<?php

namespace App\Jobs;

use App\Mail\CallSummaryEmail;
use App\Models\Call;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PortalUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCallSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    
    protected Call $call;
    protected ?array $customRecipients;
    protected ?string $customMessage;
    
    /**
     * Create a new job instance.
     */
    public function __construct(Call $call, ?array $customRecipients = null, ?string $customMessage = null)
    {
        $this->call = $call;
        $this->customRecipients = $customRecipients;
        $this->customMessage = $customMessage;
        $this->queue = 'emails';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Reload call with relationships
            $this->call->load(['company', 'branch', 'customer', 'appointment']);
            
            $company = $this->call->company;
            
            if (!$company) {
                Log::warning('Cannot send call summary - no company found', [
                    'call_id' => $this->call->id
                ]);
                return;
            }

            // Check if call summaries are enabled
            if (!$company->send_call_summaries && !$this->customRecipients) {
                Log::info('Call summaries disabled for company', [
                    'company_id' => $company->id,
                    'call_id' => $this->call->id
                ]);
                return;
            }

            // Get recipients
            $recipients = $this->getRecipients($company, $this->call->branch);
            
            if (empty($recipients)) {
                Log::warning('No recipients configured for call summary', [
                    'company_id' => $company->id,
                    'call_id' => $this->call->id
                ]);
                return;
            }

            // Get notification settings
            $settings = $this->getNotificationSettings($company, $this->call->branch);
            
            // Send email to each recipient
            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient['email'])
                        ->send(new CallSummaryEmail(
                            $this->call,
                            $settings['include_transcript'],
                            $settings['include_csv'],
                            $this->customMessage,
                            $recipient['type'] ?? 'internal'
                        ));
                    
                    Log::info('Call summary email sent', [
                        'call_id' => $this->call->id,
                        'recipient' => $recipient['email'],
                        'type' => $recipient['type'] ?? 'internal'
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to send call summary email to recipient', [
                        'call_id' => $this->call->id,
                        'recipient' => $recipient['email'],
                        'error' => $e->getMessage()
                    ]);
                    // Continue with other recipients
                }
            }
            
            // Update call metadata to track email sent
            $metadata = $this->call->metadata ?? [];
            $metadata['summary_emails_sent'] = [
                'sent_at' => now()->toIso8601String(),
                'recipients' => array_column($recipients, 'email'),
                'settings' => $settings
            ];
            $this->call->update(['metadata' => $metadata]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send call summary job', [
                'call_id' => $this->call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get recipients for the call summary
     */
    protected function getRecipients(Company $company, ?Branch $branch): array
    {
        $recipients = [];
        
        // Use custom recipients if provided
        if ($this->customRecipients) {
            foreach ($this->customRecipients as $email) {
                $recipients[] = [
                    'email' => $email,
                    'type' => 'custom'
                ];
            }
            return $recipients;
        }
        
        // Check branch-specific notification email first
        if ($branch && $branch->notification_email) {
            $recipients[] = [
                'email' => $branch->notification_email,
                'type' => 'branch'
            ];
        }
        
        // Add company-configured recipients
        $companyRecipients = $company->call_summary_recipients ?? [];
        foreach ($companyRecipients as $email) {
            $recipients[] = [
                'email' => $email,
                'type' => 'company'
            ];
        }
        
        // Add users with call notification preferences
        $users = PortalUser::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();
            
        foreach ($users as $user) {
            $prefs = $user->call_notification_preferences ?? [];
            if ($prefs['receive_summaries'] ?? false) {
                $recipients[] = [
                    'email' => $user->email,
                    'type' => 'user',
                    'user_id' => $user->id
                ];
            }
        }
        
        // Remove duplicates
        $uniqueEmails = [];
        $uniqueRecipients = [];
        foreach ($recipients as $recipient) {
            $email = strtolower($recipient['email']);
            if (!in_array($email, $uniqueEmails)) {
                $uniqueEmails[] = $email;
                $uniqueRecipients[] = $recipient;
            }
        }
        
        return $uniqueRecipients;
    }

    /**
     * Get notification settings (merge company, branch, and user preferences)
     */
    protected function getNotificationSettings(Company $company, ?Branch $branch): array
    {
        // Start with company defaults
        $settings = [
            'include_transcript' => $company->include_transcript_in_summary ?? true,
            'include_csv' => $company->include_csv_export ?? false,
        ];
        
        // Override with branch settings if available
        if ($branch && $branch->call_notification_overrides) {
            $overrides = $branch->call_notification_overrides;
            if (isset($overrides['include_transcript'])) {
                $settings['include_transcript'] = $overrides['include_transcript'];
            }
            if (isset($overrides['include_csv'])) {
                $settings['include_csv'] = $overrides['include_csv'];
            }
        }
        
        return $settings;
    }
}
