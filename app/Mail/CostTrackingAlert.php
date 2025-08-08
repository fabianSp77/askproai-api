<?php

namespace App\Mail;

use App\Models\BillingAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CostTrackingAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public BillingAlert $alert)
    {
        // Set queue priority based on severity
        $this->priority = match ($alert->severity) {
            'critical' => 10,
            'warning' => 5,
            default => 1
        };
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getSubject();
        
        return new Envelope(
            subject: $subject,
            tags: ['cost-alert', $this->alert->alert_type, $this->alert->severity],
            metadata: [
                'alert_id' => $this->alert->id,
                'company_id' => $this->alert->company_id,
                'alert_type' => $this->alert->alert_type,
                'severity' => $this->alert->severity
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.cost-tracking-alert',
            with: [
                'alert' => $this->alert,
                'company' => $this->alert->company,
                'severity_color' => $this->getSeverityColor(),
                'severity_icon' => $this->getSeverityIcon(),
                'action_items' => $this->getActionItems(),
                'dashboard_url' => $this->getDashboardUrl(),
                'support_contact' => config('mail.support_contact', 'support@askproai.de')
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get email subject based on alert type and severity
     */
    protected function getSubject(): string
    {
        $companyName = $this->alert->company->name;
        $severityPrefix = match ($this->alert->severity) {
            'critical' => '🚨 URGENT',
            'warning' => '⚠️ WARNING',
            default => '📊 INFO'
        };

        return match ($this->alert->alert_type) {
            'low_balance' => "{$severityPrefix}: Low Account Balance - {$companyName}",
            'zero_balance' => "🚨 CRITICAL: Account Balance Zero - {$companyName}",
            'usage_spike' => "{$severityPrefix}: Usage Spike Detected - {$companyName}",
            'budget_exceeded' => "{$severityPrefix}: Budget Exceeded - {$companyName}",
            'cost_anomaly' => "{$severityPrefix}: Cost Anomaly Detected - {$companyName}",
            default => "{$severityPrefix}: Cost Alert - {$companyName}"
        };
    }

    /**
     * Get color for severity
     */
    protected function getSeverityColor(): string
    {
        return match ($this->alert->severity) {
            'critical' => '#ef4444', // red-500
            'warning' => '#f59e0b',  // amber-500
            'info' => '#3b82f6',     // blue-500
            default => '#6b7280'     // gray-500
        };
    }

    /**
     * Get icon for severity
     */
    protected function getSeverityIcon(): string
    {
        return match ($this->alert->severity) {
            'critical' => '🚨',
            'warning' => '⚠️',
            'info' => '📊',
            default => '📈'
        };
    }

    /**
     * Get recommended action items based on alert type
     */
    protected function getActionItems(): array
    {
        return match ($this->alert->alert_type) {
            'low_balance', 'zero_balance' => [
                'Add funds to your account immediately',
                'Enable auto-topup to prevent service interruption',
                'Review your usage patterns',
                'Contact support if you have questions about charges'
            ],
            'usage_spike' => [
                'Review recent call activity for unusual patterns',
                'Check for any unauthorized usage',
                'Verify current campaigns and call volumes',
                'Consider implementing usage limits if needed'
            ],
            'budget_exceeded' => [
                'Review monthly spending vs budget',
                'Adjust budget limits if growth is expected',
                'Analyze cost drivers and optimize usage',
                'Consider upgrading to a higher tier plan'
            ],
            'cost_anomaly' => [
                'Investigate the cause of unusual spending',
                'Review call logs for the affected period',
                'Check for any system issues or unusual campaigns',
                'Contact support if spending seems incorrect'
            ],
            default => [
                'Review your account activity',
                'Contact support if you need assistance',
                'Monitor your usage and costs regularly'
            ]
        };
    }

    /**
     * Get dashboard URL for the alert
     */
    protected function getDashboardUrl(): string
    {
        $baseUrl = config('app.url');
        $alertId = $this->alert->id;
        
        return "{$baseUrl}/telescope/cost-alerts?alert_id={$alertId}";
    }

    /**
     * Get usage data for the template
     */
    public function getUsageData(): array
    {
        $data = $this->alert->data ?? [];
        
        return [
            'current_balance' => $data['balance'] ?? null,
            'threshold' => $data['threshold'] ?? null,
            'percentage' => $data['percentage'] ?? null,
            'monthly_spend' => $data['monthly_spend'] ?? null,
            'monthly_budget' => $data['monthly_budget'] ?? null,
            'recommended_topup' => $data['recommended_topup'] ?? null
        ];
    }

    /**
     * Format currency amount
     */
    public function formatCurrency(?float $amount): string
    {
        if ($amount === null) {
            return 'N/A';
        }
        
        return '€' . number_format($amount, 2);
    }

    /**
     * Format percentage
     */
    public function formatPercentage(?float $percentage): string
    {
        if ($percentage === null) {
            return 'N/A';
        }
        
        return round($percentage, 1) . '%';
    }
}