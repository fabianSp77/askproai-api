<?php

namespace Database\Factories;

use App\Models\WebhookEvent;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        $providers = [WebhookEvent::PROVIDER_RETELL, WebhookEvent::PROVIDER_CALCOM, WebhookEvent::PROVIDER_STRIPE];
        $provider = $this->faker->randomElement($providers);
        
        $payload = $this->generatePayloadForProvider($provider);
        
        return [
            'company_id' => Company::factory(),
            'provider' => $provider,
            'event_type' => $this->getEventTypeForProvider($provider),
            'event_id' => $this->getEventIdForProvider($provider, $payload),
            'idempotency_key' => WebhookEvent::generateIdempotencyKey($provider, $payload),
            'payload' => $payload,
            'headers' => $this->generateHeadersForProvider($provider),
            'status' => $this->faker->randomElement([
                WebhookEvent::STATUS_PENDING,
                WebhookEvent::STATUS_PROCESSING,
                WebhookEvent::STATUS_COMPLETED,
                WebhookEvent::STATUS_FAILED
            ]),
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 hour', 'now'),
            'error_message' => $this->faker->optional()->sentence(),
            'retry_count' => $this->faker->numberBetween(0, 3),
            'correlation_id' => Str::uuid()->toString(),
            'received_at' => $this->faker->dateTimeBetween('-1 hour', 'now')
        ];
    }

    /**
     * Create a Retell webhook event
     */
    public function retell(): static
    {
        return $this->state(function (array $attributes) {
            $payload = [
                'event' => 'call_ended',
                'call' => [
                    'call_id' => 'retell_call_' . $this->faker->uuid,
                    'agent_id' => 'agent_' . $this->faker->uuid,
                    'from_number' => '+49' . $this->faker->numerify('##########'),
                    'to_number' => '+49' . $this->faker->numerify('##########'),
                    'direction' => $this->faker->randomElement(['inbound', 'outbound']),
                    'start_timestamp' => now()->subMinutes(10)->timestamp * 1000,
                    'end_timestamp' => now()->timestamp * 1000,
                    'call_duration' => $this->faker->numberBetween(30, 600),
                    'disconnection_reason' => $this->faker->randomElement(['user_hangup', 'agent_hangup', 'timeout']),
                    'recording_url' => $this->faker->url(),
                    'transcript' => $this->faker->paragraph(5)
                ]
            ];

            return [
                'provider' => WebhookEvent::PROVIDER_RETELL,
                'event_type' => 'call_ended',
                'event_id' => $payload['call']['call_id'],
                'payload' => $payload,
                'idempotency_key' => WebhookEvent::generateIdempotencyKey(WebhookEvent::PROVIDER_RETELL, $payload),
                'headers' => [
                    'x-retell-signature' => ['test_signature_' . $this->faker->uuid]
                ]
            ];
        });
    }

    /**
     * Create a Cal.com webhook event
     */
    public function calcom(): static
    {
        return $this->state(function (array $attributes) {
            $payload = [
                'triggerEvent' => 'BOOKING_CREATED',
                'createdAt' => now()->toIso8601String(),
                'payload' => [
                    'type' => 'BOOKING_CREATED',
                    'uid' => 'booking_' . $this->faker->uuid,
                    'title' => $this->faker->sentence(3),
                    'startTime' => now()->addDay()->toIso8601String(),
                    'endTime' => now()->addDay()->addHour()->toIso8601String(),
                    'eventTypeId' => $this->faker->numberBetween(1000, 9999)
                ]
            ];

            return [
                'provider' => WebhookEvent::PROVIDER_CALCOM,
                'event_type' => 'BOOKING_CREATED',
                'event_id' => $payload['payload']['uid'],
                'payload' => $payload,
                'idempotency_key' => WebhookEvent::generateIdempotencyKey(WebhookEvent::PROVIDER_CALCOM, $payload),
                'headers' => [
                    'x-cal-signature-256' => ['sha256=' . $this->faker->sha256]
                ]
            ];
        });
    }

    /**
     * Create a completed webhook event
     */
    public function completed(): static
    {
        return $this->state([
            'status' => WebhookEvent::STATUS_COMPLETED,
            'processed_at' => now(),
            'error_message' => null
        ]);
    }

    /**
     * Create a failed webhook event
     */
    public function failed(): static
    {
        return $this->state([
            'status' => WebhookEvent::STATUS_FAILED,
            'processed_at' => now(),
            'error_message' => $this->faker->sentence(),
            'retry_count' => $this->faker->numberBetween(1, 3)
        ]);
    }

    /**
     * Create a pending webhook event
     */
    public function pending(): static
    {
        return $this->state([
            'status' => WebhookEvent::STATUS_PENDING,
            'processed_at' => null,
            'error_message' => null,
            'retry_count' => 0
        ]);
    }

    /**
     * Generate payload for specific provider
     */
    private function generatePayloadForProvider(string $provider): array
    {
        return match ($provider) {
            WebhookEvent::PROVIDER_RETELL => [
                'event' => 'call_ended',
                'call' => [
                    'call_id' => 'retell_call_' . $this->faker->uuid,
                    'agent_id' => 'agent_' . $this->faker->uuid,
                    'from_number' => '+49' . $this->faker->numerify('##########'),
                    'to_number' => '+49' . $this->faker->numerify('##########'),
                    'direction' => 'inbound',
                    'call_duration' => $this->faker->numberBetween(30, 600)
                ]
            ],
            WebhookEvent::PROVIDER_CALCOM => [
                'triggerEvent' => 'BOOKING_CREATED',
                'payload' => [
                    'uid' => 'booking_' . $this->faker->uuid,
                    'title' => $this->faker->sentence(3),
                    'eventTypeId' => $this->faker->numberBetween(1000, 9999)
                ]
            ],
            WebhookEvent::PROVIDER_STRIPE => [
                'id' => 'evt_' . $this->faker->uuid,
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_' . $this->faker->uuid,
                        'amount' => $this->faker->numberBetween(1000, 100000)
                    ]
                ]
            ],
            default => ['test' => 'data']
        };
    }

    /**
     * Get event type for provider
     */
    private function getEventTypeForProvider(string $provider): string
    {
        return match ($provider) {
            WebhookEvent::PROVIDER_RETELL => 'call_ended',
            WebhookEvent::PROVIDER_CALCOM => 'BOOKING_CREATED',
            WebhookEvent::PROVIDER_STRIPE => 'payment_intent.succeeded',
            default => 'unknown'
        };
    }

    /**
     * Get event ID for provider
     */
    private function getEventIdForProvider(string $provider, array $payload): string
    {
        return match ($provider) {
            WebhookEvent::PROVIDER_RETELL => $payload['call']['call_id'] ?? $this->faker->uuid,
            WebhookEvent::PROVIDER_CALCOM => $payload['payload']['uid'] ?? $this->faker->uuid,
            WebhookEvent::PROVIDER_STRIPE => $payload['id'] ?? $this->faker->uuid,
            default => $this->faker->uuid
        };
    }

    /**
     * Generate headers for provider
     */
    private function generateHeadersForProvider(string $provider): array
    {
        return match ($provider) {
            WebhookEvent::PROVIDER_RETELL => [
                'x-retell-signature' => ['test_signature_' . $this->faker->uuid]
            ],
            WebhookEvent::PROVIDER_CALCOM => [
                'x-cal-signature-256' => ['sha256=' . $this->faker->sha256]
            ],
            WebhookEvent::PROVIDER_STRIPE => [
                'stripe-signature' => ['t=' . time() . ',v1=' . $this->faker->sha256]
            ],
            default => []
        };
    }
}