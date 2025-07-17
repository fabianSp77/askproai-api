<?php

namespace Tests\Unit\Mail;

use Tests\TestCase;
use App\Mail\CallSummaryEmail;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Mail;

class CallSummaryEmailTest extends TestCase
{
    private Call $call;
    private Company $company;
    private Branch $branch;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'logo_url' => 'https://example.com/logo.png',
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Branch',
            'phone' => '+499876543210',
            'email' => 'branch@example.com',
        ]);
        
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+491234567890',
        ]);
        
        $this->call = Call::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'call_id' => 'call_test_123',
            'from_number' => $this->customer->phone,
            'to_number' => $this->branch->phone,
            'duration' => 120,
            'transcript' => 'Test transcript content',
            'summary' => 'Test call summary',
            'recording_url' => 'https://example.com/recording.mp3',
            'created_at' => now(),
        ]);
    }

    /** @test */
    public function it_creates_call_summary_email_with_correct_data()
    {
        // Arrange & Act
        $mailable = new CallSummaryEmail($this->call);

        // Assert
        $this->assertEquals('Call Summary - ' . $this->customer->name, $mailable->subject);
        $this->assertEquals($this->call->id, $mailable->call->id);
        $this->assertContains($this->customer->email, $mailable->to);
    }

    /** @test */
    public function it_renders_email_content_correctly()
    {
        // Arrange
        $mailable = new CallSummaryEmail($this->call);

        // Act
        $rendered = $mailable->render();

        // Assert
        $this->assertStringContainsString($this->customer->name, $rendered);
        $this->assertStringContainsString($this->call->summary, $rendered);
        $this->assertStringContainsString('120', $rendered); // Duration
        $this->assertStringContainsString($this->call->created_at->format('d.m.Y'), $rendered);
    }

    /** @test */
    public function it_includes_recording_link_when_available()
    {
        // Arrange
        $mailable = new CallSummaryEmail($this->call);

        // Act
        $rendered = $mailable->render();

        // Assert
        $this->assertStringContainsString('Recording', $rendered);
        $this->assertStringContainsString($this->call->recording_url, $rendered);
    }

    /** @test */
    public function it_does_not_include_recording_link_when_not_available()
    {
        // Arrange
        $this->call->update(['recording_url' => null]);
        $mailable = new CallSummaryEmail($this->call);

        // Act
        $rendered = $mailable->render();

        // Assert
        $this->assertStringNotContainsString('Recording', $rendered);
    }

    /** @test */
    public function it_includes_company_branding()
    {
        // Arrange
        $mailable = new CallSummaryEmail($this->call);

        // Act
        $rendered = $mailable->render();

        // Assert
        $this->assertStringContainsString($this->company->name, $rendered);
        $this->assertStringContainsString($this->company->logo_url, $rendered);
    }

    /** @test */
    public function it_attaches_transcript_as_text_file()
    {
        // Arrange
        $mailable = new CallSummaryEmail($this->call);

        // Act
        $attachments = $mailable->attachments();

        // Assert
        $this->assertCount(1, $attachments);
        $this->assertStringContainsString('transcript', $attachments[0]['name']);
        $this->assertEquals('text/plain', $attachments[0]['mime']);
    }

    /** @test */
    public function it_sends_email_successfully()
    {
        // Arrange
        Mail::fake();

        // Act
        Mail::to($this->customer->email)->send(new CallSummaryEmail($this->call));

        // Assert
        Mail::assertSent(CallSummaryEmail::class, function ($mail) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->call->id === $this->call->id;
        });
    }

    /** @test */
    public function it_uses_customer_preferred_language()
    {
        // Arrange
        $this->customer->update(['language' => 'de']);
        $mailable = new CallSummaryEmail($this->call);

        // Act
        $mailable->locale('de');
        $rendered = $mailable->render();

        // Assert
        $this->assertStringContainsString('Anrufzusammenfassung', $rendered);
    }

    /** @test */
    public function it_includes_action_items_when_available()
    {
        // Arrange
        $this->call->update([
            'action_items' => [
                'Send quote for service',
                'Schedule follow-up call',
            ],
        ]);
        $mailable = new CallSummaryEmail($this->call);

        // Act
        $rendered = $mailable->render();

        // Assert
        $this->assertStringContainsString('Action Items', $rendered);
        $this->assertStringContainsString('Send quote for service', $rendered);
        $this->assertStringContainsString('Schedule follow-up call', $rendered);
    }

    /** @test */
    public function it_formats_call_duration_correctly()
    {
        // Test various durations
        $testCases = [
            30 => '0:30',
            90 => '1:30',
            3600 => '60:00',
            3665 => '61:05',
        ];

        foreach ($testCases as $seconds => $expected) {
            $this->call->update(['duration' => $seconds]);
            $mailable = new CallSummaryEmail($this->call);
            $rendered = $mailable->render();
            
            $this->assertStringContainsString($expected, $rendered);
        }
    }
}