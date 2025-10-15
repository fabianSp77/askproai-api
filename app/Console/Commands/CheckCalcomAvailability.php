<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Services\CalcomV2Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Health Check Command: Cal.com Availability Monitoring
 *
 * 🔧 CREATED 2025-10-15: Part of Cal.com Zero Slots Fix (Stage 2)
 *
 * Purpose:
 * - Daily automated check of all Event Types for availability issues
 * - Early detection of 0-slots problem before customers notice
 * - Actionable diagnostics for quick resolution
 *
 * Schedule: Daily at 06:00 (before business hours)
 * Cron: 0 6 * * * php artisan calcom:check-availability
 *
 * Exit Codes:
 * - 0: All Event Types have availability ✅
 * - 1: One or more Event Types have 0 slots ⚠️
 * - 2: Critical error (API down, config missing) 🚨
 *
 * @see STUFE_1_UND_2_UEBERSICHT.md
 */
class CheckCalcomAvailability extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'calcom:check-availability
                            {--company= : Check specific company ID only}
                            {--service= : Check specific service ID only}
                            {--days=7 : Number of days to check (default: 7)}
                            {--detailed : Show detailed slot counts per day}';

    /**
     * The console command description.
     */
    protected $description = 'Check Cal.com availability for all services and detect zero-slots issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Cal.com Availability Health Check');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Configuration
        $days = (int)$this->option('days');
        $startDate = Carbon::tomorrow()->startOfDay();
        $endDate = $startDate->copy()->addDays($days - 1)->endOfDay();

        $this->info("📅 Checking availability: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} ({$days} days)");
        $this->newLine();

        // Get services to check
        $services = $this->getServicesToCheck();

        if ($services->isEmpty()) {
            $this->warn('⚠️ No services found with Cal.com Event Type IDs');
            return self::SUCCESS;
        }

        $this->info("📋 Found {$services->count()} service(s) to check");
        $this->newLine();

        // Check each service
        $results = [];
        $hasIssues = false;

        foreach ($services as $service) {
            $result = $this->checkServiceAvailability($service, $startDate, $endDate, $days);
            $results[] = $result;

            if ($result['status'] === 'error' || $result['total_slots'] === 0) {
                $hasIssues = true;
            }
        }

        // Summary
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 Summary');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $healthy = collect($results)->where('status', 'ok')->count();
        $unhealthy = collect($results)->where('total_slots', 0)->count();
        $errors = collect($results)->where('status', 'error')->count();

        $this->line("✅ Healthy: {$healthy}");
        if ($unhealthy > 0) {
            $this->line("<fg=yellow>⚠️ Zero Slots: {$unhealthy}</>");
        }
        if ($errors > 0) {
            $this->line("<fg=red>❌ Errors: {$errors}</>");
        }

        // Log results
        Log::channel('calcom')->info('Cal.com Availability Health Check completed', [
            'total_services' => $services->count(),
            'healthy' => $healthy,
            'zero_slots' => $unhealthy,
            'errors' => $errors,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'results' => $results
        ]);

        // Actionable recommendations
        if ($hasIssues) {
            $this->newLine();
            $this->warn('⚡ ACTIONABLE STEPS:');
            $this->line('1. Check Cal.com Dashboard: Availability Schedule configured?');
            $this->line('2. Check Team Members: Do they have availability assigned?');
            $this->line('3. Check Minimum Notice: Is it too restrictive? (<24h recommended)');
            $this->line('4. Run: php artisan cache:clear && retry');
            $this->line('5. See: CALCOM_QUICK_FIX_ANLEITUNG.md');
        }

        // Exit code
        if ($errors > 0) {
            return 2; // Critical error
        }

        if ($unhealthy > 0) {
            return 1; // Zero slots detected
        }

        return self::SUCCESS;
    }

    /**
     * Get services to check based on command options
     */
    private function getServicesToCheck()
    {
        $query = Service::whereNotNull('calcom_event_type_id')
            ->where('is_active', true)
            ->with(['company']);

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        if ($serviceId = $this->option('service')) {
            $query->where('id', $serviceId);
        }

        return $query->get();
    }

    /**
     * Check availability for a specific service
     */
    private function checkServiceAvailability(Service $service, Carbon $startDate, Carbon $endDate, int $days): array
    {
        $company = $service->company;
        $eventTypeId = $service->calcom_event_type_id;
        $teamId = $company->calcom_team_id;

        $this->line("🔄 Checking: <fg=cyan>{$service->name}</> (Service #{$service->id})");
        $this->line("   Company: {$company->name} (Team ID: {$teamId})");
        $this->line("   Event Type: {$eventTypeId}");

        try {
            // Initialize Cal.com client
            $calcom = new CalcomV2Client($company);

            // Get available slots
            $response = $calcom->getAvailableSlots(
                $eventTypeId,
                $startDate,
                $endDate
            );

            if (!$response->successful()) {
                $this->error("   ❌ ERROR: HTTP {$response->status()}");
                $this->line("   Response: " . $response->body());

                return [
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'event_type_id' => $eventTypeId,
                    'team_id' => $teamId,
                    'status' => 'error',
                    'error' => "HTTP {$response->status()}: {$response->body()}",
                    'total_slots' => 0
                ];
            }

            $data = $response->json();
            $slots = $data['data']['slots'] ?? [];

            // Count total slots
            $totalSlots = 0;
            $slotsPerDay = [];

            foreach ($slots as $date => $dateSlots) {
                $count = count($dateSlots);
                $totalSlots += $count;
                $slotsPerDay[$date] = $count;
            }

            // Status
            if ($totalSlots === 0) {
                $this->line("   <fg=yellow>⚠️ ZERO SLOTS DETECTED</> (0 available slots in next {$days} days)");
            } else {
                $this->line("   <fg=green>✅ OK</> ({$totalSlots} available slots)");
            }

            // Detailed output
            if ($this->option('detailed') && !empty($slotsPerDay)) {
                $this->line("   Slots per day:");
                foreach ($slotsPerDay as $date => $count) {
                    $this->line("     {$date}: {$count} slots");
                }
            }

            $this->newLine();

            return [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $eventTypeId,
                'team_id' => $teamId,
                'status' => $totalSlots > 0 ? 'ok' : 'zero_slots',
                'total_slots' => $totalSlots,
                'days_with_slots' => count($slotsPerDay),
                'slots_per_day' => $slotsPerDay
            ];

        } catch (\Exception $e) {
            $this->error("   ❌ EXCEPTION: {$e->getMessage()}");
            $this->newLine();

            Log::error('Cal.com availability check failed', [
                'service_id' => $service->id,
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $eventTypeId,
                'team_id' => $teamId,
                'status' => 'error',
                'error' => $e->getMessage(),
                'total_slots' => 0
            ];
        }
    }
}
