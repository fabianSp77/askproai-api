<?php

namespace Tests\Unit\Setup;

use Tests\TestCase;
use App\Services\Setup\SetupOrchestrator;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class SetupOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    protected SetupOrchestrator $orchestrator;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orchestrator = new SetupOrchestrator();
        $this->user = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_creates_company_with_branches_successfully()
    {
        $data = [
            'company_name' => 'Test Medical Practice',
            'industry' => 'medical',
            'branches' => [
                [
                    'name' => 'Main Branch',
                    'city' => 'Berlin',
                    'phone_number' => '+49 30 12345678',
                    'address' => 'Test Street 1',
                ],
                [
                    'name' => 'Second Branch',
                    'city' => 'Munich',
                    'phone_number' => '+49 89 12345678',
                    'address' => 'Test Street 2',
                ]
            ]
        ];

        $result = $this->orchestrator->execute($data);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Company::class, $result['company']);
        $this->assertCount(2, $result['branches']);
        $this->assertEquals('Test Medical Practice', $result['company']->name);
        
        // Verify database
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Medical Practice',
            'industry' => 'medical'
        ]);
        
        $this->assertDatabaseHas('branches', [
            'name' => 'Main Branch',
            'city' => 'Berlin'
        ]);
    }

    /** @test */
    public function it_rollsback_on_failure()
    {
        $data = [
            'company_name' => str_repeat('a', 256), // Too long, will fail
            'industry' => 'medical',
            'branches' => [
                ['name' => 'Branch 1', 'city' => 'Berlin']
            ]
        ];

        try {
            $this->orchestrator->execute($data);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Transaction should have rolled back
            $this->assertDatabaseCount('companies', 0);
            $this->assertDatabaseCount('branches', 0);
        }
    }

    /** @test */
    public function it_reports_progress_correctly()
    {
        $progressReports = [];
        
        $this->orchestrator->onProgress(function ($percentage, $message) use (&$progressReports) {
            $progressReports[] = ['percentage' => $percentage, 'message' => $message];
        });

        $data = [
            'company_name' => 'Progress Test Company',
            'industry' => 'beauty',
            'branches' => [
                ['name' => 'Test Branch', 'city' => 'Hamburg']
            ]
        ];

        $this->orchestrator->execute($data);

        // Verify progress was reported
        $this->assertNotEmpty($progressReports);
        $this->assertEquals(10, $progressReports[0]['percentage']);
        $this->assertStringContainsString('Firma wird erstellt', $progressReports[0]['message']);
        
        // Verify final progress
        $lastProgress = end($progressReports);
        $this->assertEquals(95, $lastProgress['percentage']);
    }

    /** @test */
    public function it_creates_services_from_industry_template()
    {
        $data = [
            'company_name' => 'Beauty Salon Test',
            'industry' => 'beauty',
            'branches' => [
                ['name' => 'Salon', 'city' => 'Berlin']
            ]
        ];

        $result = $this->orchestrator->execute($data);

        $this->assertNotEmpty($result['services']);
        
        // Beauty industry should have specific services
        $serviceNames = collect($result['services'])->pluck('name')->toArray();
        $this->assertContains('Haarschnitt', $serviceNames);
        $this->assertContains('Färben', $serviceNames);
        $this->assertContains('Maniküre', $serviceNames);
    }

    /** @test */
    public function it_handles_edit_mode_correctly()
    {
        // Create existing company
        $existingCompany = Company::factory()->create([
            'name' => 'Old Name',
            'industry' => 'medical'
        ]);

        $data = [
            'company_id' => $existingCompany->id,
            'company_name' => 'Updated Name',
            'industry' => 'beauty',
            'branches' => [
                ['name' => 'New Branch', 'city' => 'Frankfurt']
            ]
        ];

        $result = $this->orchestrator->execute($data);

        $this->assertTrue($result['success']);
        $this->assertEquals($existingCompany->id, $result['company']->id);
        
        // Verify company was updated
        $existingCompany->refresh();
        $this->assertEquals('Updated Name', $existingCompany->name);
        $this->assertEquals('beauty', $existingCompany->industry);
    }
}