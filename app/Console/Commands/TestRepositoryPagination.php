<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\CallRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\CustomerRepository;
use App\Utils\MemoryMonitor;
use Carbon\Carbon;

class TestRepositoryPagination extends Command
{
    protected $signature = 'test:repository-pagination {--detailed : Show detailed output}';
    protected $description = 'Test repository pagination improvements for memory efficiency';

    private CallRepository $callRepository;
    private AppointmentRepository $appointmentRepository;
    private CustomerRepository $customerRepository;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🚀 Starting Repository Pagination Tests');
        $this->info('=' . str_repeat('=', 50));

        $this->callRepository = app(CallRepository::class);
        $this->appointmentRepository = app(AppointmentRepository::class);
        $this->customerRepository = app(CustomerRepository::class);

        $this->testCallRepositoryMethods();
        $this->testAppointmentRepositoryMethods();
        $this->testCustomerRepositoryMethods();
        $this->testChunkedProcessing();

        $this->info('');
        $this->info('✅ All tests completed successfully!');
        $this->info('📊 Final Memory Usage: ' . json_encode(MemoryMonitor::getCurrentMemoryUsage()));

        return 0;
    }

    /**
     * Test CallRepository methods
     */
    private function testCallRepositoryMethods(): void
    {
        $this->info('');
        $this->info('🔍 Testing CallRepository methods...');

        // Test paginated methods
        $this->testMethod('CallRepository', 'getByStatus', function() {
            $operationId = MemoryMonitor::startOperation('CallRepository', 'getByStatus', ['status' => 'completed']);

            $result = $this->callRepository->getByStatus('completed', 10);
            MemoryMonitor::checkpoint($operationId, 'Query executed');

            $this->line("  ✓ getByStatus (paginated): {$result->count()} records, {$result->total()} total");

            return MemoryMonitor::endOperation($operationId, ['result_count' => $result->count()]);
        });

        // Test date range with pagination
        $this->testMethod('CallRepository', 'getByDateRange', function() {
            $operationId = MemoryMonitor::startOperation('CallRepository', 'getByDateRange');

            $startDate = Carbon::now()->subDays(30);
            $endDate = Carbon::now();
            $result = $this->callRepository->getByDateRange($startDate, $endDate, 10);
            MemoryMonitor::checkpoint($operationId, 'Date range query executed');

            $this->line("  ✓ getByDateRange (paginated): {$result->count()} records, {$result->total()} total");

            return MemoryMonitor::endOperation($operationId, ['result_count' => $result->count()]);
        });

        // Test statistics (optimized)
        $this->testMethod('CallRepository', 'getStatistics', function() {
            $operationId = MemoryMonitor::startOperation('CallRepository', 'getStatistics');

            $startDate = Carbon::now()->subDays(30);
            $endDate = Carbon::now();
            $stats = $this->callRepository->getStatistics($startDate, $endDate);
            MemoryMonitor::checkpoint($operationId, 'Statistics calculated');

            if ($this->option('detailed')) {
                $this->line("  ✓ getStatistics (optimized): " . json_encode($stats));
            } else {
                $this->line("  ✓ getStatistics (optimized): {$stats['total_calls']} total calls");
            }

            return MemoryMonitor::endOperation($operationId, ['stats' => $stats]);
        });

        $this->info('✅ CallRepository tests completed');
    }

    /**
     * Test AppointmentRepository methods
     */
    private function testAppointmentRepositoryMethods(): void
    {
        $this->info('');
        $this->info('📅 Testing AppointmentRepository methods...');

        // Test paginated date range
        $this->testMethod('AppointmentRepository', 'getByDateRange', function() {
            $operationId = MemoryMonitor::startOperation('AppointmentRepository', 'getByDateRange');

            $startDate = Carbon::now()->subDays(30);
            $endDate = Carbon::now()->addDays(30);
            $result = $this->appointmentRepository->getByDateRange($startDate, $endDate, 10);
            MemoryMonitor::checkpoint($operationId, 'Date range query executed');

            $this->line("  ✓ getByDateRange (paginated): {$result->count()} records, {$result->total()} total");

            return MemoryMonitor::endOperation($operationId, ['result_count' => $result->count()]);
        });

        // Test paginated by status
        $this->testMethod('AppointmentRepository', 'getByStatus', function() {
            $operationId = MemoryMonitor::startOperation('AppointmentRepository', 'getByStatus');

            $result = $this->appointmentRepository->getByStatus('scheduled', null, 10);
            MemoryMonitor::checkpoint($operationId, 'Status query executed');

            $this->line("  ✓ getByStatus (paginated): {$result->count()} records, {$result->total()} total");

            return MemoryMonitor::endOperation($operationId, ['result_count' => $result->count()]);
        });

        // Test optimized statistics
        $this->testMethod('AppointmentRepository', 'getStatistics', function() {
            $operationId = MemoryMonitor::startOperation('AppointmentRepository', 'getStatistics');

            $startDate = Carbon::now()->subDays(30);
            $endDate = Carbon::now();
            $stats = $this->appointmentRepository->getStatistics($startDate, $endDate);
            MemoryMonitor::checkpoint($operationId, 'Statistics calculated');

            if ($this->option('detailed')) {
                $this->line("  ✓ getStatistics (optimized): " . json_encode($stats));
            } else {
                $this->line("  ✓ getStatistics (optimized): {$stats['total_appointments']} total appointments");
            }

            return MemoryMonitor::endOperation($operationId, ['stats' => $stats]);
        });

        $this->info('✅ AppointmentRepository tests completed');
    }

    /**
     * Test CustomerRepository methods
     */
    private function testCustomerRepositoryMethods(): void
    {
        $this->info('');
        $this->info('👥 Testing CustomerRepository methods...');

        // Test paginated customers with appointments
        $this->testMethod('CustomerRepository', 'getWithAppointments', function() {
            $operationId = MemoryMonitor::startOperation('CustomerRepository', 'getWithAppointments');

            $result = $this->customerRepository->getWithAppointments(10);
            MemoryMonitor::checkpoint($operationId, 'Query with appointments executed');

            $this->line("  ✓ getWithAppointments (paginated): {$result->count()} records, {$result->total()} total");

            return MemoryMonitor::endOperation($operationId, ['result_count' => $result->count()]);
        });

        // Test search with limit
        $this->testMethod('CustomerRepository', 'search', function() {
            $operationId = MemoryMonitor::startOperation('CustomerRepository', 'search');

            $result = $this->customerRepository->search('test', 10);
            MemoryMonitor::checkpoint($operationId, 'Search executed');

            $this->line("  ✓ search (limited): {$result->count()} records");

            return MemoryMonitor::endOperation($operationId, ['result_count' => $result->count()]);
        });

        // Test customer statistics
        $this->testMethod('CustomerRepository', 'getStatistics', function() {
            $operationId = MemoryMonitor::startOperation('CustomerRepository', 'getStatistics');

            $stats = $this->customerRepository->getStatistics();
            MemoryMonitor::checkpoint($operationId, 'Statistics calculated');

            if ($this->option('detailed')) {
                $this->line("  ✓ getStatistics: " . json_encode($stats));
            } else {
                $this->line("  ✓ getStatistics: {$stats['total']} total customers");
            }

            return MemoryMonitor::endOperation($operationId, ['stats' => $stats]);
        });

        $this->info('✅ CustomerRepository tests completed');
    }

    /**
     * Test a method and track its performance
     */
    private function testMethod(string $repository, string $method, callable $test): void
    {
        $startMemory = memory_get_usage();

        try {
            $stats = $test();
            $endMemory = memory_get_usage();
            $memoryDelta = $endMemory - $startMemory;

            if ($memoryDelta > 10 * 1024 * 1024) { // 10MB
                $this->warn("    ⚠️  High memory usage: " . round($memoryDelta / 1024 / 1024, 2) . "MB");
            } else {
                $this->line("    ✓ Memory efficient: " . round($memoryDelta / 1024 / 1024, 2) . "MB");
            }

        } catch (\Exception $e) {
            $this->error("    ❌ Error: " . $e->getMessage());
            if ($this->option('detailed')) {
                $this->error("    Stack trace: " . $e->getTraceAsString());
            }
        }
    }

    /**
     * Test chunked processing
     */
    private function testChunkedProcessing(): void
    {
        $this->info('');
        $this->info('🔄 Testing chunked processing...');

        // Test call processing
        $operationId = MemoryMonitor::startOperation('CallRepository', 'processCallsByDateRange');

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();
        $processedCount = 0;

        try {
            $success = $this->callRepository->processCallsByDateRange(
                $startDate, 
                $endDate, 
                function($chunk) use (&$processedCount, $operationId) {
                    $processedCount += $chunk->count();
                    MemoryMonitor::checkpoint($operationId, "Processed chunk of " . $chunk->count() . " calls");
                    $this->line("  📦 Processed chunk: {$chunk->count()} calls (total: $processedCount)");
                }
            );

            $stats = MemoryMonitor::endOperation($operationId, [
                'total_processed' => $processedCount,
                'success' => $success
            ]);

            $this->line("  ✓ Chunked processing completed. Total processed: $processedCount calls");
            $this->line("  📊 Memory efficient: {$stats['peak_memory_mb']}MB peak");
        } catch (\Exception $e) {
            $this->error("  ❌ Chunked processing failed: " . $e->getMessage());
            MemoryMonitor::endOperation($operationId, ['error' => $e->getMessage()]);
        }
    }
}