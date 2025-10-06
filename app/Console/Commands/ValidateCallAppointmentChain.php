<?php

namespace App\Console\Commands;

use App\Models\{Call, Appointment, Customer};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateCallAppointmentChain extends Command
{
    protected $signature = 'appointments:validate-chain {call_id? : Specific call ID to validate}';
    protected $description = 'Validate Call → Customer → Appointment data chain and report inconsistencies';

    public function handle(): int
    {
        $this->info('🔍 Validating Call-Appointment-Customer Chain...');
        $this->newLine();

        if ($callId = $this->argument('call_id')) {
            return $this->validateSingleCall($callId);
        }

        return $this->validateAllCalls();
    }

    private function validateSingleCall(int $callId): int
    {
        $call = Call::with(['customer', 'appointments'])->find($callId);

        if (!$call) {
            $this->error("❌ Call {$callId} not found");
            return 1;
        }

        $this->info("📞 Call ID: {$call->id} (Retell: {$call->retell_call_id})");
        $this->info("   Created: {$call->created_at}");
        $this->newLine();

        $issues = $this->checkCall($call);

        if (empty($issues)) {
            $this->info("✅ All validations passed!");
            return 0;
        }

        $this->error("⚠️  Found " . count($issues) . " issue(s):");
        foreach ($issues as $issue) {
            $this->line("   • {$issue}");
        }

        return 1;
    }

    private function validateAllCalls(): int
    {
        $this->info('Checking all calls with booking_confirmed=1...');
        $this->newLine();

        $calls = Call::where('booking_confirmed', 1)
            ->orWhereNotNull('booking_details')
            ->with(['customer', 'appointments'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        if ($calls->isEmpty()) {
            $this->warn('No calls with bookings found');
            return 0;
        }

        $totalIssues = 0;
        $callsWithIssues = 0;

        $this->table(
            ['Call ID', 'Created', 'Company', 'Customer', 'Appointment', 'Status'],
            $calls->map(function ($call) use (&$totalIssues, &$callsWithIssues) {
                $issues = $this->checkCall($call, false);
                if (!empty($issues)) {
                    $totalIssues += count($issues);
                    $callsWithIssues++;
                }

                return [
                    $call->id,
                    $call->created_at->format('Y-m-d H:i'),
                    $call->company_id ?: '❌ Missing',
                    $call->customer_id ? "✓ {$call->customer_id}" : '❌ Missing',
                    $call->appointments->count() > 0 ? "✓ {$call->appointments->count()}" : '❌ None',
                    empty($issues) ? '✅ OK' : '⚠️ ' . count($issues) . ' issues'
                ];
            })
        );

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Total calls checked: {$calls->count()}");
        $this->line("  Calls with issues: {$callsWithIssues}");
        $this->line("  Total issues found: {$totalIssues}");

        return $callsWithIssues > 0 ? 1 : 0;
    }

    private function checkCall(Call $call, bool $verbose = true): array
    {
        $issues = [];

        // Check 1: Company ID
        if (!$call->company_id) {
            $issues[] = "Missing company_id";
            if ($verbose) {
                $this->warn("⚠️  Missing company_id - Call is orphaned");
            }
        }

        // Check 2: Customer
        if (!$call->customer_id) {
            $issues[] = "Missing customer_id";
            if ($verbose) {
                $this->warn("⚠️  Missing customer_id - No customer linked");
            }
        } else {
            if (!$call->customer) {
                $issues[] = "Customer {$call->customer_id} does not exist (foreign key violation)";
                if ($verbose) {
                    $this->error("❌ Customer {$call->customer_id} referenced but doesn't exist!");
                }
            } elseif ($verbose) {
                $this->info("✓ Customer: {$call->customer->name} ({$call->customer->email})");
            }
        }

        // Check 3: Booking confirmed but no appointment
        if ($call->booking_confirmed && $call->appointments->isEmpty()) {
            $issues[] = "Booking confirmed but no appointment exists";
            if ($verbose) {
                $this->error("❌ booking_confirmed=1 but NO appointment record found!");

                // Try to extract Cal.com booking ID from booking_details
                if ($call->booking_details) {
                    $details = json_decode($call->booking_details, true);
                    $calcomId = $details['calcom_booking']['id'] ?? null;
                    if ($calcomId) {
                        $this->line("   Cal.com Booking ID: {$calcomId}");
                        $this->line("   💡 Run: php artisan appointments:backfill {$call->id}");
                    }
                }
            }
        }

        // Check 4: Has appointment
        if ($call->appointments->isNotEmpty()) {
            $appointment = $call->appointments->first();
            if ($verbose) {
                $this->info("✓ Appointment: ID {$appointment->id}");
                $this->info("   Time: {$appointment->starts_at}");
                $this->info("   Status: {$appointment->status}");
                $this->info("   Cal.com Booking: {$appointment->calcom_v2_booking_id}");
            }

            // Check 4a: Appointment customer matches call customer
            if ($appointment->customer_id !== $call->customer_id) {
                $issues[] = "Customer mismatch (Call: {$call->customer_id}, Appointment: {$appointment->customer_id})";
                if ($verbose) {
                    $this->warn("⚠️  Customer ID mismatch!");
                }
            }

            // Check 4b: Appointment company matches call company
            if ($appointment->company_id !== $call->company_id) {
                $issues[] = "Company mismatch (Call: {$call->company_id}, Appointment: {$appointment->company_id})";
                if ($verbose) {
                    $this->warn("⚠️  Company ID mismatch!");
                }
            }
        }

        // Check 5: Has booking_details but not confirmed
        if ($call->booking_details && !$call->booking_confirmed) {
            $issues[] = "Has booking_details but booking_confirmed=0";
            if ($verbose) {
                $this->warn("⚠️  Has booking details but booking_confirmed flag is false");
            }
        }

        return $issues;
    }
}
