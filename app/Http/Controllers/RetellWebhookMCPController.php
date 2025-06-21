<?php

namespace App\Http\Controllers;

use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPContextResolver;
use App\Services\MCP\MCPBookingOrchestrator;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Services\MCP\WebhookMCPServer;
use App\Services\RateLimiter\ApiRateLimiter;
use App\Http\Requests\Webhook\RetellWebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Retell Webhook Controller - MCP Version
 * 
 * This is a migrated version that uses the MCP (Modular Component Pattern) architecture
 * for better modularity, error handling, and tenant isolation.
 */
class RetellWebhookMCPController extends Controller
{
    protected MCPOrchestrator $mcpOrchestrator;
    protected MCPContextResolver $contextResolver;
    protected MCPBookingOrchestrator $bookingOrchestrator;
    protected ApiRateLimiter $rateLimiter;
    
    public function __construct(
        MCPOrchestrator $mcpOrchestrator,
        MCPContextResolver $contextResolver,
        MCPBookingOrchestrator $bookingOrchestrator,
        ApiRateLimiter $rateLimiter
    ) {
        $this->mcpOrchestrator = $mcpOrchestrator;
        $this->contextResolver = $contextResolver;
        $this->bookingOrchestrator = $bookingOrchestrator;
        $this->rateLimiter = $rateLimiter;
    }
    
    /**
     * Process Retell webhook using MCP architecture
     */
    public function processWebhook(RetellWebhookRequest $request): JsonResponse
    {
        $correlationId = $request->input('correlation_id') ?? Str::uuid()->toString();
        
        try {
            Log::info('MCP Retell: Starting webhook processing', [
                'correlation_id' => $correlationId,
                'event' => $request->input('event'),
                'call_id' => $request->input('call.call_id')
            ]);
            // Rate limiting check
            if (!$this->rateLimiter->checkWebhook('retell', $request->ip())) {
                Log::warning('MCP Retell: Rate limit exceeded', [
                    'ip' => $request->ip(),
                    'correlation_id' => $correlationId
                ]);
                
                return $this->errorResponse('Rate limit exceeded', 429, $correlationId);
            }
            
            // Log incoming webhook
            Log::info('MCP Retell: Webhook received', [
                'event' => $request->input('event'),
                'call_id' => $request->input('call.call_id'),
                'correlation_id' => $correlationId,
                'headers' => $request->headers->all()
            ]);
            
            // Handle special case: inbound calls (synchronous)
            if ($request->input('event') === 'call_inbound') {
                return $this->handleInboundCall($request, $correlationId);
            }
            
            // Process all other events through MCP
            $response = $this->processThroughMCP($request, $correlationId);
            
            return $this->successResponse($response, $correlationId);
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            Log::error('MCP Retell: Signature verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            return $this->errorResponse('Invalid signature', 401, $correlationId);
            
        } catch (\Exception $e) {
            Log::error('MCP Retell: Processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => $request->all()
            ]);
            
            // Return success to prevent Retell from retrying
            // But include error details in development
            // For debugging, return the actual error details
            return $this->successResponse([
                'processed' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ], $correlationId);
        }
    }
    
    /**
     * Process webhook through MCP architecture
     */
    protected function processThroughMCP(RetellWebhookRequest $request, string $correlationId): array
    {
        $payload = $request->all();
        $event = $request->input('event');
        
        // For webhooks, we bypass MCP orchestrator and call webhook service directly
        // since we don't have tenant context yet
        $webhookService = app(\App\Services\MCP\WebhookMCPServer::class);
        
        Log::info('MCP Retell: Passing to WebhookMCPServer', [
            'payload' => $payload,
            'correlation_id' => $correlationId
        ]);
        
        $result = $webhookService->processRetellWebhook($payload);
        
        Log::info('MCP Retell: WebhookMCPServer result', [
            'result' => $result,
            'correlation_id' => $correlationId
        ]);
        
        if (!$result['success']) {
            throw new \RuntimeException($result['message'] ?? 'Webhook processing failed');
        }
        
        // Handle duplicate webhooks
        if ($result['duplicate'] ?? false) {
            return [
                'success' => true,
                'duplicate' => true,
                'message' => 'Webhook already processed'
            ];
        }
        
        // For now, let's skip the booking orchestrator and just use the webhook service result
        // TODO: Re-enable booking orchestrator after fixing context resolution
        // if ($event === 'call_ended' && $this->hasBookingData($payload)) {
        //     return $this->processBookingThroughMCP($payload, $correlationId);
        // }
        
        return $result;
    }
    
    /**
     * Process booking through MCP Booking Orchestrator
     */
    protected function processBookingThroughMCP(array $payload, string $correlationId): array
    {
        try {
            // Transform payload to match booking orchestrator expectations
            $bookingPayload = [
                'event_type' => $payload['event'] ?? 'call_ended',
                'call_id' => $payload['call']['call_id'] ?? null,
                'correlation_id' => $correlationId,
                'timestamp' => $payload['call']['end_timestamp'] ?? time(),
                // Include the full payload data
                ...$payload
            ];
            
            // Let the booking orchestrator handle the complete flow
            $result = $this->bookingOrchestrator->handleWebhook($bookingPayload);
            
            if (!$result['success']) {
                Log::warning('MCP Retell: Booking processing failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'correlation_id' => $correlationId
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('MCP Retell: Booking orchestration failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Handle inbound calls with real-time availability checking
     */
    protected function handleInboundCall(Request $request, string $correlationId): JsonResponse
    {
        try {
            $callData = $request->all();
            $toNumber = $callData['call_inbound']['to_number'] ?? null;
            
            if (!$toNumber) {
                Log::warning('MCP Retell: No to_number in inbound call', [
                    'correlation_id' => $correlationId
                ]);
                
                return $this->inboundCallResponse(null, $correlationId);
            }
            
            // Resolve context from phone number
            $context = $this->contextResolver->resolveFromPhone($toNumber);
            
            if (!$context['success']) {
                Log::warning('MCP Retell: Context resolution failed for inbound call', [
                    'phone' => $toNumber,
                    'error' => $context['error'] ?? 'Unknown error',
                    'correlation_id' => $correlationId
                ]);
                
                // Use fallback if available
                return $this->inboundCallResponse(null, $correlationId);
            }
            
            // Set tenant context
            $this->contextResolver->setTenantContext($context['company']['id']);
            
            // Build response with company/branch specific data
            $response = [
                'response' => [
                    'agent_id' => $context['branch']['retell_agent_id'] ?? 
                                 $context['company']['settings']['retell_agent_id'] ?? 
                                 config('services.retell.default_agent_id'),
                    'dynamic_variables' => [
                        'company_name' => $context['company']['name'],
                        'branch_name' => $context['branch']['name'],
                        'caller_number' => $callData['call_inbound']['from_number'] ?? '',
                        'business_hours' => $this->formatBusinessHours($context['branch']['business_hours']),
                        'services_available' => implode(', ', array_column($context['services'], 'name')),
                        'timezone' => $context['branch']['timezone']
                    ]
                ]
            ];
            
            // Check if this is an availability check request
            if ($this->isAvailabilityCheckRequest($callData)) {
                $availabilityData = $this->checkAvailabilityThroughMCP(
                    $callData['dynamic_variables'] ?? [],
                    $context,
                    $correlationId
                );
                
                $response['response']['dynamic_variables'] = array_merge(
                    $response['response']['dynamic_variables'],
                    $availabilityData
                );
            }
            
            Log::info('MCP Retell: Inbound call response prepared', [
                'company_id' => $context['company']['id'],
                'branch_id' => $context['branch']['id'],
                'agent_id' => $response['response']['agent_id'],
                'correlation_id' => $correlationId
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('MCP Retell: Inbound call handling failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            return $this->inboundCallResponse(null, $correlationId, 'Error processing inbound call');
        }
    }
    
    /**
     * Check availability through MCP
     */
    protected function checkAvailabilityThroughMCP(array $variables, array $context, string $correlationId): array
    {
        try {
            $requestedDate = $variables['requested_date'] ?? null;
            $requestedTime = $variables['requested_time'] ?? null;
            $eventTypeId = $variables['event_type_id'] ?? $context['branch']['calcom_event_type_id'];
            
            if (!$requestedDate || !$eventTypeId) {
                return ['availability_checked' => false];
            }
            
            // Create MCP request for availability check
            $mcpRequest = new MCPRequest(
                service: 'calcom',
                operation: 'checkAvailability',
                params: [
                    'event_type_id' => $eventTypeId,
                    'date' => $requestedDate,
                    'time' => $requestedTime,
                    'timezone' => $context['branch']['timezone'],
                    'duration' => $variables['duration'] ?? 30
                ],
                tenantId: $context['company']['id'],
                metadata: [
                    'branch_id' => $context['branch']['id'],
                    'check_type' => 'real_time_voice'
                ],
                correlationId: $correlationId
            );
            
            $mcpResponse = $this->mcpOrchestrator->route($mcpRequest);
            
            if (!$mcpResponse->isSuccess()) {
                Log::warning('MCP Retell: Availability check failed', [
                    'error' => $mcpResponse->getError(),
                    'correlation_id' => $correlationId
                ]);
                
                return [
                    'availability_checked' => true,
                    'available_slots' => 'Verfügbarkeitsprüfung fehlgeschlagen',
                    'slots_count' => 0
                ];
            }
            
            $availabilityData = $mcpResponse->getData();
            
            // Format response for voice
            if ($requestedTime && isset($availabilityData['is_available'])) {
                // Specific time slot check
                if ($availabilityData['is_available']) {
                    return [
                        'availability_checked' => true,
                        'requested_slot_available' => true,
                        'available_slots' => $requestedTime . ' Uhr',
                        'slots_count' => 1
                    ];
                } else {
                    // Find alternatives
                    $alternatives = $this->findAlternativesThroughMCP($requestedDate, $requestedTime, $context, $correlationId);
                    
                    return [
                        'availability_checked' => true,
                        'requested_slot_available' => false,
                        'alternative_slots' => $this->formatAlternativesForVoice($alternatives),
                        'slots_count' => count($alternatives)
                    ];
                }
            } else {
                // General day availability
                $slots = $availabilityData['slots'] ?? [];
                $formattedSlots = $this->formatSlotsForVoice(array_slice($slots, 0, 5));
                
                return [
                    'availability_checked' => true,
                    'available_slots' => $formattedSlots ?: 'keine Termine verfügbar',
                    'slots_count' => count($slots),
                    'day_has_availability' => !empty($slots)
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('MCP Retell: Availability check error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            return [
                'availability_checked' => true,
                'error' => 'Verfügbarkeitsprüfung fehlgeschlagen'
            ];
        }
    }
    
    /**
     * Find alternatives through MCP
     */
    protected function findAlternativesThroughMCP(string $date, string $time, array $context, string $correlationId): array
    {
        $alternatives = $this->bookingOrchestrator->findAlternatives(
            [
                'date' => $date,
                'time' => $time,
                'duration' => 30,
                'service_id' => null
            ],
            $context,
            $correlationId
        );
        
        return $alternatives;
    }
    
    /**
     * Check if request contains booking data
     */
    protected function hasBookingData(array $payload): bool
    {
        $dynamicVars = $payload['call']['retell_llm_dynamic_variables'] ?? [];
        
        // Check if essential booking fields are present
        return !empty($dynamicVars['datum']) || 
               !empty($dynamicVars['date']) ||
               !empty($payload['call']['metadata']['booking_requested']);
    }
    
    /**
     * Check if this is an availability check request
     */
    protected function isAvailabilityCheckRequest(array $callData): bool
    {
        return isset($callData['dynamic_variables']['check_availability']) && 
               $callData['dynamic_variables']['check_availability'] === true;
    }
    
    /**
     * Format slots for voice response
     */
    protected function formatSlotsForVoice(array $slots): string
    {
        if (empty($slots)) {
            return '';
        }
        
        $formatted = array_map(function($slot) {
            $time = Carbon::parse($slot['time'] ?? $slot);
            return $time->format('H:i') . ' Uhr';
        }, $slots);
        
        return implode(', ', $formatted);
    }
    
    /**
     * Format alternatives for voice response
     */
    protected function formatAlternativesForVoice(array $alternatives): string
    {
        if (empty($alternatives)) {
            return 'keine Alternativen gefunden';
        }
        
        $formatted = [];
        foreach ($alternatives as $alt) {
            $datetime = Carbon::parse($alt['datetime'] ?? $alt['date'] . ' ' . $alt['time']);
            $formatted[] = $this->formatDateTimeForVoice($datetime);
        }
        
        // Natural language joining
        if (count($formatted) === 1) {
            return $formatted[0];
        } elseif (count($formatted) === 2) {
            return $formatted[0] . ' oder ' . $formatted[1];
        } else {
            $last = array_pop($formatted);
            return implode(', ', $formatted) . ' oder ' . $last;
        }
    }
    
    /**
     * Format date and time for German voice response
     */
    protected function formatDateTimeForVoice(Carbon $datetime): string
    {
        $weekdays = [
            'Monday' => 'Montag',
            'Tuesday' => 'Dienstag',
            'Wednesday' => 'Mittwoch',
            'Thursday' => 'Donnerstag',
            'Friday' => 'Freitag',
            'Saturday' => 'Samstag',
            'Sunday' => 'Sonntag'
        ];
        
        $today = Carbon::now();
        $tomorrow = Carbon::tomorrow();
        
        if ($datetime->isSameDay($today)) {
            $dateStr = 'heute';
        } elseif ($datetime->isSameDay($tomorrow)) {
            $dateStr = 'morgen';
        } else {
            $weekday = $weekdays[$datetime->format('l')] ?? $datetime->format('l');
            $dateStr = $weekday . ', den ' . $datetime->format('j. F');
        }
        
        return $dateStr . ' um ' . $datetime->format('H:i') . ' Uhr';
    }
    
    /**
     * Format business hours for voice
     */
    protected function formatBusinessHours(?array $hours): string
    {
        if (empty($hours)) {
            return 'Montag bis Freitag 9 bis 18 Uhr';
        }
        
        // TODO: Implement proper business hours formatting
        return 'Montag bis Freitag 9 bis 18 Uhr';
    }
    
    /**
     * Build inbound call response
     */
    protected function inboundCallResponse(?array $context, string $correlationId, ?string $error = null): JsonResponse
    {
        $response = [
            'response' => [
                'agent_id' => config('services.retell.default_agent_id'),
                'dynamic_variables' => [
                    'company_name' => $context['company']['name'] ?? 'AskProAI',
                    'error' => $error
                ]
            ]
        ];
        
        Log::info('MCP Retell: Sending inbound call response', [
            'has_context' => !is_null($context),
            'has_error' => !is_null($error),
            'correlation_id' => $correlationId
        ]);
        
        return response()->json($response);
    }
    
    /**
     * Build success response
     */
    protected function successResponse(array $data, string $correlationId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'correlation_id' => $correlationId
        ]);
    }
    
    /**
     * Build error response
     */
    protected function errorResponse(string $message, int $status, string $correlationId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'correlation_id' => $correlationId
        ], $status);
    }
}