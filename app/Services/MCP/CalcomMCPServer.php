<?php

namespace App\Services\MCP;

use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\CircuitBreaker\CircuitState;
use App\Exceptions\CircuitBreakerOpenException;
use Carbon\Carbon;

class CalcomMCPServer
{
    protected array $config;
    protected CircuitBreaker $circuitBreaker;
    
    public function __construct()
    {
        $this->config = [
            'cache' => [
                'ttl' => 300,
                'prefix' => 'mcp:calcom'
            ],
            'circuit_breaker' => [
                'failure_threshold' => 5,
                'success_threshold' => 2,
                'timeout' => 60,
                'half_open_requests' => 3
            ]
        ];
        
        $this->circuitBreaker = new CircuitBreaker(
            $this->config['circuit_breaker']['failure_threshold'],
            $this->config['circuit_breaker']['success_threshold'],
            $this->config['circuit_breaker']['timeout'],
            $this->config['circuit_breaker']['half_open_requests']
        );
    }
    
    /**
     * Get event types for a company
     */
    public function getEventTypes(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        $cacheKey = $this->getCacheKey('event_types', ['company_id' => $companyId]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId) {
            try {
                $company = Company::find($companyId);
                if (!$company) {
                    return ['error' => 'Company not found'];
                }
                
                // Use company API key or fall back to default
                $apiKey = $company->calcom_api_key 
                    ? decrypt($company->calcom_api_key) 
                    : config('services.calcom.api_key');
                    
                if (!$apiKey) {
                    return ['error' => 'No Cal.com API key configured'];
                }
                
                $calcomService = new CalcomV2Service($apiKey);
                $response = $calcomService->getEventTypes();
                
                if ($response['success']) {
                    return [
                        'event_types' => $response['data'],
                        'count' => count($response['data']),
                        'company' => $company->name
                    ];
                }
                
                return ['error' => 'Failed to fetch event types', 'message' => $response['error'] ?? 'Unknown error'];
                
            } catch (\Exception $e) {
                Log::error('MCP CalCom getEventTypes error', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
            }
        });
    }
    
    /**
     * Check availability for a specific date/time with caching and circuit breaker
     */
    public function checkAvailability(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $eventTypeId = $params['event_type_id'] ?? null;
        $dateFrom = $params['date_from'] ?? now()->format('Y-m-d');
        $dateTo = $params['date_to'] ?? now()->addDays(7)->format('Y-m-d');
        $timezone = $params['timezone'] ?? 'Europe/Berlin';
        
        if (!$companyId || !$eventTypeId) {
            return ['error' => 'company_id and event_type_id are required'];
        }
        
        // Cache key for availability
        $cacheKey = $this->getCacheKey('availability', [
            'company_id' => $companyId,
            'event_type_id' => $eventTypeId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'timezone' => $timezone
        ]);
        
        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            Log::debug('MCP CalCom availability from cache', ['cache_key' => $cacheKey]);
            return $cached;
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return ['error' => 'Company not found or Cal.com not configured'];
            }
            
            // Execute with circuit breaker protection
            $result = $this->circuitBreaker->call('calcom_availability', function () use ($company, $eventTypeId, $dateFrom, $dateTo, $timezone) {
                $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
                
                return $calcomService->getAvailability([
                    'eventTypeId' => $eventTypeId,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'timeZone' => $timezone
                ]);
            }, function () {
                // Fallback when circuit is open
                return [
                    'success' => false,
                    'error' => 'Service temporarily unavailable',
                    'fallback' => true
                ];
            });
            
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'available_slots' => $result['data'],
                    'date_range' => [
                        'from' => $dateFrom,
                        'to' => $dateTo
                    ],
                    'event_type_id' => $eventTypeId,
                    'timezone' => $timezone,
                    'cached_until' => now()->addSeconds($this->config['cache']['ttl'])->toIso8601String()
                ];
                
                // Cache successful response
                Cache::put($cacheKey, $response, $this->config['cache']['ttl']);
                
                return $response;
            }
            
            return [
                'success' => false,
                'error' => 'Failed to check availability',
                'message' => $result['error'] ?? 'Unknown error',
                'fallback' => $result['fallback'] ?? false
            ];
            
        } catch (CircuitBreakerOpenException $e) {
            Log::warning('MCP CalCom circuit breaker open', [
                'service' => 'calcom_availability',
                'message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Service temporarily unavailable',
                'message' => 'Please try again later',
                'circuit_breaker_open' => true
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom checkAvailability error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Exception occurred',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get bookings for a company
     */
    public function getBookings(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $status = $params['status'] ?? null;
        $dateFrom = $params['date_from'] ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $params['date_to'] ?? now()->format('Y-m-d');
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return ['error' => 'Company not found or Cal.com not configured'];
            }
            
            $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
            
            $queryParams = [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ];
            
            if ($status) {
                $queryParams['status'] = $status;
            }
            
            $response = $calcomService->getBookings($queryParams);
            
            if ($response['success']) {
                return [
                    'bookings' => $response['data'],
                    'count' => count($response['data']),
                    'filters' => [
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'status' => $status
                    ]
                ];
            }
            
            return ['error' => 'Failed to fetch bookings', 'message' => $response['error'] ?? 'Unknown error'];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom getBookings error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update Event Type Settings (nur synchronisierbare Felder)
     */
    public function updateEventTypeSettings(array $params): array
    {
        $eventTypeId = $params['event_type_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$eventTypeId || !$companyId) {
            return ['error' => 'event_type_id and company_id are required'];
        }
        
        // Finde lokalen Event Type
        $eventType = CalcomEventType::where('calcom_numeric_event_type_id', $eventTypeId)
            ->where('company_id', $companyId)
            ->first();
            
        if (!$eventType) {
            return ['error' => 'Event type not found'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return ['error' => 'Company not found or Cal.com not configured'];
            }
            
            // Bereite Update-Daten vor (nur synchronisierbare Felder)
            $updateData = [];
            
            // Basis-Informationen
            if (isset($params['name'])) $updateData['title'] = $params['name'];
            if (isset($params['description'])) $updateData['description'] = $params['description'];
            if (isset($params['duration_minutes'])) $updateData['length'] = $params['duration_minutes'];
            
            // Buchungseinstellungen
            if (isset($params['minimum_booking_notice'])) {
                $updateData['minimumBookingNotice'] = $params['minimum_booking_notice'];
            }
            if (isset($params['booking_future_limit'])) {
                $updateData['periodDays'] = $params['booking_future_limit'];
            }
            if (isset($params['buffer_before']) || isset($params['buffer_after'])) {
                $updateData['beforeEventBuffer'] = $params['buffer_before'] ?? 0;
                $updateData['afterEventBuffer'] = $params['buffer_after'] ?? 0;
            }
            
            // Locations
            if (isset($params['locations'])) {
                $updateData['locations'] = $params['locations'];
            }
            
            // Limits
            if (isset($params['max_bookings_per_day'])) {
                $updateData['bookingLimits'] = [
                    'PER_DAY' => $params['max_bookings_per_day']
                ];
            }
            if (isset($params['seats_per_time_slot'])) {
                $updateData['seatsPerTimeSlot'] = $params['seats_per_time_slot'];
            }
            
            // Update via Cal.com API
            $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
            $result = $calcomService->updateEventType($eventTypeId, $updateData);
            
            if ($result['success'] ?? false) {
                // Update lokale Daten
                $eventType->fill($params);
                $eventType->last_synced_at = now();
                $eventType->sync_status = 'synced';
                
                // Update Checklist
                if (isset($params['name']) || isset($params['duration_minutes'])) {
                    $eventType->updateChecklistItem('basic_info', true);
                }
                if (isset($params['minimum_booking_notice'])) {
                    $eventType->updateChecklistItem('booking_settings', true);
                }
                if (isset($params['locations']) && !empty($params['locations'])) {
                    $eventType->updateChecklistItem('locations', true);
                }
                
                $eventType->save();
                
                return [
                    'success' => true,
                    'event_type' => $eventType->fresh(),
                    'message' => 'Event type settings updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to update in Cal.com',
                'details' => $result['error'] ?? 'Unknown error'
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom updateEventTypeSettings error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generate Cal.com direct link for specific settings
     */
    public function generateCalcomDirectLink(array $params): array
    {
        $eventTypeId = $params['event_type_id'] ?? null;
        $section = $params['section'] ?? 'setup';
        
        if (!$eventTypeId) {
            return ['error' => 'event_type_id is required'];
        }
        
        $baseUrl = config('services.calcom.app_url', 'https://app.cal.com');
        
        // Verschiedene Sections in Cal.com
        $sectionMap = [
            'setup' => '',
            'availability' => '?tabName=availability',
            'limits' => '?tabName=limits',
            'advanced' => '?tabName=advanced',
            'workflows' => '?tabName=workflows',
            'webhooks' => '?tabName=webhooks',
            'team' => '?tabName=team'
        ];
        
        $sectionPath = $sectionMap[$section] ?? '';
        $url = "{$baseUrl}/event-types/{$eventTypeId}{$sectionPath}";
        
        // Section names in German
        $sectionNames = [
            'setup' => 'Grundeinstellungen',
            'availability' => 'Verfügbarkeiten',
            'limits' => 'Limits & Beschränkungen',
            'advanced' => 'Erweiterte Einstellungen',
            'workflows' => 'Workflows & Benachrichtigungen',
            'webhooks' => 'Webhooks',
            'team' => 'Team-Einstellungen'
        ];
        
        $instructions = $this->getInstructionsForSection($section);
        
        return [
            'success' => true,
            'url' => $url,
            'section' => $section,
            'section_name' => $sectionNames[$section] ?? ucfirst($section),
            'instructions' => $instructions['steps'][0] ?? 'Konfigurieren Sie diese Einstellungen in Cal.com'
        ];
    }
    
    /**
     * Get instructions for specific Cal.com section
     */
    protected function getInstructionsForSection(string $section): array
    {
        $instructions = [
            'availability' => [
                'title' => 'Verfügbarkeiten einstellen',
                'steps' => [
                    '1. Wählen Sie einen Schedule oder erstellen Sie einen neuen',
                    '2. Definieren Sie Ihre Arbeitszeiten',
                    '3. Fügen Sie Ausnahmen für Feiertage hinzu',
                    '4. Speichern Sie die Änderungen'
                ]
            ],
            'advanced' => [
                'title' => 'Erweiterte Einstellungen',
                'steps' => [
                    '1. Custom Fields: Fügen Sie benutzerdefinierte Felder hinzu',
                    '2. Bestätigungen: Aktivieren Sie manuelle Bestätigungen',
                    '3. Erinnerungen: Konfigurieren Sie E-Mail/SMS Erinnerungen'
                ]
            ],
            'workflows' => [
                'title' => 'Workflows & Benachrichtigungen',
                'steps' => [
                    '1. Erstellen Sie automatische E-Mails',
                    '2. Konfigurieren Sie SMS-Benachrichtigungen',
                    '3. Richten Sie Webhook-Trigger ein'
                ]
            ]
        ];
        
        return $instructions[$section] ?? ['title' => 'Einstellungen', 'steps' => []];
    }
    
    /**
     * Validate Event Type Configuration
     */
    public function validateEventTypeConfiguration(array $params): array
    {
        $eventTypeId = $params['event_type_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$eventTypeId || !$companyId) {
            return ['error' => 'event_type_id and company_id are required'];
        }
        
        try {
            $eventType = CalcomEventType::where('calcom_numeric_event_type_id', $eventTypeId)
                ->where('company_id', $companyId)
                ->first();
                
            if (!$eventType) {
                return ['error' => 'Event type not found'];
            }
            
            // Validierungsprüfungen
            $issues = [];
            
            // Basis-Validierung
            if (empty($eventType->name)) {
                $issues[] = ['field' => 'name', 'message' => 'Name fehlt'];
            }
            if (!$eventType->duration_minutes || $eventType->duration_minutes < 5) {
                $issues[] = ['field' => 'duration', 'message' => 'Ungültige Dauer'];
            }
            
            // Verfügbarkeit
            if (empty($eventType->schedule_id)) {
                $issues[] = ['field' => 'availability', 'message' => 'Keine Verfügbarkeiten definiert'];
            }
            
            // Locations
            if (empty($eventType->locations)) {
                $issues[] = ['field' => 'locations', 'message' => 'Keine Standorte/Orte definiert'];
            }
            
            // Staff Assignment
            if ($eventType->assignedStaff()->count() === 0) {
                $issues[] = ['field' => 'staff', 'message' => 'Keine Mitarbeiter zugewiesen'];
            }
            
            $isValid = empty($issues);
            
            return [
                'success' => true,
                'valid' => $isValid,
                'issues' => $issues,
                'setup_progress' => $eventType->getSetupProgress(),
                'setup_status' => $eventType->setup_status
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom validateEventTypeConfiguration error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get event type assignments
     */
    public function getEventTypeAssignments(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        $cacheKey = $this->getCacheKey('assignments', ['company_id' => $companyId]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId) {
            try {
                $branches = Branch::where('company_id', $companyId)
                    ->with(['staff', 'services'])
                    ->get();
                
                $eventTypes = CalcomEventType::where('company_id', $companyId)
                    ->with(['staffAssignments.staff'])
                    ->get();
                
                $assignments = [];
                
                foreach ($branches as $branch) {
                    $branchData = [
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name,
                        'calcom_event_type_id' => $branch->calcom_event_type_id,
                        'staff' => []
                    ];
                    
                    foreach ($branch->staff as $staff) {
                        $staffEventTypes = $eventTypes->filter(function ($et) use ($staff) {
                            return $et->staffAssignments->contains('staff_id', $staff->id);
                        });
                        
                        $branchData['staff'][] = [
                            'staff_id' => $staff->id,
                            'staff_name' => $staff->name,
                            'assigned_event_types' => $staffEventTypes->map(function ($et) {
                                return [
                                    'id' => $et->id,
                                    'title' => $et->title,
                                    'slug' => $et->slug
                                ];
                            })->values()
                        ];
                    }
                    
                    $assignments[] = $branchData;
                }
                
                return [
                    'company_id' => $companyId,
                    'branches' => $assignments,
                    'total_event_types' => $eventTypes->count(),
                    'generated_at' => now()->toIso8601String()
                ];
                
            } catch (\Exception $e) {
                Log::error('MCP CalCom getEventTypeAssignments error', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => 'Failed to get assignments', 'message' => $e->getMessage()];
            }
        });
    }
    
    /**
     * Create a booking via Cal.com API with retry logic and circuit breaker
     */
    public function createBooking(array $params): array
    {
        // Validate required fields
        $requiredFields = ['company_id', 'event_type_id', 'start', 'name', 'email'];
        foreach ($requiredFields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                return [
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => "Field '{$field}' is required"
                ];
            }
        }
        
        $companyId = $params['company_id'];
        $eventTypeId = $params['event_type_id'];
        $start = $params['start'];
        $end = $params['end'] ?? null;
        $name = $params['name'];
        $email = $params['email'];
        $phone = $params['phone'] ?? null;
        $notes = $params['notes'] ?? null;
        $timezone = $params['timezone'] ?? 'Europe/Berlin';
        $metadata = $params['metadata'] ?? [];
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return [
                    'success' => false,
                    'error' => 'configuration_error',
                    'message' => 'Company not found or Cal.com not configured'
                ];
            }
            
            // Generate idempotency key for this booking attempt
            $idempotencyKey = $this->generateIdempotencyKey($params);
            
            // Check if this booking was already created (idempotency check)
            $existingBookingKey = "mcp:calcom:booking:{$idempotencyKey}";
            if ($existingBooking = Cache::get($existingBookingKey)) {
                Log::info('MCP CalCom returning existing booking (idempotency)', [
                    'idempotency_key' => $idempotencyKey
                ]);
                return $existingBooking;
            }
            
            // Execute with circuit breaker and retry logic
            $maxRetries = 3;
            $retryDelay = 1; // seconds
            $lastError = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $result = $this->circuitBreaker->call('calcom_booking', function () use ($company, $eventTypeId, $start, $end, $name, $email, $phone, $notes, $timezone, $metadata) {
                        $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
                        
                        // If end time not provided, calculate based on event type
                        if (!$end) {
                            $eventType = CalcomEventType::where('company_id', $company->id)
                                ->where('calcom_numeric_event_type_id', $eventTypeId)
                                ->first();
                            
                            if ($eventType) {
                                $startTime = Carbon::parse($start);
                                $end = $startTime->copy()->addMinutes($eventType->duration_minutes)->toIso8601String();
                            } else {
                                // Default to 30 minutes if event type not found
                                Log::warning('CalcomEventType not found, using default 30 minutes', [
                                    'company_id' => $company->id,
                                    'event_type_id' => $eventTypeId
                                ]);
                                $startTime = Carbon::parse($start);
                                $end = $startTime->copy()->addMinutes(30)->toIso8601String();
                            }
                        }
                        
                        // Ensure metadata values are strings
                        $stringMetadata = [];
                        foreach ($metadata as $key => $value) {
                            $stringMetadata[$key] = (string)$value;
                        }
                        
                        // Add teamId for team event types
                        $bookingCustomerData = [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'timeZone' => $timezone,
                            'metadata' => $stringMetadata
                        ];
                        
                        // Check if this is a team event type
                        $eventType = CalcomEventType::where('company_id', $company->id)
                            ->where('calcom_numeric_event_type_id', $eventTypeId)
                            ->first();
                            
                        if ($eventType && $eventType->is_team_event && $eventType->team_id) {
                            $bookingCustomerData['teamId'] = $eventType->team_id;
                            Log::info('MCP CalCom: Adding team ID for team event', [
                                'event_type_id' => $eventTypeId,
                                'team_id' => $eventType->team_id
                            ]);
                        }
                        
                        return $calcomService->bookAppointment(
                            $eventTypeId,
                            $start,
                            $end,
                            $bookingCustomerData,
                            $notes
                        );
                    });
                    
                    if ($result && isset($result['id'])) {
                        $response = [
                            'success' => true,
                            'booking' => [
                                'id' => $result['id'],
                                'uid' => $result['uid'] ?? null,
                                'start' => $start,
                                'end' => $end,
                                'status' => $result['status'] ?? 'ACCEPTED',
                                'event_type_id' => $eventTypeId
                            ],
                            'message' => 'Booking created successfully',
                            'attempts' => $attempt
                        ];
                        
                        // Cache successful booking for idempotency (24 hours)
                        Cache::put($existingBookingKey, $response, 86400);
                        
                        // Clear availability cache for this event type
                        $this->clearAvailabilityCache($companyId, $eventTypeId);
                        
                        Log::info('MCP CalCom booking created', [
                            'booking_id' => $result['id'],
                            'attempts' => $attempt
                        ]);
                        
                        return $response;
                    }
                    
                    $lastError = 'Booking creation failed - API returned empty response';
                    
                } catch (CircuitBreakerOpenException $e) {
                    return [
                        'success' => false,
                        'error' => 'service_unavailable',
                        'message' => 'Booking service temporarily unavailable',
                        'circuit_breaker_open' => true
                    ];
                    
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::warning('MCP CalCom booking attempt failed', [
                        'attempt' => $attempt,
                        'error' => $lastError
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        $retryDelay *= 2; // Exponential backoff
                    }
                }
            }
            
            // All retries failed
            Log::error('MCP CalCom booking failed after all retries', [
                'params' => $params,
                'last_error' => $lastError
            ]);
            
            return [
                'success' => false,
                'error' => 'booking_failed',
                'message' => $lastError ?? 'Booking creation failed after multiple attempts',
                'attempts' => $maxRetries
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom createBooking error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'error' => 'exception',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync event types from Cal.com
     */
    public function syncEventTypes(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return ['error' => 'Company not found or Cal.com not configured'];
            }
            
            // Clear cache first
            $this->clearCache(['company_id' => $companyId]);
            
            $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
            $response = $calcomService->getEventTypes();
            
            if (!$response['success']) {
                return ['error' => 'Failed to fetch event types from Cal.com'];
            }
            
            $synced = 0;
            $errors = [];
            
            foreach ($response['data'] as $eventTypeData) {
                try {
                    CalcomEventType::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'calcom_id' => $eventTypeData['id']
                        ],
                        [
                            'title' => $eventTypeData['title'],
                            'slug' => $eventTypeData['slug'],
                            'description' => $eventTypeData['description'] ?? null,
                            'length' => $eventTypeData['length'],
                            'metadata' => $eventTypeData,
                            'is_active' => true
                        ]
                    );
                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'event_type_id' => $eventTypeData['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'success' => true,
                'synced_count' => $synced,
                'total_count' => count($response['data']),
                'errors' => $errors,
                'synced_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom syncEventTypes error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Sync failed', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test Cal.com connection
     */
    public function testConnection(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company) {
                return ['error' => 'Company not found'];
            }
            
            if (!$company->calcom_api_key) {
                return [
                    'connected' => false,
                    'message' => 'Cal.com API key not configured'
                ];
            }
            
            $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
            $response = $calcomService->getMe();
            
            if ($response['success']) {
                return [
                    'connected' => true,
                    'user' => $response['data'],
                    'company' => $company->name,
                    'tested_at' => now()->toIso8601String()
                ];
            }
            
            return [
                'connected' => false,
                'message' => 'Connection failed',
                'error' => $response['error'] ?? 'Unknown error'
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom testConnection error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'connected' => false,
                'message' => 'Exception occurred',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params = []): string
    {
        $prefix = $this->config['cache']['prefix'];
        $key = "{$prefix}:{$type}";
        
        if (!empty($params)) {
            $key .= ':' . md5(json_encode($params));
        }
        
        return $key;
    }
    
    /**
     * Update an existing booking
     */
    public function updateBooking(array $params): array
    {
        // Validate required fields
        $requiredFields = ['company_id', 'booking_id'];
        foreach ($requiredFields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                return [
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => "Field '{$field}' is required"
                ];
            }
        }
        
        $companyId = $params['company_id'];
        $bookingId = $params['booking_id'];
        $rescheduleReason = $params['reschedule_reason'] ?? 'Customer requested reschedule';
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return [
                    'success' => false,
                    'error' => 'configuration_error',
                    'message' => 'Company not found or Cal.com not configured'
                ];
            }
            
            // Build update data
            $updateData = [];
            if (isset($params['start'])) {
                $updateData['start'] = $params['start'];
            }
            if (isset($params['end'])) {
                $updateData['end'] = $params['end'];
            }
            if (isset($params['title'])) {
                $updateData['title'] = $params['title'];
            }
            if (isset($params['description'])) {
                $updateData['description'] = $params['description'];
            }
            
            if (empty($updateData)) {
                return [
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => 'No fields to update'
                ];
            }
            
            // Execute with circuit breaker
            $result = $this->circuitBreaker->call('calcom_update_booking', function () use ($company, $bookingId, $updateData, $rescheduleReason) {
                $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
                
                // Cal.com V2 uses PATCH for updates
                return $calcomService->updateBooking($bookingId, array_merge($updateData, [
                    'rescheduleReason' => $rescheduleReason
                ]));
            });
            
            if ($result && $result['success']) {
                // Clear related caches
                $this->clearBookingCache($companyId, $bookingId);
                if (isset($params['event_type_id'])) {
                    $this->clearAvailabilityCache($companyId, $params['event_type_id']);
                }
                
                Log::info('MCP CalCom booking updated', [
                    'booking_id' => $bookingId,
                    'updates' => array_keys($updateData)
                ]);
                
                return [
                    'success' => true,
                    'booking' => $result['data'],
                    'message' => 'Booking updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'update_failed',
                'message' => $result['error'] ?? 'Failed to update booking'
            ];
            
        } catch (CircuitBreakerOpenException $e) {
            return [
                'success' => false,
                'error' => 'service_unavailable',
                'message' => 'Update service temporarily unavailable',
                'circuit_breaker_open' => true
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom updateBooking error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'error' => 'exception',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel a booking
     */
    public function cancelBooking(array $params): array
    {
        // Validate required fields
        $requiredFields = ['company_id', 'booking_id'];
        foreach ($requiredFields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                return [
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => "Field '{$field}' is required"
                ];
            }
        }
        
        $companyId = $params['company_id'];
        $bookingId = $params['booking_id'];
        $cancellationReason = $params['cancellation_reason'] ?? 'Booking cancelled by customer';
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return [
                    'success' => false,
                    'error' => 'configuration_error',
                    'message' => 'Company not found or Cal.com not configured'
                ];
            }
            
            // Execute with circuit breaker
            $result = $this->circuitBreaker->call('calcom_cancel_booking', function () use ($company, $bookingId, $cancellationReason) {
                $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
                
                return $calcomService->cancelBooking($bookingId, $cancellationReason);
            });
            
            if ($result && $result['success']) {
                // Clear related caches
                $this->clearBookingCache($companyId, $bookingId);
                if (isset($params['event_type_id'])) {
                    $this->clearAvailabilityCache($companyId, $params['event_type_id']);
                }
                
                Log::info('MCP CalCom booking cancelled', [
                    'booking_id' => $bookingId,
                    'reason' => $cancellationReason
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Booking cancelled successfully',
                    'booking_id' => $bookingId,
                    'cancelled_at' => now()->toIso8601String()
                ];
            }
            
            return [
                'success' => false,
                'error' => 'cancellation_failed',
                'message' => $result['error'] ?? 'Failed to cancel booking'
            ];
            
        } catch (CircuitBreakerOpenException $e) {
            return [
                'success' => false,
                'error' => 'service_unavailable',
                'message' => 'Cancellation service temporarily unavailable',
                'circuit_breaker_open' => true
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom cancelBooking error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'error' => 'exception',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Find alternative time slots when preferred slot is not available
     */
    public function findAlternativeSlots(array $params): array
    {
        // Validate required fields
        $requiredFields = ['company_id', 'event_type_id', 'preferred_start'];
        foreach ($requiredFields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                return [
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => "Field '{$field}' is required"
                ];
            }
        }
        
        $companyId = $params['company_id'];
        $eventTypeId = $params['event_type_id'];
        $preferredStart = Carbon::parse($params['preferred_start']);
        $searchDays = $params['search_days'] ?? 7;
        $maxAlternatives = $params['max_alternatives'] ?? 5;
        $timezone = $params['timezone'] ?? 'Europe/Berlin';
        
        try {
            // Get availability for the search period
            $dateFrom = $preferredStart->copy()->startOfDay();
            $dateTo = $preferredStart->copy()->addDays($searchDays)->endOfDay();
            
            $availabilityResult = $this->checkAvailability([
                'company_id' => $companyId,
                'event_type_id' => $eventTypeId,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'timezone' => $timezone
            ]);
            
            if (!$availabilityResult['success']) {
                return $availabilityResult;
            }
            
            $availableSlots = $availabilityResult['available_slots'] ?? [];
            if (empty($availableSlots)) {
                return [
                    'success' => true,
                    'alternatives' => [],
                    'message' => 'No alternative slots available in the search period'
                ];
            }
            
            // Find alternatives based on proximity to preferred time
            $alternatives = [];
            $preferredHour = $preferredStart->hour;
            $preferredMinute = $preferredStart->minute;
            
            foreach ($availableSlots as $slot) {
                $slotTime = Carbon::parse($slot['start']);
                
                // Calculate time difference
                $hourDiff = abs($slotTime->hour - $preferredHour);
                $dayDiff = $slotTime->startOfDay()->diffInDays($preferredStart->startOfDay());
                
                // Score based on proximity (lower is better)
                $score = ($dayDiff * 24) + $hourDiff;
                
                $alternatives[] = [
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'date' => $slotTime->format('Y-m-d'),
                    'time' => $slotTime->format('H:i'),
                    'day_of_week' => $slotTime->format('l'),
                    'days_from_preferred' => $dayDiff,
                    'score' => $score
                ];
            }
            
            // Sort by score (closest to preferred time first)
            usort($alternatives, function ($a, $b) {
                return $a['score'] <=> $b['score'];
            });
            
            // Limit results
            $alternatives = array_slice($alternatives, 0, $maxAlternatives);
            
            // Remove score from results
            $alternatives = array_map(function ($alt) {
                unset($alt['score']);
                return $alt;
            }, $alternatives);
            
            return [
                'success' => true,
                'preferred_start' => $preferredStart->toIso8601String(),
                'alternatives' => $alternatives,
                'search_period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d')
                ],
                'total_available' => count($availableSlots),
                'message' => count($alternatives) > 0 ? 'Alternative slots found' : 'No suitable alternatives found'
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom findAlternativeSlots error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'error' => 'exception',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Clear cache
     */
    public function clearCache(array $params = []): void
    {
        if (isset($params['company_id'])) {
            Cache::forget($this->getCacheKey('event_types', ['company_id' => $params['company_id']]));
            Cache::forget($this->getCacheKey('assignments', ['company_id' => $params['company_id']]));
        } else {
            // Clear all CalCom cache
            Cache::flush();
        }
    }
    
    /**
     * Clear availability cache for a specific event type
     */
    protected function clearAvailabilityCache(int $companyId, int $eventTypeId): void
    {
        // Clear all availability cache entries for this event type
        $pattern = $this->config['cache']['prefix'] . ':availability:*' . md5(json_encode([
            'company_id' => $companyId,
            'event_type_id' => $eventTypeId
        ])) . '*';
        
        // Note: This is a simplified version. In production, you might want to use Redis SCAN
        // to find and delete matching keys
        Cache::tags(['calcom', "company_{$companyId}", "event_type_{$eventTypeId}"])->flush();
    }
    
    /**
     * Clear booking cache
     */
    protected function clearBookingCache(int $companyId, int $bookingId): void
    {
        $cacheKey = $this->getCacheKey('booking', [
            'company_id' => $companyId,
            'booking_id' => $bookingId
        ]);
        Cache::forget($cacheKey);
    }
    
    /**
     * Generate idempotency key for booking requests
     */
    protected function generateIdempotencyKey(array $params): string
    {
        $key = $params['company_id'] . ':' . $params['event_type_id'] . ':' . $params['start'] . ':' . $params['email'];
        return md5($key);
    }
}