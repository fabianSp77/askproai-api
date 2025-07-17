<?php

namespace Tests\Performance;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Jobs\SendCallSummaryEmailJob;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailPerformanceTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private NotificationService $notificationService;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->notificationService = app(NotificationService::class);
        $this->company = Company::factory()->create();
    }

    /** @test */
    #[Test]
    public function it_can_send_bulk_emails_efficiently()
    {
        // Arrange
        Mail::fake();
        Queue::fake();
        
        // Create 1000 customers
        $customers = Customer::factory()->count(1000)->create([
            'company_id' => $this->company->id,
        ]);
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Act - Queue emails for all customers
        foreach ($customers->chunk(100) as $chunk) {
            foreach ($chunk as $customer) {
                $call = Call::factory()->create([
                    'company_id' => $this->company->id,
                    'customer_id' => $customer->id,
                ]);
                
                SendCallSummaryEmailJob::dispatch($call);
            }
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Assert
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        $this->assertLessThan(5000, $executionTime, 'Bulk email queueing took more than 5 seconds');
        $this->assertLessThan(50, $memoryUsed, 'Bulk email queueing used more than 50MB of memory');
        
        Queue::assertPushed(SendCallSummaryEmailJob::class, 1000);
    }

    /** @test */
    #[Test]
    public function it_handles_appointment_reminders_efficiently()
    {
        // Arrange
        Mail::fake();
        
        // Create appointments at various reminder intervals
        $appointmentCount = 500;
        $now = Carbon::now();
        
        // Create appointments needing 24h reminders
        Appointment::factory()->count($appointmentCount / 3)->create([
            'company_id' => $this->company->id,
            'appointment_datetime' => $now->copy()->addHours(24),
            'reminder_24h_sent' => false,
        ]);
        
        // Create appointments needing 2h reminders
        Appointment::factory()->count($appointmentCount / 3)->create([
            'company_id' => $this->company->id,
            'appointment_datetime' => $now->copy()->addHours(2),
            'reminder_2h_sent' => false,
        ]);
        
        // Create appointments needing 30min reminders
        Appointment::factory()->count($appointmentCount / 3)->create([
            'company_id' => $this->company->id,
            'appointment_datetime' => $now->copy()->addMinutes(30),
            'reminder_30min_sent' => false,
        ]);

        $startTime = microtime(true);
        DB::enableQueryLog();

        // Act
        $this->notificationService->sendAppointmentReminders();

        $endTime = microtime(true);
        $queries = count(DB::getQueryLog());

        // Assert
        $executionTime = ($endTime - $startTime) * 1000;
        
        $this->assertLessThan(3000, $executionTime, 'Reminder processing took more than 3 seconds');
        $this->assertLessThan(50, $queries, 'Too many database queries executed');
    }

    /** @test */
    #[Test]
    public function it_renders_email_templates_quickly()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $mailable = new \App\Mail\AppointmentConfirmationMail($appointment);
        
        $iterations = 100;
        $totalTime = 0;

        // Act - Render template multiple times
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $rendered = $mailable->render();
            $endTime = microtime(true);
            
            $totalTime += ($endTime - $startTime) * 1000;
        }

        $averageTime = $totalTime / $iterations;

        // Assert
        $this->assertLessThan(50, $averageTime, 'Average template rendering time exceeds 50ms');
        $this->assertNotEmpty($rendered);
    }

    /** @test */
    #[Test]
    public function it_processes_queued_emails_concurrently()
    {
        // This test would typically be run with actual queue workers
        // Here we simulate the behavior
        
        // Arrange
        $jobs = [];
        for ($i = 0; $i < 100; $i++) {
            $call = Call::factory()->create([
                'company_id' => $this->company->id,
            ]);
            $jobs[] = new SendCallSummaryEmailJob($call);
        }

        $startTime = microtime(true);

        // Act - Simulate concurrent processing
        $chunks = array_chunk($jobs, 10); // 10 jobs per worker
        foreach ($chunks as $chunk) {
            // In real scenario, these would be processed by different workers
            foreach ($chunk as $job) {
                // Simulate job processing
                try {
                    Mail::fake();
                    $job->handle();
                } catch (\Exception $e) {
                    // Handle failure
                }
            }
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertLessThan(5000, $totalTime, 'Queue processing took more than 5 seconds');
    }

    /** @test */
    #[Test]
    public function it_efficiently_queries_email_logs()
    {
        // Arrange - Create email log entries
        DB::table('email_logs')->insert(
            array_map(function ($i) {
                return [
                    'company_id' => $this->company->id,
                    'recipient' => "user{$i}@example.com",
                    'subject' => 'Test Email',
                    'type' => 'appointment_confirmation',
                    'status' => 'sent',
                    'sent_at' => now()->subDays(rand(1, 30)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, range(1, 10000))
        );

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Act - Query logs with various filters
        $results = DB::table('email_logs')
            ->where('company_id', $this->company->id)
            ->where('sent_at', '>=', now()->subDays(7))
            ->where('status', 'sent')
            ->orderBy('sent_at', 'desc')
            ->limit(100)
            ->get();

        $endTime = microtime(true);
        $queries = DB::getQueryLog();

        // Assert
        $executionTime = ($endTime - $startTime) * 1000;
        
        $this->assertLessThan(100, $executionTime, 'Email log query took more than 100ms');
        $this->assertCount(1, $queries, 'Multiple queries executed for single operation');
        $this->assertCount(100, $results);
    }

    /** @test */
    #[Test]
    public function it_handles_attachment_generation_efficiently()
    {
        // Arrange
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'transcript' => str_repeat('Long transcript content. ', 10000), // ~250KB
        ]);
        
        $mailable = new \App\Mail\CallSummaryEmail($call);
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Act
        $attachments = $mailable->attachments();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Assert
        $executionTime = ($endTime - $startTime) * 1000;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

        $this->assertLessThan(100, $executionTime, 'Attachment generation took more than 100ms');
        $this->assertLessThan(10, $memoryUsed, 'Attachment generation used more than 10MB');
        $this->assertCount(1, $attachments);
    }

    /** @test */
    #[Test]
    public function it_caches_email_templates_effectively()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $mailable = new \App\Mail\AppointmentConfirmationMail($appointment);
        
        // Warm up cache
        $mailable->render();
        
        $iterations = 50;
        $totalTime = 0;

        // Act - Render same template multiple times (should use cache)
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $mailable->render();
            $endTime = microtime(true);
            
            $totalTime += ($endTime - $startTime) * 1000;
        }

        $averageTime = $totalTime / $iterations;

        // Assert - Cached renders should be much faster
        $this->assertLessThan(10, $averageTime, 'Cached template rendering exceeds 10ms');
    }
}