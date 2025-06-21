<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Models\Customer;
use App\Models\Company;
use App\Models\User;

class TestPortalFeatures extends Command
{
    protected $signature = 'portal:test';
    protected $description = 'Test all portal and admin features';

    public function handle()
    {
        $this->info('🔍 COMPREHENSIVE PORTAL & ADMIN FEATURE TEST');
        $this->info('==========================================');
        $this->newLine();

        // Test endpoints
        $testEndpoints = [
            // Help Center
            ['name' => 'Help Center Home', 'url' => '/help', 'type' => 'public'],
            ['name' => 'Help Center Category', 'url' => '/help/category/getting-started', 'type' => 'public'],
            ['name' => 'Help Center Article', 'url' => '/help/article/how-to-book-appointment', 'type' => 'public'],
            ['name' => 'Help Center Search', 'url' => '/help/search?q=appointment', 'type' => 'public'],
            
            // Portal Pages (requires auth)
            ['name' => 'Portal Dashboard', 'url' => '/portal', 'type' => 'auth'],
            ['name' => 'Portal Appointments', 'url' => '/portal/appointments', 'type' => 'auth'],
            ['name' => 'Portal Knowledge', 'url' => '/portal/knowledge', 'type' => 'auth'],
            ['name' => 'Portal Invoices', 'url' => '/portal/invoices', 'type' => 'auth'],
            ['name' => 'Portal Profile', 'url' => '/portal/profile', 'type' => 'auth'],
            
            // Admin Pages (requires admin auth)
            ['name' => 'Admin Dashboard', 'url' => '/admin', 'type' => 'admin'],
            ['name' => 'Admin Knowledge Base', 'url' => '/admin/knowledge-base', 'type' => 'admin'],
        ];

        $results = [];
        $passed = 0;
        $failed = 0;

        // Test using HTTP client
        $baseUrl = config('app.url', 'http://localhost');
        
        foreach ($testEndpoints as $endpoint) {
            $this->info("Testing: {$endpoint['name']} ({$endpoint['url']})... ");
            
            try {
                $response = Http::timeout(10)->get($baseUrl . $endpoint['url']);
                $statusCode = $response->status();
                
                if ($statusCode === 200) {
                    $this->info("✅ OK (200)");
                    $passed++;
                } elseif ($statusCode === 302 && in_array($endpoint['type'], ['auth', 'admin'])) {
                    $this->warn("⚠️  Redirect (302) - Expected for auth pages");
                    $passed++;
                } else {
                    $this->error("❌ FAIL ({$statusCode})");
                    $failed++;
                }
                
                $results[] = [
                    'endpoint' => $endpoint['name'],
                    'url' => $endpoint['url'],
                    'status' => $statusCode === 200 ? 'PASS' : ($statusCode === 302 ? 'REDIRECT' : 'FAIL'),
                    'code' => $statusCode
                ];
                
            } catch (\Exception $e) {
                $this->error("❌ ERROR: " . $e->getMessage());
                $failed++;
                $results[] = [
                    'endpoint' => $endpoint['name'],
                    'url' => $endpoint['url'],
                    'status' => 'ERROR',
                    'error' => $e->getMessage()
                ];
            }
        }

        $this->newLine();
        $this->info('📊 TEST SUMMARY');
        $this->info('===============');
        $this->info("Total Tests: " . count($testEndpoints));
        $this->info("Passed: $passed ✅");
        $this->error("Failed: $failed ❌");
        $this->info("Success Rate: " . round(($passed / count($testEndpoints)) * 100, 2) . "%");
        
        // Additional checks
        $this->newLine();
        $this->info('🔧 ADDITIONAL CHECKS');
        $this->info('===================');
        
        // Check routes
        $this->newLine();
        $this->info('📍 Route Registration:');
        $routes = Route::getRoutes();
        $portalRoutes = 0;
        $helpRoutes = 0;
        $adminRoutes = 0;

        foreach ($routes as $route) {
            $uri = $route->uri();
            if (strpos($uri, 'portal/') === 0) $portalRoutes++;
            if (strpos($uri, 'help/') === 0 || $uri === 'help') $helpRoutes++;
            if (strpos($uri, 'admin/') === 0) $adminRoutes++;
        }

        $this->info("- Portal routes: $portalRoutes");
        $this->info("- Help routes: $helpRoutes");
        $this->info("- Admin routes: $adminRoutes");

        // Check controllers
        $this->newLine();
        $this->info('📦 Controller Existence:');
        $controllers = [
            'HelpCenterController' => \App\Http\Controllers\HelpCenterController::class,
            'CustomerDashboardController' => \App\Http\Controllers\Portal\CustomerDashboardController::class,
            'CustomerAppointmentController' => \App\Http\Controllers\Portal\CustomerAppointmentController::class,
            'CustomerKnowledgeController' => \App\Http\Controllers\Portal\CustomerKnowledgeController::class,
            'CustomerInvoiceController' => \App\Http\Controllers\Portal\CustomerInvoiceController::class,
            'CustomerProfileController' => \App\Http\Controllers\Portal\CustomerProfileController::class,
        ];

        foreach ($controllers as $name => $class) {
            if (class_exists($class)) {
                $this->info("✅ $name exists");
            } else {
                $this->error("❌ $name NOT FOUND");
            }
        }

        // Check views
        $this->newLine();
        $this->info('📄 View Files:');
        $views = [
            'help-center.index' => resource_path('views/help-center/index.blade.php'),
            'help-center.category' => resource_path('views/help-center/category.blade.php'),
            'help-center.article' => resource_path('views/help-center/article.blade.php'),
            'portal.dashboard' => resource_path('views/portal/dashboard.blade.php'),
            'portal.appointments.index' => resource_path('views/portal/appointments/index.blade.php'),
            'portal.knowledge.index' => resource_path('views/portal/knowledge/index.blade.php'),
            'portal.invoices.index' => resource_path('views/portal/invoices/index.blade.php'),
            'portal.profile.index' => resource_path('views/portal/profile/index.blade.php'),
        ];

        foreach ($views as $name => $path) {
            if (file_exists($path)) {
                $this->info("✅ $name exists");
            } else {
                $this->error("❌ $name NOT FOUND at $path");
            }
        }

        // Check database tables
        $this->newLine();
        $this->info('💾 Database Tables:');
        try {
            $tables = \DB::select('SHOW TABLES');
            $tableNames = array_map(function($table) {
                return array_values((array)$table)[0];
            }, $tables);
            
            $requiredTables = ['knowledge_base_categories', 'knowledge_base_articles', 'customers'];
            foreach ($requiredTables as $table) {
                if (in_array($table, $tableNames)) {
                    $this->info("✅ Table '$table' exists");
                } else {
                    $this->error("❌ Table '$table' NOT FOUND");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Could not check tables: " . $e->getMessage());
        }

        // Test data counts
        $this->newLine();
        $this->info('📈 Data Counts:');
        try {
            $this->info("- Customers: " . Customer::count());
            $this->info("- Companies: " . Company::count());
            $this->info("- Admin Users: " . User::count());
            
            if (class_exists('App\Models\KnowledgeBaseCategory')) {
                $this->info("- Knowledge Categories: " . \App\Models\KnowledgeBaseCategory::count());
            }
            if (class_exists('App\Models\KnowledgeBaseArticle')) {
                $this->info("- Knowledge Articles: " . \App\Models\KnowledgeBaseArticle::count());
            }
        } catch (\Exception $e) {
            $this->error("Error counting data: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('✨ Test complete!');
        
        // Save report
        $report = [
            'timestamp' => now()->toIso8601String(),
            'summary' => [
                'total' => count($testEndpoints),
                'passed' => $passed,
                'failed' => $failed,
                'success_rate' => round(($passed / count($testEndpoints)) * 100, 2) . '%'
            ],
            'results' => $results
        ];

        $filename = 'portal-test-report-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents(storage_path($filename), json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📄 Detailed report saved to storage/$filename");
        
        return $failed > 0 ? 1 : 0;
    }
}