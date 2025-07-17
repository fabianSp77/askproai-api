<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\SendCallSummaryEmailJob;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Company;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class SendCallSummaryEmailJobTest extends TestCase
{
    private Call $call;
    private Customer $customer;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create([
            'call_summary_email_enabled' => true,
        ]);
        
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'customer@example.com',
        ]);
        
        $this->call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'duration' => 120,
            'transcript' => 'Test transcript',
            'summary' => 'Test summary',
        ]);
    }

    /** @test */
    public function it_queues_job_on_correct_queue()
    {
        // Arrange
        Queue::fake();

        // Act
        SendCallSummaryEmailJob::dispatch($this->call);

        // Assert
        Queue::assertPushed(SendCallSummaryEmailJob::class, function ($job) {
            return $job->queue === 'emails';
        });
    }

    /** @test */
    public function it_sends_call_summary_email()
    {
        // Arrange
        Mail::fake();
        $job = new SendCallSummaryEmailJob($this->call);

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(CallSummaryEmail::class, function ($mail) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->call->id === $this->call->id;
        });
    }

    /** @test */
    public function it_does_not_send_email_when_feature_disabled()
    {
        // Arrange
        Mail::fake();
        $this->company->update(['call_summary_email_enabled' => false]);
        $job = new SendCallSummaryEmailJob($this->call);

        // Act
        $job->handle();

        // Assert
        Mail::assertNothingSent();
    }

    /** @test */
    public function it_does_not_send_email_when_customer_has_no_email()
    {
        // Arrange
        Mail::fake();
        $this->customer->update(['email' => null]);
        $job = new SendCallSummaryEmailJob($this->call);

        // Act
        $job->handle();

        // Assert
        Mail::assertNothingSent();
    }

    /** @test */
    public function it_marks_email_as_sent_after_successful_delivery()
    {
        // Arrange
        Mail::fake();
        $job = new SendCallSummaryEmailJob($this->call);

        // Act
        $job->handle();

        // Assert
        $this->assertTrue($this->call->fresh()->summary_email_sent);
        $this->assertNotNull($this->call->fresh()->summary_email_sent_at);
    }

    /** @test */
    public function it_handles_email_sending_failure()
    {
        // Arrange
        Mail::shouldReceive('to->send')->andThrow(new \Exception('SMTP error'));
        Log::shouldReceive('error')->once();
        
        $job = new SendCallSummaryEmailJob($this->call);

        // Act & Assert
        $this->expectException(\Exception::class);
        $job->handle();
        
        // Email should not be marked as sent
        $this->assertFalse($this->call->fresh()->summary_email_sent);
    }

    /** @test */
    public function it_has_correct_retry_configuration()
    {
        // Arrange
        $job = new SendCallSummaryEmailJob($this->call);

        // Assert
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff());
    }

    /** @test */
    public function it_implements_should_be_unique()
    {
        // Arrange
        $job = new SendCallSummaryEmailJob($this->call);

        // Assert
        $this->assertEquals($this->call->id, $job->uniqueId());
    }

    /** @test */
    public function it_does_not_send_duplicate_emails()
    {
        // Arrange
        Mail::fake();
        $this->call->update([
            'summary_email_sent' => true,
            'summary_email_sent_at' => now(),
        ]);
        
        $job = new SendCallSummaryEmailJob($this->call);

        // Act
        $job->handle();

        // Assert
        Mail::assertNothingSent();
    }

    /** @test */
    public function it_respects_customer_email_preferences()
    {
        // Arrange
        Mail::fake();
        $this->customer->update(['email_notifications_enabled' => false]);
        
        $job = new SendCallSummaryEmailJob($this->call);

        // Act
        $job->handle();

        // Assert
        Mail::assertNothingSent();
    }

    /** @test */
    public function it_logs_successful_email_delivery()
    {
        // Arrange
        Mail::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('Call summary email sent', [
                'call_id' => $this->call->id,
                'customer_id' => $this->customer->id,
                'email' => $this->customer->email,
            ]);
        
        $job = new SendCallSummaryEmailJob($this->call);

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(CallSummaryEmail::class);
    }

    /** @test */
    public function it_handles_deleted_call_gracefully()
    {
        // Arrange
        Mail::fake();
        $callId = $this->call->id;
        $this->call->delete();
        
        $job = new SendCallSummaryEmailJob(Call::withTrashed()->find($callId));

        // Act
        $job->handle();

        // Assert
        Mail::assertNothingSent();
    }
}