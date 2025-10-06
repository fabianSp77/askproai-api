<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\RetellAgent;
use App\Models\PhoneNumber;
use App\Models\Company;
use App\Models\Branch;

class VerifyProductionData extends Command
{
    protected $signature = 'data:verify';
    protected $description = 'Verify the integrity and quality of production data after cleanup';

    public function handle()
    {
        $this->info('🔍 Production Data Verification');
        $this->info(str_repeat('=', 60));

        // 1. Customer Data Quality
        $this->verifyCustomers();

        // 2. Call Data Quality
        $this->verifyCalls();

        // 3. Appointment Data Quality
        $this->verifyAppointments();

        // 4. Service Configuration
        $this->verifyServices();

        // 5. Agent Configuration
        $this->verifyAgents();

        // 6. System Configuration
        $this->verifySystemConfig();

        $this->info('');
        $this->info('✅ Verification Complete!');

        return 0;
    }

    private function verifyCustomers(): void
    {
        $this->info('📋 Customer Data:');
        $customers = Customer::all();

        $this->info('  Total customers: ' . $customers->count());

        $issues = [];
        $valid = 0;

        foreach ($customers as $customer) {
            $hasIssue = false;

            // Check for test patterns
            if (str_contains(strtolower($customer->name), 'test') ||
                str_contains(strtolower($customer->name), 'demo') ||
                str_contains(strtolower($customer->name), 'anrufer')) {
                $issues[] = "  ⚠️  Customer '{$customer->name}' (ID: {$customer->id}) may be test data";
                $hasIssue = true;
            }

            // Check for valid contact info
            if (!$customer->phone || !$customer->email) {
                $missing = [];
                if (!$customer->phone) $missing[] = 'phone';
                if (!$customer->email) $missing[] = 'email';
                $issues[] = "  ⚠️  Customer '{$customer->name}' (ID: {$customer->id}) missing: " . implode(', ', $missing);
                $hasIssue = true;
            }

            if (!$hasIssue) {
                $valid++;
            }
        }

        $this->info("  ✓ Valid customers: $valid/" . $customers->count());

        if (!empty($issues)) {
            $this->warn('  Issues found:');
            foreach ($issues as $issue) {
                $this->warn($issue);
            }
        }

        // Show sample of clean data
        $this->info('');
        $this->info('  Sample Production Customers:');
        $sampleCustomers = Customer::limit(5)->get(['id', 'name', 'email', 'phone']);
        $this->table(
            ['ID', 'Name', 'Email', 'Phone'],
            $sampleCustomers->map(function ($c) {
                return [
                    $c->id,
                    substr($c->name, 0, 30),
                    substr($c->email, 0, 30),
                    substr($c->phone, 0, 20)
                ];
            })
        );
    }

    private function verifyCalls(): void
    {
        $this->info('');
        $this->info('📞 Call Data:');
        $calls = Call::all();

        $this->info('  Total calls: ' . $calls->count());

        $callsWithCustomer = Call::whereNotNull('customer_id')->count();
        $callsWithTranscript = Call::whereNotNull('transcript')
            ->where('transcript', '!=', '')
            ->count();
        $callsWithDuration = Call::where('duration_sec', '>', 0)->count();
        $callsFromRetell = Call::whereNotNull('retell_call_id')->count();

        $this->info("  ✓ Calls linked to customers: $callsWithCustomer/" . $calls->count() .
                    " (" . round($callsWithCustomer / max($calls->count(), 1) * 100, 1) . "%)");
        $this->info("  ✓ Calls with transcript: $callsWithTranscript/" . $calls->count() .
                    " (" . round($callsWithTranscript / max($calls->count(), 1) * 100, 1) . "%)");
        $this->info("  ✓ Calls with duration > 0: $callsWithDuration/" . $calls->count() .
                    " (" . round($callsWithDuration / max($calls->count(), 1) * 100, 1) . "%)");
        $this->info("  ✓ Calls from Retell: $callsFromRetell/" . $calls->count() .
                    " (" . round($callsFromRetell / max($calls->count(), 1) * 100, 1) . "%)");

        // Check for orphaned calls
        $orphaned = Call::whereNull('customer_id')->count();
        if ($orphaned > 0) {
            $this->warn("  ⚠️  Found $orphaned orphaned calls without customers");
        }

        // Show call distribution by status
        $this->info('');
        $this->info('  Call Status Distribution:');
        $statusCounts = Call::selectRaw('call_status, COUNT(*) as count')
            ->groupBy('call_status')
            ->get();

        foreach ($statusCounts as $status) {
            $this->info("    • " . ($status->call_status ?: 'null') . ": " . $status->count);
        }
    }

    private function verifyAppointments(): void
    {
        $this->info('');
        $this->info('📅 Appointment Data:');
        $appointments = Appointment::all();

        $this->info('  Total appointments: ' . $appointments->count());

        $appointmentsWithCustomer = Appointment::whereNotNull('customer_id')->count();
        $appointmentsWithStaff = Appointment::whereNotNull('staff_id')->count();
        $appointmentsWithService = Appointment::whereNotNull('service_id')->count();
        $futureAppointments = Appointment::where('starts_at', '>', now())->count();

        $this->info("  ✓ Linked to customers: $appointmentsWithCustomer/" . $appointments->count());
        $this->info("  ✓ Assigned to staff: $appointmentsWithStaff/" . $appointments->count());
        $this->info("  ✓ Has service: $appointmentsWithService/" . $appointments->count());
        $this->info("  ✓ Future appointments: $futureAppointments");

        // Show appointment status distribution
        $this->info('');
        $this->info('  Appointment Status:');
        $statusCounts = Appointment::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        foreach ($statusCounts as $status) {
            $this->info("    • " . ($status->status ?: 'null') . ": " . $status->count);
        }
    }

    private function verifyServices(): void
    {
        $this->info('');
        $this->info('🛠️ Service Configuration:');
        $services = Service::all();

        $this->info('  Total services: ' . $services->count());

        $activeServices = Service::where('is_active', true)->count();
        $this->info("  ✓ Active services: $activeServices");

        // List all services
        $this->info('  Services:');
        foreach ($services as $service) {
            $status = $service->is_active ? '✓' : '✗';
            $this->info("    [$status] {$service->name} (ID: {$service->id})");
        }
    }

    private function verifyAgents(): void
    {
        $this->info('');
        $this->info('🤖 Retell Agents:');
        $agents = RetellAgent::all();

        $this->info('  Total agents: ' . $agents->count());

        // List all agents
        $this->info('  Agents:');
        foreach ($agents as $agent) {
            $callCount = Call::where('agent_id', $agent->id)->count();
            $this->info("    • {$agent->name} (ID: {$agent->id}) - $callCount calls");
        }
    }

    private function verifySystemConfig(): void
    {
        $this->info('');
        $this->info('⚙️ System Configuration:');

        $companies = Company::count();
        $branches = Branch::count();
        $phoneNumbers = PhoneNumber::count();

        $this->info("  Companies: $companies");
        $this->info("  Branches: $branches");
        $this->info("  Phone Numbers: $phoneNumbers");

        // Check phone number assignments
        $phoneNumbersWithBranch = PhoneNumber::whereNotNull('branch_id')->count();
        $this->info("  ✓ Phone numbers assigned to branches: $phoneNumbersWithBranch/$phoneNumbers");

        // Show phone number details
        $this->info('');
        $this->info('  Phone Number Configuration:');
        $phoneNumbers = PhoneNumber::with('branch')->get();
        foreach ($phoneNumbers as $phone) {
            $branchName = $phone->branch ? $phone->branch->name : 'Not assigned';
            $this->info("    • {$phone->number} → $branchName");
        }
    }
}