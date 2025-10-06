<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IntegrationService
{
    /**
     * Test connection for any integration
     */
    public function testConnection(Integration $integration): bool
    {
        return match($integration->provider) {
            'calcom' => $this->testCalcomConnection($integration),
            'retell' => $this->testRetellConnection($integration),
            'webhook' => $this->testWebhookConnection($integration),
            'api' => $this->testApiConnection($integration),
            'oauth2' => $this->testOAuthConnection($integration),
            default => false,
        };
    }

    /**
     * Sync data for an integration
     */
    public function sync(Integration $integration): array
    {
        if (!$integration->canSync()) {
            throw new \Exception('Integration cannot sync in current state');
        }

        $integration->update(['status' => Integration::STATUS_SYNCING]);

        try {
            $result = match($integration->provider) {
                'calcom' => $this->syncCalcom($integration),
                'retell' => $this->syncRetell($integration),
                default => $this->genericSync($integration),
            };

            $integration->markSyncSuccess();

            return $result;
        } catch (\Exception $e) {
            $integration->markSyncError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Cal.com specific methods
     */
    protected function testCalcomConnection(Integration $integration): bool
    {
        if (!$integration->api_key) {
            throw new \Exception('Cal.com API Key is missing');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $integration->api_key,
            'Content-Type' => 'application/json',
        ])->get($this->getCalcomBaseUrl($integration) . '/event-types');

        $integration->incrementApiCalls();

        if ($response->successful()) {
            return true;
        }

        throw new \Exception('Cal.com API Error: ' . $response->status() . ' - ' . $response->body());
    }

    protected function syncCalcom(Integration $integration): array
    {
        $synced = [
            'event_types' => 0,
            'bookings' => 0,
            'availability' => 0,
        ];

        // Sync Event Types
        if ($this->shouldSync($integration, 'event_types')) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $integration->api_key,
            ])->get($this->getCalcomBaseUrl($integration) . '/event-types');

            if ($response->successful()) {
                $eventTypes = $response->json('data', []);
                foreach ($eventTypes as $eventType) {
                    $this->processCalcomEventType($integration, $eventType);
                    $synced['event_types']++;
                }
            }
            $integration->incrementApiCalls();
        }

        // Sync Bookings
        if ($this->shouldSync($integration, 'bookings')) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $integration->api_key,
            ])->get($this->getCalcomBaseUrl($integration) . '/bookings');

            if ($response->successful()) {
                $bookings = $response->json('data', []);
                foreach ($bookings as $booking) {
                    $this->processCalcomBooking($integration, $booking);
                    $synced['bookings']++;
                }
            }
            $integration->incrementApiCalls();
        }

        $integration->update([
            'records_synced' => $integration->records_synced + array_sum($synced),
        ]);

        return $synced;
    }

    protected function processCalcomEventType(Integration $integration, array $eventType): void
    {
        // Map and store event type data
        $mappedData = $this->mapFields($integration, $eventType, 'event_type');

        // Store or update in local database
        if ($integration->company_id) {
            // Update Service model if mapping exists
            $service = \App\Models\Service::where('calcom_event_type_id', $eventType['id'])
                ->where('company_id', $integration->company_id)
                ->first();

            if ($service) {
                $service->update([
                    'name' => $eventType['title'] ?? $service->name,
                    'description' => $eventType['description'] ?? $service->description,
                    'duration_minutes' => $eventType['length'] ?? $service->duration_minutes,
                ]);
            }
        }

        Log::info('Cal.com Event Type processed', [
            'integration_id' => $integration->id,
            'event_type_id' => $eventType['id'],
        ]);
    }

    protected function processCalcomBooking(Integration $integration, array $booking): void
    {
        // Map and store booking data
        $mappedData = $this->mapFields($integration, $booking, 'booking');

        // Store or update in local database
        if ($integration->company_id) {
            // Update Appointment model if mapping exists
            $appointment = \App\Models\Appointment::where('external_id', $booking['id'])
                ->where('company_id', $integration->company_id)
                ->first();

            if (!$appointment && isset($booking['startTime'])) {
                \App\Models\Appointment::create([
                    'company_id' => $integration->company_id,
                    'customer_id' => null, // Would need customer mapping
                    'service_id' => null, // Would need service mapping
                    'staff_id' => null, // Would need staff mapping
                    'external_id' => $booking['id'],
                    'starts_at' => Carbon::parse($booking['startTime']),
                    'ends_at' => Carbon::parse($booking['endTime']),
                    'status' => $this->mapBookingStatus($booking['status'] ?? 'confirmed'),
                    'notes' => $booking['description'] ?? null,
                ]);
            }
        }

        Log::info('Cal.com Booking processed', [
            'integration_id' => $integration->id,
            'booking_id' => $booking['id'],
        ]);
    }

    /**
     * Retell AI specific methods
     */
    protected function testRetellConnection(Integration $integration): bool
    {
        if (!$integration->api_key) {
            throw new \Exception('Retell API Key is missing');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $integration->api_key,
        ])->get('https://api.retellai.com/agents');

        $integration->incrementApiCalls();

        if ($response->successful()) {
            return true;
        }

        throw new \Exception('Retell API Error: ' . $response->status());
    }

    protected function syncRetell(Integration $integration): array
    {
        $synced = [
            'agents' => 0,
            'calls' => 0,
        ];

        // Sync Agents
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $integration->api_key,
        ])->get('https://api.retellai.com/agents');

        if ($response->successful()) {
            $agents = $response->json('data', []);
            foreach ($agents as $agent) {
                $this->processRetellAgent($integration, $agent);
                $synced['agents']++;
            }
        }
        $integration->incrementApiCalls();

        // Sync Recent Calls
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $integration->api_key,
        ])->get('https://api.retellai.com/calls');

        if ($response->successful()) {
            $calls = $response->json('data', []);
            foreach ($calls as $call) {
                $this->processRetellCall($integration, $call);
                $synced['calls']++;
            }
        }
        $integration->incrementApiCalls();

        return $synced;
    }

    protected function processRetellAgent(Integration $integration, array $agent): void
    {
        // Store agent configuration
        $config = $integration->config ?? [];
        $config['agents'][$agent['id']] = [
            'name' => $agent['name'],
            'phone_number' => $agent['phone_number'] ?? null,
            'updated_at' => now(),
        ];
        $integration->update(['config' => $config]);

        Log::info('Retell Agent processed', [
            'integration_id' => $integration->id,
            'agent_id' => $agent['id'],
        ]);
    }

    protected function processRetellCall(Integration $integration, array $call): void
    {
        // Store call data
        if ($integration->company_id) {
            \App\Models\Call::updateOrCreate(
                ['external_id' => $call['id']],
                [
                    'company_id' => $integration->company_id,
                    'customer_id' => null, // Would need phone number mapping
                    'staff_id' => null,
                    'type' => 'ai_agent',
                    'status' => $call['status'] ?? 'completed',
                    'duration_seconds' => $call['duration'] ?? 0,
                    'notes' => $call['transcript'] ?? null,
                    'metadata' => [
                        'agent_id' => $call['agent_id'],
                        'retell_call_id' => $call['id'],
                    ],
                    'created_at' => Carbon::parse($call['created_at']),
                ]
            );
        }

        Log::info('Retell Call processed', [
            'integration_id' => $integration->id,
            'call_id' => $call['id'],
        ]);
    }

    /**
     * Webhook specific methods
     */
    protected function testWebhookConnection(Integration $integration): bool
    {
        if (!$integration->webhook_url) {
            throw new \Exception('Webhook URL is missing');
        }

        $testPayload = [
            'test' => true,
            'timestamp' => now()->toIso8601String(),
            'integration_id' => $integration->id,
        ];

        if ($integration->webhook_secret) {
            $testPayload['signature'] = hash_hmac('sha256', json_encode($testPayload), $integration->webhook_secret);
        }

        $response = Http::timeout(5)->post($integration->webhook_url, $testPayload);

        if ($response->successful()) {
            return true;
        }

        throw new \Exception('Webhook Error: ' . $response->status());
    }

    /**
     * Generic API methods
     */
    protected function testApiConnection(Integration $integration): bool
    {
        $config = $integration->config ?? [];
        $endpoint = $config['test_endpoint'] ?? $config['base_url'] ?? null;

        if (!$endpoint) {
            throw new \Exception('API endpoint not configured');
        }

        $headers = [];
        if ($integration->api_key) {
            $headers['Authorization'] = 'Bearer ' . $integration->api_key;
        }

        $response = Http::withHeaders($headers)->get($endpoint);
        $integration->incrementApiCalls();

        if ($response->successful()) {
            return true;
        }

        throw new \Exception('API Error: ' . $response->status());
    }

    /**
     * OAuth2 methods
     */
    protected function testOAuthConnection(Integration $integration): bool
    {
        if (!$integration->access_token) {
            throw new \Exception('OAuth access token is missing');
        }

        // Try to refresh token if needed
        if ($integration->refresh_token && $this->isTokenExpired($integration)) {
            $this->refreshOAuthToken($integration);
        }

        // Test with current token
        $config = $integration->config ?? [];
        $testEndpoint = $config['test_endpoint'] ?? null;

        if (!$testEndpoint) {
            return !empty($integration->access_token);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $integration->access_token,
        ])->get($testEndpoint);

        $integration->incrementApiCalls();

        return $response->successful();
    }

    protected function refreshOAuthToken(Integration $integration): void
    {
        $config = $integration->config ?? [];
        $tokenEndpoint = $config['token_endpoint'] ?? null;

        if (!$tokenEndpoint || !$integration->refresh_token) {
            throw new \Exception('Cannot refresh OAuth token: missing configuration');
        }

        $response = Http::post($tokenEndpoint, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $integration->refresh_token,
            'client_id' => $config['client_id'] ?? null,
            'client_secret' => $integration->api_secret,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $integration->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $integration->refresh_token,
            ]);

            // Store token expiry
            if (isset($data['expires_in'])) {
                $config['token_expires_at'] = now()->addSeconds($data['expires_in'])->toIso8601String();
                $integration->update(['config' => $config]);
            }
        } else {
            throw new \Exception('Failed to refresh OAuth token');
        }
    }

    protected function isTokenExpired(Integration $integration): bool
    {
        $config = $integration->config ?? [];
        $expiresAt = $config['token_expires_at'] ?? null;

        if (!$expiresAt) {
            return false;
        }

        return Carbon::parse($expiresAt)->isPast();
    }

    /**
     * Generic sync method
     */
    protected function genericSync(Integration $integration): array
    {
        $config = $integration->config ?? [];
        $syncEndpoint = $config['sync_endpoint'] ?? null;

        if (!$syncEndpoint) {
            throw new \Exception('Sync endpoint not configured');
        }

        $headers = [];
        if ($integration->api_key) {
            $headers['Authorization'] = 'Bearer ' . $integration->api_key;
        }

        $response = Http::withHeaders($headers)->get($syncEndpoint);
        $integration->incrementApiCalls();

        if ($response->successful()) {
            $data = $response->json();
            $recordCount = is_array($data) ? count($data) : 1;

            $integration->update([
                'records_synced' => $integration->records_synced + $recordCount,
            ]);

            return ['records' => $recordCount];
        }

        throw new \Exception('Sync failed: ' . $response->status());
    }

    /**
     * Helper methods
     */
    protected function shouldSync(Integration $integration, string $entity): bool
    {
        $syncSettings = $integration->sync_settings ?? [];
        return $syncSettings[$entity] ?? true;
    }

    protected function mapFields(Integration $integration, array $data, string $entity): array
    {
        $mappings = $integration->field_mappings ?? [];
        $entityMappings = $mappings[$entity] ?? [];

        $mapped = [];
        foreach ($entityMappings as $local => $remote) {
            $mapped[$local] = data_get($data, $remote);
        }

        return $mapped;
    }

    protected function mapBookingStatus(string $calcomStatus): string
    {
        return match($calcomStatus) {
            'ACCEPTED', 'confirmed' => 'confirmed',
            'PENDING' => 'pending',
            'CANCELLED' => 'cancelled',
            'REJECTED' => 'cancelled',
            default => 'pending',
        };
    }

    protected function getCalcomBaseUrl(Integration $integration): string
    {
        $config = $integration->config ?? [];
        $baseUrl = $config['base_url'] ?? 'https://api.cal.com/v1';

        return rtrim($baseUrl, '/');
    }

    /**
     * Get health status for all integrations
     */
    public function updateAllHealthStatuses(): void
    {
        Integration::active()->chunk(100, function ($integrations) {
            foreach ($integrations as $integration) {
                $integration->updateHealthStatus();
            }
        });
    }

    /**
     * Process webhook payload
     */
    public function processWebhook(Integration $integration, array $payload, ?string $signature = null): void
    {
        // Validate signature if configured
        if ($integration->webhook_secret && $signature) {
            $expectedSignature = hash_hmac('sha256', json_encode($payload), $integration->webhook_secret);
            if (!hash_equals($expectedSignature, $signature)) {
                throw new \Exception('Invalid webhook signature');
            }
        }

        // Process based on provider
        match($integration->provider) {
            'calcom' => $this->processCalcomWebhook($integration, $payload),
            'retell' => $this->processRetellWebhook($integration, $payload),
            default => $this->processGenericWebhook($integration, $payload),
        };

        $integration->update([
            'last_sync_at' => now(),
            'records_synced' => $integration->records_synced + 1,
        ]);
    }

    protected function processCalcomWebhook(Integration $integration, array $payload): void
    {
        $event = $payload['triggerEvent'] ?? $payload['event'] ?? null;

        match($event) {
            'BOOKING_CREATED' => $this->processCalcomBooking($integration, $payload['payload']),
            'BOOKING_RESCHEDULED' => $this->processCalcomBooking($integration, $payload['payload']),
            'BOOKING_CANCELLED' => $this->processCalcomBookingCancellation($integration, $payload['payload']),
            default => Log::info('Unhandled Cal.com webhook event', ['event' => $event]),
        };
    }

    protected function processCalcomBookingCancellation(Integration $integration, array $booking): void
    {
        if ($integration->company_id) {
            $appointment = \App\Models\Appointment::where('external_id', $booking['id'])
                ->where('company_id', $integration->company_id)
                ->first();

            if ($appointment) {
                $appointment->update(['status' => 'cancelled']);
            }
        }
    }

    protected function processRetellWebhook(Integration $integration, array $payload): void
    {
        $event = $payload['event'] ?? null;

        match($event) {
            'call.completed' => $this->processRetellCall($integration, $payload['data']),
            'agent.updated' => $this->processRetellAgent($integration, $payload['data']),
            default => Log::info('Unhandled Retell webhook event', ['event' => $event]),
        };
    }

    protected function processGenericWebhook(Integration $integration, array $payload): void
    {
        // Store webhook data for later processing
        $metadata = $integration->metadata ?? [];
        $metadata['last_webhook'] = [
            'received_at' => now()->toIso8601String(),
            'payload' => $payload,
        ];
        $integration->update(['metadata' => $metadata]);

        Log::info('Generic webhook received', [
            'integration_id' => $integration->id,
            'payload_keys' => array_keys($payload),
        ]);
    }
}