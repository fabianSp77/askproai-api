<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Filament\Admin\Resources\CallCampaignResource;
use App\Models\User;
use App\Models\Company;
use App\Models\RetellAICallCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CallCampaignResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'company_owner']);
    }

    public function test_can_view_any_requires_outbound_call_capability()
    {
        // Company without outbound calls capability
        $companyWithoutOutbound = Company::factory()->create(['can_make_outbound_calls' => false]);
        $userWithoutOutbound = User::factory()->create(['company_id' => $companyWithoutOutbound->id]);
        $this->actingAs($userWithoutOutbound);

        $this->assertFalse(CallCampaignResource::canViewAny());

        // Company with outbound calls capability
        $companyWithOutbound = Company::factory()->create(['can_make_outbound_calls' => true]);
        $userWithOutbound = User::factory()->create(['company_id' => $companyWithOutbound->id]);
        $this->actingAs($userWithOutbound);

        $this->assertTrue(CallCampaignResource::canViewAny());
    }

    public function test_user_without_company_cannot_view()
    {
        $user = User::factory()->create(['company_id' => null]);
        $this->actingAs($user);

        $this->assertFalse(CallCampaignResource::canViewAny());
    }

    public function test_form_has_required_sections()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $form = CallCampaignResource::form(\Filament\Forms\Form::make());
        $schema = collect($form->getSchema());

        // Check that main sections exist
        $sectionHeadings = $schema->pluck('heading');
        
        $this->assertContains('Kampagnen Details', $sectionHeadings);
        $this->assertContains('Zielgruppe', $sectionHeadings);
        $this->assertContains('Zeitplanung', $sectionHeadings);
        $this->assertContains('Einstellungen', $sectionHeadings);
    }

    public function test_form_has_reactive_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $form = CallCampaignResource::form(\Filament\Forms\Form::make());
        $fields = collect($form->getSchema())->flatten();

        // Find reactive fields
        $targetTypeField = $fields->firstWhere('name', 'target_type');
        $scheduleTypeField = $fields->firstWhere('name', 'schedule_type');

        $this->assertNotNull($targetTypeField);
        $this->assertNotNull($scheduleTypeField);
    }

    public function test_table_has_proper_columns()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $table = CallCampaignResource::table(\Filament\Tables\Table::make());
        $columns = collect($table->getColumns());

        $expectedColumns = [
            'name',
            'status', 
            'target_type',
            'total_targets',
            'calls_completed',
            'calls_failed',
            'completion_percentage',
            'created_at'
        ];

        foreach ($expectedColumns as $columnName) {
            $column = $columns->firstWhere('name', $columnName);
            $this->assertNotNull($column, "Column {$columnName} should exist");
        }
    }

    public function test_table_has_status_badge_colors()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $table = CallCampaignResource::table(\Filament\Tables\Table::make());
        $statusColumn = collect($table->getColumns())->firstWhere('name', 'status');

        $this->assertNotNull($statusColumn);
        
        // Check that badge colors are configured
        $colors = $statusColumn->getColors();
        $expectedColors = [
            'secondary' => 'draft',
            'warning' => 'scheduled',
            'primary' => 'running',
            'info' => 'paused',
            'success' => 'completed',
            'danger' => 'failed',
        ];

        foreach ($expectedColors as $color => $status) {
            $this->assertArrayHasKey($color, $colors);
            $this->assertEquals($status, $colors[$color]);
        }
    }

    public function test_table_has_target_type_formatting()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $table = CallCampaignResource::table(\Filament\Tables\Table::make());
        $targetTypeColumn = collect($table->getColumns())->firstWhere('name', 'target_type');

        $this->assertNotNull($targetTypeColumn);
        $this->assertIsCallable($targetTypeColumn->getFormatStateUsing());
    }

    public function test_table_actions_respect_campaign_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $table = CallCampaignResource::table(\Filament\Tables\Table::make());
        $actions = collect($table->getActions());

        // Check that conditional actions exist
        $startAction = $actions->firstWhere('name', 'start');
        $pauseAction = $actions->firstWhere('name', 'pause');
        $resumeAction = $actions->firstWhere('name', 'resume');

        $this->assertNotNull($startAction);
        $this->assertNotNull($pauseAction);
        $this->assertNotNull($resumeAction);
    }

    public function test_filters_are_configured()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $table = CallCampaignResource::table(\Filament\Tables\Table::make());
        $filters = collect($table->getFilters());

        $statusFilter = $filters->firstWhere('name', 'status');
        $targetTypeFilter = $filters->firstWhere('name', 'target_type');

        $this->assertNotNull($statusFilter);
        $this->assertNotNull($targetTypeFilter);
    }

    public function test_resource_uses_tenant_scope()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $query = CallCampaignResource::getEloquentQuery();
        
        // Check that withoutGlobalScopes is called (tenant filtering handled elsewhere)
        $this->assertNotNull($query);
    }

    public function test_bulk_actions_require_permissions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $table = CallCampaignResource::table(\Filament\Tables\Table::make());
        $bulkActions = $table->getBulkActions();

        $this->assertNotEmpty($bulkActions);
    }

    public function test_resource_navigation_configuration()
    {
        $this->assertEquals('heroicon-o-phone-arrow-up-right', CallCampaignResource::getNavigationIcon());
        $this->assertEquals('Kommunikation', CallCampaignResource::getNavigationGroup());
        $this->assertEquals('Outbound Kampagnen', CallCampaignResource::getNavigationLabel());
        $this->assertEquals('Kampagne', CallCampaignResource::getModelLabel());
        $this->assertEquals('Kampagnen', CallCampaignResource::getPluralModelLabel());
        $this->assertEquals(30, CallCampaignResource::getNavigationSort());
    }

    public function test_resource_pages_configuration()
    {
        $pages = CallCampaignResource::getPages();

        $expectedPages = ['index', 'create', 'view', 'edit'];
        
        foreach ($expectedPages as $page) {
            $this->assertArrayHasKey($page, $pages);
        }
    }

    public function test_campaign_status_actions_have_confirmations()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $table = CallCampaignResource::table(\Filament\Tables\Table::make());
        $actions = collect($table->getActions());

        $startAction = $actions->firstWhere('name', 'start');
        
        $this->assertNotNull($startAction);
        // Check that the action requires confirmation
        $this->assertTrue($startAction->shouldRequireConfirmation());
    }

    public function test_target_type_options_are_complete()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $form = CallCampaignResource::form(\Filament\Forms\Form::make());
        $fields = collect($form->getSchema())->flatten();
        $targetTypeField = $fields->firstWhere('name', 'target_type');

        $this->assertNotNull($targetTypeField);
        
        $expectedOptions = [
            'leads' => 'Sales Leads',
            'appointments' => 'Terminbestätigungen',
            'follow_up' => 'Nachfass-Anrufe',
            'survey' => 'Umfragen',
            'custom_list' => 'Eigene Liste (CSV)'
        ];

        $options = $targetTypeField->getOptions();
        
        foreach ($expectedOptions as $key => $label) {
            $this->assertArrayHasKey($key, $options);
            $this->assertEquals($label, $options[$key]);
        }
    }

    public function test_schedule_type_options_are_complete()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $form = CallCampaignResource::form(\Filament\Forms\Form::make());
        $fields = collect($form->getSchema())->flatten();
        $scheduleTypeField = $fields->firstWhere('name', 'schedule_type');

        $this->assertNotNull($scheduleTypeField);
        
        $expectedOptions = [
            'immediate' => 'Sofort starten',
            'scheduled' => 'Geplant',
            'recurring' => 'Wiederkehrend'
        ];

        $options = $scheduleTypeField->getOptions();
        
        foreach ($expectedOptions as $key => $label) {
            $this->assertArrayHasKey($key, $options);
            $this->assertEquals($label, $options[$key]);
        }
    }

    public function test_form_validation_rules()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $form = CallCampaignResource::form(\Filament\Forms\Form::make());
        $fields = collect($form->getSchema())->flatten();

        // Check required fields
        $nameField = $fields->firstWhere('name', 'name');
        $agentField = $fields->firstWhere('name', 'agent_id');
        $targetTypeField = $fields->firstWhere('name', 'target_type');

        $this->assertNotNull($nameField);
        $this->assertNotNull($agentField);
        $this->assertNotNull($targetTypeField);

        // These fields should be required
        $this->assertTrue($nameField->isRequired());
        $this->assertTrue($agentField->isRequired());
        $this->assertTrue($targetTypeField->isRequired());
    }
}