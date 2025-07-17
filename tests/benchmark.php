<?php

/**
 * Performance Benchmark Script
 * 
 * Usage: php tests/benchmark.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\CalcomService;
use App\Services\AppointmentService;
use App\Repositories\CustomerRepository;
use App\Repositories\AppointmentRepository;

class Benchmark
{
    private array $results = [];
    
    public function run(): void
    {
        echo "\n🚀 AskProAI Performance Benchmark\n";
        echo "================================\n\n";
        
        $this->setupTestData();
        
        // Database Benchmarks
        $this->benchmark('Model Creation', [$this, 'benchmarkModelCreation'], 100);
        $this->benchmark('Repository Queries', [$this, 'benchmarkRepositoryQueries'], 50);
        $this->benchmark('Complex Queries', [$this, 'benchmarkComplexQueries'], 20);
        
        // Service Benchmarks
        $this->benchmark('Service Operations', [$this, 'benchmarkServiceOperations'], 30);
        $this->benchmark('Cache Operations', [$this, 'benchmarkCacheOperations'], 100);
        
        // API Benchmarks
        $this->benchmark('JSON Serialization', [$this, 'benchmarkJsonSerialization'], 100);
        
        $this->printResults();
        $this->cleanup();
    }
    
    private function benchmark(string $name, callable $callback, int $iterations): void
    {
        echo "Running: $name ($iterations iterations)... ";
        
        $times = [];
        $memoryUsages = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            $callback();
            
            $times[] = (microtime(true) - $startTime) * 1000; // Convert to ms
            $memoryUsages[] = (memory_get_usage() - $startMemory) / 1024 / 1024; // Convert to MB
        }
        
        $this->results[$name] = [
            'iterations' => $iterations,
            'avg_time' => round(array_sum($times) / count($times), 2),
            'min_time' => round(min($times), 2),
            'max_time' => round(max($times), 2),
            'avg_memory' => round(array_sum($memoryUsages) / count($memoryUsages), 2),
            'total_time' => round(array_sum($times), 2),
        ];
        
        echo "✅\n";
    }
    
    private function setupTestData(): void
    {
        echo "Setting up test data... ";
        
        // Create test company
        $this->company = Company::factory()->create();
        app()->instance('current_company_id', $this->company->id);
        
        // Create test customers
        $this->customers = Customer::factory()->count(100)->create([
            'company_id' => $this->company->id
        ]);
        
        echo "✅\n\n";
    }
    
    private function benchmarkModelCreation(): void
    {
        Customer::factory()->create([
            'company_id' => $this->company->id
        ]);
    }
    
    private function benchmarkRepositoryQueries(): void
    {
        $repo = new CustomerRepository();
        $phone = $this->customers->random()->phone;
        $repo->findByPhone($phone);
    }
    
    private function benchmarkComplexQueries(): void
    {
        Customer::where('company_id', $this->company->id)
            ->whereHas('appointments', function ($query) {
                $query->where('status', 'scheduled')
                      ->where('starts_at', '>', now());
            })
            ->with(['appointments' => function ($query) {
                $query->orderBy('starts_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    private function benchmarkServiceOperations(): void
    {
        $service = new AppointmentService();
        // Simulate service operation without actual API calls
        $data = [
            'customer_name' => 'Test Customer',
            'service_id' => 1,
            'starts_at' => now()->addDays(1),
            'duration' => 60,
        ];
        // Service operation would go here
    }
    
    private function benchmarkCacheOperations(): void
    {
        $key = 'benchmark_' . uniqid();
        $data = ['test' => 'data', 'timestamp' => now()];
        
        Cache::put($key, $data, 60);
        Cache::get($key);
        Cache::forget($key);
    }
    
    private function benchmarkJsonSerialization(): void
    {
        $customers = Customer::with('appointments')
            ->limit(10)
            ->get();
            
        $json = $customers->toJson();
        $decoded = json_decode($json, true);
    }
    
    private function printResults(): void
    {
        echo "\n📊 Benchmark Results\n";
        echo "===================\n\n";
        
        $totalTime = 0;
        
        foreach ($this->results as $name => $result) {
            echo sprintf(
                "%-25s | Avg: %6.2fms | Min: %6.2fms | Max: %6.2fms | Memory: %5.2fMB\n",
                $name,
                $result['avg_time'],
                $result['min_time'],
                $result['max_time'],
                $result['avg_memory']
            );
            $totalTime += $result['total_time'];
        }
        
        echo "\n";
        echo sprintf("Total benchmark time: %.2fs\n", $totalTime / 1000);
        
        // Performance recommendations
        echo "\n💡 Performance Analysis\n";
        echo "======================\n";
        
        foreach ($this->results as $name => $result) {
            if ($result['avg_time'] > 50) {
                echo "⚠️  $name: Consider optimization (avg {$result['avg_time']}ms)\n";
            } elseif ($result['avg_time'] < 10) {
                echo "✅ $name: Excellent performance\n";
            }
        }
        
        // Save results to JSON
        $jsonFile = __DIR__ . '/benchmark-results.json';
        file_put_contents($jsonFile, json_encode([
            'timestamp' => now()->toIso8601String(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'results' => $this->results,
        ], JSON_PRETTY_PRINT));
        
        echo "\n📄 Results saved to: $jsonFile\n";
    }
    
    private function cleanup(): void
    {
        echo "\nCleaning up test data... ";
        
        // Clean up test data
        Customer::where('company_id', $this->company->id)->delete();
        $this->company->delete();
        
        echo "✅\n";
    }
    
    private Company $company;
    private $customers;
}

// Run benchmark
$benchmark = new Benchmark();
$benchmark->run();