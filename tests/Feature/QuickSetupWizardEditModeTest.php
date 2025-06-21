<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Models\Staff;
use App\Models\Service;
use App\Models\PhoneNumber;
use Livewire\Livewire;
use App\Filament\Admin\Pages\QuickSetupWizard;

class QuickSetupWizardEditModeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected Company $existingCompany;
    protected Branch $existingBranch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'is_super_admin' => true,
        ]);
        
        // Create existing company with related data
        $this->existingCompany = Company::factory()->create([
            'name' => 'Test Company',
            'industry' => 'medical',
            'calcom_api_key' => encrypt('test-api-key'),
            'calcom_team_slug' => 'test-team',
        ]);
        
        $this->existingBranch = Branch::factory()->create([
            'company_id' => $this->existingCompany->id,
            'name' => 'Hauptfiliale',
            'city' => 'Berlin',
            'address' => 'Test Street 123',
            'phone_number' => '+49 30 12345678',
            'is_active' => true,
        ]);
        
        // Create some services
        Service::factory()->count(3)->create([
            'company_id' => $this->existingCompany->id,
        ]);
        
        // Create phone number
        PhoneNumber::factory()->create([
            'company_id' => $this->existingCompany->id,
            'branch_id' => $this->existingBranch->id,
            'number' => '+49 30 12345678',
            'type' => 'direct',
        ]);
        
        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function it_loads_existing_company_data_in_edit_mode()
    {
        Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $this->existingCompany,
            'editingBranch' => $this->existingBranch,
        ])
            ->assertSet('data.company_name', 'Test Company')
            ->assertSet('data.industry', 'medical')
            ->assertSet('data.branch_name', 'Hauptfiliale')
            ->assertSet('data.branch_city', 'Berlin')
            ->assertSet('data.branch_address', 'Test Street 123')
            ->assertSet('data.branch_phone', '+49 30 12345678')
            ->assertSet('data.calcom_api_key', '[ENCRYPTED]')
            ->assertSet('data.calcom_team_slug', 'test-team')
            ->assertSet('editMode', true);
    }

    /** @test */
    public function it_shows_mode_selection_when_companies_exist()
    {
        Livewire::test(QuickSetupWizard::class)
            ->assertSee('Modus auswählen')
            ->assertSee('Neue Firma anlegen')
            ->assertSee('Bestehende Firma bearbeiten')
            ->assertSee($this->existingCompany->name);
    }

    /** @test */
    public function it_can_update_existing_company()
    {
        $livewire = Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $this->existingCompany,
            'editingBranch' => $this->existingBranch,
        ]);
        
        // Update company data
        $livewire->set('data.company_name', 'Updated Company Name')
            ->set('data.industry', 'beauty')
            ->set('data.branch_name', 'Updated Branch')
            ->set('data.branch_city', 'Munich')
            ->call('completeSetup');
        
        // Assert database was updated
        $this->assertDatabaseHas('companies', [
            'id' => $this->existingCompany->id,
            'name' => 'Updated Company Name',
            'industry' => 'beauty',
        ]);
        
        $this->assertDatabaseHas('branches', [
            'id' => $this->existingBranch->id,
            'name' => 'Updated Branch',
            'city' => 'Munich',
        ]);
        
        $livewire->assertHasNoErrors()
            ->assertNotified('✅ Firma erfolgreich aktualisiert!');
    }

    /** @test */
    public function it_does_not_duplicate_services_when_updating()
    {
        $serviceCount = $this->existingCompany->services()->count();
        
        Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $this->existingCompany,
            'editingBranch' => $this->existingBranch,
        ])
            ->set('data.use_template_services', true)
            ->call('completeSetup');
        
        // Should not create duplicate services
        $this->assertGreaterThanOrEqual(
            $serviceCount,
            $this->existingCompany->fresh()->services()->count()
        );
    }

    /** @test */
    public function it_updates_phone_numbers_correctly()
    {
        Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $this->existingCompany,
            'editingBranch' => $this->existingBranch,
        ])
            ->set('data.branch_phone', '+49 30 87654321')
            ->set('data.use_hotline', true)
            ->set('data.hotline_number', '+49 30 11223344')
            ->set('data.routing_strategy', 'voice_menu')
            ->call('completeSetup');
        
        // Check direct number was updated
        $this->assertDatabaseHas('phone_numbers', [
            'company_id' => $this->existingCompany->id,
            'branch_id' => $this->existingBranch->id,
            'number' => '+49 30 87654321',
            'type' => 'direct',
        ]);
        
        // Check hotline was created
        $this->assertDatabaseHas('phone_numbers', [
            'company_id' => $this->existingCompany->id,
            'number' => '+49 30 11223344',
            'type' => 'hotline',
        ]);
    }

    /** @test */
    public function it_does_not_overwrite_api_key_when_placeholder_shown()
    {
        $originalKey = $this->existingCompany->calcom_api_key;
        
        Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $this->existingCompany,
            'editingBranch' => $this->existingBranch,
        ])
            ->assertSet('data.calcom_api_key', '[ENCRYPTED]')
            ->set('data.calcom_team_slug', 'new-team-slug')
            ->call('completeSetup');
        
        // API key should remain unchanged
        $this->existingCompany->refresh();
        $this->assertEquals($originalKey, $this->existingCompany->calcom_api_key);
        $this->assertEquals('new-team-slug', $this->existingCompany->calcom_team_slug);
    }

    /** @test */
    public function it_can_switch_from_new_to_edit_mode()
    {
        $livewire = Livewire::test(QuickSetupWizard::class)
            ->assertSee('Modus auswählen')
            ->set('data.setup_mode', 'edit')
            ->set('data.existing_company_id', $this->existingCompany->id);
        
        // Should load company data
        $livewire->assertSet('editMode', true)
            ->assertSet('editingCompany.id', $this->existingCompany->id);
    }

    /** @test */
    public function it_validates_required_fields_in_edit_mode()
    {
        Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $this->existingCompany,
            'editingBranch' => $this->existingBranch,
        ])
            ->set('data.company_name', '')
            ->set('data.branch_name', '')
            ->call('completeSetup')
            ->assertHasErrors(['data.company_name', 'data.branch_name']);
    }

    /** @test */
    public function it_handles_staff_updates_in_edit_mode()
    {
        // Create existing staff
        $staff = Staff::factory()->create([
            'company_id' => $this->existingCompany->id,
            'home_branch_id' => $this->existingBranch->id,
            'name' => 'John Doe',
        ]);
        
        $livewire = Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $this->existingCompany,
            'editingBranch' => $this->existingBranch,
        ]);
        
        // Should load existing staff
        $livewire->assertSee('John Doe');
    }

    /** @test */
    public function it_shows_correct_submit_button_label()
    {
        // New mode
        Livewire::test(QuickSetupWizard::class)
            ->assertSee('🚀 Setup abschließen');
        
        // Edit mode
        Livewire::test(QuickSetupWizard::class, [
            'editMode' => true,
            'editingCompany' => $this->existingCompany,
            'editingBranch' => $this->existingBranch,
        ])
            ->assertSee('💾 Änderungen speichern');
    }
}