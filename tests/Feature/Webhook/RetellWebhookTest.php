<?php // tests/Feature/Webhook/RetellWebhookTest.php

namespace Tests\Feature\Webhook;

// DB Facade wird hier nicht mehr benötigt

use App\Mail\ErrorNotificationMail;
use App\Models\Call;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase; // <-- STANDARD-Trait verwenden!
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetellWebhookTest extends TestCase
{
    // Verwende die Standard-Trait RefreshDatabase
    use RefreshDatabase;

    // Eigenschaften für Tenants und User
    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $defaultUser;

    /**
     * Setup runs before each test method.
     * Dank RefreshDatabase ist die DB hier bereits migriert UND leer.
     * Wir erstellen hier NUR die Testdaten, die benötigt werden.
     */
    protected function setUp(): void
    {
        parent::setUp(); // WICHTIG: Zuerst parent::setUp() aufrufen!

        // KEINE DB::table()->delete() Aufrufe hier! Das macht RefreshDatabase.

        // Erstelle Tenants und User neu für jeden Test.
        $this->tenant1 = Tenant::factory()->create(['api_key' => 'tenant1-test-key']);
        $this->tenant2 = Tenant::factory()->create(['api_key' => 'tenant2-test-key']);
        $this->defaultUser = User::factory()->create(['id' => 1]);
    }

    // --- Payloads (unverändert) ---
    private function getSuccessfulCallPayload(string $callIdSuffix = ''): array { return [ "call_id" => "test_call_" . uniqid($callIdSuffix), "status" => "completed", "phone_number" => "+491701234567", "duration" => 120, "transcript" => "...", "user_sentiment" => "positive", "call_successful" => true, "_name" => "Max Mustermann", "_email" => "test@example.com", "_datum__termin" => now()->addWeek()->format('Y-m-d'), "_uhrzeit__termin" => "14:30", ]; }
    private function getInfoCallPayload(string $callIdSuffix = ''): array { return [ "call_id" => "test_call_" . uniqid($callIdSuffix), "status" => "completed", "phone_number" => "+491709876543", "duration" => 60, "transcript" => "...", "user_sentiment" => "neutral", "call_successful" => true, ]; }
    private function getFailedCallPayload(string $callIdSuffix = ''): array { return [ "call_id" => "test_call_" . uniqid($callIdSuffix), "status" => "failed", "phone_number" => "+491701112223", "duration" => 10, "transcript" => "...", "user_sentiment" => "negative", "call_successful" => false, "disconnect_reason" => "CALLER_HUNG_UP", ]; }
    private function getInvalidDataPayload(): array { return [ /* call_id fehlt */ "status" => "completed", "_datum__termin" => "kein-datum", "_uhrzeit__termin" => "morgen", "_email" => "keine-email" ]; }
    // --- Ende Payloads ---

    #[Test] public function it_processes_successful_webhook_and_triggers_mocked_booking_for_correct_tenant(): void
    {
         Log::spy(); Mail::fake();
         Http::fake([ '*/event-types/*' => Http::response(['event_type' => ['id' => 2026901, 'length' => 30]], 200), '*/bookings*' => Http::response(['booking' => ['id' => 'mocked_cal_booking_123']], 201), config('services.calcom.base_url').'/*' => Http::response('Unexpected', 500) ]);
         $payload = $this->getSuccessfulCallPayload();
         $response = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant1->api_key])->postJson('/api/webhooks/retell', $payload);
         $response->assertStatus(200)->assertJson(['success' => true]);
         $this->assertDatabaseHas('calls', [ 'call_id' => $payload['call_id'], 'tenant_id' => $this->tenant1->id, 'name' => $payload['_name'], 'email' => $payload['_email'] ]);
         Http::assertSentCount(2); Mail::assertNothingSent(); Log::assertLogged('info');
    }

    #[Test] public function it_processes_webhook_without_appointment_data_correctly_for_correct_tenant(): void
    {
         Log::spy(); Mail::fake(); $payload = $this->getInfoCallPayload();
         $response = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant1->api_key])->postJson('/api/webhooks/retell', $payload);
         $response->assertStatus(200)->assertJson(['success' => true]);
         $this->assertDatabaseHas('calls', [ 'call_id' => $payload['call_id'], 'tenant_id' => $this->tenant1->id, 'name' => null, 'email' => null ]);
         Log::assertNotLogged(fn ($l, $m) => str_contains($m, 'Starte Cal.com')); Mail::assertNothingSent();
    }

    #[Test] public function it_processes_failed_call_webhook_correctly_for_correct_tenant(): void
    {
        Log::spy(); Mail::fake(); $payload = $this->getFailedCallPayload();
        $response = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant1->api_key])->postJson('/api/webhooks/retell', $payload);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('calls', [ 'call_id' => $payload['call_id'], 'tenant_id' => $this->tenant1->id, 'successful' => false ]);
        Log::assertNotLogged(fn ($l, $m) => str_contains($m, 'Starte Cal.com')); Mail::assertNothingSent();
    }

    #[Test] public function it_rejects_webhook_with_invalid_data_for_tenant(): void
    {
         Log::spy(); Mail::fake(); $payload = $this->getInvalidDataPayload();
         $response = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant1->api_key])->postJson('/api/webhooks/retell', $payload);
         $response->assertStatus(422);
         $response->assertJsonStructure(['error', 'details' => ['call_id', '_datum__termin', '_uhrzeit__termin', '_email']]);
         $this->assertDatabaseMissing('calls', ['tenant_id' => $this->tenant1->id, 'call_status' => $payload['status'] ?? null]);
         Log::assertLogged('error'); Mail::assertNothingSent();
    }

    #[Test] public function it_handles_calcom_service_failure_gracefully_for_tenant(): void
    {
        Log::spy(); Mail::fake();
        Http::fake([ '*/event-types/*' => Http::response(['event_type' => ['id' => 2026901, 'length' => 30]], 200), '*/bookings*' => Http::response('Internal Cal.com Error', 500), config('services.calcom.base_url').'/*' => Http::response('Unexpected Cal.com', 500) ]);
        $payload = $this->getSuccessfulCallPayload();
        $response = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant1->api_key])->postJson('/api/webhooks/retell', $payload);
        $response->assertStatus(500)->assertExactJson(['error' => 'Interner Serverfehler bei der Webhook-Verarbeitung.']);
        $this->assertDatabaseHas('calls', ['call_id' => $payload['call_id'], 'tenant_id' => $this->tenant1->id]);
        Log::assertLogged('error');
        Mail::assertSent(ErrorNotificationMail::class);
    }

    #[Test]
    public function it_rejects_request_with_invalid_api_key(): void
    {
        Log::spy(); Mail::fake();
        $payload = ['call_id' => 'irrelevant_call_id'];
        $response = $this->withHeaders(['X-Tenant-Api-Key' => 'dieser-key-ist-ungueltig'])->postJson('/api/webhooks/retell', $payload);
        $response->assertStatus(401);
        $response->assertExactJson(['error' => 'Unauthorized: Invalid API Key.']);
        $this->assertDatabaseMissing('calls', ['call_id' => $payload['call_id']]);
        Log::assertLogged('warning'); Mail::assertNothingSent();
    }

    #[Test]
    public function it_rejects_request_without_api_key(): void
    {
         Log::spy(); Mail::fake();
         $payload = ['call_id' => 'irrelevant_call_id'];
         $response = $this->postJson('/api/webhooks/retell', $payload);
         $response->assertStatus(401);
         $response->assertExactJson(['error' => 'Unauthorized: Tenant could not be identified.']);
         $this->assertDatabaseMissing('calls', ['call_id' => $payload['call_id']]);
         Log::assertLogged('warning'); Mail::assertNothingSent();
    }
}
