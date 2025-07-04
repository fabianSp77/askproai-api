<?php

namespace Tests\Unit\Services\Billing;

use Tests\TestCase;
use App\Services\Billing\DunningService;
use App\Services\StripeServiceWithCircuitBreaker;
use App\Services\NotificationService;
use App\Models\DunningProcess;
use App\Models\DunningConfiguration;
use App\Models\DunningActivity;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessDunningRetryJob;
use Mockery;

class DunningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DunningService $service;
    protected $mockStripeService;
    protected $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Queue::fake();
        
        $this->mockStripeService = Mockery::mock(StripeServiceWithCircuitBreaker::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
        
        $this->service = new DunningService(
            $this->mockStripeService,
            $this->mockNotificationService
        );
    }

    public function test_handle_failed_payment_creates_dunning_process()
    {
        $company = Company::factory()->create();
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'total' => 100.00,
            'status' => 'overdue'
        ]);
        
        $this->mockNotificationService->shouldReceive('sendDunningStartedNotification')
            ->once()
            ->with(Mockery::type(DunningProcess::class));
        
        $process = $this->service->handleFailedPayment($invoice, 'insufficient_funds');
        
        $this->assertInstanceOf(DunningProcess::class, $process);
        $this->assertEquals($company->id, $process->company_id);
        $this->assertEquals($invoice->id, $process->invoice_id);
        $this->assertEquals('insufficient_funds', $process->failure_reason);
        $this->assertEquals('active', $process->status);
        $this->assertEquals(1, $process->retry_count);
        $this->assertNotNull($process->next_retry_date);
        
        Queue::assertPushed(ProcessDunningRetryJob::class);
    }

    public function test_retry_payment_successful()
    {
        $company = Company::factory()->create();
        $invoice = Invoice::factory()->create(['company_id' => $company->id]);
        $process = DunningProcess::factory()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'status' => 'active',
            'retry_count' => 1
        ]);
        
        $this->mockStripeService->shouldReceive('retryInvoicePayment')
            ->once()
            ->with($invoice->stripe_invoice_id)
            ->andReturn(['paid' => true, 'invoice' => ['id' => 'inv_123']]);
        
        $this->mockNotificationService->shouldReceive('sendPaymentRecoveredNotification')
            ->once()
            ->with(Mockery::type(DunningProcess::class));
        
        $result = $this->service->retryPayment($process);
        
        $this->assertTrue($result);
        
        $process->refresh();
        $this->assertEquals('recovered', $process->status);
        $this->assertNotNull($process->recovered_at);
        
        $this->assertDatabaseHas('dunning_activities', [
            'dunning_process_id' => $process->id,
            'activity_type' => 'payment_retry',
            'status' => 'success'
        ]);
    }

    public function test_retry_payment_failed_schedules_next_retry()
    {
        $company = Company::factory()->create();
        $config = DunningConfiguration::factory()->create([
            'company_id' => $company->id,
            'max_retry_attempts' => 3
        ]);
        
        $invoice = Invoice::factory()->create(['company_id' => $company->id]);
        $process = DunningProcess::factory()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'status' => 'active',
            'retry_count' => 1
        ]);
        
        $this->mockStripeService->shouldReceive('retryInvoicePayment')
            ->once()
            ->andReturn(['paid' => false, 'error' => 'Card declined']);
        
        $this->mockNotificationService->shouldReceive('sendPaymentRetryWarningNotification')
            ->once();
        
        $result = $this->service->retryPayment($process);
        
        $this->assertFalse($result);
        
        $process->refresh();
        $this->assertEquals('active', $process->status);
        $this->assertEquals(2, $process->retry_count);
        $this->assertNotNull($process->next_retry_date);
        
        Queue::assertPushed(ProcessDunningRetryJob::class);
    }

    public function test_retry_payment_max_attempts_reached_pauses_service()
    {
        $company = Company::factory()->create();
        $config = DunningConfiguration::factory()->create([
            'company_id' => $company->id,
            'max_retry_attempts' => 3,
            'pause_service_on_failure' => true
        ]);
        
        $invoice = Invoice::factory()->create(['company_id' => $company->id]);
        $process = DunningProcess::factory()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'status' => 'active',
            'retry_count' => 3
        ]);
        
        $this->mockStripeService->shouldReceive('retryInvoicePayment')
            ->once()
            ->andReturn(['paid' => false]);
        
        $this->mockNotificationService->shouldReceive('sendServicePausedNotification')
            ->once();
        
        $result = $this->service->retryPayment($process);
        
        $this->assertFalse($result);
        
        $process->refresh();
        $this->assertEquals('failed', $process->status);
        $this->assertNotNull($process->service_paused_at);
    }

    public function test_process_due_retries()
    {
        $company = Company::factory()->create();
        
        // Create processes due for retry
        $dueProcesses = DunningProcess::factory()->count(3)->create([
            'company_id' => $company->id,
            'status' => 'active',
            'next_retry_date' => Carbon::now()->subHour()
        ]);
        
        // Create process not yet due
        DunningProcess::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'next_retry_date' => Carbon::now()->addHour()
        ]);
        
        $count = $this->service->processDueRetries();
        
        $this->assertEquals(3, $count);
        Queue::assertPushed(ProcessDunningRetryJob::class, 3);
    }

    public function test_get_configuration_returns_default_when_none_exists()
    {
        $company = Company::factory()->create();
        
        $config = $this->service->getConfiguration($company);
        
        $this->assertInstanceOf(DunningConfiguration::class, $config);
        $this->assertEquals($company->id, $config->company_id);
        $this->assertTrue($config->is_enabled);
        $this->assertEquals(3, $config->max_retry_attempts);
        $this->assertEquals([3, 5, 7], $config->retry_delays);
    }

    public function test_update_configuration()
    {
        $company = Company::factory()->create();
        
        $config = $this->service->updateConfiguration($company, [
            'is_enabled' => false,
            'max_retry_attempts' => 5,
            'retry_delays' => [1, 2, 3, 5, 8]
        ]);
        
        $this->assertFalse($config->is_enabled);
        $this->assertEquals(5, $config->max_retry_attempts);
        $this->assertEquals([1, 2, 3, 5, 8], $config->retry_delays);
    }

    public function test_get_dunning_statistics()
    {
        $company = Company::factory()->create();
        
        // Create test data
        DunningProcess::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'invoice_amount' => 100
        ]);
        
        DunningProcess::factory()->count(2)->create([
            'company_id' => $company->id,
            'status' => 'recovered',
            'invoice_amount' => 200,
            'recovered_at' => Carbon::now()->subDays(5)
        ]);
        
        DunningProcess::factory()->create([
            'company_id' => $company->id,
            'status' => 'failed',
            'invoice_amount' => 150
        ]);
        
        $stats = $this->service->getDunningStatistics($company);
        
        $this->assertEquals(1, $stats['active_processes']);
        $this->assertEquals(100, $stats['total_outstanding']);
        $this->assertEquals(2, $stats['recovered_this_month']);
        $this->assertEquals(400, $stats['recovered_amount_this_month']);
        $this->assertEquals(66.67, $stats['recovery_rate']);
        $this->assertEquals(5, $stats['avg_recovery_days']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}