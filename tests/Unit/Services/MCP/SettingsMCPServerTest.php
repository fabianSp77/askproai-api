<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\SettingsMCPServer;
use App\Models\Company;
use App\Models\User;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SettingsMCPServerTest extends TestCase
{
    use RefreshDatabase;
    
    protected SettingsMCPServer $mcp;
    protected Company $company;
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcp = new SettingsMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'settings' => [
                'timezone' => 'Europe/Berlin',
                'currency' => 'EUR',
                'language' => 'de'
            ]
        ]);
        
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@example.com'
        ]);
        
        // Set company context
        app()->instance('currentCompany', $this->company);
        
        // Set up roles and permissions
        $this->setupRolesAndPermissions();
    }
    
    protected function setupRolesAndPermissions()
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $staffRole = Role::create(['name' => 'staff', 'guard_name' => 'web']);
        
        $permissions = [
            'manage company',
            'manage branches',
            'manage users',
            'manage services',
            'view reports'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
        
        $adminRole->syncPermissions($permissions);
        $staffRole->syncPermissions(['view reports']);
        
        $this->user->assignRole('admin');
    }
    
    public function test_get_tools_returns_correct_structure()
    {
        $tools = $this->mcp->getTools();
        
        $this->assertIsArray($tools);
        $this->assertCount(10, $tools);
        
        $toolNames = array_column($tools, 'name');
        $expectedTools = [
            'getCompanySettings',
            'updateCompanySettings',
            'getBranchSettings',
            'updateBranchSettings',
            'getUserSettings',
            'updateUserSettings',
            'getIntegrations',
            'updateIntegration',
            'getNotificationSettings',
            'updateNotificationSettings'
        ];
        
        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames);
        }
    }
    
    public function test_get_company_settings()
    {
        $result = $this->mcp->executeTool('getCompanySettings', []);
        
        $this->assertArrayHasKey('company', $result);
        $this->assertArrayHasKey('settings', $result);
        $this->assertArrayHasKey('features', $result);
        $this->assertArrayHasKey('limits', $result);
        
        $this->assertEquals('Test Company', $result['company']['name']);
        $this->assertEquals('Europe/Berlin', $result['settings']['timezone']);
        $this->assertEquals('EUR', $result['settings']['currency']);
        $this->assertEquals('de', $result['settings']['language']);
    }
    
    public function test_update_company_settings()
    {
        Event::fake();
        
        $result = $this->mcp->executeTool('updateCompanySettings', [
            'name' => 'Updated Company Name',
            'email' => 'contact@updated.com',
            'phone' => '+49123456789',
            'address' => 'New Address 123',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'settings' => [
                'timezone' => 'Europe/London',
                'currency' => 'GBP',
                'language' => 'en',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i'
            ]
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('company', $result);
        $this->assertEquals('Updated Company Name', $result['company']['name']);
        $this->assertEquals('contact@updated.com', $result['company']['email']);
        $this->assertEquals('Europe/London', $result['company']['settings']['timezone']);
        $this->assertEquals('GBP', $result['company']['settings']['currency']);
        
        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'name' => 'Updated Company Name',
            'email' => 'contact@updated.com'
        ]);
        
        Event::assertDispatched(\App\Events\CompanySettingsUpdated::class);
    }
    
    public function test_get_branch_settings()
    {
        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'settings' => [
                'working_hours' => [
                    'monday' => ['09:00-18:00'],
                    'tuesday' => ['09:00-18:00'],
                    'wednesday' => ['09:00-18:00'],
                    'thursday' => ['09:00-18:00'],
                    'friday' => ['09:00-17:00'],
                    'saturday' => [],
                    'sunday' => []
                ],
                'booking_buffer' => 15,
                'max_advance_booking_days' => 30
            ]
        ]);
        
        $result = $this->mcp->executeTool('getBranchSettings', [
            'branch_id' => $branch->id
        ]);
        
        $this->assertArrayHasKey('branch', $result);
        $this->assertArrayHasKey('settings', $result);
        $this->assertArrayHasKey('staff_count', $result);
        $this->assertArrayHasKey('services', $result);
        
        $this->assertEquals('Main Branch', $result['branch']['name']);
        $this->assertArrayHasKey('working_hours', $result['settings']);
        $this->assertEquals(15, $result['settings']['booking_buffer']);
    }
    
    public function test_update_branch_settings()
    {
        Event::fake();
        
        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Branch'
        ]);
        
        $result = $this->mcp->executeTool('updateBranchSettings', [
            'branch_id' => $branch->id,
            'name' => 'Updated Branch',
            'phone' => '+49987654321',
            'email' => 'branch@example.com',
            'settings' => [
                'working_hours' => [
                    'monday' => ['10:00-19:00'],
                    'tuesday' => ['10:00-19:00'],
                    'wednesday' => ['10:00-19:00'],
                    'thursday' => ['10:00-19:00'],
                    'friday' => ['10:00-18:00'],
                    'saturday' => ['10:00-14:00'],
                    'sunday' => []
                ],
                'booking_buffer' => 30,
                'max_advance_booking_days' => 60,
                'allow_online_booking' => true
            ]
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Updated Branch', $result['branch']['name']);
        $this->assertEquals(30, $result['branch']['settings']['booking_buffer']);
        $this->assertTrue($result['branch']['settings']['allow_online_booking']);
        
        Event::assertDispatched(\App\Events\BranchSettingsUpdated::class);
    }
    
    public function test_get_user_settings()
    {
        $result = $this->mcp->executeTool('getUserSettings', [
            'user_id' => $this->user->id
        ]);
        
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('preferences', $result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('roles', $result);
        
        $this->assertEquals('admin@example.com', $result['user']['email']);
        $this->assertContains('admin', $result['roles']);
        $this->assertContains('manage company', $result['permissions']);
    }
    
    public function test_update_user_settings()
    {
        Event::fake();
        
        $result = $this->mcp->executeTool('updateUserSettings', [
            'user_id' => $this->user->id,
            'name' => 'Updated User Name',
            'preferences' => [
                'notifications' => [
                    'email' => true,
                    'push' => false,
                    'sms' => false
                ],
                'language' => 'en',
                'timezone' => 'UTC',
                'date_format' => 'd/m/Y'
            ]
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Updated User Name', $result['user']['name']);
        $this->assertEquals('en', $result['user']['preferences']['language']);
        $this->assertTrue($result['user']['preferences']['notifications']['email']);
        $this->assertFalse($result['user']['preferences']['notifications']['push']);
        
        Event::assertDispatched(\App\Events\UserSettingsUpdated::class);
    }
    
    public function test_update_user_password()
    {
        $result = $this->mcp->executeTool('updateUserSettings', [
            'user_id' => $this->user->id,
            'current_password' => 'password', // default factory password
            'new_password' => 'NewSecurePassword123!'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('password updated', $result['message']);
        
        // Verify password was changed
        $user = User::find($this->user->id);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));
    }
    
    public function test_update_user_password_with_wrong_current()
    {
        $result = $this->mcp->executeTool('updateUserSettings', [
            'user_id' => $this->user->id,
            'current_password' => 'wrong_password',
            'new_password' => 'NewSecurePassword123!'
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('incorrect', $result['error']);
    }
    
    public function test_get_integrations()
    {
        // Create integrations
        Integration::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'calcom',
            'name' => 'Cal.com Integration',
            'is_active' => true,
            'config' => ['api_key' => 'encrypted_key']
        ]);
        
        Integration::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'retell',
            'name' => 'Retell.ai Integration',
            'is_active' => false,
            'config' => ['api_key' => 'encrypted_key']
        ]);
        
        $result = $this->mcp->executeTool('getIntegrations', []);
        
        $this->assertArrayHasKey('integrations', $result);
        $this->assertArrayHasKey('available_types', $result);
        $this->assertCount(2, $result['integrations']);
        
        $calcomIntegration = collect($result['integrations'])->firstWhere('type', 'calcom');
        $this->assertEquals('Cal.com Integration', $calcomIntegration['name']);
        $this->assertTrue($calcomIntegration['is_active']);
        $this->assertArrayNotHasKey('api_key', $calcomIntegration['config']); // Should be masked
    }
    
    public function test_update_integration()
    {
        Event::fake();
        
        $integration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'stripe',
            'name' => 'Stripe Payment',
            'is_active' => false
        ]);
        
        $result = $this->mcp->executeTool('updateIntegration', [
            'integration_id' => $integration->id,
            'is_active' => true,
            'config' => [
                'api_key' => 'sk_test_newkey123',
                'webhook_secret' => 'whsec_newsecret456'
            ]
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertTrue($result['integration']['is_active']);
        $this->assertArrayHasKey('message', $result);
        
        $this->assertDatabaseHas('integrations', [
            'id' => $integration->id,
            'is_active' => true
        ]);
        
        Event::assertDispatched(\App\Events\IntegrationUpdated::class);
    }
    
    public function test_get_notification_settings()
    {
        $result = $this->mcp->executeTool('getNotificationSettings', []);
        
        $this->assertArrayHasKey('channels', $result);
        $this->assertArrayHasKey('templates', $result);
        $this->assertArrayHasKey('triggers', $result);
        
        $this->assertArrayHasKey('email', $result['channels']);
        $this->assertArrayHasKey('sms', $result['channels']);
        $this->assertArrayHasKey('push', $result['channels']);
        
        $this->assertArrayHasKey('appointment_confirmation', $result['templates']);
        $this->assertArrayHasKey('appointment_reminder', $result['templates']);
        $this->assertArrayHasKey('appointment_cancelled', $result['templates']);
    }
    
    public function test_update_notification_settings()
    {
        Event::fake();
        
        $result = $this->mcp->executeTool('updateNotificationSettings', [
            'channels' => [
                'email' => [
                    'enabled' => true,
                    'from_name' => 'Test Company',
                    'from_email' => 'noreply@example.com'
                ],
                'sms' => [
                    'enabled' => true,
                    'provider' => 'twilio',
                    'from_number' => '+1234567890'
                ]
            ],
            'triggers' => [
                'appointment_confirmation' => [
                    'enabled' => true,
                    'channels' => ['email', 'sms'],
                    'timing' => 'immediate'
                ],
                'appointment_reminder' => [
                    'enabled' => true,
                    'channels' => ['email'],
                    'timing' => '24_hours_before'
                ]
            ]
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('settings', $result);
        $this->assertTrue($result['settings']['channels']['email']['enabled']);
        $this->assertTrue($result['settings']['channels']['sms']['enabled']);
        $this->assertEquals('twilio', $result['settings']['channels']['sms']['provider']);
        
        Event::assertDispatched(\App\Events\NotificationSettingsUpdated::class);
    }
    
    public function test_execute_tool_with_invalid_tool_name()
    {
        $result = $this->mcp->executeTool('invalidTool', []);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }
    
    public function test_settings_validation()
    {
        // Test invalid timezone
        $result = $this->mcp->executeTool('updateCompanySettings', [
            'settings' => [
                'timezone' => 'Invalid/Timezone'
            ]
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('timezone', $result['error']);
        
        // Test invalid currency
        $result = $this->mcp->executeTool('updateCompanySettings', [
            'settings' => [
                'currency' => 'XXX'
            ]
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('currency', $result['error']);
    }
    
    public function test_permission_checks()
    {
        // Create non-admin user
        $staffUser = User::factory()->create([
            'company_id' => $this->company->id
        ]);
        $staffUser->assignRole('staff');
        
        // Set current user context
        $this->actingAs($staffUser);
        
        // Try to update company settings without permission
        $result = $this->mcp->executeTool('updateCompanySettings', [
            'name' => 'Should Not Update'
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('permission', $result['error']);
    }
}