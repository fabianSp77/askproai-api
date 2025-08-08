<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MCP\HairSalonMCPServer;
use Illuminate\Support\Facades\Log;

/**
 * MCP Bridge Controller for Retell.ai
 * 
 * This controller acts as a bridge between Retell.ai's MCP protocol
 * and our Hair Salon MCP Server implementation.
 */
class RetellMCPBridgeController extends Controller
{
    private HairSalonMCPServer $mcpServer;
    
    public function __construct(HairSalonMCPServer $mcpServer)
    {
        $this->mcpServer = $mcpServer;
        
        // Always ensure default company is set
        $defaultCompany = \App\Models\Company::find(1);
        if ($defaultCompany) {
            $this->mcpServer->setSalonCompany($defaultCompany);
        }
    }
    
    /**
     * Main MCP endpoint that handles all MCP requests from Retell
     */
    public function handle(Request $request)
    {
        // Handle CORS preflight
        if ($request->method() === 'OPTIONS') {
            return response('', 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With, Authorization',
                'Access-Control-Max-Age' => '3600'
            ]);
        }
        
        Log::info('Retell MCP Request received', [
            'method' => $request->method(),
            'path' => $request->path(),
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);
        
        // Handle MCP protocol
        $method = $request->input('method');
        $params = $request->input('params', []);
        $id = $request->input('id');
        
        // Support both 'method' and 'tool' fields for Retell compatibility
        if (!$method && $request->has('tool')) {
            $method = $request->input('tool');
        }
        
        // If no method in body, check URL path
        if (!$method) {
            $path = $request->path();
            if (str_contains($path, '/list_services')) {
                $method = 'list_services';
            } elseif (str_contains($path, '/check_availability')) {
                $method = 'check_availability';
            } elseif (str_contains($path, '/book_appointment')) {
                $method = 'book_appointment';
            } elseif (str_contains($path, '/schedule_callback')) {
                $method = 'schedule_callback';
            } elseif (str_contains($path, '/initialize')) {
                $method = 'initialize';
            }
        }
        
        try {
            // Set default company_id if not provided
            if (!isset($params['company_id'])) {
                $params['company_id'] = $request->query('company_id', 1);
            }
            
            // Route to appropriate method
            $result = match($method) {
                'list_services', 'tools/list_services' => $this->handleListServices($params),
                'check_availability', 'tools/check_availability' => $this->handleCheckAvailability($params),
                'book_appointment', 'tools/book_appointment' => $this->handleBookAppointment($params),
                'schedule_callback', 'tools/schedule_callback' => $this->handleScheduleCallback($params),
                'initialize', 'tools/list' => $this->handleInitialize($params),
                default => $this->handleUnknownMethod($method)
            };
            
            // Return MCP-formatted response with proper UTF-8 encoding and CORS headers
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id ?? null,
                'result' => $result
            ], 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With',
                'Content-Type' => 'application/json; charset=utf-8'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
        } catch (\Exception $e) {
            Log::error('MCP Bridge Error', [
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id ?? null,
                'error' => [
                    'code' => -32603,
                    'message' => $e->getMessage()
                ]
            ], 500, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With',
                'Content-Type' => 'application/json; charset=utf-8'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
    
    /**
     * List available services
     */
    private function handleListServices(array $params)
    {
        // Ensure company is set
        if (isset($params['company_id'])) {
            $company = \App\Models\Company::find($params['company_id']);
            if ($company) {
                $this->mcpServer->setSalonCompany($company);
                \Log::info('MCP: Company set', ['company' => $company->name, 'id' => $company->id]);
            } else {
                \Log::warning('MCP: Company not found', ['company_id' => $params['company_id']]);
            }
        }
        
        $result = $this->mcpServer->getServices($params);
        \Log::info('MCP: getServices result', [
            'success' => $result['success'] ?? false,
            'services_count' => isset($result['services']) ? count($result['services']) : 0,
            'error' => $result['error'] ?? null
        ]);
        
        // Transform for Retell MCP format
        if ($result['success'] && isset($result['services'])) {
            return [
                'services' => array_map(function($service) {
                    return [
                        'id' => $service['id'],
                        'name' => $service['name'],
                        'duration' => $service['duration_minutes'] ?? $service['duration'] ?? 30,
                        'price' => $service['price'],
                        'requires_consultation' => $service['requires_consultation'] ?? false,
                        'description' => $service['description'] ?? ''
                    ];
                }, $result['services'])
            ];
        }
        
        throw new \Exception($result['error'] ?? 'Failed to fetch services');
    }
    
    /**
     * Check availability for appointments
     */
    private function handleCheckAvailability(array $params)
    {
        // Ensure company is set
        if (isset($params['company_id'])) {
            $company = \App\Models\Company::find($params['company_id']);
            if ($company) {
                $this->mcpServer->setSalonCompany($company);
            }
        }
        
        // Validate required parameters
        if (!isset($params['service_id'])) {
            throw new \Exception('service_id is required');
        }
        
        if (!isset($params['date'])) {
            $params['date'] = now()->format('Y-m-d');
        }
        
        $result = $this->mcpServer->checkAvailability($params);
        
        if ($result['success'] && isset($result['available_slots'])) {
            return [
                'available_slots' => $result['available_slots'],
                'service_id' => $params['service_id'],
                'date' => $params['date']
            ];
        }
        
        throw new \Exception($result['error'] ?? 'No availability found');
    }
    
    /**
     * Book an appointment
     */
    private function handleBookAppointment(array $params)
    {
        // Ensure company is set
        if (isset($params['company_id'])) {
            $company = \App\Models\Company::find($params['company_id']);
            if ($company) {
                $this->mcpServer->setSalonCompany($company);
            }
        }
        
        // Validate required parameters
        $required = ['customer_name', 'customer_phone', 'service_id', 'staff_id', 'datetime'];
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \Exception("$field is required");
            }
        }
        
        $result = $this->mcpServer->bookAppointment($params);
        
        if ($result['success']) {
            return [
                'booking_confirmed' => true,
                'appointment_id' => $result['appointment']['id'] ?? null,
                'datetime' => $params['datetime'],
                'service' => $result['appointment']['service']['name'] ?? 'Service',
                'staff' => $result['appointment']['staff']['name'] ?? 'Staff',
                'message' => 'Termin erfolgreich gebucht'
            ];
        }
        
        throw new \Exception($result['error'] ?? 'Booking failed');
    }
    
    /**
     * Schedule a callback for consultation
     */
    private function handleScheduleCallback(array $params)
    {
        // Ensure company is set
        if (isset($params['company_id'])) {
            $company = \App\Models\Company::find($params['company_id']);
            if ($company) {
                $this->mcpServer->setSalonCompany($company);
            }
        }
        
        // Validate required parameters
        if (!isset($params['customer_name']) || !isset($params['customer_phone'])) {
            throw new \Exception('customer_name and customer_phone are required');
        }
        
        $result = $this->mcpServer->scheduleCallback($params);
        
        if ($result['success']) {
            return [
                'callback_scheduled' => true,
                'callback_id' => $result['callback']['id'] ?? null,
                'message' => 'Beratungsrückruf wurde vereinbart'
            ];
        }
        
        throw new \Exception($result['error'] ?? 'Failed to schedule callback');
    }
    
    /**
     * Initialize MCP connection with proper tool discovery
     */
    private function handleInitialize(array $params)
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => [
                    [
                        'name' => 'list_services',
                        'description' => 'Liste alle verfügbaren Friseur-Services mit Preisen und Dauer',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'company_id' => [
                                    'type' => 'integer',
                                    'description' => 'ID des Friseursalons',
                                    'default' => 1
                                ]
                            ]
                        ]
                    ],
                    [
                        'name' => 'check_availability',
                        'description' => 'Prüfe verfügbare Termine für einen Service',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'service_id' => [
                                    'type' => 'integer',
                                    'description' => 'ID des gewünschten Services',
                                    'required' => true
                                ],
                                'date' => [
                                    'type' => 'string',
                                    'description' => 'Datum im Format YYYY-MM-DD',
                                    'default' => 'today'
                                ],
                                'staff_id' => [
                                    'type' => 'integer',
                                    'description' => 'Optional: Spezifische Mitarbeiterin'
                                ]
                            ],
                            'required' => ['service_id']
                        ]
                    ],
                    [
                        'name' => 'book_appointment',
                        'description' => 'Buche einen Termin für einen Kunden',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'customer_name' => [
                                    'type' => 'string',
                                    'description' => 'Name des Kunden',
                                    'required' => true
                                ],
                                'customer_phone' => [
                                    'type' => 'string',
                                    'description' => 'Telefonnummer des Kunden',
                                    'required' => true
                                ],
                                'service_id' => [
                                    'type' => 'integer',
                                    'description' => 'ID des Services',
                                    'required' => true
                                ],
                                'staff_id' => [
                                    'type' => 'integer',
                                    'description' => 'ID der Mitarbeiterin',
                                    'required' => true
                                ],
                                'datetime' => [
                                    'type' => 'string',
                                    'description' => 'Termin im Format YYYY-MM-DD HH:MM',
                                    'required' => true
                                ],
                                'notes' => [
                                    'type' => 'string',
                                    'description' => 'Zusätzliche Notizen'
                                ]
                            ],
                            'required' => ['customer_name', 'customer_phone', 'service_id', 'staff_id', 'datetime']
                        ]
                    ],
                    [
                        'name' => 'schedule_callback',
                        'description' => 'Vereinbare einen Rückruf für Beratung',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'customer_name' => [
                                    'type' => 'string',
                                    'description' => 'Name des Kunden',
                                    'required' => true
                                ],
                                'customer_phone' => [
                                    'type' => 'string',
                                    'description' => 'Telefonnummer für Rückruf',
                                    'required' => true
                                ],
                                'service_id' => [
                                    'type' => 'integer',
                                    'description' => 'Service für Beratung'
                                ],
                                'preferred_time' => [
                                    'type' => 'string',
                                    'description' => 'Bevorzugte Rückrufzeit'
                                ],
                                'notes' => [
                                    'type' => 'string',
                                    'description' => 'Beratungswunsch'
                                ]
                            ],
                            'required' => ['customer_name', 'customer_phone']
                        ]
                    ]
                ]
            ],
            'serverInfo' => [
                'name' => 'Hair Salon MCP Server',
                'version' => '2.0',
                'company_id' => $params['company_id'] ?? 1
            ]
        ];
    }
    
    /**
     * Handle unknown methods
     */
    private function handleUnknownMethod($method)
    {
        throw new \Exception("Unknown method: $method");
    }
    
    /**
     * Health check endpoint for MCP
     */
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'Hair Salon MCP Bridge',
            'version' => '2.0',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}