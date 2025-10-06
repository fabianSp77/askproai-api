<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\RetellAgent;

class DataCleanup extends Command
{
    protected $signature = 'data:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Clean up test data and keep only production data from Retell and Cal.com';

    private array $stats = [
        'customers' => 0,
        'calls' => 0,
        'appointments' => 0,
        'services' => 0,
        'agents' => 0,
    ];

    private array $testCustomerIds = [];
    private array $orphanedCallIds = [];
    private array $testServiceIds = [];
    private array $testAppointmentIds = [];
    private array $testAgentIds = [];

    public function handle()
    {
        $this->info('🧹 Test Data Cleanup Process');
        $this->info(str_repeat('=', 50));

        // Identify all test data
        $this->identifyTestData();

        // Show summary
        $this->showSummary();

        // Get confirmation
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('Do you want to proceed with cleanup? This cannot be undone!')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        // Execute cleanup
        if (!$this->option('dry-run')) {
            $this->executeCleanup();
            $this->info('');
            $this->info('✅ Cleanup completed successfully!');
            $this->showFinalStats();
        } else {
            $this->warn('DRY RUN - No data was actually deleted');
        }

        return 0;
    }

    private function identifyTestData(): void
    {
        $this->info('Analyzing data patterns...');

        // Test customers (based on our previous analysis)
        $testPatterns = [
            'name LIKE "%Test%"',
            'name LIKE "%Demo%"',
            'name LIKE "%Anrufer%"',
            'name LIKE "%Neue%Anrufer%"',
            'name LIKE "%Anonym%"',
            'name LIKE "%+49%"',
            'name REGEXP "^[0-9]+$"',
            'email IS NULL OR email = ""',
            'phone IS NULL OR phone = ""',
            'created_at < "2024-01-01"' // Old test data
        ];

        $query = Customer::query();
        $first = true;
        foreach ($testPatterns as $pattern) {
            if ($first) {
                $query->whereRaw("($pattern)");
                $first = false;
            } else {
                $query->orWhereRaw("($pattern)");
            }
        }

        $this->testCustomerIds = $query->pluck('id')->toArray();
        $this->info("  → Found " . count($this->testCustomerIds) . " test customers");

        // Orphaned calls (no customer)
        $this->orphanedCallIds = Call::whereNull('customer_id')
            ->orWhere('customer_id', 0)
            ->orWhereNotIn('customer_id', Customer::pluck('id'))
            ->pluck('id')
            ->toArray();
        $this->info("  → Found " . count($this->orphanedCallIds) . " orphaned calls");

        // Additional test calls (0 duration, test transcripts)
        $testCalls = Call::where('duration_sec', 0)
            ->orWhere('duration_ms', 0)
            ->orWhere('transcript', 'LIKE', '%test%')
            ->orWhere('transcript', 'LIKE', '%Test%')
            ->orWhere('call_status', 'test')
            ->pluck('id')
            ->toArray();

        $this->orphanedCallIds = array_unique(array_merge($this->orphanedCallIds, $testCalls));
        $this->info("  → Total test/orphaned calls: " . count($this->orphanedCallIds));

        // Test services
        $this->testServiceIds = Service::where('name', 'LIKE', '%Test%')
            ->orWhere('name', 'LIKE', '%Demo%')
            ->orWhere('description', 'LIKE', '%test%')
            ->pluck('id')
            ->toArray();
        $this->info("  → Found " . count($this->testServiceIds) . " test services");

        // Test appointments
        $this->testAppointmentIds = Appointment::where('notes', 'LIKE', '%test%')
            ->orWhere('notes', 'LIKE', '%Test%')
            ->orWhere('notes', 'LIKE', '%Demo%')
            ->orWhereIn('customer_id', $this->testCustomerIds)
            ->pluck('id')
            ->toArray();
        $this->info("  → Found " . count($this->testAppointmentIds) . " test appointments");

        // Test agents
        $this->testAgentIds = RetellAgent::where('name', 'LIKE', '%Test%')
            ->orWhere('name', 'LIKE', '%Demo%')
            ->pluck('id')
            ->toArray();
        $this->info("  → Found " . count($this->testAgentIds) . " test agents");
    }

    private function showSummary(): void
    {
        $this->info('');
        $this->info('📊 Cleanup Summary:');
        $this->info(str_repeat('-', 50));

        $this->table(
            ['Type', 'Count', 'Action'],
            [
                ['Test Customers', count($this->testCustomerIds), 'Delete with cascading'],
                ['Orphaned Calls', count($this->orphanedCallIds), 'Delete'],
                ['Test Services', count($this->testServiceIds), 'Delete'],
                ['Test Appointments', count($this->testAppointmentIds), 'Delete'],
                ['Test Agents', count($this->testAgentIds), 'Delete'],
            ]
        );

        // Show what will remain
        $remainingCustomers = Customer::whereNotIn('id', $this->testCustomerIds)->count();
        $remainingCalls = Call::whereNotIn('id', $this->orphanedCallIds)
            ->whereNotIn('customer_id', $this->testCustomerIds)
            ->count();
        $remainingAppointments = Appointment::whereNotIn('id', $this->testAppointmentIds)->count();
        $remainingServices = Service::whereNotIn('id', $this->testServiceIds)->count();
        $remainingAgents = RetellAgent::whereNotIn('id', $this->testAgentIds)->count();

        $this->info('');
        $this->info('✅ Production Data (will be kept):');
        $this->info(str_repeat('-', 50));

        $this->table(
            ['Type', 'Count'],
            [
                ['Production Customers', $remainingCustomers],
                ['Production Calls', $remainingCalls],
                ['Production Appointments', $remainingAppointments],
                ['Production Services', $remainingServices],
                ['Production Agents', $remainingAgents],
            ]
        );
    }

    private function executeCleanup(): void
    {
        $this->info('');
        $this->info('🔧 Executing cleanup...');

        DB::beginTransaction();

        try {
            // Delete test appointments first (foreign key constraints)
            if (!empty($this->testAppointmentIds)) {
                $count = Appointment::whereIn('id', $this->testAppointmentIds)->delete();
                $this->stats['appointments'] = $count;
                $this->info("  ✓ Deleted $count test appointments");
            }

            // Delete orphaned calls
            if (!empty($this->orphanedCallIds)) {
                $count = Call::whereIn('id', $this->orphanedCallIds)->delete();
                $this->stats['calls'] = $count;
                $this->info("  ✓ Deleted $count orphaned/test calls");
            }

            // Delete calls from test customers
            if (!empty($this->testCustomerIds)) {
                $count = Call::whereIn('customer_id', $this->testCustomerIds)->delete();
                $this->stats['calls'] += $count;
                $this->info("  ✓ Deleted $count calls from test customers");
            }

            // Delete test customers
            if (!empty($this->testCustomerIds)) {
                $count = Customer::whereIn('id', $this->testCustomerIds)->delete();
                $this->stats['customers'] = $count;
                $this->info("  ✓ Deleted $count test customers");
            }

            // Delete test services
            if (!empty($this->testServiceIds)) {
                $count = Service::whereIn('id', $this->testServiceIds)->delete();
                $this->stats['services'] = $count;
                $this->info("  ✓ Deleted $count test services");
            }

            // Delete test agents
            if (!empty($this->testAgentIds)) {
                $count = RetellAgent::whereIn('id', $this->testAgentIds)->delete();
                $this->stats['agents'] = $count;
                $this->info("  ✓ Deleted $count test agents");
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function showFinalStats(): void
    {
        $this->info('');
        $this->info('📈 Final Database State:');
        $this->info(str_repeat('=', 50));

        $finalStats = [
            ['Customers', Customer::count(), $this->stats['customers'] . ' deleted'],
            ['Calls', Call::count(), $this->stats['calls'] . ' deleted'],
            ['Appointments', Appointment::count(), $this->stats['appointments'] . ' deleted'],
            ['Services', Service::count(), $this->stats['services'] . ' deleted'],
            ['Agents', RetellAgent::count(), $this->stats['agents'] . ' deleted'],
        ];

        $this->table(['Type', 'Remaining', 'Cleaned'], $finalStats);

        // Show data quality metrics
        $customersWithPhone = Customer::whereNotNull('phone')->where('phone', '!=', '')->count();
        $customersWithEmail = Customer::whereNotNull('email')->where('email', '!=', '')->count();
        $callsWithTranscript = Call::whereNotNull('transcript')->where('transcript', '!=', '')->count();
        $callsWithCustomer = Call::whereNotNull('customer_id')->count();

        $this->info('');
        $this->info('📊 Data Quality Metrics:');
        $this->info(str_repeat('-', 50));

        $qualityMetrics = [
            ['Customers with phone', $customersWithPhone . '/' . Customer::count(),
             $customersWithPhone > 0 ? round($customersWithPhone / Customer::count() * 100, 1) . '%' : '0%'],
            ['Customers with email', $customersWithEmail . '/' . Customer::count(),
             $customersWithEmail > 0 ? round($customersWithEmail / Customer::count() * 100, 1) . '%' : '0%'],
            ['Calls with transcript', $callsWithTranscript . '/' . Call::count(),
             $callsWithTranscript > 0 ? round($callsWithTranscript / Call::count() * 100, 1) . '%' : '0%'],
            ['Calls linked to customer', $callsWithCustomer . '/' . Call::count(),
             $callsWithCustomer > 0 ? round($callsWithCustomer / Call::count() * 100, 1) . '%' : '0%'],
        ];

        $this->table(['Metric', 'Count', 'Percentage'], $qualityMetrics);
    }
}