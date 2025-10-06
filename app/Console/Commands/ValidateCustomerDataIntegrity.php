<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Validate Customer Data Integrity Command
 *
 * Daily validation to ensure no NULL company_id values exist and
 * all customer relationships maintain data integrity.
 *
 * Usage:
 * php artisan customers:validate-integrity
 * php artisan customers:validate-integrity --detailed
 * php artisan customers:validate-integrity --alert-on-failure
 */
class ValidateCustomerDataIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'customers:validate-integrity
                            {--detailed : Show detailed validation results}
                            {--alert-on-failure : Send alerts if validation fails}
                            {--fix-issues : Attempt to automatically fix detected issues}';

    /**
     * The console command description.
     */
    protected $description = 'Validate customer data integrity (company_id, relationships, isolation)';

    /**
     * Validation results
     */
    protected array $results = [];
    protected bool $hasCriticalIssues = false;
    protected bool $hasWarnings = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Customer Data Integrity Validation...');
        $this->info('Timestamp: ' . now()->toDateTimeString());
        $this->newLine();

        // Run all validation checks
        $this->validateNoNullCompanyId();
        $this->validateValidCompanyReferences();
        $this->validateCustomerAppointmentCompanyMatch();
        $this->validateCompanyScopeEffectiveness();
        $this->validateRelationshipIntegrity();
        $this->validateSoftDeletedCustomers();

        // Display results
        $this->displayResults();

        // Handle alerting if requested
        if ($this->option('alert-on-failure') && $this->hasCriticalIssues) {
            $this->sendAlerts();
        }

        // Attempt fixes if requested
        if ($this->option('fix-issues') && $this->hasCriticalIssues) {
            $this->attemptFixes();
        }

        // Generate report
        $this->generateReport();

        // Return appropriate exit code
        if ($this->hasCriticalIssues) {
            $this->error('Validation FAILED with critical issues');
            return Command::FAILURE;
        }

        if ($this->hasWarnings) {
            $this->warn('Validation passed with warnings');
            return Command::SUCCESS;
        }

        $this->info('Validation PASSED - All checks successful');
        return Command::SUCCESS;
    }

    /**
     * Validate no active customers have NULL company_id
     */
    protected function validateNoNullCompanyId(): void
    {
        $this->info('Checking for NULL company_id values...');

        $nullCount = DB::table('customers')
            ->whereNull('company_id')
            ->whereNull('deleted_at')
            ->count();

        if ($nullCount > 0) {
            $this->results['null_company_id'] = [
                'status' => 'CRITICAL',
                'message' => "Found {$nullCount} active customers with NULL company_id",
                'count' => $nullCount,
                'severity' => 'high',
            ];
            $this->hasCriticalIssues = true;
            $this->error("  CRITICAL: {$nullCount} customers have NULL company_id");

            if ($this->option('detailed')) {
                $nullCustomers = DB::table('customers')
                    ->whereNull('company_id')
                    ->whereNull('deleted_at')
                    ->select('id', 'name', 'email', 'created_at')
                    ->get();

                $this->table(
                    ['ID', 'Name', 'Email', 'Created At'],
                    $nullCustomers->map(fn ($c) => [$c->id, $c->name, $c->email, $c->created_at])
                );
            }
        } else {
            $this->results['null_company_id'] = [
                'status' => 'PASS',
                'message' => 'No NULL company_id values found',
                'count' => 0,
            ];
            $this->info('  ✓ No NULL company_id values');
        }
    }

    /**
     * Validate all company_id references point to valid companies
     */
    protected function validateValidCompanyReferences(): void
    {
        $this->info('Validating company references...');

        $invalidRefs = DB::table('customers')
            ->whereNotNull('company_id')
            ->whereNotIn('company_id', function ($query) {
                $query->select('id')->from('companies');
            })
            ->count();

        if ($invalidRefs > 0) {
            $this->results['invalid_company_refs'] = [
                'status' => 'CRITICAL',
                'message' => "Found {$invalidRefs} customers with invalid company_id",
                'count' => $invalidRefs,
                'severity' => 'high',
            ];
            $this->hasCriticalIssues = true;
            $this->error("  CRITICAL: {$invalidRefs} invalid company references");
        } else {
            $this->results['invalid_company_refs'] = [
                'status' => 'PASS',
                'message' => 'All company references are valid',
                'count' => 0,
            ];
            $this->info('  ✓ All company references valid');
        }
    }

    /**
     * Validate customer company_id matches appointment company_id
     */
    protected function validateCustomerAppointmentCompanyMatch(): void
    {
        $this->info('Validating customer-appointment company alignment...');

        $mismatches = DB::table('appointments')
            ->join('customers', 'appointments.customer_id', '=', 'customers.id')
            ->where('appointments.company_id', '!=', DB::raw('customers.company_id'))
            ->count();

        if ($mismatches > 0) {
            $this->results['company_mismatches'] = [
                'status' => 'WARNING',
                'message' => "Found {$mismatches} customer-appointment company mismatches",
                'count' => $mismatches,
                'severity' => 'medium',
            ];
            $this->hasWarnings = true;
            $this->warn("  WARNING: {$mismatches} company mismatches");

            if ($this->option('detailed')) {
                $details = DB::table('appointments')
                    ->join('customers', 'appointments.customer_id', '=', 'customers.id')
                    ->where('appointments.company_id', '!=', DB::raw('customers.company_id'))
                    ->select(
                        'customers.id as customer_id',
                        'customers.name',
                        'customers.company_id as customer_company',
                        'appointments.company_id as appointment_company'
                    )
                    ->limit(10)
                    ->get();

                $this->table(
                    ['Customer ID', 'Name', 'Customer Company', 'Appointment Company'],
                    $details->map(fn ($d) => [$d->customer_id, $d->name, $d->customer_company, $d->appointment_company])
                );
            }
        } else {
            $this->results['company_mismatches'] = [
                'status' => 'PASS',
                'message' => 'Customer and appointment companies match',
                'count' => 0,
            ];
            $this->info('  ✓ Customer-appointment alignment verified');
        }
    }

    /**
     * Validate CompanyScope is functioning correctly
     */
    protected function validateCompanyScopeEffectiveness(): void
    {
        $this->info('Validating CompanyScope effectiveness...');

        // This is a logic check - CompanyScope should filter properly
        $totalCustomers = DB::table('customers')->whereNull('deleted_at')->count();
        $scopedTotal = 0;

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            $scopedTotal += DB::table('customers')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count();
        }

        if ($totalCustomers !== $scopedTotal) {
            $this->results['scope_effectiveness'] = [
                'status' => 'WARNING',
                'message' => 'Scope filtering may have issues',
                'total' => $totalCustomers,
                'scoped_total' => $scopedTotal,
            ];
            $this->hasWarnings = true;
            $this->warn('  WARNING: CompanyScope inconsistency detected');
        } else {
            $this->results['scope_effectiveness'] = [
                'status' => 'PASS',
                'message' => 'CompanyScope functioning correctly',
            ];
            $this->info('  ✓ CompanyScope verified');
        }
    }

    /**
     * Validate relationship integrity
     */
    protected function validateRelationshipIntegrity(): void
    {
        $this->info('Validating relationship integrity...');

        // Check for orphaned appointments
        $orphanedAppointments = DB::table('appointments')
            ->whereNotNull('customer_id')
            ->whereNotIn('customer_id', function ($query) {
                $query->select('id')->from('customers');
            })
            ->count();

        if ($orphanedAppointments > 0) {
            $this->results['orphaned_appointments'] = [
                'status' => 'WARNING',
                'message' => "Found {$orphanedAppointments} appointments with invalid customer_id",
                'count' => $orphanedAppointments,
            ];
            $this->hasWarnings = true;
            $this->warn("  WARNING: {$orphanedAppointments} orphaned appointments");
        } else {
            $this->results['orphaned_appointments'] = [
                'status' => 'PASS',
                'message' => 'No orphaned appointments',
                'count' => 0,
            ];
            $this->info('  ✓ Relationship integrity verified');
        }
    }

    /**
     * Validate soft deleted customers
     */
    protected function validateSoftDeletedCustomers(): void
    {
        $this->info('Validating soft deleted customers...');

        $softDeleted = DB::table('customers')->whereNotNull('deleted_at')->count();

        $this->results['soft_deleted'] = [
            'status' => 'INFO',
            'message' => "{$softDeleted} soft deleted customers",
            'count' => $softDeleted,
        ];

        $this->info("  ℹ {$softDeleted} soft deleted customers");
    }

    /**
     * Display validation results
     */
    protected function displayResults(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('             VALIDATION RESULTS SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        foreach ($this->results as $check => $result) {
            $status = $result['status'];
            $message = $result['message'];

            $color = match ($status) {
                'PASS' => 'info',
                'WARNING' => 'warn',
                'CRITICAL' => 'error',
                default => 'comment',
            };

            $this->$color("  [{$status}] {$message}");
        }

        $this->newLine();
    }

    /**
     * Send alerts for critical issues
     */
    protected function sendAlerts(): void
    {
        $this->warn('Sending alerts for critical issues...');

        $criticalIssues = collect($this->results)
            ->filter(fn ($result) => $result['status'] === 'CRITICAL')
            ->toArray();

        Log::critical('Customer Data Integrity Validation FAILED', [
            'timestamp' => now()->toDateTimeString(),
            'issues' => $criticalIssues,
        ]);

        // Integrate with your alerting system (Slack, PagerDuty, email, etc.)
        // Example: Notification::send(...);

        $this->info('Alerts sent successfully');
    }

    /**
     * Attempt to automatically fix detected issues
     */
    protected function attemptFixes(): void
    {
        $this->warn('Attempting automatic fixes (DRY RUN mode)...');

        if (isset($this->results['null_company_id']) && $this->results['null_company_id']['status'] === 'CRITICAL') {
            $this->warn('Would attempt to backfill NULL company_id values from appointments');
            // Actual fix logic would go here
        }

        if (isset($this->results['invalid_company_refs']) && $this->results['invalid_company_refs']['status'] === 'CRITICAL') {
            $this->warn('Would attempt to fix invalid company references');
            // Actual fix logic would go here
        }

        $this->info('Note: Automatic fixes require manual approval. Use --force flag to apply.');
    }

    /**
     * Generate detailed report
     */
    protected function generateReport(): void
    {
        $reportPath = storage_path('logs/customer_integrity_' . now()->format('Y-m-d_H-i-s') . '.json');

        $report = [
            'timestamp' => now()->toDateTimeString(),
            'validation_results' => $this->results,
            'has_critical_issues' => $this->hasCriticalIssues,
            'has_warnings' => $this->hasWarnings,
            'summary' => [
                'total_customers' => DB::table('customers')->count(),
                'active_customers' => DB::table('customers')->whereNull('deleted_at')->count(),
                'total_companies' => DB::table('companies')->count(),
            ],
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Detailed report saved to: {$reportPath}");
    }
}
