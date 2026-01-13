<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ServiceCase;
use App\Models\ServiceCaseActivityLog;
use App\Models\ServiceGatewayExchangeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill missing Activity Log entries for historical Service Cases.
 *
 * Creates:
 * - category_assigned: From case category_id at creation time
 * - enrichment_started: Estimated from enriched_at
 * - email_sent: From successful email exchange logs
 * - webhook_sent: From successful webhook exchange logs
 *
 * Idempotent: Skips cases that already have the respective events.
 */
class BackfillActivityLogsCommand extends Command
{
    protected $signature = 'service-gateway:backfill-activity-logs
                            {--case-id= : Backfill only a specific case}
                            {--dry-run : Show what would be created without actually creating}';

    protected $description = 'Backfill missing Activity Log entries for historical Service Cases';

    private int $created = 0;
    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('🔄 Starting Activity Log Backfill...');
        $this->newLine();

        $caseId = $this->option('case-id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $query = ServiceCase::query()
            ->with(['category', 'customer']);

        if ($caseId) {
            $query->where('id', $caseId);
            $this->info("Processing only Case #{$caseId}");
        }

        $cases = $query->get();
        $this->info("Found {$cases->count()} cases to process");
        $this->newLine();

        $bar = $this->output->createProgressBar($cases->count());
        $bar->start();

        foreach ($cases as $case) {
            $this->backfillCase($case, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Backfill complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $this->created],
                ['Skipped (already exists)', $this->skipped],
            ]
        );

        return Command::SUCCESS;
    }

    private function backfillCase(ServiceCase $case, bool $dryRun): void
    {
        // 1. Category Assigned
        $this->backfillCategoryAssigned($case, $dryRun);

        // 2. Enrichment Started
        $this->backfillEnrichmentStarted($case, $dryRun);

        // 3. Enrichment Completed (add details if missing)
        $this->updateEnrichmentCompletedDetails($case, $dryRun);

        // 4. Email Sent (from Exchange Logs)
        $this->backfillEmailSent($case, $dryRun);

        // 5. Webhook Sent (from Exchange Logs)
        $this->backfillWebhookSent($case, $dryRun);
    }

    private function backfillCategoryAssigned(ServiceCase $case, bool $dryRun): void
    {
        // Skip if no category
        if (!$case->category_id) {
            return;
        }

        // Check if already exists
        $exists = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_CATEGORY_ASSIGNED)
            ->exists();

        if ($exists) {
            $this->skipped++;
            return;
        }

        if ($dryRun) {
            $this->created++;
            return;
        }

        ServiceCaseActivityLog::create([
            'service_case_id' => $case->id,
            'company_id' => $case->company_id,
            'user_id' => null,
            'action' => ServiceCaseActivityLog::ACTION_CATEGORY_ASSIGNED,
            'old_values' => null,
            'new_values' => [
                'category_id' => $case->category_id,
                'category_name' => $case->category?->name ?? 'Unbekannt',
                'case_type' => $case->case_type,
                'priority' => $case->priority,
            ],
            'reason' => "KI-Kategorisierung: " . ($case->category?->name ?? 'Unbekannt') .
                       " (Typ: {$case->case_type}, Priorität: {$case->priority})",
            'created_at' => $case->created_at, // Same time as case creation
        ]);

        $this->created++;
    }

    private function backfillEnrichmentStarted(ServiceCase $case, bool $dryRun): void
    {
        // Skip if not enriched
        if ($case->enrichment_status !== ServiceCase::ENRICHMENT_ENRICHED) {
            return;
        }

        // Check if already exists
        $exists = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_ENRICHMENT_STARTED)
            ->exists();

        if ($exists) {
            $this->skipped++;
            return;
        }

        if ($dryRun) {
            $this->created++;
            return;
        }

        // Estimate start time: enriched_at minus ~30 seconds (typical processing time)
        $estimatedStartTime = $case->enriched_at?->subSeconds(30) ?? $case->created_at->addSeconds(60);

        ServiceCaseActivityLog::create([
            'service_case_id' => $case->id,
            'company_id' => $case->company_id,
            'user_id' => null,
            'action' => ServiceCaseActivityLog::ACTION_ENRICHMENT_STARTED,
            'old_values' => ['enrichment_status' => ServiceCase::ENRICHMENT_PENDING],
            'new_values' => ['enrichment_status' => 'in_progress'],
            'reason' => "Anreicherung mit Transkript und Audio gestartet (Backfill)",
            'created_at' => $estimatedStartTime,
        ]);

        $this->created++;
    }

    private function updateEnrichmentCompletedDetails(ServiceCase $case, bool $dryRun): void
    {
        // Find existing enrichment_completed log without details
        $log = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_ENRICHMENT_COMPLETED)
            ->whereNull('reason')
            ->first();

        if (!$log) {
            return;
        }

        if ($dryRun) {
            $this->created++; // Count as update
            return;
        }

        // Add details from case data
        $log->update([
            'new_values' => array_merge($log->new_values ?? [], [
                'transcript_segments' => $case->transcript_segment_count,
                'transcript_chars' => $case->transcript_char_count,
            ]),
            'reason' => "Transkript verarbeitet: " .
                       ($case->transcript_segment_count ?? 0) . " Segmente, " .
                       ($case->transcript_char_count ?? 0) . " Zeichen (Backfill)",
        ]);

        $this->created++;
    }

    private function backfillEmailSent(ServiceCase $case, bool $dryRun): void
    {
        // Check if already exists
        $exists = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_EMAIL_SENT)
            ->exists();

        if ($exists) {
            $this->skipped++;
            return;
        }

        // Find successful email exchange log
        $emailLog = ServiceGatewayExchangeLog::where('service_case_id', $case->id)
            ->where('direction', 'outbound')
            ->where('status_code', 200)
            ->where(function ($q) {
                $q->where('endpoint', 'like', '%mail%')
                  ->orWhere('endpoint', 'like', '%email%')
                  ->orWhere('endpoint', '=', 'email');
            })
            ->first();

        // Alternative: Check if output was sent and config has email enabled
        if (!$emailLog && $case->output_status === ServiceCase::OUTPUT_SENT) {
            $hasEmailConfig = $case->category?->outputConfiguration?->output_type &&
                             in_array($case->category->outputConfiguration->output_type, ['email', 'hybrid']);

            if (!$hasEmailConfig) {
                return;
            }

            // Create based on output_status change
            if ($dryRun) {
                $this->created++;
                return;
            }

            // Find when output was sent
            $outputLog = ServiceCaseActivityLog::where('service_case_id', $case->id)
                ->where('action', ServiceCaseActivityLog::ACTION_OUTPUT_STATUS_CHANGED)
                ->whereJsonContains('new_values->output_status', 'sent')
                ->first();

            ServiceCaseActivityLog::create([
                'service_case_id' => $case->id,
                'company_id' => $case->company_id,
                'user_id' => null,
                'action' => ServiceCaseActivityLog::ACTION_EMAIL_SENT,
                'old_values' => null,
                'new_values' => [
                    'recipients' => 'Konfigurierte Empfänger',
                    'count' => 1,
                ],
                'reason' => "E-Mail-Benachrichtigung gesendet (Backfill)",
                'created_at' => $outputLog?->created_at ?? $case->updated_at,
            ]);

            $this->created++;
            return;
        }

        if (!$emailLog) {
            return;
        }

        if ($dryRun) {
            $this->created++;
            return;
        }

        ServiceCaseActivityLog::create([
            'service_case_id' => $case->id,
            'company_id' => $case->company_id,
            'user_id' => null,
            'action' => ServiceCaseActivityLog::ACTION_EMAIL_SENT,
            'old_values' => null,
            'new_values' => [
                'status_code' => $emailLog->status_code,
                'duration_ms' => $emailLog->duration_ms,
            ],
            'reason' => "E-Mail-Benachrichtigung gesendet (Backfill)",
            'created_at' => $emailLog->created_at,
        ]);

        $this->created++;
    }

    private function backfillWebhookSent(ServiceCase $case, bool $dryRun): void
    {
        // Check if already exists
        $exists = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_WEBHOOK_SENT)
            ->exists();

        if ($exists) {
            $this->skipped++;
            return;
        }

        // Find successful webhook exchange log
        $webhookLog = ServiceGatewayExchangeLog::where('service_case_id', $case->id)
            ->where('direction', 'outbound')
            ->where('http_method', 'POST')
            ->whereBetween('status_code', [200, 299])
            ->where('endpoint', 'not like', '%mail%')
            ->where('endpoint', 'not like', '%email%')
            ->where('endpoint', '!=', 'email')
            ->first();

        if (!$webhookLog) {
            // Check if output was sent and config has webhook enabled
            if ($case->output_status === ServiceCase::OUTPUT_SENT) {
                $hasWebhookConfig = $case->category?->outputConfiguration?->output_type &&
                                   in_array($case->category->outputConfiguration->output_type, ['webhook', 'hybrid']);

                if ($hasWebhookConfig) {
                    if ($dryRun) {
                        $this->created++;
                        return;
                    }

                    $outputLog = ServiceCaseActivityLog::where('service_case_id', $case->id)
                        ->where('action', ServiceCaseActivityLog::ACTION_OUTPUT_STATUS_CHANGED)
                        ->whereJsonContains('new_values->output_status', 'sent')
                        ->first();

                    ServiceCaseActivityLog::create([
                        'service_case_id' => $case->id,
                        'company_id' => $case->company_id,
                        'user_id' => null,
                        'action' => ServiceCaseActivityLog::ACTION_WEBHOOK_SENT,
                        'old_values' => null,
                        'new_values' => [
                            'external_reference' => $case->external_reference,
                        ],
                        'reason' => $case->external_reference
                            ? "Webhook gesendet, Ticket erstellt: {$case->external_reference} (Backfill)"
                            : "Webhook erfolgreich gesendet (Backfill)",
                        'created_at' => $outputLog?->created_at ?? $case->updated_at,
                    ]);

                    $this->created++;
                }
            }
            return;
        }

        if ($dryRun) {
            $this->created++;
            return;
        }

        // Extract external reference from response if available
        $externalRef = $case->external_reference;

        ServiceCaseActivityLog::create([
            'service_case_id' => $case->id,
            'company_id' => $case->company_id,
            'user_id' => null,
            'action' => ServiceCaseActivityLog::ACTION_WEBHOOK_SENT,
            'old_values' => null,
            'new_values' => [
                'url' => $this->maskUrl($webhookLog->endpoint),
                'status_code' => $webhookLog->status_code,
                'external_reference' => $externalRef,
                'duration_ms' => $webhookLog->duration_ms,
            ],
            'reason' => $externalRef
                ? "Webhook gesendet, Ticket erstellt: {$externalRef} (Backfill)"
                : "Webhook erfolgreich gesendet (Backfill)",
            'created_at' => $webhookLog->created_at,
        ]);

        $this->created++;
    }

    private function maskUrl(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? 'unknown';
        $path = $parsed['path'] ?? '/';

        // Truncate path if too long
        if (strlen($path) > 30) {
            $path = substr($path, 0, 27) . '...';
        }

        return "{$host}{$path}";
    }
}
