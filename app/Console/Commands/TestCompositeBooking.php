<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Appointment;
use App\Services\AppointmentPhaseCreationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestCompositeBooking extends Command
{
    protected $signature = 'test:composite-booking {service_id=441} {--date=}';
    protected $description = 'Test composite service booking (Dauerwelle)';

    public function handle()
    {
        $this->info('🚀 DAUERWELLE COMPOSITE BOOKING TEST');
        $this->info(str_repeat('=', 80));
        $this->newLine();

        // Load service
        $serviceId = $this->argument('service_id');
        $service = Service::find($serviceId);

        if (!$service) {
            $this->error("Service {$serviceId} not found");
            return 1;
        }

        if (!$service->composite) {
            $this->error("Service {$service->name} is not composite");
            return 1;
        }

        $this->info("Service: {$service->name}");
        $this->info("Composite: YES");
        $this->info("Segments: " . count($service->segments));
        $this->info("Duration: {$service->duration_minutes} min");
        $this->newLine();

        // Get branch and company
        $branch = Branch::first();
        $company = $branch->company;

        // Get staff
        $staff = Staff::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereHas('services', function($q) use ($service) {
                $q->where('service_id', $service->id);
            })
            ->first();

        if (!$staff) {
            $this->error("No active staff found for service");
            return 1;
        }

        // Get or create customer
        $customer = Customer::where('phone', '+4915112345678')->first();
        if (!$customer) {
            $customer = Customer::create([
                'company_id' => $company->id,
                'name' => 'Max Mustermann (Test)',
                'phone' => '+4915112345678',
                'email' => 'max.mustermann@test.com',
            ]);
        }

        // Calculate start time
        $dateOption = $this->option('date');
        if ($dateOption) {
            $startTime = Carbon::parse($dateOption);
        } else {
            $startTime = Carbon::now()->next('Wednesday')->setTime(15, 0, 0);
        }

        $endTime = $startTime->copy()->addMinutes($service->duration_minutes);

        $this->info("Customer: {$customer->name}");
        $this->info("Staff: {$staff->name}");
        $this->info("Start: " . $startTime->format('d.m.Y H:i'));
        $this->info("End: " . $endTime->format('d.m.Y H:i'));
        $this->newLine();

        // Create appointment in transaction
        DB::transaction(function() use ($company, $branch, $service, $customer, $staff, $startTime, $endTime) {
            $this->info('STEP 1: Creating Appointment...');

            // Create appointment with explicit branch_id
            $appointment = new Appointment();
            $appointment->company_id = $company->id;
            $appointment->branch_id = $branch->id; // Set BEFORE other attributes
            $appointment->service_id = $service->id;
            $appointment->customer_id = $customer->id;
            $appointment->staff_id = $staff->id;
            $appointment->starts_at = $startTime;
            $appointment->ends_at = $endTime;
            $appointment->status = 'scheduled';
            $appointment->source = 'test_composite_cli';
            $appointment->notes = 'E2E Test: Dauerwelle Composite via CLI';
            $appointment->sync_origin = 'system';
            $appointment->calcom_sync_status = 'pending';
            $appointment->save();

            $this->info("✅ Appointment created: ID {$appointment->id}");
            $this->newLine();

            $this->info('STEP 2: Creating Phases...');

            $phaseService = new AppointmentPhaseCreationService();
            $phases = $phaseService->createPhasesFromSegments($appointment);

            $phaseCount = is_array($phases) ? count($phases) : $phases->count();
            $this->info("✅ Created {$phaseCount} phases");
            $this->newLine();

            $this->info('PHASE DETAILS:');
            $this->info(str_repeat('-', 80));

            foreach ($phases as $phase) {
                $this->line(sprintf(
                    '   %d. [%-6s] %-30s %s-%s (%2d min) %s',
                    $phase->sequence_order,
                    $phase->segment_key,
                    $phase->segment_name,
                    Carbon::parse($phase->start_time)->format('H:i'),
                    Carbon::parse($phase->end_time)->format('H:i'),
                    $phase->duration_minutes,
                    $phase->phase_type === 'active' ? '👤' : '⏱️'
                ));
            }

            $this->newLine();
            $this->info('✅ BOOKING COMPLETE!');
            $this->info(str_repeat('=', 80));
            $this->info("Appointment ID: {$appointment->id}");
            $this->info("Status: {$appointment->status}");
            $this->info("Cal.com Sync Status: " . ($appointment->calcom_sync_status ?? 'pending'));
        });

        return 0;
    }
}
