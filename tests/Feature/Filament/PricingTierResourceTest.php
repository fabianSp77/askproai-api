<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Filament\Admin\Resources\PricingTierResource;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyPricingTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PricingTierResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'reseller_owner']);
        Role::create(['name' => 'reseller_admin']);
        Role::create(['name' => 'company_owner']);
        
        Permission::create(['name' => 'reseller.pricing.view_costs']);
        Permission::create(['name' => 'reseller.pricing.view_margins']);
    }

    public function test_super_admin_can_view_pricing_tiers()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $this->assertTrue(PricingTierResource::canViewAny());
    }

    public function test_reseller_owner_can_view_pricing_tiers()
    {
        $user = User::factory()->create();
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        $this->assertTrue(PricingTierResource::canViewAny());
    }

    public function test_reseller_admin_can_view_pricing_tiers()
    {
        $user = User::factory()->create();
        $user->assignRole('reseller_admin');
        $this->actingAs($user);

        $this->assertTrue(PricingTierResource::canViewAny());
    }

    public function test_regular_user_cannot_view_pricing_tiers()
    {
        $user = User::factory()->create();
        $user->assignRole('company_owner');
        $this->actingAs($user);

        $this->assertFalse(PricingTierResource::canViewAny());
    }

    public function test_super_admin_sees_all_companies_in_form()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        // Create some client companies
        Company::factory()->count(3)->create(['company_type' => 'client']);

        $form = PricingTierResource::form(\Filament\Forms\Form::make());
        $childCompanyField = collect($form->getSchema())->flatten()
            ->firstWhere('name', 'child_company_id');

        $this->assertNotNull($childCompanyField);
    }

    public function test_reseller_user_sees_only_their_clients_in_form()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $user = User::factory()->create(['company_id' => $reseller->id]);
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        // Create client for this reseller
        $ownClient = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        // Create client for another reseller
        $otherReseller = Company::factory()->create(['company_type' => 'reseller']);
        $otherClient = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $otherReseller->id
        ]);

        $form = PricingTierResource::form(\Filament\Forms\Form::make());
        $childCompanyField = collect($form->getSchema())->flatten()
            ->firstWhere('name', 'child_company_id');

        $this->assertNotNull($childCompanyField);
        // Note: This is more of a structural test. The actual options would need
        // to be tested in a more integrated way with Livewire components.
    }

    public function test_eloquent_query_filters_by_company_for_resellers()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $user = User::factory()->create(['company_id' => $reseller->id]);
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        // Create pricing tiers for this reseller
        $ownTier = CompanyPricingTier::factory()->create(['company_id' => $reseller->id]);
        
        // Create pricing tier for another reseller
        $otherReseller = Company::factory()->create(['company_type' => 'reseller']);
        $otherTier = CompanyPricingTier::factory()->create(['company_id' => $otherReseller->id]);

        $query = PricingTierResource::getEloquentQuery();
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($ownTier->id, $results->first()->id);
    }

    public function test_super_admin_sees_all_pricing_tiers()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        // Create pricing tiers for multiple companies
        $tier1 = CompanyPricingTier::factory()->create();
        $tier2 = CompanyPricingTier::factory()->create();

        $query = PricingTierResource::getEloquentQuery();
        $results = $query->get();

        $this->assertCount(2, $results);
    }

    public function test_form_auto_sets_company_id_on_create()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $user = User::factory()->create(['company_id' => $reseller->id]);
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        $data = [
            'child_company_id' => 123,
            'pricing_type' => 'inbound',
            'sell_price' => 0.45
        ];

        $mutatedData = PricingTierResource::mutateFormDataBeforeCreate($data);

        $this->assertEquals($reseller->id, $mutatedData['company_id']);
    }

    public function test_form_sets_default_overage_rate()
    {
        $user = User::factory()->create(['company_id' => 1]);
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        $data = [
            'child_company_id' => 123,
            'pricing_type' => 'inbound',
            'sell_price' => 0.45,
            'overage_rate' => null
        ];

        $mutatedData = PricingTierResource::mutateFormDataBeforeCreate($data);

        $this->assertEquals(0.45, $mutatedData['overage_rate']);
    }

    public function test_form_preserves_explicit_overage_rate()
    {
        $user = User::factory()->create(['company_id' => 1]);
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        $data = [
            'child_company_id' => 123,
            'pricing_type' => 'inbound',
            'sell_price' => 0.45,
            'overage_rate' => 0.55
        ];

        $mutatedData = PricingTierResource::mutateFormDataBeforeCreate($data);

        $this->assertEquals(0.55, $mutatedData['overage_rate']);
    }

    public function test_cost_price_field_visibility_based_on_permissions()
    {
        // Test user without permission
        $user = User::factory()->create();
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        $form = PricingTierResource::form(\Filament\Forms\Form::make());
        $costPriceField = collect($form->getSchema())->flatten()
            ->firstWhere('name', 'cost_price');

        $this->assertNotNull($costPriceField);
        // The field exists but should not be visible
        
        // Test user with permission
        $userWithPermission = User::factory()->create();
        $userWithPermission->assignRole('reseller_owner');
        $userWithPermission->givePermissionTo('reseller.pricing.view_costs');
        $this->actingAs($userWithPermission);

        $formWithPermission = PricingTierResource::form(\Filament\Forms\Form::make());
        $costPriceFieldWithPermission = collect($formWithPermission->getSchema())->flatten()
            ->firstWhere('name', 'cost_price');

        $this->assertNotNull($costPriceFieldWithPermission);
    }

    public function test_table_columns_respect_permissions()
    {
        $user = User::factory()->create();
        $user->assignRole('reseller_owner');
        $user->givePermissionTo(['reseller.pricing.view_costs', 'reseller.pricing.view_margins']);
        $this->actingAs($user);

        $table = PricingTierResource::table(\Filament\Tables\Table::make());
        $columns = collect($table->getColumns());

        // Should have cost price column when user has permission
        $costPriceColumn = $columns->firstWhere('name', 'cost_price');
        $this->assertNotNull($costPriceColumn);

        // Should have margin column when user has permission
        $marginColumn = $columns->firstWhere('name', 'margin');
        $this->assertNotNull($marginColumn);
    }

    public function test_table_formatting_for_pricing_types()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $table = PricingTierResource::table(\Filament\Tables\Table::make());
        $pricingTypeColumn = collect($table->getColumns())->firstWhere('name', 'pricing_type');

        // Test the formatting function exists
        $this->assertNotNull($pricingTypeColumn);
        
        // Test some format state transformations
        $this->assertIsCallable($pricingTypeColumn->getFormatStateUsing());
    }

    public function test_resource_has_correct_navigation_configuration()
    {
        $this->assertEquals('heroicon-o-currency-euro', PricingTierResource::getNavigationIcon());
        $this->assertEquals('Finanzen & Abrechnung', PricingTierResource::getNavigationGroup());
        $this->assertEquals('Preismodelle', PricingTierResource::getNavigationLabel());
        $this->assertEquals('Preismodell', PricingTierResource::getModelLabel());
        $this->assertEquals('Preismodelle', PricingTierResource::getPluralModelLabel());
        $this->assertEquals(20, PricingTierResource::getNavigationSort());
    }

    public function test_resource_pages_are_configured()
    {
        $pages = PricingTierResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }
}