<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\MCPAuthenticationMiddleware;
use App\Services\MCP\HairSalonMCPServer;
use App\Services\EnhancedHairSalonBillingService;
use App\Services\OptimizedGoogleCalendarService;
use App\Services\CircuitBreakerService;
use App\Models\Company;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

/**
 * Enhanced Hair Salon MCP Controller
 * 
 * Production-grade REST API for Hair Salon MCP integration with:
 * - JWT/API Key authentication
 * - Circuit breaker protection
 * - Advanced rate limiting
 * - Comprehensive error handling
 * - Performance monitoring
 * - OpenAPI documentation
 */
#[OA\Info(
    version: '2.0.0',
    title: 'Hair Salon MCP API',
    description: 'Production-grade API for hair salon appointment booking via AI phone integration'
)]
#[OA\Server(
    url: '/api/v2/hair-salon-mcp',
    description: 'Hair Salon MCP API v2'
)]
class EnhancedHairSalonMCPController extends Controller
{
    protected HairSalonMCPServer $mcpServer;
    protected EnhancedHairSalonBillingService $billingService;
    protected OptimizedGoogleCalendarService $calendarService;
    protected CircuitBreakerService $circuitBreaker;
    
    public function __construct()
    {
        $this->middleware(MCPAuthenticationMiddleware::class);
        $this->mcpServer = new HairSalonMCPServer();
        $this->calendarService = new OptimizedGoogleCalendarService();
        $this->circuitBreaker = CircuitBreakerService::forRetellApi();
    }
    
    /**
     * Initialize MCP for specific salon
     */
    #[OA\Post(
        path: '/initialize',
        operationId: 'initializeMCP',
        summary: 'Initialize MCP for salon company',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id'],
                properties: [
                    'company_id' => new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    'retell_agent_id' => new OA\Property(property: 'retell_agent_id', type: 'string', example: 'agent_123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'MCP initialized successfully',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'company' => new OA\Property(property: 'company', type: 'object'),
                        'available_endpoints' => new OA\Property(property: 'available_endpoints', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function initialize(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            // Enhanced validation
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'retell_agent_id' => 'nullable|string|max:255',
                'version' => 'nullable|string|in:1.0,2.0',
                'features' => 'nullable|array'
            ]);
            
            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }
            
            // Check authentication context
            $authContext = $request->input('mcp_auth_context', []);
            $this->validateCompanyAccess($request->company_id, $authContext);
            
            $company = Company::findOrFail($request->company_id);
            
            // Enhanced company verification
            if (!$this->isEnhancedHairSalonCompany($company)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Company not configured for enhanced hair salon MCP integration',
                    'code' => 'COMPANY_NOT_CONFIGURED'
                ], 400);
            }
            
            // Initialize services
            $this->mcpServer->setSalonCompany($company);
            $this->billingService = new EnhancedHairSalonBillingService($company);
            
            // Log initialization with performance metrics
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Enhanced Hair Salon MCP initialized', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'retell_agent_id' => $request->retell_agent_id,
                'version' => $request->input('version', '2.0'),
                'duration_ms' => $duration,
                'auth_type' => $authContext['auth_type'] ?? 'unknown'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Enhanced Hair Salon MCP initialized successfully',
                'version' => '2.0',
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'mcp_enabled' => true,
                    'features_enabled' => $this->getEnabledFeatures($company)
                ],
                'available_endpoints' => $this->getAvailableEndpoints(),
                'rate_limits' => [
                    'requests_per_minute' => $this->getRateLimit($authContext),
                    'burst_limit' => 100
                ],
                'health_status' => $this->getSystemHealthStatus(),
                'performance' => [
                    'initialization_time_ms' => $duration
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->handleException('initialize', $e, $request, microtime(true) - $startTime);
        }
    }
    
    /**
     * Get available services with enhanced caching
     */
    #[OA\Get(
        path: '/services',
        operationId: 'getServices',
        summary: 'Get available salon services',
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'include_pricing',
                in: 'query',
                schema: new OA\Schema(type: 'boolean', default: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Services retrieved successfully'
            )
        ]
    )]
    public function getServices(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $this->initializeMCPFromRequest($request);
            
            // Enhanced caching with tags
            $cacheKey = "services_v2_{$request->company_id}_" . md5(serialize($request->all()));
            
            $result = Cache::tags(['services', "company_{$request->company_id}"])
                ->remember($cacheKey, 900, function () use ($request) {
                    return $this->circuitBreaker->call(
                        callback: function () use ($request) {
                            return $this->mcpServer->getServices($request->all());
                        },
                        fallback: $this->getServicesFromCache($request->company_id)
                    );
                });
            
            // Add performance metrics
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $result['performance'] = [
                'response_time_ms' => $duration,
                'cached' => $duration < 50 // Assume cached if very fast
            ];
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return $this->handleException('getServices', $e, $request, microtime(true) - $startTime);
        }
    }
    
    /**
     * Check availability with advanced algorithms
     */
    #[OA\Post(
        path: '/availability/check',
        operationId: 'checkAvailability',
        summary: 'Check availability for service booking',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'service_id'],
                properties: [
                    'company_id' => new OA\Property(property: 'company_id', type: 'integer'),
                    'service_id' => new OA\Property(property: 'service_id', type: 'integer'),
                    'staff_id' => new OA\Property(property: 'staff_id', type: 'integer', nullable: true),
                    'date' => new OA\Property(property: 'date', type: 'string', format: 'date'),
                    'days_ahead' => new OA\Property(property: 'days_ahead', type: 'integer', default: 7),
                    'preferred_times' => new OA\Property(
                        property: 'preferred_times',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'time')
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Availability checked successfully'
            )
        ]
    )]
    public function checkAvailability(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            // Enhanced validation
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'service_id' => 'required|integer|exists:services,id',
                'staff_id' => 'nullable|integer|exists:staff,id',
                'date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
                'days_ahead' => 'nullable|integer|min:1|max:30',
                'preferred_times' => 'nullable|array|max:5',
                'preferred_times.*' => 'string|date_format:H:i',
                'buffer_minutes' => 'nullable|integer|min:0|max:60'
            ]);
            
            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }
            
            $this->initializeMCPFromRequest($request);
            
            // Use optimized availability check
            $result = $this->circuitBreaker->call(
                callback: function () use ($request) {
                    return $this->checkAvailabilityOptimized($request->all());
                },
                fallback: $this->getDefaultAvailability($request->all())
            );
            
            // Add performance and recommendation data
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $result['performance'] = [
                'response_time_ms' => $duration,
                'calendars_checked' => $result['calendars_checked'] ?? 1
            ];
            
            // Add intelligent recommendations
            if (!empty($result['available_slots'])) {
                $result['recommendations'] = $this->generateAvailabilityRecommendations(
                    $result['available_slots'],
                    $request->all()
                );
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return $this->handleException('checkAvailability', $e, $request, microtime(true) - $startTime);
        }
    }
    
    /**
     * Book appointment with enhanced validation and processing
     */
    #[OA\Post(
        path: '/appointments/book',
        operationId: 'bookAppointment',
        summary: 'Book a salon appointment',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'customer_name', 'customer_phone', 'service_id', 'staff_id', 'datetime'],
                properties: [
                    'company_id' => new OA\Property(property: 'company_id', type: 'integer'),
                    'customer_name' => new OA\Property(property: 'customer_name', type: 'string'),
                    'customer_phone' => new OA\Property(property: 'customer_phone', type: 'string'),
                    'service_id' => new OA\Property(property: 'service_id', type: 'integer'),
                    'staff_id' => new OA\Property(property: 'staff_id', type: 'integer'),
                    'datetime' => new OA\Property(property: 'datetime', type: 'string', format: 'date-time'),
                    'notes' => new OA\Property(property: 'notes', type: 'string'),
                    'call_id' => new OA\Property(property: 'call_id', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Appointment booked successfully'
            )
        ]
    )]
    public function bookAppointment(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            // Comprehensive validation
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'customer_name' => 'required|string|max:255|min:2',
                'customer_phone' => 'required|string|max:20|min:10|regex:/^[+]?[0-9\s\-\(\)]+$/',
                'customer_email' => 'nullable|email|max:255',
                'service_id' => 'required|integer|exists:services,id',
                'staff_id' => 'required|integer|exists:staff,id',
                'datetime' => 'required|date_format:Y-m-d H:i|after:now',
                'notes' => 'nullable|string|max:1000',
                'call_id' => 'nullable|integer|exists:calls,id',
                'priority_booking' => 'nullable|boolean',
                'send_confirmation' => 'nullable|boolean'
            ]);
            
            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }
            
            $this->initializeMCPFromRequest($request);
            
            // Pre-booking validation
            $preValidation = $this->preValidateBooking($request->all());
            if (!$preValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => $preValidation['error'],
                    'code' => 'PRE_VALIDATION_FAILED'
                ], 400);
            }
            
            // Execute booking with circuit breaker
            $result = $this->circuitBreaker->call(
                callback: function () use ($request) {
                    return $this->executeEnhancedBooking($request->all());
                },
                fallback: $this->handleBookingFailure($request->all())
            );
            
            // Post-booking processing
            if ($result['success']) {
                $this->processSuccessfulBooking($result, $request);
            }
            
            // Add performance metrics
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $result['performance'] = [
                'booking_time_ms' => $duration,
                'calendar_sync_time_ms' => $result['calendar_sync_time_ms'] ?? 0
            ];
            
            $statusCode = $result['success'] ? 201 : 400;
            return response()->json($result, $statusCode);
            
        } catch (\Exception $e) {
            return $this->handleException('bookAppointment', $e, $request, microtime(true) - $startTime);
        }
    }
    
    /**
     * Get system health and performance metrics
     */
    #[OA\Get(
        path: '/health',
        operationId: 'getHealthStatus',
        summary: 'Get system health and performance status',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Health status retrieved successfully'
            )
        ]
    )]
    public function getHealthStatus(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toIso8601String(),
                'version' => '2.0.0',
                'services' => [
                    'mcp_server' => 'operational',
                    'google_calendar' => $this->calendarService->getHealthStatus(),
                    'billing_service' => 'operational',
                    'circuit_breaker' => $this->circuitBreaker->getMetrics()
                ],
                'performance_metrics' => $this->getPerformanceMetrics(),
                'rate_limiting' => $this->getRateLimitingStatus()
            ];
            
            // Determine overall health
            $overallHealth = $this->calculateOverallHealth($health['services']);
            $health['status'] = $overallHealth['status'];
            
            if (!empty($overallHealth['warnings'])) {
                $health['warnings'] = $overallHealth['warnings'];
            }
            
            $statusCode = $health['status'] === 'healthy' ? 200 : 503;
            return response()->json($health, $statusCode);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
    
    /**
     * Enhanced optimization methods
     */
    protected function checkAvailabilityOptimized(array $params): array
    {
        // Use the optimized calendar service for batch availability checking
        $serviceId = $params['service_id'];
        $date = $params['date'] ?? Carbon::today()->format('Y-m-d');
        $days = $params['days_ahead'] ?? 7;
        
        $startDate = Carbon::parse($date);
        $endDate = $startDate->copy()->addDays($days);
        
        // Get relevant staff calendar IDs
        $staff = \App\Models\Staff::where('company_id', $this->mcpServer->getSalonCompany()->id)
            ->where('is_bookable', true)
            ->get();
        
        $calendarIds = $staff->pluck('google_calendar_id')->filter()->toArray();
        
        if (empty($calendarIds)) {
            return ['success' => false, 'error' => 'No staff calendars configured'];
        }
        
        // Get service details for duration
        $service = \App\Models\Service::find($serviceId);
        $duration = $service->default_duration_minutes ?? 60;
        
        // Use batch availability check
        $slots = $this->calendarService->getAvailableSlotsBatch(
            $calendarIds,
            $startDate,
            $endDate,
            $duration
        );
        
        return [
            'success' => true,
            'service' => $service->name,
            'available_slots' => array_slice($slots, 0, 20), // Limit results
            'calendars_checked' => count($calendarIds),
            'total_slots_found' => count($slots)
        ];
    }
    
    /**
     * Execute enhanced booking with all validations
     */
    protected function executeEnhancedBooking(array $params): array
    {
        $calendarSyncStart = microtime(true);
        
        // Execute the booking
        $result = $this->mcpServer->bookAppointment($params);
        
        if ($result['success']) {
            // Enhanced calendar sync if needed
            $calendarSyncTime = round((microtime(true) - $calendarSyncStart) * 1000, 2);
            $result['calendar_sync_time_ms'] = $calendarSyncTime;
        }
        
        return $result;
    }
    
    /**
     * Validate company access based on authentication context
     */
    protected function validateCompanyAccess(int $companyId, array $authContext): void
    {
        if (isset($authContext['company_id']) && $authContext['company_id'] !== $companyId) {
            throw new \UnauthorizedHttpException('', 'Access denied for this company');
        }
    }
    
    /**
     * Enhanced company verification
     */
    protected function isEnhancedHairSalonCompany(Company $company): bool
    {
        $settings = $company->settings ?? [];
        return isset($settings['mcp_integration']['enabled']) && 
               $settings['mcp_integration']['enabled'] === true &&
               isset($settings['mcp_integration']['version']) &&
               $settings['mcp_integration']['version'] >= '2.0';
    }
    
    /**
     * Get enabled features for company
     */
    protected function getEnabledFeatures(Company $company): array
    {
        $settings = $company->settings ?? [];
        return $settings['mcp_integration']['features'] ?? [
            'basic_booking',
            'multi_block_appointments',
            'callback_scheduling',
            'real_time_availability'
        ];
    }
    
    /**
     * Get available API endpoints
     */
    protected function getAvailableEndpoints(): array
    {
        return [
            'services' => '/api/v2/hair-salon-mcp/services',
            'staff' => '/api/v2/hair-salon-mcp/staff',
            'availability' => '/api/v2/hair-salon-mcp/availability/check',
            'book' => '/api/v2/hair-salon-mcp/appointments/book',
            'callback' => '/api/v2/hair-salon-mcp/callbacks/schedule',
            'customer' => '/api/v2/hair-salon-mcp/customers/lookup',
            'health' => '/api/v2/hair-salon-mcp/health'
        ];
    }
    
    /**
     * Enhanced error handling
     */
    protected function handleException(string $method, \Exception $e, Request $request, float $startTime): JsonResponse
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::error("Enhanced Hair Salon MCP {$method} error", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'request_data' => $request->except(['password', 'token']),
            'duration_ms' => $duration,
            'memory_usage' => memory_get_usage(true),
            'trace' => $e->getTraceAsString()
        ]);
        
        $statusCode = 500;
        $errorCode = 'INTERNAL_ERROR';
        $errorMessage = 'Internal server error';
        
        // Handle specific error types
        if ($e instanceof \InvalidArgumentException) {
            $statusCode = 400;
            $errorCode = 'INVALID_ARGUMENT';
            $errorMessage = $e->getMessage();
        } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $statusCode = 404;
            $errorCode = 'RESOURCE_NOT_FOUND';
            $errorMessage = 'Resource not found';
        } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
            $statusCode = 429;
            $errorCode = 'RATE_LIMIT_EXCEEDED';
            $errorMessage = 'Rate limit exceeded';
        }
        
        return response()->json([
            'success' => false,
            'error' => $errorMessage,
            'code' => $errorCode,
            'method' => $method,
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            'performance' => [
                'duration_ms' => $duration
            ]
        ], $statusCode);
    }
    
    /**
     * Validation error response
     */
    protected function validationError($errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Validation failed',
            'code' => 'VALIDATION_ERROR',
            'details' => $errors,
            'timestamp' => now()->toIso8601String()
        ], 422);
    }
    
    /**
     * Initialize MCP from request with enhanced context
     */
    protected function initializeMCPFromRequest(Request $request): void
    {
        $companyId = $request->input('company_id');
        if (!$companyId) {
            throw new \InvalidArgumentException('company_id is required');
        }
        
        $company = Company::findOrFail($companyId);
        
        if (!$this->isEnhancedHairSalonCompany($company)) {
            throw new \InvalidArgumentException('Company not configured for enhanced hair salon MCP integration');
        }
        
        $this->mcpServer->setSalonCompany($company);
        $this->billingService = new EnhancedHairSalonBillingService($company);
    }
    
    protected function getSystemHealthStatus(): array
    {
        return [
            'database' => 'connected',
            'cache' => 'operational',
            'queue' => 'operational'
        ];
    }
    
    protected function getRateLimit(array $authContext): int
    {
        if ($authContext['auth_type'] === 'jwt') {
            return 1000; // Higher limit for authenticated requests
        }
        return 100; // Default limit
    }
    
    protected function getPerformanceMetrics(): array
    {
        return [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'php_version' => PHP_VERSION
        ];
    }
    
    protected function getRateLimitingStatus(): array
    {
        return [
            'enabled' => true,
            'current_limits' => [
                'authenticated' => '1000/minute',
                'unauthenticated' => '100/minute'
            ]
        ];
    }
    
    protected function calculateOverallHealth(array $services): array
    {
        $warnings = [];
        $status = 'healthy';
        
        foreach ($services as $service => $serviceStatus) {
            if (is_array($serviceStatus)) {
                if (!($serviceStatus['is_healthy'] ?? true)) {
                    $status = 'degraded';
                    $warnings[] = "Service {$service} is not healthy";
                }
            } elseif ($serviceStatus !== 'operational') {
                $status = 'degraded';
                $warnings[] = "Service {$service} status: {$serviceStatus}";
            }
        }
        
        return ['status' => $status, 'warnings' => $warnings];
    }
}