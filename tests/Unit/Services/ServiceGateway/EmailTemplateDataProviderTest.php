<?php

namespace Tests\Unit\Services\ServiceGateway;

use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Services\ServiceGateway\EmailTemplateDataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateDataProviderTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create();
    }

    public function test_source_variable_returns_translated_label(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'source' => 'voice',
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Telefonanruf', $variables['source']);
    }

    public function test_case_type_variable_returns_translated_label(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'case_type' => 'incident',
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Incident', $variables['case_type']);
    }

    public function test_category_variable_returns_category_name(): void
    {
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Technical Support',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Technical Support', $variables['category']);
    }

    public function test_category_variable_returns_empty_string_when_null(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['category']);
    }

    public function test_source_variable_handles_different_source_types(): void
    {
        $sources = [
            'voice' => 'Telefonanruf',
            'email' => 'E-Mail',
            'web' => 'Web-Formular',
            'chat' => 'Chat',
        ];

        foreach ($sources as $source => $expectedLabel) {
            $case = ServiceCase::factory()->create([
                'company_id' => $this->company->id,
                'source' => $source,
            ]);

            $provider = new EmailTemplateDataProvider($case);
            $variables = $provider->getVariables();

            $this->assertEquals($expectedLabel, $variables['source'], "Failed for source: {$source}");
        }
    }

    public function test_case_type_variable_handles_all_types(): void
    {
        $caseTypes = [
            'incident' => 'Incident',
            'request' => 'Anfrage',
            'inquiry' => 'Anfrage (allgemein)',
        ];

        foreach ($caseTypes as $caseType => $expectedLabel) {
            $case = ServiceCase::factory()->create([
                'company_id' => $this->company->id,
                'case_type' => $caseType,
            ]);

            $provider = new EmailTemplateDataProvider($case);
            $variables = $provider->getVariables();

            $this->assertEquals($expectedLabel, $variables['case_type'], "Failed for case_type: {$caseType}");
        }
    }

    public function test_called_company_name_variable_returns_company_name(): void
    {
        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hauptfiliale Berlin',
        ]);

        $phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'number' => '+493012345678',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number_id' => $phoneNumber->id,
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals($this->company->name, $variables['called_company_name']);
    }

    public function test_called_branch_name_variable_returns_branch_name(): void
    {
        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Zentrale München',
        ]);

        $phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'number' => '+498912345678',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number_id' => $phoneNumber->id,
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Zentrale München', $variables['called_branch_name']);
    }

    public function test_service_number_formatted_variable_returns_formatted_number(): void
    {
        $phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
            'number' => '+493012345678',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number_id' => $phoneNumber->id,
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // The formatted_number accessor should return a formatted version
        $this->assertNotEmpty($variables['service_number_formatted']);
    }

    public function test_receiver_display_combines_branch_and_phone(): void
    {
        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Zentrale Berlin',
        ]);

        $phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'number' => '+493012345678',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number_id' => $phoneNumber->id,
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // Should be in format "Branch - Phone"
        $this->assertStringContainsString('Zentrale Berlin', $variables['receiver_display']);
        $this->assertStringContainsString(' - ', $variables['receiver_display']);
    }

    public function test_call_variables_return_empty_string_when_no_call(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['called_company_name']);
        $this->assertEquals('', $variables['called_branch_name']);
        $this->assertEquals('', $variables['service_number_formatted']);
        $this->assertEquals('', $variables['receiver_display']);
    }

    public function test_receiver_display_handles_missing_branch(): void
    {
        $phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'number' => '+493012345678',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number_id' => $phoneNumber->id,
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // Should only show phone number when branch is missing
        $this->assertNotEmpty($variables['receiver_display']);
        $this->assertStringNotContainsString(' - ', $variables['receiver_display']);
    }

    public function test_audio_url_variable_generates_signed_url(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'audio_object_key' => 'recordings/test-audio-file.mp3',
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertNotEmpty($variables['audio_url']);
        $this->assertStringContainsString('/audio/', $variables['audio_url']);
        $this->assertStringContainsString('signature=', $variables['audio_url']);
    }

    public function test_audio_url_variable_returns_empty_when_no_audio(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'audio_object_key' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['audio_url']);
    }

    public function test_has_audio_variable_returns_ja_when_audio_present(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'audio_object_key' => 'recordings/test-audio.mp3',
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Ja', $variables['has_audio']);
    }

    public function test_has_audio_variable_returns_nein_when_no_audio(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'audio_object_key' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Nein', $variables['has_audio']);
    }

    public function test_audio_duration_variable_formats_as_minutes_seconds(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration_sec' => 154, // 2 minutes 34 seconds
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('2:34', $variables['audio_duration']);
    }

    public function test_audio_duration_variable_formats_single_digit_seconds_with_leading_zero(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration_sec' => 65, // 1 minute 5 seconds
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('1:05', $variables['audio_duration']);
    }

    public function test_audio_duration_variable_returns_empty_when_no_call(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['audio_duration']);
    }

    public function test_audio_duration_variable_returns_empty_when_no_duration(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration_sec' => null,
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['audio_duration']);
    }

    public function test_sla_response_due_variable_formats_with_berlin_timezone(): void
    {
        $dueAt = now()->addHours(2);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => $dueAt,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $expectedFormat = $dueAt->setTimezone('Europe/Berlin')->format('d.m.Y H:i');
        $this->assertEquals($expectedFormat, $variables['sla_response_due']);
    }

    public function test_sla_resolution_due_variable_formats_with_berlin_timezone(): void
    {
        $dueAt = now()->addHours(24);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_resolution_due_at' => $dueAt,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $expectedFormat = $dueAt->setTimezone('Europe/Berlin')->format('d.m.Y H:i');
        $this->assertEquals($expectedFormat, $variables['sla_resolution_due']);
    }

    public function test_sla_due_variables_return_empty_when_no_sla_configured(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => null,
            'sla_resolution_due_at' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['sla_response_due']);
        $this->assertEquals('', $variables['sla_resolution_due']);
    }

    public function test_is_response_overdue_returns_ja_when_overdue(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => now()->subHours(1), // 1 hour ago
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Ja', $variables['is_response_overdue']);
    }

    public function test_is_response_overdue_returns_nein_when_not_overdue(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => now()->addHours(1), // 1 hour from now
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Nein', $variables['is_response_overdue']);
    }

    public function test_is_resolution_overdue_returns_ja_when_overdue(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_resolution_due_at' => now()->subHours(2), // 2 hours ago
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Ja', $variables['is_resolution_overdue']);
    }

    public function test_is_resolution_overdue_returns_nein_when_not_overdue(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_resolution_due_at' => now()->addHours(2), // 2 hours from now
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Nein', $variables['is_resolution_overdue']);
    }

    public function test_is_overdue_returns_ja_when_response_overdue(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => now()->subHours(1), // Response overdue
            'sla_resolution_due_at' => now()->addHours(2), // Resolution not overdue
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Ja', $variables['is_overdue']);
    }

    public function test_is_overdue_returns_ja_when_resolution_overdue(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => now()->addHours(1), // Response not overdue
            'sla_resolution_due_at' => now()->subHours(1), // Resolution overdue
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Ja', $variables['is_overdue']);
    }

    public function test_is_overdue_returns_nein_when_neither_overdue(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => now()->addHours(1),
            'sla_resolution_due_at' => now()->addHours(2),
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Nein', $variables['is_overdue']);
    }

    public function test_is_at_risk_returns_ja_when_less_than_30_minutes_until_due(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => now()->addMinutes(20), // 20 minutes from now
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Ja', $variables['is_at_risk']);
    }

    public function test_is_at_risk_returns_nein_when_more_than_30_minutes_until_due(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => now()->addMinutes(45), // 45 minutes from now
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Nein', $variables['is_at_risk']);
    }

    public function test_is_at_risk_returns_nein_when_already_overdue(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => now()->subMinutes(10), // 10 minutes ago
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Nein', $variables['is_at_risk']);
    }

    public function test_is_at_risk_returns_nein_when_no_sla_configured(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'sla_response_due_at' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Nein', $variables['is_at_risk']);
    }

    public function test_call_duration_variable_formats_as_minutes_seconds(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration_sec' => 154, // 2 minutes 34 seconds
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('2:34', $variables['call_duration']);
    }

    public function test_call_duration_variable_formats_with_leading_zero(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration_sec' => 65, // 1 minute 5 seconds
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('1:05', $variables['call_duration']);
    }

    public function test_call_duration_variable_returns_empty_when_no_call(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['call_duration']);
    }

    public function test_caller_number_variable_formats_german_numbers_with_spaces(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4930123456',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('+49 30 123456', $variables['caller_number']);
    }

    public function test_caller_number_variable_handles_non_german_numbers(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+1234567890',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // Should return original number if not German format
        $this->assertEquals('+1234567890', $variables['caller_number']);
    }

    public function test_caller_number_variable_returns_empty_when_no_call(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['caller_number']);
    }

    public function test_service_number_variable_formats_german_numbers_with_spaces(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'to_number' => '+4989654321',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('+49 89 654321', $variables['service_number']);
    }

    public function test_service_number_variable_returns_empty_when_no_call(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['service_number']);
    }

    public function test_format_phone_number_cleans_input(): void
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+49 (30) 123-456', // Number with formatting characters
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => $call->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // Should clean and reformat
        $this->assertEquals('+49 30 123456', $variables['caller_number']);
    }

    public function test_ai_summary_variable_from_ai_summary_field(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => [
                'ai_summary' => 'Customer reports connectivity issue with VPN',
            ],
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Customer reports connectivity issue with VPN', $variables['ai_summary']);
    }

    public function test_ai_summary_variable_fallback_to_summary_field(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => [
                'summary' => 'Fallback summary text',
            ],
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Fallback summary text', $variables['ai_summary']);
    }

    public function test_ai_summary_prefers_ai_summary_over_summary(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => [
                'ai_summary' => 'Primary AI summary',
                'summary' => 'Fallback summary',
            ],
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Primary AI summary', $variables['ai_summary']);
    }

    public function test_ai_confidence_variable_formats_as_percentage(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => [
                'confidence' => 0.95,
            ],
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('95%', $variables['ai_confidence']);
    }

    public function test_ai_confidence_variable_rounds_to_integer(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => [
                'confidence' => 0.8763,
            ],
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('88%', $variables['ai_confidence']);
    }

    public function test_customer_location_variable_from_ai_metadata(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => [
                'customer_location' => 'Berlin Office, 3rd floor',
            ],
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('Berlin Office, 3rd floor', $variables['customer_location']);
    }

    public function test_problem_since_variable_from_ai_metadata(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => [
                'problem_since' => 'gestern Morgen',
            ],
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('gestern Morgen', $variables['problem_since']);
    }

    public function test_ai_variables_return_empty_strings_when_no_metadata(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['ai_summary']);
        $this->assertEquals('', $variables['ai_confidence']);
        $this->assertEquals('', $variables['customer_location']);
        $this->assertEquals('', $variables['problem_since']);
    }

    public function test_ai_variables_return_empty_strings_when_metadata_empty_array(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'ai_metadata' => [],
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['ai_summary']);
        $this->assertEquals('', $variables['ai_confidence']);
        $this->assertEquals('', $variables['customer_location']);
        $this->assertEquals('', $variables['problem_since']);
    }

    public function test_admin_url_variable_generates_signed_route(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // Assert admin_url is not empty
        $this->assertNotEmpty($variables['admin_url']);

        // Assert admin_url contains the correct route
        $this->assertStringContainsString('filament/admin/service-cases/'.$case->id, $variables['admin_url']);

        // Assert admin_url contains signature parameter
        $this->assertStringContainsString('signature=', $variables['admin_url']);
    }

    public function test_admin_url_signature_is_valid_for_72_hours(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $adminUrl = $variables['admin_url'];

        // Assert URL is not empty
        $this->assertNotEmpty($adminUrl);

        // Parse URL to extract signature components
        $parsedUrl = parse_url($adminUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        // Assert signature components exist
        $this->assertArrayHasKey('signature', $queryParams);
        $this->assertArrayHasKey('expires', $queryParams);

        // Assert expiration is approximately 72 hours from now (allow 1 minute tolerance)
        $expectedExpires = now()->addHours(72)->timestamp;
        $actualExpires = (int) $queryParams['expires'];

        $this->assertEqualsWithDelta($expectedExpires, $actualExpires, 60);
    }

    public function test_transcript_variable_formats_segments_correctly(): void
    {
        $callSession = \App\Models\RetellCallSession::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create transcript segments
        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 1,
            'role' => 'agent',
            'text' => 'Guten Tag, wie kann ich helfen?',
            'occurred_at' => now(),
        ]);

        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 2,
            'role' => 'user',
            'text' => 'Ich habe ein Problem mit meinem Computer.',
            'occurred_at' => now(),
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_session_id' => $callSession->id,
        ]);

        $case->load('callSession.transcriptSegments');

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $expectedTranscript = "Agent: Guten Tag, wie kann ich helfen?\n".
                             "Kunde: Ich habe ein Problem mit meinem Computer.\n";

        $this->assertEquals($expectedTranscript, $variables['transcript']);
        $this->assertEquals('Nein', $variables['transcript_truncated']);
    }

    public function test_transcript_variable_truncates_at_10000_characters(): void
    {
        $callSession = \App\Models\RetellCallSession::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create a segment with text that will exceed 10,000 characters when formatted
        // Each line is "Agent: " (7 chars) + text + "\n" (1 char)
        // Need 10,001 total chars to trigger truncation
        // 10,001 - 8 (prefix + newline) = 9,993 chars of text
        $longText = str_repeat('A', 9993);

        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 1,
            'role' => 'agent',
            'text' => $longText,
            'occurred_at' => now(),
        ]);

        // Add one more character to push over the limit
        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 2,
            'role' => 'user',
            'text' => 'X',
            'occurred_at' => now(),
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_session_id' => $callSession->id,
        ]);

        $case->load('callSession.transcriptSegments');

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // Should be truncated to 10,000 chars + '...'
        $this->assertEquals(10003, strlen($variables['transcript'])); // 10000 + '...'
        $this->assertStringEndsWith('...', $variables['transcript']);
        $this->assertEquals('Ja', $variables['transcript_truncated']);
    }

    public function test_transcript_length_variable_formats_correctly(): void
    {
        $callSession = \App\Models\RetellCallSession::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create segments totaling a known length
        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 1,
            'role' => 'agent',
            'text' => 'Test', // 4 chars
            'occurred_at' => now(),
        ]);

        // "Agent: Test\n" = 12 chars

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_session_id' => $callSession->id,
        ]);

        $case->load('callSession.transcriptSegments');

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // Should be formatted with German thousands separator
        $this->assertEquals('12 Zeichen', $variables['transcript_length']);
    }

    public function test_transcript_length_shows_original_length_even_when_truncated(): void
    {
        $callSession = \App\Models\RetellCallSession::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create long transcript (15,000 chars formatted)
        $longText = str_repeat('A', 14990); // 14990 + "Agent: " + "\n" = 14998

        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 1,
            'role' => 'agent',
            'text' => $longText,
            'occurred_at' => now(),
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_session_id' => $callSession->id,
        ]);

        $case->load('callSession.transcriptSegments');

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // transcript_length should show original length formatted with German thousands separator
        $this->assertEquals('14.998 Zeichen', $variables['transcript_length']);
        $this->assertEquals('Ja', $variables['transcript_truncated']);
    }

    public function test_transcript_variables_return_empty_when_no_call_session(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_session_id' => null,
        ]);

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['transcript']);
        $this->assertEquals('Nein', $variables['transcript_truncated']);
        $this->assertEquals('', $variables['transcript_length']);
    }

    public function test_transcript_variables_return_empty_when_no_segments(): void
    {
        $callSession = \App\Models\RetellCallSession::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_session_id' => $callSession->id,
        ]);

        $case->load('callSession.transcriptSegments');

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        $this->assertEquals('', $variables['transcript']);
        $this->assertEquals('Nein', $variables['transcript_truncated']);
        $this->assertEquals('', $variables['transcript_length']);
    }

    public function test_transcript_segments_ordered_by_sequence(): void
    {
        $callSession = \App\Models\RetellCallSession::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create segments in wrong order intentionally
        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 3,
            'role' => 'user',
            'text' => 'Third',
            'occurred_at' => now(),
        ]);

        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 1,
            'role' => 'agent',
            'text' => 'First',
            'occurred_at' => now(),
        ]);

        \App\Models\RetellTranscriptSegment::create([
            'call_session_id' => $callSession->id,
            'segment_sequence' => 2,
            'role' => 'user',
            'text' => 'Second',
            'occurred_at' => now(),
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_session_id' => $callSession->id,
        ]);

        $case->load('callSession.transcriptSegments');

        $provider = new EmailTemplateDataProvider($case);
        $variables = $provider->getVariables();

        // Should be ordered by segment_sequence
        $expectedTranscript = "Agent: First\n".
                             "Kunde: Second\n".
                             "Kunde: Third\n";

        $this->assertEquals($expectedTranscript, $variables['transcript']);
    }
}
