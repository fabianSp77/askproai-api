<?php
/**
 * Load Testing Simulation Script
 * Simulates concurrent users and API requests
 */

class LoadTestSimulation
{
    private $results = [];
    private $baseUrl;
    
    public function __construct($baseUrl = 'http://localhost')
    {
        $this->baseUrl = $baseUrl;
    }
    
    public function runLoadTests()
    {
        echo "🚀 Starting Load Testing Simulation...\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // Test 1: Basic Response Times
        $this->testBasicResponseTimes();
        
        // Test 2: Concurrent Request Simulation  
        $this->testConcurrentRequests();
        
        // Test 3: Database Load Test
        $this->testDatabaseLoad();
        
        // Test 4: Memory Stress Test
        $this->testMemoryStress();
        
        // Test 5: API Endpoint Performance
        $this->testApiEndpoints();
        
        // Generate Report
        $this->generateLoadTestReport();
    }
    
    private function testBasicResponseTimes()
    {
        echo "📊 1. Basic Response Time Test\n";
        echo "-".str_repeat("-", 31)."\n";
        
        $endpoints = [
            '/admin/login' => 'Admin Login',
            '/api/health' => 'API Health Check',
            '/' => 'Home Page'
        ];
        
        foreach ($endpoints as $endpoint => $name) {
            $times = [];
            
            // Run 5 tests per endpoint
            for ($i = 0; $i < 5; $i++) {
                $start = microtime(true);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
                curl_close($ch);
                
                $duration = (microtime(true) - $start) * 1000;
                $times[] = $duration;
                
                if ($i === 0) {
                    $this->results['response_times'][$endpoint] = [
                        'http_code' => $httpCode,
                        'response_size' => strlen($response),
                        'curl_time' => $totalTime * 1000
                    ];
                }
            }
            
            $avgTime = array_sum($times) / count($times);
            $minTime = min($times);
            $maxTime = max($times);
            
            $this->results['response_times'][$endpoint]['avg_ms'] = round($avgTime, 2);
            $this->results['response_times'][$endpoint]['min_ms'] = round($minTime, 2);
            $this->results['response_times'][$endpoint]['max_ms'] = round($maxTime, 2);
            
            $status = $avgTime < 500 ? '🟢 FAST' : ($avgTime < 2000 ? '🟡 OK' : '🔴 SLOW');
            echo "  $status $name: " . round($avgTime, 1) . "ms (min: " . round($minTime, 1) . ", max: " . round($maxTime, 1) . ")\n";
        }
    }
    
    private function testConcurrentRequests()
    {
        echo "\n🔄 2. Concurrent Requests Simulation\n";
        echo "-".str_repeat("-", 37)."\n";
        
        $concurrencyLevels = [5, 10, 20];
        
        foreach ($concurrencyLevels as $concurrent) {
            echo "  Testing with $concurrent concurrent requests...\n";
            
            $start = microtime(true);
            $processes = [];
            
            // Simulate concurrent requests using multiple processes
            for ($i = 0; $i < $concurrent; $i++) {
                $cmd = "curl -s -o /dev/null -w '%{time_total},%{http_code}' " . 
                       $this->baseUrl . "/admin/login > /tmp/load_test_$i.txt &";
                exec($cmd);
                $processes[] = "/tmp/load_test_$i.txt";
            }
            
            // Wait for all processes to complete
            sleep(2);
            
            $totalTime = microtime(true) - $start;
            $responseTimes = [];
            $httpCodes = [];
            
            foreach ($processes as $file) {
                if (file_exists($file)) {
                    $result = file_get_contents($file);
                    $parts = explode(',', $result);
                    if (count($parts) === 2) {
                        $responseTimes[] = floatval($parts[0]) * 1000;
                        $httpCodes[] = intval($parts[1]);
                    }
                    unlink($file);
                }
            }
            
            $avgResponseTime = !empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0;
            $successfulRequests = array_filter($httpCodes, function($code) {
                return $code >= 200 && $code < 400;
            });
            
            $successRate = count($successfulRequests) / $concurrent * 100;
            
            $this->results['concurrent_tests'][$concurrent] = [
                'total_time_s' => round($totalTime, 2),
                'avg_response_ms' => round($avgResponseTime, 2),
                'success_rate' => round($successRate, 1),
                'requests_per_second' => round($concurrent / $totalTime, 2)
            ];
            
            $status = $successRate >= 95 ? '🟢' : ($successRate >= 80 ? '🟡' : '🔴');
            echo "    $status Success rate: {$successRate}%, Avg response: " . round($avgResponseTime, 1) . "ms\n";
        }
    }
    
    private function testDatabaseLoad()
    {
        echo "\n💾 3. Database Load Test\n";
        echo "-".str_repeat("-", 25)."\n";
        
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=askproai_db", "askproai_user", "lkZ57Dju9EDjrMxn");
            
            // Test multiple concurrent database queries
            $queries = [
                'simple_select' => 'SELECT COUNT(*) FROM calls',
                'complex_join' => 'SELECT c.id, c.phone_number, co.name 
                                 FROM calls c 
                                 LEFT JOIN companies co ON c.company_id = co.id 
                                 LIMIT 100',
                'aggregation' => 'SELECT DATE(created_at) as date, 
                                COUNT(*) as call_count, 
                                AVG(duration) as avg_duration
                                FROM calls 
                                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                GROUP BY DATE(created_at)'
            ];
            
            foreach ($queries as $name => $query) {
                $times = [];
                
                // Run each query 10 times
                for ($i = 0; $i < 10; $i++) {
                    $start = microtime(true);
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $duration = (microtime(true) - $start) * 1000;
                    $times[] = $duration;
                }
                
                $avgTime = array_sum($times) / count($times);
                $minTime = min($times);
                $maxTime = max($times);
                
                $this->results['database_load'][$name] = [
                    'avg_ms' => round($avgTime, 2),
                    'min_ms' => round($minTime, 2),
                    'max_ms' => round($maxTime, 2),
                    'result_count' => count($result)
                ];
                
                $status = $avgTime < 100 ? '🟢' : ($avgTime < 500 ? '🟡' : '🔴');
                echo "  $status $name: " . round($avgTime, 1) . "ms (results: " . count($result) . ")\n";
            }
            
        } catch (Exception $e) {
            echo "  ❌ Database load test failed: " . $e->getMessage() . "\n";
        }
    }
    
    private function testMemoryStress()
    {
        echo "\n🧠 4. Memory Stress Test\n";
        echo "-".str_repeat("-", 25)."\n";
        
        $initialMemory = memory_get_usage();
        $memoryTests = [];
        
        // Test 1: Large array processing
        $testData = [];
        for ($i = 0; $i < 50000; $i++) {
            $testData[] = [
                'id' => $i,
                'name' => 'Test Item ' . $i,
                'description' => str_repeat('Lorem ipsum dolor sit amet, ', 10),
                'timestamp' => time()
            ];
        }
        
        $memoryAfterData = memory_get_usage();
        $memoryTests['large_array'] = $memoryAfterData - $initialMemory;
        
        // Test 2: Chunked processing (memory efficient)
        $processedChunks = 0;
        $chunks = array_chunk($testData, 1000);
        foreach ($chunks as $chunk) {
            // Simulate processing
            array_map(function($item) {
                return strtoupper($item['name']);
            }, $chunk);
            $processedChunks++;
            unset($chunk);
        }
        
        $memoryAfterChunked = memory_get_usage();
        $memoryTests['after_chunked'] = $memoryAfterChunked - $initialMemory;
        
        // Test 3: Garbage collection
        unset($testData);
        gc_collect_cycles();
        
        $memoryAfterGC = memory_get_usage();
        $memoryTests['after_gc'] = $memoryAfterGC - $initialMemory;
        $memoryReclaimed = $memoryAfterChunked - $memoryAfterGC;
        
        $this->results['memory_stress'] = [
            'initial_mb' => round($initialMemory / 1024 / 1024, 2),
            'large_array_mb' => round($memoryTests['large_array'] / 1024 / 1024, 2),
            'after_chunked_mb' => round($memoryTests['after_chunked'] / 1024 / 1024, 2),
            'after_gc_mb' => round($memoryTests['after_gc'] / 1024 / 1024, 2),
            'memory_reclaimed_mb' => round($memoryReclaimed / 1024 / 1024, 2),
            'processed_chunks' => $processedChunks
        ];
        
        echo "  ✅ Large array created: " . round($memoryTests['large_array'] / 1024 / 1024, 1) . "MB\n";
        echo "  ✅ Processed $processedChunks chunks efficiently\n";
        echo "  ✅ Memory reclaimed by GC: " . round($memoryReclaimed / 1024 / 1024, 1) . "MB\n";
        
        $efficiency = $memoryReclaimed > 0 ? round(($memoryReclaimed / $memoryTests['after_chunked']) * 100, 1) : 0;
        $status = $efficiency > 50 ? '🟢 EFFICIENT' : '🔴 INEFFICIENT';
        echo "  $status GC efficiency: {$efficiency}%\n";
    }
    
    private function testApiEndpoints()
    {
        echo "\n🔌 5. API Endpoints Performance\n";
        echo "-".str_repeat("-", 32)."\n";
        
        $apiEndpoints = [
            '/api/health' => 'Health Check',
            '/api/status' => 'Status Check',
        ];
        
        foreach ($apiEndpoints as $endpoint => $name) {
            $start = microtime(true);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            curl_close($ch);
            
            $duration = (microtime(true) - $start) * 1000;
            
            $this->results['api_endpoints'][$endpoint] = [
                'http_code' => $httpCode,
                'response_time_ms' => round($duration, 2),
                'curl_time_ms' => round($totalTime * 1000, 2),
                'response_size' => strlen($response)
            ];
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $status = $duration < 200 ? '🟢 FAST' : ($duration < 1000 ? '🟡 OK' : '🔴 SLOW');
                echo "  $status $name: " . round($duration, 1) . "ms (HTTP $httpCode)\n";
            } else {
                echo "  ❌ $name: HTTP $httpCode (" . round($duration, 1) . "ms)\n";
            }
        }
    }
    
    private function generateLoadTestReport()
    {
        echo "\n📊 LOAD TEST REPORT\n";
        echo str_repeat("=", 60) . "\n";
        
        // Response Time Summary
        if (isset($this->results['response_times'])) {
            echo "\n🚀 Response Times:\n";
            foreach ($this->results['response_times'] as $endpoint => $data) {
                echo "  $endpoint: {$data['avg_ms']}ms avg\n";
            }
        }
        
        // Concurrent Test Summary
        if (isset($this->results['concurrent_tests'])) {
            echo "\n🔄 Concurrent Performance:\n";
            foreach ($this->results['concurrent_tests'] as $concurrent => $data) {
                echo "  $concurrent users: {$data['success_rate']}% success, {$data['requests_per_second']} req/s\n";
            }
        }
        
        // Database Performance
        if (isset($this->results['database_load'])) {
            echo "\n💾 Database Performance:\n";
            $totalQueries = count($this->results['database_load']);
            $avgDbTime = array_sum(array_column($this->results['database_load'], 'avg_ms')) / $totalQueries;
            echo "  Average query time: " . round($avgDbTime, 1) . "ms\n";
        }
        
        // Memory Efficiency
        if (isset($this->results['memory_stress'])) {
            $data = $this->results['memory_stress'];
            echo "\n🧠 Memory Efficiency:\n";
            echo "  Peak usage: {$data['large_array_mb']}MB\n";
            echo "  Memory reclaimed: {$data['memory_reclaimed_mb']}MB\n";
        }
        
        // Overall Assessment
        echo "\n🎯 LOAD TEST ASSESSMENT\n";
        echo "-".str_repeat("-", 24)."\n";
        
        $score = $this->calculateLoadTestScore();
        $rating = $score >= 85 ? 'EXCELLENT' : ($score >= 70 ? 'GOOD' : ($score >= 50 ? 'FAIR' : 'POOR'));
        $emoji = $score >= 85 ? '🟢' : ($score >= 70 ? '🟡' : '🔴');
        
        echo "Overall Performance Score: $score% $emoji $rating\n";
        
        // Recommendations
        echo "\n💡 Recommendations:\n";
        if (isset($this->results['response_times'])) {
            $slowEndpoints = array_filter($this->results['response_times'], function($data) {
                return $data['avg_ms'] > 1000;
            });
            
            if (!empty($slowEndpoints)) {
                echo "- Optimize " . count($slowEndpoints) . " slow endpoints\n";
            }
        }
        
        if (isset($this->results['concurrent_tests'])) {
            $lowSuccessRates = array_filter($this->results['concurrent_tests'], function($data) {
                return $data['success_rate'] < 95;
            });
            
            if (!empty($lowSuccessRates)) {
                echo "- Improve handling for concurrent requests\n";
            }
        }
        
        if (isset($this->results['database_load'])) {
            $slowQueries = array_filter($this->results['database_load'], function($data) {
                return $data['avg_ms'] > 500;
            });
            
            if (!empty($slowQueries)) {
                echo "- Optimize " . count($slowQueries) . " slow database queries\n";
            }
        }
        
        if ($score >= 85) {
            echo "✅ System is ready for production load!\n";
        } elseif ($score >= 70) {
            echo "🟡 System performs well but has room for optimization\n";
        } else {
            echo "🔴 System needs optimization before handling high load\n";
        }
        
        // Save detailed results
        $reportFile = 'load_test_results_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed results saved to: $reportFile\n";
    }
    
    private function calculateLoadTestScore()
    {
        $score = 0;
        $maxScore = 0;
        
        // Response time score (40 points)
        if (isset($this->results['response_times'])) {
            $maxScore += 40;
            $totalEndpoints = count($this->results['response_times']);
            $fastEndpoints = 0;
            
            foreach ($this->results['response_times'] as $data) {
                if ($data['avg_ms'] < 500) $fastEndpoints++;
            }
            
            $score += ($fastEndpoints / $totalEndpoints) * 40;
        }
        
        // Concurrent request score (30 points)
        if (isset($this->results['concurrent_tests'])) {
            $maxScore += 30;
            $avgSuccessRate = array_sum(array_column($this->results['concurrent_tests'], 'success_rate')) / 
                             count($this->results['concurrent_tests']);
            
            $score += ($avgSuccessRate / 100) * 30;
        }
        
        // Database performance score (20 points)
        if (isset($this->results['database_load'])) {
            $maxScore += 20;
            $avgDbTime = array_sum(array_column($this->results['database_load'], 'avg_ms')) / 
                        count($this->results['database_load']);
            
            if ($avgDbTime < 100) $score += 20;
            elseif ($avgDbTime < 300) $score += 15;
            elseif ($avgDbTime < 500) $score += 10;
            else $score += 5;
        }
        
        // Memory efficiency score (10 points)
        if (isset($this->results['memory_stress'])) {
            $maxScore += 10;
            $data = $this->results['memory_stress'];
            
            if ($data['memory_reclaimed_mb'] > 0) {
                $efficiency = ($data['memory_reclaimed_mb'] / $data['large_array_mb']) * 100;
                if ($efficiency > 50) $score += 10;
                elseif ($efficiency > 25) $score += 7;
                else $score += 3;
            }
        }
        
        return $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
    }
}

// Run the load test
$loadTest = new LoadTestSimulation();
$loadTest->runLoadTests();