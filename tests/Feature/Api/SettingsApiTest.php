<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Company $company;
    protected Branch $branch;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'settings' => [
                'timezone' => 'Europe/Berlin',
                'currency' => 'EUR',
                'language' => 'de'
            ]
        ]);
        
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        // Set up roles and permissions
        $this->setupRolesAndPermissions();
        
        // Authenticate user
        Sanctum::actingAs($this->user);
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
    
    public function test_get_company_settings()
    {
        $response = $this->getJson('/api/settings/company');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'company' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'address',
                        'city',
                        'postal_code'
                    ],
                    'settings' => [
                        'timezone',
                        'currency',
                        'language'
                    ],
                    'features',
                    'limits'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals('Test Company', $data['company']['name']);
        $this->assertEquals('Europe/Berlin', $data['settings']['timezone']);
        $this->assertEquals('EUR', $data['settings']['currency']);
        $this->assertEquals('de', $data['settings']['language']);
    }
    
    public function test_update_company_settings()
    {
        $response = $this->putJson('/api/settings/company', [
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
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Company settings updated successfully'
            ]);
        
        $this->company->refresh();
        $this->assertEquals('Updated Company Name', $this->company->name);
        $this->assertEquals('contact@updated.com', $this->company->email);
        $this->assertEquals('Europe/London', $this->company->settings['timezone']);
        $this->assertEquals('GBP', $this->company->settings['currency']);
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
        
        $response = $this->getJson("/api/settings/branches/{$branch->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'branch' => [
                        'id',
                        'name',
                        'phone',
                        'email',
                        'address'
                    ],
                    'settings' => [
                        'working_hours',
                        'booking_buffer',
                        'max_advance_booking_days'
                    ],
                    'staff_count',
                    'services'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals('Main Branch', $data['branch']['name']);
        $this->assertEquals(15, $data['settings']['booking_buffer']);
    }
    
    public function test_update_branch_settings()
    {
        $response = $this->putJson("/api/settings/branches/{$this->branch->id}", [
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
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Branch settings updated successfully'
            ]);
        
        $this->branch->refresh();
        $this->assertEquals('Updated Branch', $this->branch->name);
        $this->assertEquals(30, $this->branch->settings['booking_buffer']);
        $this->assertTrue($this->branch->settings['allow_online_booking']);
    }
    
    public function test_get_user_settings()
    {
        $response = $this->getJson("/api/settings/users/{$this->user->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'preferences'
                    ],
                    'preferences',
                    'permissions',
                    'roles'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals($this->user->email, $data['user']['email']);
        $this->assertContains('admin', $data['roles']);
        $this->assertContains('manage company', $data['permissions']);
    }
    
    public function test_update_user_settings()
    {
        $response = $this->putJson("/api/settings/users/{$this->user->id}", [
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
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User settings updated successfully'
            ]);
        
        $this->user->refresh();
        $this->assertEquals('Updated User Name', $this->user->name);
        $this->assertEquals('en', $this->user->preferences['language']);
        $this->assertTrue($this->user->preferences['notifications']['email']);
    }
    
    public function test_update_user_password()
    {
        $response = $this->putJson("/api/settings/users/{$this->user->id}/password", [
            'current_password' => 'password', // default factory password
            'new_password' => 'NewSecurePassword123!',
            'new_password_confirmation' => 'NewSecurePassword123!'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);
        
        // Verify password was changed
        $this->user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->user->password));
    }
    
    public function test_update_password_with_wrong_current()
    {
        $response = $this->putJson("/api/settings/users/{$this->user->id}/password", [
            'current_password' => 'wrong_password',
            'new_password' => 'NewSecurePassword123!',
            'new_password_confirmation' => 'NewSecurePassword123!'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
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
        
        $response = $this->getJson('/api/settings/integrations');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'integrations' => [
                        '*' => [
                            'id',
                            'type',
                            'name',
                            'is_active',
                            'config',
                            'last_synced_at'
                        ]
                    ],
                    'available_types'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertCount(2, $data['integrations']);
        
        $calcomIntegration = collect($data['integrations'])->firstWhere('type', 'calcom');
        $this->assertEquals('Cal.com Integration', $calcomIntegration['name']);
        $this->assertTrue($calcomIntegration['is_active']);
        $this->assertArrayNotHasKey('api_key', $calcomIntegration['config']); // Should be masked
    }
    
    public function test_update_integration()
    {
        $integration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'stripe',
            'name' => 'Stripe Payment',
            'is_active' => false
        ]);
        
        $response = $this->putJson("/api/settings/integrations/{$integration->id}", [
            'is_active' => true,
            'config' => [
                'api_key' => 'sk_test_newkey123',
                'webhook_secret' => 'whsec_newsecret456'
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Integration updated successfully'
            ]);
        
        $integration->refresh();
        $this->assertTrue($integration->is_active);
    }
    
    public function test_get_notification_settings()
    {
        $response = $this->getJson('/api/settings/notifications');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'channels' => [
                        'email',
                        'sms',
                        'push'
                    ],
                    'templates',
                    'triggers'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertArrayHasKey('appointment_confirmation', $data['templates']);
        $this->assertArrayHasKey('appointment_reminder', $data['templates']);
        $this->assertArrayHasKey('appointment_cancelled', $data['templates']);
    }
    
    public function test_update_notification_settings()
    {
        $response = $this->putJson('/api/settings/notifications', [
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
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification settings updated successfully'
            ]);
    }
    
    public function test_settings_validation()
    {
        // Test invalid timezone
        $response = $this->putJson('/api/settings/company', [
            'settings' => [
                'timezone' => 'Invalid/Timezone'
            ]
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings.timezone']);
        
        // Test invalid currency
        $response = $this->putJson('/api/settings/company', [
            'settings' => [
                'currency' => 'XXX'
            ]
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings.currency']);
    }
    
    public function test_permission_based_access()
    {
        // Create non-admin user
        $staffUser = User::factory()->create(['company_id' => $this->company->id]);
        $staffUser->assignRole('staff');
        
        // Authenticate as staff user
        Sanctum::actingAs($staffUser);
        
        // Try to update company settings without permission
        $response = $this->putJson('/api/settings/company', [
            'name' => 'Should Not Update'
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Unauthorized'
            ]);
    }
    
    public function test_can_only_update_own_user_settings()
    {
        // Create another user
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        
        // Try to update other user's settings
        $response = $this->putJson("/api/settings/users/{$otherUser->id}", [
            'name' => 'Should Not Update'
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'You can only update your own settings'
            ]);
    }
    
    public function test_cross_company_isolation()
    {
        // Create another company with integration
        $otherCompany = Company::factory()->create();
        $otherIntegration = Integration::factory()->create([
            'company_id' => $otherCompany->id,
            'type' => 'calcom',
            'name' => 'Other Company Integration'
        ]);
        
        // Try to access other company's integration
        $response = $this->putJson("/api/settings/integrations/{$otherIntegration->id}", [
            'is_active' => true
        ]);
        
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Integration not found'
            ]);
    }
    
    public function test_unauthorized_access()
    {
        auth()->logout();
        
        $response = $this->getJson('/api/settings/company');
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}