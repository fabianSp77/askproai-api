<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\PortalUser;
use App\Models\Company;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class EmailApiTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private PortalUser $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        
        $this->user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function authenticated_user_can_send_test_email()
    {
        // Arrange
        Mail::fake();
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->postJson('/api/email/test', [
            'to' => 'test@example.com',
            'type' => 'appointment_confirmation',
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Test email sent successfully',
            ]);

        Mail::assertSent(function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function user_can_resend_call_summary_email()
    {
        // Arrange
        Mail::fake();
        Queue::fake();
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'customer@example.com',
        ]);
        
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Act
        $response = $this->postJson("/api/calls/{$call->id}/resend-summary");

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Call summary email queued for sending',
            ]);

        Queue::assertPushed(\App\Jobs\SendCallSummaryEmailJob::class);
    }

    /** @test */
    public function user_can_preview_email_template()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Act
        $response = $this->getJson('/api/email/preview', [
            'type' => 'appointment_confirmation',
            'appointment_id' => $appointment->id,
            'locale' => 'en',
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'subject',
                'html',
                'text',
            ]);
    }

    /** @test */
    public function user_can_get_email_statistics()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->getJson('/api/email/statistics');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'sent_today',
                'sent_week',
                'sent_month',
                'failed_today',
                'bounce_rate',
                'by_type' => [
                    'appointment_confirmation',
                    'call_summary',
                    'reminder',
                ],
            ]);
    }

    /** @test */
    public function user_can_get_email_logs()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->getJson('/api/email/logs');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'recipient',
                        'subject',
                        'type',
                        'status',
                        'sent_at',
                        'opened_at',
                        'clicked_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function user_can_update_email_settings()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->putJson('/api/email/settings', [
            'appointment_confirmation_enabled' => true,
            'call_summary_enabled' => false,
            'reminder_24h_enabled' => true,
            'reminder_2h_enabled' => true,
            'reminder_30min_enabled' => false,
            'default_reply_to' => 'noreply@example.com',
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Email settings updated successfully',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'call_summary_email_enabled' => false,
        ]);
    }

    /** @test */
    public function user_can_test_smtp_connection()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->postJson('/api/email/test-smtp', [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'encryption' => 'tls',
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'details' => [
                    'connection',
                    'authentication',
                    'tls_support',
                ],
            ]);
    }

    /** @test */
    public function user_can_manage_email_templates()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act - Get templates
        $response = $this->getJson('/api/email/templates');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'subject',
                        'content',
                        'variables',
                        'last_updated',
                    ],
                ],
            ]);
    }

    /** @test */
    public function user_can_send_bulk_emails()
    {
        // Arrange
        Mail::fake();
        Queue::fake();
        Sanctum::actingAs($this->user);
        
        $customers = Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        // Act
        $response = $this->postJson('/api/email/bulk-send', [
            'customer_ids' => $customers->pluck('id')->toArray(),
            'template' => 'marketing_campaign',
            'subject' => 'Special Offer',
            'content' => 'Check out our special offers!',
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'queued_count' => 3,
            ]);

        Queue::assertPushedWithChain(\App\Jobs\SendBulkEmailJob::class);
    }

    /** @test */
    public function user_cannot_send_email_without_authentication()
    {
        // Act
        $response = $this->postJson('/api/email/test', [
            'to' => 'test@example.com',
        ]);

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function user_cannot_access_other_company_email_logs()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        $otherCompany = Company::factory()->create();
        $otherCall = Call::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Act
        $response = $this->postJson("/api/calls/{$otherCall->id}/resend-summary");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function rate_limiting_works_for_email_endpoints()
    {
        // Arrange
        Mail::fake();
        Sanctum::actingAs($this->user);

        // Act - Send multiple requests
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/email/test', [
                'to' => 'test@example.com',
            ]);
            
            if ($i < 5) {
                $response->assertOk();
            } else {
                // 6th request should be rate limited
                $response->assertStatus(429);
            }
        }
    }
}