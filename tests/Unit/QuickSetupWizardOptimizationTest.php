<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Filament\Admin\Pages\QuickSetupWizard;
use Livewire\Livewire;

class QuickSetupWizardOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'is_super_admin' => true,
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_loads_companies_efficiently_with_only_needed_fields()
    {
        // Create test companies
        Company::factory()->count(150)->create();
        
        // Track queries
        $queries = [];
        \DB::listen(function($query) use (&$queries) {
            $queries[] = $query;
        });
        
        // Load wizard
        Livewire::test(QuickSetupWizard::class);
        
        // Find the company loading query
        $companyQuery = collect($queries)->first(function($query) {
            return str_contains($query->sql, 'select') && 
                   str_contains($query->sql, 'from `companies`');
        });
        
        // Assert optimized query
        $this->assertNotNull($companyQuery);
        $this->assertStringContainsString('select `id`, `name`', $companyQuery->sql);
        $this->assertStringContainsString('where `is_active` = ?', $companyQuery->sql);
        $this->assertStringContainsString('limit 100', $companyQuery->sql);
    }

    /** @test */
    public function it_creates_multiple_branches_efficiently_with_bulk_insert()
    {
        $company = Company::factory()->create();
        
        // Track queries
        $insertQueries = 0;
        \DB::listen(function($query) use (&$insertQueries) {
            if (str_contains($query->sql, 'insert into `branches`')) {
                $insertQueries++;
            }
        });
        
        $livewire = Livewire::test(QuickSetupWizard::class)
            ->set('data.company_name', 'Test Company')
            ->set('data.industry', 'medical')
            ->set('data.branches', [
                [
                    'name' => 'Branch 1',
                    'city' => 'Berlin',
                    'phone_number' => '+49 30 12345678',
                    'address' => 'Test Street 1',
                    'features' => ['parking', 'wifi']
                ],
                [
                    'name' => 'Branch 2',
                    'city' => 'Munich',
                    'phone_number' => '+49 89 12345678',
                    'address' => 'Test Street 2',
                    'features' => ['wheelchair']
                ],
                [
                    'name' => 'Branch 3',
                    'city' => 'Hamburg',
                    'phone_number' => '+49 40 12345678',
                    'address' => 'Test Street 3',
                    'features' => []
                ]
            ])
            ->call('completeSetup');
        
        // Should use saveMany for bulk insert, not individual inserts
        $this->assertEquals(0, $insertQueries, 'Should use saveMany instead of individual inserts');
        
        // Verify all branches were created
        $this->assertDatabaseCount('branches', 3);
        $this->assertDatabaseHas('branches', ['name' => 'Branch 1', 'city' => 'Berlin']);
        $this->assertDatabaseHas('branches', ['name' => 'Branch 2', 'city' => 'Munich']);
        $this->assertDatabaseHas('branches', ['name' => 'Branch 3', 'city' => 'Hamburg']);
    }

    /** @test */
    public function it_validates_phone_numbers_correctly()
    {
        $livewire = Livewire::test(QuickSetupWizard::class)
            ->set('data.company_name', 'Test Company')
            ->set('data.industry', 'medical')
            ->set('data.branches', [
                [
                    'name' => 'Test Branch',
                    'city' => 'Berlin',
                    'phone_number' => 'invalid-phone', // Invalid format
                ]
            ]);
        
        // Should have validation error
        $livewire->call('completeSetup')
            ->assertHasErrors(['data.branches.0.phone_number']);
        
        // Valid phone number should pass
        $livewire->set('data.branches.0.phone_number', '+49 30 12345678')
            ->call('completeSetup')
            ->assertHasNoErrors();
    }

    /** @test */
    public function it_handles_database_errors_gracefully()
    {
        // Force a database error by setting invalid data
        $livewire = Livewire::test(QuickSetupWizard::class)
            ->set('data.company_name', str_repeat('a', 256)) // Too long
            ->set('data.industry', 'medical')
            ->set('data.branches', [
                ['name' => 'Test', 'city' => 'Berlin', 'phone_number' => '+49 30 12345678']
            ])
            ->call('completeSetup');
        
        // Should show user-friendly error
        $livewire->assertNotified('Datenbankfehler');
        
        // Should not create any records due to transaction rollback
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('branches', 0);
    }

    /** @test */
    public function it_loads_all_branches_in_edit_mode()
    {
        // Create company with multiple branches
        $company = Company::factory()->create();
        $branches = Branch::factory()->count(3)->create([
            'company_id' => $company->id,
        ]);
        
        // Load wizard in edit mode
        $livewire = Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $company,
            'editingBranch' => $branches->first(),
        ]);
        
        // Should load all branches
        $this->assertCount(3, $livewire->get('data.branches'));
        
        // Verify branch data is loaded correctly
        foreach ($branches as $index => $branch) {
            $this->assertEquals($branch->name, $livewire->get("data.branches.{$index}.name"));
            $this->assertEquals($branch->city, $livewire->get("data.branches.{$index}.city"));
        }
    }

    /** @test */
    public function it_performs_within_acceptable_time_limits()
    {
        // Create test data
        Company::factory()->count(50)->create();
        
        $startTime = microtime(true);
        
        // Load wizard
        $livewire = Livewire::test(QuickSetupWizard::class);
        
        $loadTime = microtime(true) - $startTime;
        
        // Should load within 500ms
        $this->assertLessThan(0.5, $loadTime, 'Wizard should load within 500ms');
        
        // Test complete setup performance
        $startTime = microtime(true);
        
        $livewire->set('data.company_name', 'Performance Test Company')
            ->set('data.industry', 'medical')
            ->set('data.branches', [
                ['name' => 'Branch 1', 'city' => 'Berlin', 'phone_number' => '+49 30 12345678'],
                ['name' => 'Branch 2', 'city' => 'Munich', 'phone_number' => '+49 89 12345678'],
            ])
            ->call('completeSetup');
        
        $setupTime = microtime(true) - $startTime;
        
        // Should complete within 1 second
        $this->assertLessThan(1.0, $setupTime, 'Setup should complete within 1 second');
    }

    /** @test */
    public function it_prevents_duplicate_company_creation()
    {
        // Create existing company
        Company::factory()->create(['name' => 'Existing Company']);
        
        $livewire = Livewire::test(QuickSetupWizard::class)
            ->set('data.company_name', 'Existing Company')
            ->set('data.industry', 'medical')
            ->set('data.branches', [
                ['name' => 'Branch', 'city' => 'Berlin', 'phone_number' => '+49 30 12345678']
            ])
            ->call('completeSetup');
        
        // Should have validation error
        $livewire->assertHasErrors(['data.company_name']);
        
        // Should not create duplicate
        $this->assertDatabaseCount('companies', 1);
    }

    /** @test */
    public function it_uses_transactions_for_data_integrity()
    {
        // Mock an exception during branch creation
        $this->mock(Branch::class, function($mock) {
            $mock->shouldReceive('saveMany')
                ->andThrow(new \Exception('Simulated error'));
        });
        
        $livewire = Livewire::test(QuickSetupWizard::class)
            ->set('data.company_name', 'Transaction Test')
            ->set('data.industry', 'medical')
            ->set('data.branches', [
                ['name' => 'Branch', 'city' => 'Berlin', 'phone_number' => '+49 30 12345678']
            ])
            ->call('completeSetup');
        
        // Should rollback all changes
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('branches', 0);
        
        // Should show error notification
        $livewire->assertNotified('Setup fehlgeschlagen');
    }
}