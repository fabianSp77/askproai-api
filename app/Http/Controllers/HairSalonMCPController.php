<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MCP\HairSalonMCPServer;
use App\Services\HairSalonBillingService;
use App\Models\Company;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Hair Salon MCP HTTP Controller
 * 
 * Provides REST API endpoints for Retell.ai integration:
 * - Service listings with consultation requirements
 * - Staff availability checking
 * - Appointment booking with complex time blocks
 * - Callback scheduling for consultation services
 * - Usage tracking and billing
 */
class HairSalonMCPController extends Controller
{
    protected HairSalonMCPServer $mcpServer;
    protected HairSalonBillingService $billingService;
    
    public function __construct()
    {
        $this->mcpServer = new HairSalonMCPServer();
    }
    
    /**
     * Initialize MCP for specific salon
     */
    public function initialize(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'retell_agent_id' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }
            
            $company = Company::findOrFail($request->company_id);
            
            // Verify this is a hair salon company
            if (!$this->isHairSalonCompany($company)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Company is not configured for hair salon MCP integration'
                ], 400);
            }
            
            $this->mcpServer->setSalonCompany($company);
            $this->billingService = new HairSalonBillingService($company);
            
            // Log MCP initialization
            Log::info('Hair Salon MCP initialized', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'retell_agent_id' => $request->retell_agent_id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Hair Salon MCP initialized successfully',
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'mcp_enabled' => true
                ],
                'available_endpoints' => [
                    'services' => '/api/hair-salon-mcp/services',
                    'staff' => '/api/hair-salon-mcp/staff',
                    'availability' => '/api/hair-salon-mcp/availability',
                    'book' => '/api/hair-salon-mcp/book',
                    'callback' => '/api/hair-salon-mcp/callback',
                    'customer' => '/api/hair-salon-mcp/customer'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Hair Salon MCP initialization failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Initialization failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get available services
     */
    public function getServices(Request $request): JsonResponse
    {
        try {
            $this->initializeMCPFromRequest($request);
            
            $result = $this->mcpServer->getServices($request->all());
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return $this->handleError('getServices', $e, $request);
        }
    }
    
    /**
     * Get staff members
     */
    public function getStaff(Request $request): JsonResponse
    {
        try {
            $this->initializeMCPFromRequest($request);
            
            $result = $this->mcpServer->getStaff($request->all());
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return $this->handleError('getStaff', $e, $request);
        }
    }
    
    /**
     * Check availability
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'service_id' => 'required|integer|exists:services,id',
                'staff_id' => 'nullable|integer|exists:staff,id',
                'date' => 'nullable|date_format:Y-m-d',
                'days' => 'nullable|integer|min:1|max:14'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }
            
            $this->initializeMCPFromRequest($request);
            
            $result = $this->mcpServer->checkAvailability($request->all());
            
            // Cache the result for 5 minutes
            $cacheKey = "availability_" . md5(serialize($request->all()));
            Cache::put($cacheKey, $result, 300);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return $this->handleError('checkAvailability', $e, $request);
        }
    }
    
    /**
     * Book appointment
     */
    public function bookAppointment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'service_id' => 'required|integer|exists:services,id',
                'staff_id' => 'required|integer|exists:staff,id',
                'datetime' => 'required|date_format:Y-m-d H:i',
                'notes' => 'nullable|string|max:1000',
                'call_id' => 'nullable|integer'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }
            
            $this->initializeMCPFromRequest($request);
            
            // Track the booking attempt
            $startTime = microtime(true);
            
            $result = $this->mcpServer->bookAppointment($request->all());
            
            // Track billing if successful
            if ($result['success'] && isset($request->call_id)) {
                $call = Call::find($request->call_id);
                if ($call) {
                    $this->billingService->trackCallUsage($call);
                    
                    // If appointment was created, track that too
                    if (isset($result['appointment_id'])) {
                        $appointment = \App\Models\Appointment::find($result['appointment_id']);
                        if ($appointment) {
                            $this->billingService->trackAppointmentBooking($appointment, $call);
                        }
                    }
                }
            }
            
            // Log booking attempt
            Log::info('Hair Salon booking attempt', [
                'success' => $result['success'],
                'customer_phone' => $request->customer_phone,
                'service_id' => $request->service_id,
                'staff_id' => $request->staff_id,
                'datetime' => $request->datetime,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return $this->handleError('bookAppointment', $e, $request);
        }
    }
    
    /**
     * Schedule callback for consultation
     */
    public function scheduleCallback(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'customer_phone' => 'required|string|max:20',
                'service_id' => 'nullable|integer|exists:services,id',
                'preferred_time' => 'nullable|date_format:Y-m-d H:i',
                'notes' => 'nullable|string|max:1000'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }
            
            $this->initializeMCPFromRequest($request);
            
            // Get or create customer first
            $customer = \App\Models\Customer::firstOrCreate(
                [
                    'phone' => $request->customer_phone,
                    'company_id' => $request->company_id
                ],
                [
                    'name' => $request->customer_name ?? 'Kunde via Telefon',
                    'source' => 'phone'
                ]
            );
            
            $callbackParams = array_merge($request->all(), [
                'customer_id' => $customer->id
            ]);
            
            $result = $this->mcpServer->scheduleCallback($callbackParams);
            
            // Log callback request
            Log::info('Hair Salon callback scheduled', [
                'customer_phone' => $request->customer_phone,
                'service_id' => $request->service_id,
                'preferred_time' => $request->preferred_time,
                'callback_id' => $result['callback_id'] ?? null
            ]);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return $this->handleError('scheduleCallback', $e, $request);
        }
    }
    
    /**
     * Get customer by phone
     */
    public function getCustomer(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'phone' => 'required|string|max:20'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }
            
            $this->initializeMCPFromRequest($request);
            
            $result = $this->mcpServer->getCustomerByPhone($request->all());
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return $this->handleError('getCustomer', $e, $request);
        }
    }
    
    /**
     * Get usage statistics
     */
    public function getUsageStats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }
            
            $company = Company::findOrFail($request->company_id);
            $billingService = new HairSalonBillingService($company);
            
            $stats = $billingService->getCurrentUsageStats();
            
            return response()->json([
                'success' => true,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            return $this->handleError('getUsageStats', $e, $request);
        }
    }
    
    /**
     * Get monthly billing report
     */
    public function getMonthlyReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'month' => 'nullable|date_format:Y-m'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }
            
            $company = Company::findOrFail($request->company_id);
            $billingService = new HairSalonBillingService($company);
            
            $month = $request->month ? Carbon::createFromFormat('Y-m', $request->month) : null;
            $report = $billingService->getMonthlyUsageReport($month);
            
            return response()->json([
                'success' => true,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'report' => $report
            ]);
            
        } catch (\Exception $e) {
            return $this->handleError('getMonthlyReport', $e, $request);
        }
    }
    
    /**
     * Health check endpoint
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toIso8601String(),
                'services' => [
                    'mcp_server' => 'operational',
                    'google_calendar' => 'operational',
                    'billing_service' => 'operational'
                ],
                'version' => '1.0.0'
            ];
            
            // Test Google Calendar connection
            try {
                $calendarService = new \App\Services\GoogleCalendarService();
                // Test with a known calendar ID
                $testCalendarId = '8356d9e1f6480e139b45d109b4ccfd9d293bfe3b0a72d6f626dbfd6c03142a6a@group.calendar.google.com';
                $calendarService->validateCalendarAccess($testCalendarId);
            } catch (\Exception $e) {
                $health['services']['google_calendar'] = 'degraded';
                $health['warnings'][] = 'Google Calendar connection issues';
            }
            
            return response()->json($health);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
    
    /**
     * Initialize MCP from request
     */
    protected function initializeMCPFromRequest(Request $request): void
    {
        $companyId = $request->input('company_id');
        if (!$companyId) {
            throw new \InvalidArgumentException('company_id is required');
        }
        
        $company = Company::findOrFail($companyId);
        
        if (!$this->isHairSalonCompany($company)) {
            throw new \InvalidArgumentException('Company is not configured for hair salon MCP integration');
        }
        
        $this->mcpServer->setSalonCompany($company);
        $this->billingService = new HairSalonBillingService($company);
    }
    
    /**
     * Check if company is configured for hair salon
     */
    protected function isHairSalonCompany(Company $company): bool
    {
        $settings = $company->settings ?? [];
        return isset($settings['mcp_integration']['enabled']) && 
               $settings['mcp_integration']['enabled'] === true;
    }
    
    /**
     * Handle errors consistently
     */
    protected function handleError(string $method, \Exception $e, Request $request): JsonResponse
    {
        Log::error("Hair Salon MCP {$method} error", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'request_data' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $statusCode = 500;
        $errorMessage = 'Internal server error';
        
        // Handle specific error types
        if ($e instanceof \InvalidArgumentException) {
            $statusCode = 400;
            $errorMessage = $e->getMessage();
        } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $statusCode = 404;
            $errorMessage = 'Resource not found';
        }
        
        return response()->json([
            'success' => false,
            'error' => $errorMessage,
            'method' => $method,
            'timestamp' => now()->toIso8601String()
        ], $statusCode);
    }
}