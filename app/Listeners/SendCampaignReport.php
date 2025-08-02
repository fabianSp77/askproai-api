<?php

namespace App\Listeners;

use App\Events\CallCampaignCompleted;
use App\Mail\CampaignCompletedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCampaignReport implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CallCampaignCompleted $event): void
    {
        $campaign = $event->campaign;
        
        // Log campaign completion
        Log::channel('retell-mcp')->info('Call campaign completed', [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'company_id' => $campaign->company_id,
            'total_targets' => $campaign->total_targets,
            'calls_completed' => $campaign->calls_completed,
            'calls_failed' => $campaign->calls_failed,
            'success_rate' => $campaign->success_rate,
            'duration_minutes' => $campaign->started_at->diffInMinutes($campaign->completed_at),
        ]);
        
        // Generate campaign report
        $report = $this->generateCampaignReport($campaign);
        
        // Send email to campaign creator and company admins
        $recipients = $this->getCampaignRecipients($campaign);
        
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->queue(new CampaignCompletedMail($campaign, $report));
            } catch (\Exception $e) {
                Log::error('Failed to send campaign report email', [
                    'campaign_id' => $campaign->id,
                    'recipient' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Track metrics
        if (config('retell-mcp.monitoring.metrics_enabled')) {
            app('monitoring.metrics')->increment('retell_mcp.campaigns.completed', 1, [
                'company_id' => $campaign->company_id,
                'status' => $campaign->status,
            ]);
            
            app('monitoring.metrics')->gauge('retell_mcp.campaigns.success_rate', $campaign->success_rate, [
                'company_id' => $campaign->company_id,
            ]);
        }
    }
    
    /**
     * Generate campaign report data
     */
    protected function generateCampaignReport(RetellAICallCampaign $campaign): array
    {
        $calls = $campaign->calls()->with('customer')->get();
        
        $successfulCalls = $calls->where('status', 'completed');
        $failedCalls = $calls->whereIn('status', ['failed', 'no-answer']);
        
        return [
            'summary' => [
                'total_calls' => $calls->count(),
                'successful_calls' => $successfulCalls->count(),
                'failed_calls' => $failedCalls->count(),
                'average_duration' => $successfulCalls->avg('duration_sec'),
                'total_cost' => $calls->sum('cost'),
            ],
            'by_status' => $calls->groupBy('status')->map->count(),
            'by_hour' => $calls->groupBy(function ($call) {
                return $call->created_at->format('H');
            })->map->count(),
            'top_outcomes' => $this->extractTopOutcomes($successfulCalls),
            'failed_reasons' => $this->extractFailedReasons($failedCalls),
        ];
    }
    
    /**
     * Get recipients for campaign report
     */
    protected function getCampaignRecipients(RetellAICallCampaign $campaign): array
    {
        $recipients = [];
        
        // Campaign creator
        if ($campaign->creator) {
            $recipients[] = $campaign->creator;
        }
        
        // Company admins
        $admins = User::where('company_id', $campaign->company_id)
            ->where('role', 'admin')
            ->where('is_active', true)
            ->get();
        
        foreach ($admins as $admin) {
            $recipients[] = $admin;
        }
        
        // Remove duplicates
        return collect($recipients)->unique('id')->values()->all();
    }
    
    /**
     * Extract top outcomes from successful calls
     */
    protected function extractTopOutcomes($calls): array
    {
        // This would analyze call transcripts or metadata for outcomes
        // For now, return placeholder data
        return [
            'appointments_booked' => $calls->filter(function ($call) {
                return str_contains($call->metadata['purpose'] ?? '', 'appointment');
            })->count(),
            'information_provided' => $calls->filter(function ($call) {
                return str_contains($call->metadata['purpose'] ?? '', 'information');
            })->count(),
            'follow_ups_scheduled' => $calls->filter(function ($call) {
                return str_contains($call->metadata['purpose'] ?? '', 'follow_up');
            })->count(),
        ];
    }
    
    /**
     * Extract reasons for failed calls
     */
    protected function extractFailedReasons($calls): array
    {
        return $calls->groupBy(function ($call) {
            return $call->metadata['failure_reason'] ?? 'unknown';
        })->map->count()->toArray();
    }
}