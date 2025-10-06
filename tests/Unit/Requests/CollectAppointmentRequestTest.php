<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\CollectAppointmentRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Unit Test: CollectAppointmentRequest E-Mail Sanitization
 *
 * Testet dass prepareForValidation() E-Mails mit Leerzeichen BEFORE validation bereinigt
 * KRITISCH: Verhindert dass Speech-to-Text Fehler die Funktion blockieren
 */
class CollectAppointmentRequestTest extends TestCase
{
    /**
     * Test: E-Mail mit Leerzeichen wird VOR Validation bereinigt
     *
     * @test
     */
    public function email_with_spaces_is_sanitized_before_validation()
    {
        // Simulate Retell webhook data with problematic email (spaces from speech-to-text)
        $requestData = [
            'args' => [
                'datum' => 'heute',
                'uhrzeit' => '17:00',
                'name' => 'Test User',
                'email' => 'Fub Handy@Gmail.com',  // ← Space from Retell!
                'dienstleistung' => 'Beratung'
            ]
        ];

        // Create request instance
        $request = CollectAppointmentRequest::create('/test', 'POST', $requestData);

        // Manually trigger prepareForValidation (normally done automatically)
        $request->setContainer($this->app);
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        // Get the email AFTER preparation
        $sanitizedEmail = $request->input('args.email');

        // ASSERT: Spaces should be removed
        $this->assertEquals(
            'FubHandy@Gmail.com',
            $sanitizedEmail,
            'prepareForValidation() should remove spaces from email BEFORE validation'
        );

        // ASSERT: Email should now be valid
        $validator = Validator::make($request->all(), $request->rules());
        $this->assertFalse(
            $validator->fails(),
            'Validation should pass after prepareForValidation() removes spaces. Errors: ' .
            json_encode($validator->errors()->toArray())
        );
    }

    /**
     * Test: Multiple E-Mail test cases
     *
     * @test
     */
    public function various_email_formats_are_sanitized_correctly()
    {
        $testCases = [
            ['input' => 'Fub Handy@Gmail.com', 'expected' => 'FubHandy@Gmail.com'],
            ['input' => 'Ab Handy@Gmail.com', 'expected' => 'AbHandy@Gmail.com'],
            ['input' => 'test user@example.com', 'expected' => 'testuser@example.com'],
            ['input' => 'normal@email.com', 'expected' => 'normal@email.com'],
            ['input' => 'max  mustermann@test.de', 'expected' => 'maxmustermann@test.de'],
        ];

        foreach ($testCases as $testCase) {
            $requestData = [
                'args' => [
                    'email' => $testCase['input']
                ]
            ];

            $request = CollectAppointmentRequest::create('/test', 'POST', $requestData);
            $request->setContainer($this->app);

            // Trigger prepareForValidation
            $reflection = new \ReflectionClass($request);
            $method = $reflection->getMethod('prepareForValidation');
            $method->setAccessible(true);
            $method->invoke($request);

            $sanitizedEmail = $request->input('args.email');

            $this->assertEquals(
                $testCase['expected'],
                $sanitizedEmail,
                "Failed for input: '{$testCase['input']}'"
            );

            // Verify validation passes
            $validator = Validator::make($request->all(), $request->rules());
            $this->assertFalse(
                $validator->fails(),
                "Validation failed for: '{$testCase['input']}' → '{$sanitizedEmail}'"
            );
        }
    }
}
