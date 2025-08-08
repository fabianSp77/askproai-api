<?php

/**
 * Hair Salon MCP Setup Script
 * 
 * Quick setup script to initialize the Hair Salon MCP system:
 * - Run database migrations
 * - Seed demo data
 * - Verify installation
 * - Display connection information
 * 
 * Run with: php setup-hair-salon-mcp.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;

class HairSalonMCPSetup
{
    public function run(): void
    {
        $this->outputHeader("💇‍♀️ Hair Salon MCP Setup");
        
        try {
            // Step 1: Run migrations
            $this->outputStep("Running database migrations...");
            $this->runMigrations();
            
            // Step 2: Seed data
            $this->outputStep("Seeding Hair Salon demo data...");
            $this->seedData();
            
            // Step 3: Verify installation
            $this->outputStep("Verifying installation...");
            $company = $this->verifyInstallation();
            
            // Step 4: Display connection info
            $this->outputStep("Installation complete!");
            $this->displayConnectionInfo($company);
            
            $this->outputSuccess("✅ Hair Salon MCP system is ready to use!");
            
        } catch (\Exception $e) {
            $this->outputError("❌ Setup failed: " . $e->getMessage());
            $this->outputError("Stack trace: " . $e->getTraceAsString());
            exit(1);
        }
    }
    
    protected function runMigrations(): void
    {
        try {
            // Run the new service fields migration
            Artisan::call('migrate', ['--force' => true]);
            $this->outputSuccess("✅ Database migrations completed");
        } catch (\Exception $e) {
            throw new \Exception("Migration failed: " . $e->getMessage());
        }
    }
    
    protected function seedData(): void
    {
        try {
            // Run the Hair Salon seeder
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\HairSalonSeeder',
                '--force' => true
            ]);
            $this->outputSuccess("✅ Demo data seeded successfully");
        } catch (\Exception $e) {
            throw new \Exception("Seeding failed: " . $e->getMessage());
        }
    }
    
    protected function verifyInstallation(): Company
    {
        // Verify company was created
        $company = Company::where('name', 'Hair & Style Salon')->first();
        if (!$company) {
            throw new \Exception("Hair salon company not found after seeding");
        }
        
        // Count related records
        $staffCount = Staff::where('company_id', $company->id)->count();
        $serviceCount = Service::where('company_id', $company->id)->count();
        $customerCount = Customer::where('company_id', $company->id)->count();
        
        $this->outputInfo("📊 Installation Summary:");
        $this->outputInfo("   Company: {$company->name} (ID: {$company->id})");
        $this->outputInfo("   Staff Members: {$staffCount}");
        $this->outputInfo("   Services: {$serviceCount}");
        $this->outputInfo("   Demo Customers: {$customerCount}");
        
        // Verify specific services exist
        $consultationServices = Service::where('company_id', $company->id)
            ->whereJsonContains('metadata->consultation_required', true)
            ->count();
            
        $multiBlockServices = Service::where('company_id', $company->id)
            ->whereJsonContains('metadata->multi_block', true)
            ->count();
            
        $this->outputInfo("   Consultation Services: {$consultationServices}");
        $this->outputInfo("   Multi-block Services: {$multiBlockServices}");
        
        return $company;
    }
    
    protected function displayConnectionInfo(Company $company): void
    {
        $baseUrl = config('app.url', 'http://localhost');
        
        $this->outputSection("🌐 API Endpoints");
        
        $endpoints = [
            "Health Check" => "GET {$baseUrl}/api/hair-salon-mcp/health",
            "Initialize MCP" => "POST {$baseUrl}/api/hair-salon-mcp/initialize",
            "Get Services" => "POST {$baseUrl}/api/hair-salon-mcp/services",
            "Get Staff" => "POST {$baseUrl}/api/hair-salon-mcp/staff", 
            "Check Availability" => "POST {$baseUrl}/api/hair-salon-mcp/availability",
            "Book Appointment" => "POST {$baseUrl}/api/hair-salon-mcp/book",
            "Schedule Callback" => "POST {$baseUrl}/api/hair-salon-mcp/callback",
            "Customer Lookup" => "POST {$baseUrl}/api/hair-salon-mcp/customer"
        ];
        
        foreach ($endpoints as $name => $url) {
            $this->outputInfo("   {$name}: {$url}");
        }
        
        $this->outputSection("🔐 Authentication");
        $this->outputInfo("   Company ID: {$company->id}");
        $this->outputInfo("   Include 'company_id' in all API requests");
        
        $this->outputSection("🎨 Staff Google Calendars");
        $staff = Staff::where('company_id', $company->id)->get();
        foreach ($staff as $member) {
            if ($member->google_calendar_id) {
                $this->outputInfo("   {$member->name}: {$member->google_calendar_id}");
            }
        }
        
        $this->outputSection("💰 Billing Configuration");
        $this->outputInfo("   Cost per minute: €" . config('hairsalon.billing.cost_per_minute', 0.30));
        $this->outputInfo("   Setup fee: €" . config('hairsalon.billing.setup_fee', 199.00));
        $this->outputInfo("   Monthly fee: €" . config('hairsalon.billing.monthly_fee', 49.00));
        
        $this->outputSection("📞 Retell.ai Integration");
        $this->outputInfo("   MCP endpoints are ready for Retell.ai integration");
        $this->outputInfo("   Configure your Retell.ai agent to use these endpoints");
        $this->outputInfo("   Enable webhook for call completion tracking");
        
        $this->outputSection("🧪 Testing");
        $this->outputInfo("   Run comprehensive tests: php test-hair-salon-mcp-comprehensive.php");
        $this->outputInfo("   Test specific endpoint: curl {$baseUrl}/api/hair-salon-mcp/health");
        
        $this->outputSection("📚 Example API Calls");
        $this->displayExampleCalls($company->id, $baseUrl);
    }
    
    protected function displayExampleCalls(int $companyId, string $baseUrl): void
    {
        $examples = [
            "Initialize MCP" => [
                "method" => "POST",
                "url" => "{$baseUrl}/api/hair-salon-mcp/initialize",
                "body" => json_encode([
                    "company_id" => $companyId,
                    "retell_agent_id" => "hair_salon_agent_001"
                ], JSON_PRETTY_PRINT)
            ],
            "Get Services" => [
                "method" => "POST", 
                "url" => "{$baseUrl}/api/hair-salon-mcp/services",
                "body" => json_encode([
                    "company_id" => $companyId
                ], JSON_PRETTY_PRINT)
            ],
            "Check Availability" => [
                "method" => "POST",
                "url" => "{$baseUrl}/api/hair-salon-mcp/availability", 
                "body" => json_encode([
                    "company_id" => $companyId,
                    "service_id" => 1,
                    "date" => date('Y-m-d', strtotime('+1 day')),
                    "days" => 7
                ], JSON_PRETTY_PRINT)
            ]
        ];
        
        foreach ($examples as $name => $example) {
            $this->outputInfo("\n   📝 {$name}:");
            $this->outputInfo("   curl -X {$example['method']} \\\n     -H 'Content-Type: application/json' \\\n     -d '{$this->formatJsonForCurl($example['body'])}' \\\n     {$example['url']}");
        }
    }
    
    protected function formatJsonForCurl(string $json): string
    {
        return str_replace(["\n", "    "], ["", ""], $json);
    }
    
    protected function outputHeader(string $text): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🔥 " . $text . " 🔥\n";  
        echo str_repeat("=", 80) . "\n\n";
    }
    
    protected function outputSection(string $text): void
    {
        echo "\n" . str_repeat("-", 60) . "\n";
        echo $text . "\n";
        echo str_repeat("-", 60) . "\n";
    }
    
    protected function outputStep(string $text): void
    {
        echo "\n🔧 " . $text . "\n";
    }
    
    protected function outputSuccess(string $text): void
    {
        echo "\033[0;32m{$text}\033[0m\n";
    }
    
    protected function outputError(string $text): void
    {
        echo "\033[0;31m{$text}\033[0m\n";
    }
    
    protected function outputInfo(string $text): void
    {
        echo "\033[0;36m{$text}\033[0m\n";
    }
}

// Run the setup
$setup = new HairSalonMCPSetup();
$setup->run();