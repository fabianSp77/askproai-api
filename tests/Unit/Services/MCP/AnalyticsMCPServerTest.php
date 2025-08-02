<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\AnalyticsMCPServer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsMCPServerTest extends TestCase
{
    use RefreshDatabase;
    
    protected AnalyticsMCPServer $mcp;
    protected Company $company;
    protected Branch $branch;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcp = new AnalyticsMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        // Set company context
        app()->instance('currentCompany', $this->company);
    }
    
    public function test_get_tools_returns_correct_structure()
    {
        $tools = $this->mcp->getTools();
        
        $this->assertIsArray($tools);
        $this->assertCount(8, $tools);
        
        $toolNames = array_column($tools, 'name');
        $expectedTools = [
            'predictRevenue',
            'predictAppointmentDemand',
            'analyzeCustomerBehavior',
            'getPerformanceInsights',
            'detectAnomalies',
            'getGrowthMetrics',
            'forecastTrends',
            'optimizeScheduling'
        ];
        
        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames);
        }
    }
    
    public function test_predict_revenue()
    {
        // Create historical revenue data
        $service1 = Service::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
        $service2 = Service::factory()->create(['company_id' => $this->company->id, 'price' => 200]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create appointment pattern over last 90 days
        for ($i = 90; $i > 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayOfWeek = $date->dayOfWeek;
            
            // Simulate weekly pattern (more appointments on weekdays)
            $appointmentCount = in_array($dayOfWeek, [0, 6]) ? rand(2, 5) : rand(5, 10);
            
            for ($j = 0; $j < $appointmentCount; $j++) {
                Appointment::factory()->create([
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                    'staff_id' => $staff->id,
                    'service_id' => rand(0, 1) ? $service1->id : $service2->id,
                    'status' => 'completed',
                    'price' => rand(0, 1) ? 100 : 200,
                    'created_at' => $date,
                    'starts_at' => $date
                ]);
            }
        }
        
        $result = $this->mcp->executeTool('predictRevenue', [
            'period' => 'next_30_days',
            'confidence_level' => 0.95
        ]);
        
        $this->assertArrayHasKey('prediction', $result);
        $this->assertArrayHasKey('confidence_interval', $result);
        $this->assertArrayHasKey('factors', $result);
        $this->assertArrayHasKey('historical_average', $result);
        $this->assertArrayHasKey('predicted_by_day', $result);
        $this->assertArrayHasKey('predicted_by_service', $result);
        
        $this->assertIsNumeric($result['prediction']);
        $this->assertGreaterThan(0, $result['prediction']);
        $this->assertEquals(0.95, $result['confidence_interval']['confidence_level']);
        $this->assertLessThan($result['confidence_interval']['upper'], $result['confidence_interval']['lower']);
    }
    
    public function test_predict_appointment_demand()
    {
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create historical appointment patterns
        for ($week = 12; $week > 0; $week--) {
            for ($day = 0; $day < 7; $day++) {
                $date = Carbon::now()->subWeeks($week)->startOfWeek()->addDays($day);
                
                // Create time-based patterns (morning and afternoon peaks)
                foreach ([9, 10, 11, 14, 15, 16] as $hour) {
                    if (rand(0, 100) < 70) { // 70% booking rate during peak hours
                        Appointment::factory()->create([
                            'company_id' => $this->company->id,
                            'branch_id' => $this->branch->id,
                            'staff_id' => $staff->id,
                            'service_id' => $service->id,
                            'starts_at' => $date->copy()->hour($hour),
                            'status' => 'completed'
                        ]);
                    }
                }
            }
        }
        
        $result = $this->mcp->executeTool('predictAppointmentDemand', [
            'date' => Carbon::now()->addWeek()->format('Y-m-d'),
            'service_id' => $service->id
        ]);
        
        $this->assertArrayHasKey('predicted_demand', $result);
        $this->assertArrayHasKey('peak_hours', $result);
        $this->assertArrayHasKey('recommended_staff', $result);
        $this->assertArrayHasKey('hourly_predictions', $result);
        $this->assertArrayHasKey('confidence_score', $result);
        
        $this->assertIsArray($result['peak_hours']);
        $this->assertNotEmpty($result['peak_hours']);
        
        // Peak hours should include our seeded peak times
        $peakHours = array_column($result['peak_hours'], 'hour');
        $this->assertContains(10, $peakHours);
        $this->assertContains(15, $peakHours);
    }
    
    public function test_analyze_customer_behavior()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subMonths(6)
        ]);
        
        $services = Service::factory()->count(3)->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create appointment history
        foreach ($services as $index => $service) {
            Appointment::factory()->count(5 - $index)->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'branch_id' => $this->branch->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'status' => 'completed',
                'created_at' => Carbon::now()->subMonths(6 - $index)
            ]);
        }
        
        // Add some cancelled appointments
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'service_id' => $services->first()->id,
            'status' => 'cancelled'
        ]);
        
        $result = $this->mcp->executeTool('analyzeCustomerBehavior', [
            'customer_id' => $customer->id
        ]);
        
        $this->assertArrayHasKey('segments', $result);
        $this->assertArrayHasKey('lifetime_value', $result);
        $this->assertArrayHasKey('churn_probability', $result);
        $this->assertArrayHasKey('preferred_services', $result);
        $this->assertArrayHasKey('booking_patterns', $result);
        $this->assertArrayHasKey('recommendations', $result);
        
        $this->assertContains('regular', $result['segments']); // Should be segmented as regular
        $this->assertIsNumeric($result['churn_probability']);
        $this->assertBetween($result['churn_probability'], 0, 1);
        $this->assertNotEmpty($result['preferred_services']);
        $this->assertEquals($services->first()->id, $result['preferred_services'][0]['service_id']);
    }
    
    public function test_get_performance_insights()
    {
        // Create staff with different performance levels
        $staff1 = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $staff2 = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $service = Service::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
        
        // High performer - many completed appointments
        Appointment::factory()->count(50)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff1->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'price' => 100,
            'duration' => 60,
            'created_at' => Carbon::now()->subMonth()
        ]);
        
        // Low performer - fewer appointments, more cancellations
        Appointment::factory()->count(20)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff2->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'price' => 100,
            'duration' => 90,
            'created_at' => Carbon::now()->subMonth()
        ]);
        
        Appointment::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff2->id,
            'service_id' => $service->id,
            'status' => 'cancelled',
            'created_at' => Carbon::now()->subMonth()
        ]);
        
        $result = $this->mcp->executeTool('getPerformanceInsights', [
            'entity_type' => 'staff',
            'period' => 'last_30_days'
        ]);
        
        $this->assertArrayHasKey('top_performers', $result);
        $this->assertArrayHasKey('improvement_areas', $result);
        $this->assertArrayHasKey('efficiency_metrics', $result);
        $this->assertArrayHasKey('recommendations', $result);
        
        $this->assertEquals($staff1->id, $result['top_performers'][0]['id']);
        $this->assertContains($staff2->id, array_column($result['improvement_areas'], 'id'));
    }
    
    public function test_detect_anomalies()
    {
        $service = Service::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create normal pattern
        for ($i = 30; $i > 5; $i--) {
            Appointment::factory()->count(rand(5, 8))->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays($i)
            ]);
        }
        
        // Create anomaly - sudden spike
        Appointment::factory()->count(25)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(3)
        ]);
        
        // Create anomaly - sudden drop
        Appointment::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDay()
        ]);
        
        $result = $this->mcp->executeTool('detectAnomalies', [
            'metrics' => ['appointments', 'revenue'],
            'sensitivity' => 'medium'
        ]);
        
        $this->assertArrayHasKey('anomalies', $result);
        $this->assertArrayHasKey('severity_breakdown', $result);
        $this->assertArrayHasKey('affected_metrics', $result);
        
        $this->assertNotEmpty($result['anomalies']);
        
        // Should detect the spike
        $spikeAnomaly = collect($result['anomalies'])->first(function ($anomaly) {
            return $anomaly['type'] === 'spike';
        });
        $this->assertNotNull($spikeAnomaly);
    }
    
    public function test_get_growth_metrics()
    {
        // Create growing customer base
        for ($month = 6; $month >= 0; $month--) {
            $customerCount = 10 + (6 - $month) * 5; // Growth pattern
            Customer::factory()->count($customerCount)->create([
                'company_id' => $this->company->id,
                'created_at' => Carbon::now()->subMonths($month)
            ]);
        }
        
        // Create growing revenue
        $service = Service::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        for ($month = 6; $month >= 0; $month--) {
            $appointmentCount = 20 + (6 - $month) * 10;
            Appointment::factory()->count($appointmentCount)->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'status' => 'completed',
                'price' => 100,
                'created_at' => Carbon::now()->subMonths($month)
            ]);
        }
        
        $result = $this->mcp->executeTool('getGrowthMetrics', [
            'period' => 'last_6_months'
        ]);
        
        $this->assertArrayHasKey('customer_growth', $result);
        $this->assertArrayHasKey('revenue_growth', $result);
        $this->assertArrayHasKey('appointment_growth', $result);
        $this->assertArrayHasKey('growth_rate', $result);
        $this->assertArrayHasKey('projected_growth', $result);
        $this->assertArrayHasKey('growth_drivers', $result);
        
        $this->assertGreaterThan(0, $result['customer_growth']['percentage']);
        $this->assertGreaterThan(0, $result['revenue_growth']['percentage']);
    }
    
    public function test_forecast_trends()
    {
        // Create seasonal pattern
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        for ($week = 52; $week > 0; $week--) {
            $date = Carbon::now()->subWeeks($week);
            $month = $date->month;
            
            // Simulate seasonal pattern (higher in summer)
            $baseCount = in_array($month, [6, 7, 8]) ? 15 : 8;
            $appointmentCount = $baseCount + rand(-3, 3);
            
            Appointment::factory()->count($appointmentCount)->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'status' => 'completed',
                'created_at' => $date
            ]);
        }
        
        $result = $this->mcp->executeTool('forecastTrends', [
            'metrics' => ['appointments', 'utilization'],
            'horizon' => 'next_3_months'
        ]);
        
        $this->assertArrayHasKey('forecasts', $result);
        $this->assertArrayHasKey('seasonality', $result);
        $this->assertArrayHasKey('trend_direction', $result);
        $this->assertArrayHasKey('confidence_levels', $result);
        
        $this->assertArrayHasKey('appointments', $result['forecasts']);
        $this->assertNotEmpty($result['seasonality']);
        $this->assertContains($result['trend_direction'], ['increasing', 'decreasing', 'stable']);
    }
    
    public function test_optimize_scheduling()
    {
        // Create staff with different schedules
        $staff1 = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $staff2 = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'duration' => 60
        ]);
        
        // Create existing appointments
        $date = Carbon::now()->addWeek()->startOfDay();
        
        // Staff 1 - morning appointments
        foreach ([9, 10, 11] as $hour) {
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'staff_id' => $staff1->id,
                'service_id' => $service->id,
                'starts_at' => $date->copy()->hour($hour),
                'ends_at' => $date->copy()->hour($hour)->addMinutes(60),
                'status' => 'scheduled'
            ]);
        }
        
        // Staff 2 - afternoon appointments
        foreach ([14, 15, 16] as $hour) {
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'staff_id' => $staff2->id,
                'service_id' => $service->id,
                'starts_at' => $date->copy()->hour($hour),
                'ends_at' => $date->copy()->hour($hour)->addMinutes(60),
                'status' => 'scheduled'
            ]);
        }
        
        $result = $this->mcp->executeTool('optimizeScheduling', [
            'date' => $date->format('Y-m-d'),
            'objective' => 'maximize_utilization'
        ]);
        
        $this->assertArrayHasKey('current_utilization', $result);
        $this->assertArrayHasKey('optimized_utilization', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('available_slots', $result);
        $this->assertArrayHasKey('bottlenecks', $result);
        
        $this->assertGreaterThanOrEqual(
            $result['current_utilization'],
            $result['optimized_utilization']
        );
        $this->assertNotEmpty($result['recommendations']);
    }
    
    public function test_execute_tool_with_invalid_tool_name()
    {
        $result = $this->mcp->executeTool('invalidTool', []);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }
    
    public function test_ml_algorithm_edge_cases()
    {
        // Test with insufficient data
        $result = $this->mcp->executeTool('predictRevenue', [
            'period' => 'next_30_days'
        ]);
        
        $this->assertArrayHasKey('warning', $result);
        $this->assertStringContainsString('insufficient data', strtolower($result['warning']));
        
        // Test with single data point
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'price' => 100
        ]);
        
        $result = $this->mcp->executeTool('forecastTrends', [
            'metrics' => ['revenue'],
            'horizon' => 'next_month'
        ]);
        
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('forecasts', $result);
        $this->assertEquals('insufficient_data', $result['forecasts']['revenue']['status']);
    }
    
    protected function assertBetween($value, $min, $max)
    {
        $this->assertGreaterThanOrEqual($min, $value);
        $this->assertLessThanOrEqual($max, $value);
    }
}