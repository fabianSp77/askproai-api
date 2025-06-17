<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\Company;
use App\Models\CompanyPricing;
use App\Services\PricingService;
use App\Services\CurrencyConverter;

class TestPricingCalculation extends Command
{
    protected $signature = 'pricing:test {--call-id=} {--company-id=}';
    
    protected $description = 'Test pricing calculation and verify all components';

    public function handle()
    {
        $this->info('=== PRICING CALCULATION TEST ===');
        
        // Test CurrencyConverter
        $this->testCurrencyConverter();
        
        // Test Pricing for specific call
        if ($callId = $this->option('call-id')) {
            $this->testCallPricing($callId);
        }
        
        // Test Company Pricing
        if ($companyId = $this->option('company-id')) {
            $this->testCompanyPricing($companyId);
        }
        
        // General test if no options
        if (!$this->option('call-id') && !$this->option('company-id')) {
            $this->runGeneralTest();
        }
    }
    
    private function testCurrencyConverter()
    {
        $this->info("\n📊 Testing CurrencyConverter...");
        
        // Test cases
        $testCases = [
            ['cents' => 10.13, 'expected' => 0.0932],
            ['cents' => 100, 'expected' => 0.92],
            ['cents' => 1000, 'expected' => 9.20],
        ];
        
        foreach ($testCases as $test) {
            $result = CurrencyConverter::centsToEuros($test['cents']);
            $status = abs($result - $test['expected']) < 0.0001 ? '✅' : '❌';
            
            $this->line(sprintf(
                "%s %.2f cents = €%.4f (expected €%.4f)",
                $status,
                $test['cents'],
                $result,
                $test['expected']
            ));
        }
        
        // Test array format
        $costData = [
            'combined_cost' => 10.1333333,
            'total_cost' => 10.1333333
        ];
        
        $result = CurrencyConverter::convertRetellCostToEuros($costData);
        $this->line("\n📦 Array cost data: " . json_encode($costData));
        $this->line("💶 Converted to EUR: €" . number_format($result, 4));
    }
    
    private function testCallPricing($callId)
    {
        $this->info("\n📞 Testing pricing for Call ID: $callId");
        
        $call = Call::find($callId);
        if (!$call) {
            $this->error("Call not found!");
            return;
        }
        
        $this->table(
            ['Field', 'Value'],
            [
                ['Call ID', $call->id],
                ['Company ID', $call->company_id],
                ['Branch ID', $call->branch_id ?? 'N/A'],
                ['Duration', $call->duration_sec . ' sec (' . round($call->duration_sec / 60, 2) . ' min)'],
                ['Our Cost', '€' . number_format($call->cost, 4)],
                ['Cost Breakdown', $call->cost_breakdown ? 'Yes' : 'No'],
            ]
        );
        
        $pricingService = new PricingService();
        $pricing = $pricingService->calculateCallPrice($call);
        
        $this->info("\n💰 Pricing Calculation Result:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Customer Price', '€' . number_format($pricing['customer_price'], 4)],
                ['Price per Minute', '€' . number_format($pricing['price_per_minute'], 4)],
                ['Minutes Used', round($pricing['minutes_used'] ?? 0, 2)],
                ['Included Minutes', $pricing['included_minutes'] ?? 0],
                ['Current Month Usage', round($pricing['current_month_minutes'] ?? 0, 2)],
                ['Pricing Model ID', $pricing['pricing_model_id'] ?? 'None'],
                ['Error', $pricing['error'] ?? 'None'],
            ]
        );
        
        // Calculate margin
        if (!isset($pricing['error'])) {
            $margin = $pricing['customer_price'] - $call->cost;
            $marginPercent = $pricing['customer_price'] > 0 
                ? ($margin / $pricing['customer_price']) * 100 
                : 0;
                
            $this->info("\n📈 Margin Analysis:");
            $this->line("Our Cost: €" . number_format($call->cost, 4));
            $this->line("Customer Price: €" . number_format($pricing['customer_price'], 4));
            $this->line("Margin: €" . number_format($margin, 4) . " (" . round($marginPercent, 1) . "%)");
        }
    }
    
    private function testCompanyPricing($companyId)
    {
        $this->info("\n🏢 Testing pricing for Company ID: $companyId");
        
        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Company not found!");
            return;
        }
        
        $this->line("Company: " . $company->name);
        
        $pricing = CompanyPricing::getCurrentForCompany($companyId);
        if (!$pricing) {
            $this->warn("No active pricing model found for this company!");
            return;
        }
        
        $this->table(
            ['Field', 'Value'],
            [
                ['Pricing ID', $pricing->id],
                ['Price per Minute', '€' . number_format($pricing->price_per_minute, 4)],
                ['Included Minutes', $pricing->included_minutes],
                ['Overage Price', '€' . number_format($pricing->overage_price_per_minute ?? $pricing->price_per_minute, 4)],
                ['Monthly Base Fee', '€' . number_format($pricing->monthly_base_fee ?? 0, 2)],
                ['Setup Fee', '€' . number_format($pricing->setup_fee ?? 0, 2)],
                ['Valid From', $pricing->valid_from->format('d.m.Y')],
                ['Valid Until', $pricing->valid_until ? $pricing->valid_until->format('d.m.Y') : 'Unlimited'],
            ]
        );
        
        // Test calculations
        $this->info("\n🧮 Test Calculations:");
        $testMinutes = [30, 100, 200, 500];
        
        foreach ($testMinutes as $minutes) {
            $price = $pricing->calculatePrice($minutes * 60, 0);
            $this->line(sprintf(
                "%d minutes = €%.4f (€%.4f/min effective)",
                $minutes,
                $price,
                $minutes > 0 ? $price / $minutes : 0
            ));
        }
    }
    
    private function runGeneralTest()
    {
        $this->info("\n🔍 Running general test with latest call...");
        
        $call = Call::whereNotNull('cost')
            ->where('cost', '>', 0)
            ->latest()
            ->first();
            
        if ($call) {
            $this->testCallPricing($call->id);
        } else {
            $this->warn("No calls with cost found!");
        }
    }
}