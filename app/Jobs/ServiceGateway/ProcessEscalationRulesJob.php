<?php

namespace App\Jobs\ServiceGateway;

use App\Mail\EscalationNotificationMail;
use App\Models\Company;
use App\Models\EscalationRule;
use App\Models\EscalationRuleExecution;
use App\Models\ServiceCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ProcessEscalationRulesJob
 *
 * Scheduled job that processes all active escalation rules.
 * Runs every 5 minutes via Laravel Scheduler.
 *
 * Only processes rules for companies with escalation_rules_enabled = true.
 */
class ProcessEscalationRulesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes max

    public function handle(): void
    {
        Log::info('[Escalation] Starting escalation rules processing');

        // Get all companies with escalation enabled
        $companies = Company::where('escalation_rules_enabled', true)
            ->where('is_active', true)
            ->get();

        if ($companies->isEmpty()) {
            Log::info('[Escalation] No companies with escalation rules enabled');
            return;
        }

        $totalExecutions = 0;

        foreach ($companies as $company) {
            $executions = $this->processCompanyRules($company);
            $totalExecutions += $executions;
        }

        Log::info("[Escalation] Completed. Total executions: {$totalExecutions}");
    }

    protected function processCompanyRules(Company $company): int
    {
        $rules = EscalationRule::where('company_id', $company->id)
            ->active()
            ->ordered()
            ->get();

        if ($rules->isEmpty()) {
            return 0;
        }

        // Get all open cases for this company
        $cases = ServiceCase::where('company_id', $company->id)
            ->open()
            ->get();

        $executionCount = 0;

        foreach ($rules as $rule) {
            foreach ($cases as $case) {
                // Check if rule conditions match
                if (!$rule->matchesCase($case)) {
                    continue;
                }

                // Check if trigger condition is met
                if (!$rule->shouldTrigger($case)) {
                    continue;
                }

                // Check if already executed recently (prevent duplicate execution)
                if ($this->wasRecentlyExecuted($rule, $case)) {
                    continue;
                }

                // Execute the rule action
                $success = $this->executeAction($rule, $case);
                $executionCount++;

                if ($success) {
                    Log::info("[Escalation] Rule '{$rule->name}' executed for case {$case->case_number}");
                }
            }

            // Update last_executed_at
            $rule->update(['last_executed_at' => now()]);
        }

        return $executionCount;
    }

    /**
     * Check if this rule was recently executed for this case (within last hour).
     */
    protected function wasRecentlyExecuted(EscalationRule $rule, ServiceCase $case): bool
    {
        return EscalationRuleExecution::where('escalation_rule_id', $rule->id)
            ->where('service_case_id', $case->id)
            ->where('executed_at', '>=', now()->subHour())
            ->exists();
    }

    /**
     * Execute the escalation action.
     */
    protected function executeAction(EscalationRule $rule, ServiceCase $case): bool
    {
        $execution = new EscalationRuleExecution([
            'escalation_rule_id' => $rule->id,
            'service_case_id' => $case->id,
            'action_type' => $rule->action_type,
            'executed_at' => now(),
        ]);

        try {
            $result = match ($rule->action_type) {
                EscalationRule::ACTION_NOTIFY_EMAIL => $this->executeEmailNotification($rule, $case),
                EscalationRule::ACTION_REASSIGN_GROUP => $this->executeReassignGroup($rule, $case),
                EscalationRule::ACTION_ESCALATE_PRIORITY => $this->executeEscalatePriority($rule, $case),
                EscalationRule::ACTION_NOTIFY_WEBHOOK => $this->executeWebhookNotification($rule, $case),
                default => ['success' => false, 'error' => 'Unknown action type'],
            };

            $execution->success = $result['success'] ?? true;
            $execution->action_result = $result;
            $execution->error_message = $result['error'] ?? null;

        } catch (\Exception $e) {
            Log::error("[Escalation] Action failed: {$e->getMessage()}", [
                'rule_id' => $rule->id,
                'case_id' => $case->id,
                'exception' => $e,
            ]);

            $execution->success = false;
            $execution->error_message = $e->getMessage();
        }

        $execution->save();

        return $execution->success;
    }

    /**
     * Send email notification.
     */
    protected function executeEmailNotification(EscalationRule $rule, ServiceCase $case): array
    {
        $config = $rule->action_config ?? [];
        $recipients = $this->parseEmailRecipients($config['email_recipients'] ?? '');

        if (empty($recipients)) {
            return ['success' => false, 'error' => 'No email recipients configured'];
        }

        $subject = $this->replaceVariables(
            $config['email_subject'] ?? 'Eskalation: {case_number}',
            $case
        );

        // For now, send a simple notification email
        // In production, you'd use a proper Mailable class
        $emailsSent = [];
        $emailsFailed = [];

        foreach ($recipients as $email) {
            try {
                Mail::raw(
                    $this->buildEmailBody($rule, $case),
                    function ($message) use ($email, $subject) {
                        $message->to($email)
                            ->subject($subject);
                    }
                );
                $emailsSent[] = $email;
            } catch (\Exception $e) {
                Log::error("[Escalation] Failed to send email notification", [
                    'rule_id' => $rule->id,
                    'case_id' => $case->id,
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                ]);
                $emailsFailed[] = $email;
                // Continue to next recipient instead of stopping
            }
        }

        return [
            'success' => empty($emailsFailed),
            'recipients_sent' => $emailsSent,
            'recipients_failed' => $emailsFailed,
            'subject' => $subject,
        ];
    }

    /**
     * Reassign case to a different group.
     */
    protected function executeReassignGroup(EscalationRule $rule, ServiceCase $case): array
    {
        $config = $rule->action_config ?? [];
        $targetGroupId = $config['target_group_id'] ?? null;

        if (!$targetGroupId) {
            return ['success' => false, 'error' => 'No target group configured'];
        }

        $previousGroupId = $case->assigned_group_id;

        $case->update([
            'assigned_group_id' => $targetGroupId,
            'assigned_to' => null, // Clear individual assignment
        ]);

        return [
            'success' => true,
            'previous_group_id' => $previousGroupId,
            'new_group_id' => $targetGroupId,
        ];
    }

    /**
     * Escalate case priority.
     */
    protected function executeEscalatePriority(EscalationRule $rule, ServiceCase $case): array
    {
        $config = $rule->action_config ?? [];
        $targetPriority = $config['target_priority'] ?? ServiceCase::PRIORITY_HIGH;

        $previousPriority = $case->priority;

        // Only escalate if new priority is higher
        $priorityOrder = [
            ServiceCase::PRIORITY_LOW => 0,
            ServiceCase::PRIORITY_NORMAL => 1,
            ServiceCase::PRIORITY_HIGH => 2,
            ServiceCase::PRIORITY_CRITICAL => 3,
        ];

        if (($priorityOrder[$targetPriority] ?? 0) <= ($priorityOrder[$previousPriority] ?? 0)) {
            return [
                'success' => false,
                'error' => 'Target priority is not higher than current priority',
                'current' => $previousPriority,
                'target' => $targetPriority,
            ];
        }

        $case->update(['priority' => $targetPriority]);

        return [
            'success' => true,
            'previous_priority' => $previousPriority,
            'new_priority' => $targetPriority,
        ];
    }

    /**
     * Call external webhook.
     */
    protected function executeWebhookNotification(EscalationRule $rule, ServiceCase $case): array
    {
        $config = $rule->action_config ?? [];
        $webhookUrl = $config['webhook_url'] ?? null;

        if (!$webhookUrl) {
            return ['success' => false, 'error' => 'No webhook URL configured'];
        }

        $payload = [
            'event' => 'escalation_triggered',
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'trigger_type' => $rule->trigger_type,
            'case' => [
                'id' => $case->id,
                'case_number' => $case->case_number,
                'short_description' => $case->short_description,
                'status' => $case->status,
                'priority' => $case->priority,
                'created_at' => $case->created_at->toIso8601String(),
                'sla_response_due_at' => $case->sla_response_due_at?->toIso8601String(),
                'sla_resolution_due_at' => $case->sla_resolution_due_at?->toIso8601String(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $headers = ['Content-Type' => 'application/json'];

        // Add signature if secret is configured
        if (!empty($config['webhook_secret'])) {
            $signature = hash_hmac('sha256', json_encode($payload), $config['webhook_secret']);
            $headers['X-Signature'] = $signature;
        }

        $response = Http::withHeaders($headers)
            ->timeout(10)
            ->post($webhookUrl, $payload);

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'webhook_url' => $webhookUrl,
            'error' => $response->failed() ? $response->body() : null,
        ];
    }

    /**
     * Parse comma-separated email recipients.
     */
    protected function parseEmailRecipients(string $recipients): array
    {
        return array_filter(
            array_map('trim', explode(',', $recipients)),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        );
    }

    /**
     * Replace variables in template string.
     */
    protected function replaceVariables(string $template, ServiceCase $case): string
    {
        return str_replace(
            ['{case_number}', '{priority}', '{category}', '{status}'],
            [
                $case->case_number,
                $case->priority,
                $case->category?->name ?? 'N/A',
                $case->status,
            ],
            $template
        );
    }

    /**
     * Build email body for escalation notification.
     */
    protected function buildEmailBody(EscalationRule $rule, ServiceCase $case): string
    {
        return <<<TEXT
Eskalationsregel ausgelöst: {$rule->name}

Case: {$case->case_number}
Beschreibung: {$case->short_description}
Status: {$case->status}
Priorität: {$case->priority}
Kategorie: {$case->category?->name}

Trigger: {$rule->trigger_label}

Erstellt: {$case->created_at->format('d.m.Y H:i')}
SLA Response: {$case->sla_response_due_at?->format('d.m.Y H:i')}
SLA Resolution: {$case->sla_resolution_due_at?->format('d.m.Y H:i')}

---
Diese E-Mail wurde automatisch generiert.
TEXT;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[ProcessEscalationRulesJob] permanently failed', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
