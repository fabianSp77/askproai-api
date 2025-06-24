<?php

namespace Tests\E2E;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * @group e2e
 * @group wizard
 */
class QuickSetupWizardE2ETest extends TestCase
{
    use RefreshDatabase;
    
    private User $admin;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true
        ]);
        
        // Mock external services
        $this->mockCalcomApi();
        $this->mockRetellApi();
    }
    
    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function complete_wizard_flow_creates_all_required_entities()
    {
        $this->actingAs($this->admin);
        
        // Step 1: Company & Branch
        $companyData = [
            'company_name' => 'Test Salon GmbH',
            'industry' => 'salon',
            'branch_name' => 'Hauptfiliale',
            'branch_city' => 'Berlin',
            'branch_address' => 'Teststraße 123',
            'branch_phone' => '+49 30 12345678',
            'branch_features' => ['parking', 'wheelchair']
        ];
        
        // Step 2: Phone Configuration
        $phoneData = [
            'phone_strategy' => 'hotline',
            'hotline_number' => '+49 30 98765432',
            'hotline_greeting' => 'Willkommen bei Test Salon',
            'voice_menu_options' => [
                [
                    'key' => '1',
                    'branch' => 'main',
                    'description' => 'Hauptfiliale Berlin'
                ],
                [
                    'key' => '2', 
                    'branch' => 'branch2',
                    'description' => 'Filiale Hamburg'
                ]
            ],
            'enable_sms' => true,
            'enable_whatsapp' => false
        ];
        
        // Step 3: Cal.com Configuration
        $calcomData = [
            'calcom_connection_type' => 'api_key',
            'calcom_api_key' => 'cal_test_key_123',
            'calcom_team_slug' => 'test-salon',
            'import_event_types' => true
        ];
        
        // Step 4: Retell AI Setup
        $retellData = [
            'phone_setup' => 'new',
            'ai_voice' => 'sarah',
            'use_template_greeting' => true,
            'enable_test_call' => false
        ];
        
        // Step 5: Integration Check
        // This step triggers API tests - mocked above
        
        // Step 6: Services & Staff
        $servicesData = [
            'services' => [
                ['name' => 'Haarschnitt', 'duration' => 30, 'price' => 35],
                ['name' => 'Färben', 'duration' => 90, 'price' => 85],
                ['name' => 'Styling', 'duration' => 45, 'price' => 45]
            ],
            'staff' => [
                [
                    'name' => 'Maria Schmidt',
                    'email' => 'maria@test-salon.de',
                    'languages' => ['de', 'en'],
                    'skills' => ['Colorist', 'Stylist'],
                    'experience_level' => 3,
                    'certifications' => ['Master Colorist 2023']
                ],
                [
                    'name' => 'Tom Weber',
                    'email' => 'tom@test-salon.de', 
                    'languages' => ['de', 'tr'],
                    'skills' => ['Barber', 'Stylist'],
                    'experience_level' => 2,
                    'certifications' => []
                ]
            ]
        ];
        
        // Combine all data
        $wizardData = array_merge(
            $companyData,
            $phoneData,
            $calcomData,
            $retellData,
            $servicesData
        );
        
        // Submit wizard
        $response = $this->post(route('filament.admin.pages.quick-setup-wizard.submit'), [
            'data' => $wizardData
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        
        // Verify company created
        $company = Company::where('name', 'Test Salon GmbH')->first();
        $this->assertNotNull($company);
        $this->assertEquals('salon', $company->industry);
        
        // Verify branch created
        $branch = Branch::where('company_id', $company->id)->first();
        $this->assertNotNull($branch);
        $this->assertEquals('Hauptfiliale', $branch->name);
        $this->assertEquals('Berlin', $branch->city);
        $this->assertContains('parking', $branch->features);
        $this->assertContains('wheelchair', $branch->features);
        
        // Verify phone numbers created
        $phoneNumbers = PhoneNumber::where('company_id', $company->id)->get();
        $this->assertCount(2, $phoneNumbers); // Hotline + Branch
        
        $hotline = $phoneNumbers->where('type', 'hotline')->first();
        $this->assertEquals('+49 30 98765432', $hotline->number);
        $this->assertNotNull($hotline->routing_config);
        
        // Verify services created
        $services = Service::where('company_id', $company->id)->get();
        $this->assertCount(3, $services);
        $this->assertTrue($services->contains('name', 'Haarschnitt'));
        
        // Verify staff created
        $staff = Staff::where('company_id', $company->id)->get();
        $this->assertCount(2, $staff);
        
        $maria = $staff->where('name', 'Maria Schmidt')->first();
        $this->assertContains('de', $maria->languages);
        $this->assertContains('en', $maria->languages);
        $this->assertEquals(3, $maria->experience_level);
        
        // Verify health checks pass
        $healthService = app(\App\Services\HealthCheckService::class);
        $healthService->setCompany($company);
        $report = $healthService->runAll();
        
        $this->assertNotEquals('unhealthy', $report->status);
    }
    
    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function wizard_validates_required_fields_at_each_step()
    {
        $this->actingAs($this->admin);
        
        // Test missing company name
        $response = $this->post(route('filament.admin.pages.quick-setup-wizard.submit'), [
            'data' => [
                'company_name' => '', // Missing required field
                'industry' => 'salon'
            ]
        ]);
        
        $response->assertSessionHasErrors(['data.company_name']);
        
        // Test invalid phone number format
        $response = $this->post(route('filament.admin.pages.quick-setup-wizard.submit'), [
            'data' => [
                'company_name' => 'Test',
                'branch_phone' => 'invalid-phone' // Invalid format
            ]
        ]);
        
        $response->assertSessionHasErrors(['data.branch_phone']);
    }
    
    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function wizard_integration_check_shows_live_api_status()
    {
        $this->actingAs($this->admin);
        
        // Mock failed Cal.com connection
        Http::fake([
            'api.cal.com/*' => Http::response([], 401)
        ]);
        
        $response = $this->post(route('filament.admin.pages.quick-setup-wizard.test-calcom'), [
            'data' => [
                'calcom_api_key' => 'invalid_key'
            ]
        ]);
        
        $response->assertJson([
            'success' => false,
            'error' => 'Verbindung fehlgeschlagen. Bitte überprüfen Sie Ihren API Key.'
        ]);
        
        // Mock successful connection
        Http::fake([
            'api.cal.com/v2/me' => Http::response([
                'user' => [
                    'name' => 'Test User',
                    'email' => 'test@cal.com'
                ]
            ])
        ]);
        
        $response = $this->post(route('filament.admin.pages.quick-setup-wizard.test-calcom'), [
            'data' => [
                'calcom_api_key' => 'valid_key'
            ]
        ]);
        
        $response->assertJson([
            'success' => true,
            'user' => [
                'name' => 'Test User',
                'email' => 'test@cal.com'
            ]
        ]);
    }
    
    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function wizard_creates_industry_specific_prompt_template()
    {
        $this->actingAs($this->admin);
        
        // Create company with medical industry
        $wizardData = $this->getBaseWizardData();
        $wizardData['industry'] = 'medical';
        $wizardData['company_name'] = 'Praxis Dr. Test';
        
        $response = $this->post(route('filament.admin.pages.quick-setup-wizard.submit'), [
            'data' => $wizardData
        ]);
        
        $response->assertRedirect();
        
        $company = Company::where('name', 'Praxis Dr. Test')->first();
        $branch = $company->branches->first();
        
        // Generate prompt
        $promptService = app(\App\Services\PromptTemplateService::class);
        $prompt = $promptService->renderPrompt($branch, 'medical');
        
        // Verify medical-specific content
        $this->assertStringContainsString('medizinische Fachangestellte', $prompt);
        $this->assertStringContainsString('Notfälle', $prompt);
        $this->assertStringContainsString('Versichertenkarte', $prompt);
        $this->assertStringNotContainsString('Haarschnitt', $prompt); // Salon content
    }
    
    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function wizard_review_step_shows_health_check_status()
    {
        $this->actingAs($this->admin);
        
        // Create partial setup
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        
        // No phone numbers - should show unhealthy
        $response = $this->get(route('filament.admin.pages.quick-setup-wizard.health-check', [
            'company_id' => $company->id
        ]));
        
        $response->assertOk();
        $response->assertSee('Phone Routing');
        $response->assertSee('unhealthy');
        $response->assertSee('Keine Telefonnummern konfiguriert');
    }
    
    /**
     * Helper method to get base wizard data
     */
    private function getBaseWizardData(): array
    {
        return [
            'company_name' => 'Test Company',
            'industry' => 'generic',
            'branch_name' => 'Main Branch',
            'branch_city' => 'Berlin',
            'branch_phone' => '+49 30 12345678',
            'phone_strategy' => 'direct',
            'calcom_connection_type' => 'api_key',
            'calcom_api_key' => 'test_key',
            'ai_voice' => 'sarah',
            'services' => [
                ['name' => 'Service 1', 'duration' => 30, 'price' => 50]
            ],
            'staff' => [
                ['name' => 'Staff 1', 'email' => 'staff1@test.com']
            ]
        ];
    }
    
    /**
     * Mock Cal.com API responses
     */
    private function mockCalcomApi(): void
    {
        Http::fake([
            'api.cal.com/v2/me' => Http::response([
                'user' => [
                    'id' => 1,
                    'name' => 'Test User',
                    'email' => 'test@cal.com'
                ]
            ]),
            'api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'title' => 'Test Event',
                        'slug' => 'test-event',
                        'length' => 30
                    ]
                ]
            ])
        ]);
    }
    
    /**
     * Mock Retell API responses
     */
    private function mockRetellApi(): void
    {
        Http::fake([
            'api.retellai.com/v1/agents' => Http::response([
                [
                    'agent_id' => 'test_agent_123',
                    'agent_name' => 'Test Agent',
                    'voice_id' => 'sarah'
                ]
            ]),
            'api.retellai.com/v1/phone-numbers' => Http::response([
                [
                    'phone_number' => '+49 30 12345678',
                    'status' => 'active'
                ]
            ])
        ]);
    }
}