<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CalcomEventTypeSyncService
{
    public static function validateApiKey($apiKey): array
    {
        if (!$apiKey) {
            return [
                'valid' => false,
                'error' => 'API-Key ist leer'
            ];
        }

        try {
            // Methode 1: Cal.com v2 API mit Bearer Token (Standard)
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2023-05-26',
            ])->timeout(10)->get('https://api.cal.com/v2/me');

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Cal.com API v2 validation successful', ['data' => $data]);
                return [
                    'valid' => true,
                    'user_id' => $data['data']['id'] ?? null,
                    'username' => $data['data']['username'] ?? null,
                    'email' => $data['data']['email'] ?? null,
                    'method' => 'v2_bearer'
                ];
            }

            // Methode 2: v1 API mit Bearer Token
            $response2 = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get('https://api.cal.com/v1/me');

            if ($response2->successful()) {
                $data = $response2->json();
                Log::info('Cal.com API v1 Bearer validation successful', ['data' => $data]);
                return [
                    'valid' => true,
                    'user_id' => $data['id'] ?? null,
                    'username' => $data['username'] ?? null,
                    'email' => $data['email'] ?? null,
                    'method' => 'v1_bearer'
                ];
            }

            // Methode 3: v1 API mit Query Parameter (Fallback)
            $response3 = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(10)->get('https://api.cal.com/v1/me?apiKey=' . $apiKey);

            if ($response3->successful()) {
                $data = $response3->json();
                Log::info('Cal.com API v1 query validation successful', ['data' => $data]);
                return [
                    'valid' => true,
                    'user_id' => $data['id'] ?? null,
                    'username' => $data['username'] ?? null,
                    'email' => $data['email'] ?? null,
                    'method' => 'v1_query'
                ];
            }

            // Methode 4: Direkte Bookings-API testen (oft verfügbar wenn /me nicht funktioniert)
            $response4 = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get('https://api.cal.com/v1/event-types');

            if ($response4->successful()) {
                Log::info('Cal.com API validation via event-types successful');
                return [
                    'valid' => true,
                    'user_id' => 'unknown',
                    'username' => 'via_event_types',
                    'email' => 'unknown',
                    'method' => 'event_types_test'
                ];
            }

            // Alle Methoden fehlgeschlagen - detaillierte Logs
            Log::error('Cal.com API validation failed - all methods', [
                'api_key_prefix' => substr($apiKey, 0, 20) . '...',
                'v2_bearer' => [
                    'status' => $response->status(),
                    'body' => $response->body()
                ],
                'v1_bearer' => [
                    'status' => $response2->status(),
                    'body' => $response2->body()
                ],
                'v1_query' => [
                    'status' => $response3->status(),
                    'body' => $response3->body()
                ],
                'event_types_test' => [
                    'status' => $response4->status(),
                    'body' => $response4->body()
                ]
            ]);

            return [
                'valid' => false,
                'error' => 'API-Key wird von Cal.com nicht akzeptiert. Überprüfen Sie den API-Key in Ihrem Cal.com Dashboard.'
            ];

        } catch (\Exception $e) {
            Log::error('Cal.com API validation exception', [
                'error' => $e->getMessage(),
                'api_key_prefix' => substr($apiKey, 0, 20) . '...'
            ]);
            return [
                'valid' => false,
                'error' => 'Verbindungsfehler: ' . $e->getMessage()
            ];
        }
    }

    public static function fetchEventTypes($apiKey, $useCache = false): array
    {
        if (!$apiKey) {
            return [];
        }

        $cacheKey = 'calcom_event_types_' . md5($apiKey);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Methode 1: v1 API mit Bearer Token (meist erfolgreich)
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(15)->get('https://api.cal.com/v1/event-types');

            $eventTypes = [];

            if ($response->successful()) {
                $data = $response->json();
                $eventTypes = $data['event_types'] ?? [];
                Log::info('Cal.com Event-Types fetched via v1 Bearer', ['count' => count($eventTypes)]);
            } else {
                // Methode 2: v2 API mit Bearer Token
                $response2 = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                    'cal-api-version' => '2023-05-26',
                ])->timeout(15)->get('https://api.cal.com/v2/event-types');

                if ($response2->successful()) {
                    $data = $response2->json();
                    $eventTypes = $data['data'] ?? [];
                    Log::info('Cal.com Event-Types fetched via v2 Bearer', ['count' => count($eventTypes)]);
                } else {
                    // Methode 3: v1 API mit Query Parameter (Fallback)
                    $response3 = Http::withHeaders([
                        'Content-Type' => 'application/json',
                    ])->timeout(15)->get('https://api.cal.com/v1/event-types?apiKey=' . $apiKey);

                    if ($response3->successful()) {
                        $data = $response3->json();
                        $eventTypes = $data['event_types'] ?? [];
                        Log::info('Cal.com Event-Types fetched via v1 query', ['count' => count($eventTypes)]);
                    } else {
                        Log::error('All Cal.com Event-Types fetch methods failed', [
                            'v1_bearer' => ['status' => $response->status(), 'body' => $response->body()],
                            'v2_bearer' => ['status' => $response2->status(), 'body' => $response2->body()],
                            'v1_query' => ['status' => $response3->status(), 'body' => $response3->body()],
                        ]);
                        return [];
                    }
                }
            }

            // Event-Types verarbeiten
            $enrichedEventTypes = [];
            foreach ($eventTypes as $eventType) {
                $enrichedEventTypes[] = self::enrichEventTypeData($eventType, $apiKey);
            }

            // Cache für 30 Minuten
            if (!empty($enrichedEventTypes)) {
                Cache::put($cacheKey, $enrichedEventTypes, 1800);
            }

            return $enrichedEventTypes;

        } catch (\Exception $e) {
            Log::error('Cal.com Event-Types fetch error', [
                'error' => $e->getMessage(),
                'api_key_prefix' => substr($apiKey, 0, 20) . '...'
            ]);
            return [];
        }
    }

    protected static function enrichEventTypeData($eventType, $apiKey): array
    {
        // Webhook-Status prüfen
        $webhookStatus = self::checkWebhookStatus($eventType['id'], $apiKey);

        return [
            'id' => $eventType['id'],
            'title' => $eventType['title'],
            'slug' => $eventType['slug'] ?? '',
            'length' => $eventType['length'] ?? 30,
            'hidden' => $eventType['hidden'] ?? false,
            'teamId' => $eventType['teamId'] ?? null,
            'userId' => $eventType['userId'] ?? null,
            'webhook_configured' => $webhookStatus,
            'booking_limits' => $eventType['bookingLimits'] ?? null,
            'locations' => $eventType['locations'] ?? [],
            'metadata' => $eventType['metadata'] ?? []
        ];
    }

    public static function checkWebhookStatus($eventTypeId, $apiKey): bool
    {
        try {
            // Webhooks mit Bearer Token abrufen
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get('https://api.cal.com/v1/webhooks');

            if ($response->successful()) {
                $webhooks = $response->json()['webhooks'] ?? [];

                foreach ($webhooks as $webhook) {
                    $triggers = $webhook['triggers'] ?? [];
                    $eventTypeIds = $webhook['eventTypeIds'] ?? [];

                    // Prüfen ob Webhook für diesen Event-Type konfiguriert ist
                    if (in_array($eventTypeId, $eventTypeIds) || empty($eventTypeIds)) {
                        if (in_array('BOOKING_CREATED', $triggers)) {
                            return true;
                        }
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Webhook status check failed', [
                'eventTypeId' => $eventTypeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public static function clearCache($apiKey): void
    {
        $cacheKey = 'calcom_event_types_' . md5($apiKey);
        Cache::forget($cacheKey);
    }

    // Debug-Methode für Troubleshooting
    public static function debugApiCall($apiKey): array
    {
        $results = [];

        try {
            // Test 1: v2 Bearer
            $response1 = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2023-05-26',
            ])->timeout(10)->get('https://api.cal.com/v2/me');

            $results['v2_bearer'] = [
                'status' => $response1->status(),
                'success' => $response1->successful(),
                'body' => $response1->json()
            ];

            // Test 2: v1 Bearer
            $response2 = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get('https://api.cal.com/v1/me');

            $results['v1_bearer'] = [
                'status' => $response2->status(),
                'success' => $response2->successful(),
                'body' => $response2->json()
            ];

            // Test 3: v1 Query
            $response3 = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(10)->get('https://api.cal.com/v1/me?apiKey=' . $apiKey);

            $results['v1_query'] = [
                'status' => $response3->status(),
                'success' => $response3->successful(),
                'body' => $response3->json()
            ];

            // Test 4: Event-Types
            $response4 = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get('https://api.cal.com/v1/event-types');

            $results['event_types'] = [
                'status' => $response4->status(),
                'success' => $response4->successful(),
                'body' => $response4->json()
            ];

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }
}
