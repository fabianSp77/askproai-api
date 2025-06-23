# AskProAI MCP-First Technical Specification
**Version**: 1.0  
**Date**: 2025-06-23  
**Status**: DRAFT  
**Author**: Claude Code

## Executive Summary

Diese Spezifikation definiert die vollständige technische Architektur für AskProAI mit einem **MCP-First Ansatz**. Alle externen Integrationen (Retell.ai, Cal.com) werden ausschließlich über MCP Server abstrahiert. Die UI kommuniziert nur mit MCP Servern, niemals direkt mit externen APIs.

### Kernprinzipien
1. **MCP-Only Communication**: Keine direkten API Calls zu externen Services
2. **Unified Protocol**: Alle MCP Server verwenden das JSON-RPC 2.0 Protokoll
3. **Service Discovery**: Automatische Registrierung und Health Checks
4. **Complete Abstraction**: UI kennt keine externen API Details

## 1. MCP Server Architecture

### 1.1 Existing MCP Servers (Verified)
```yaml
Core Servers:
  - RetellMCPServer: Phone AI management
  - CalcomMCPServer: Calendar integration  
  - WebhookMCPServer: Main orchestrator
  - DatabaseMCPServer: Data operations
  - QueueMCPServer: Job management

Support Servers:
  - AppointmentMCPServer: Appointment CRUD
  - CustomerMCPServer: Customer management
  - BranchMCPServer: Branch operations
  - CompanyMCPServer: Company settings
  - KnowledgeMCPServer: AI knowledge base
  - StripeMCPServer: Payment processing
  - SentryMCPServer: Error monitoring
```

### 1.2 New MCP Servers to Implement
```yaml
Configuration Servers:
  - RetellConfigurationMCPServer: Manage Retell settings via UI
  - RetellCustomFunctionMCPServer: Handle custom functions
  - WebhookConfigurationMCPServer: Configure webhooks

Management Servers:
  - AppointmentManagementMCPServer: Change/cancel appointments
  - AgentVersionMCPServer: Manage agent versions
  - PhoneNumberMCPServer: Phone number management
```

## 2. MCP Protocol Specification

### 2.1 Standard Message Format
```json
{
  "jsonrpc": "2.0",
  "id": "unique-request-id",
  "method": "server.method.action",
  "params": {
    "company_id": 123,
    "data": {}
  }
}
```

### 2.2 Response Format
```json
{
  "jsonrpc": "2.0",
  "id": "unique-request-id",
  "result": {
    "success": true,
    "data": {},
    "metadata": {
      "cached": false,
      "processing_time_ms": 145
    }
  }
}
```

### 2.3 Error Format
```json
{
  "jsonrpc": "2.0",
  "id": "unique-request-id",
  "error": {
    "code": -32603,
    "message": "Internal error",
    "data": {
      "type": "ServiceUnavailable",
      "retry_after": 60
    }
  }
}
```

## 3. RetellConfigurationMCPServer

### 3.1 Purpose
Manages all Retell.ai configuration through MCP, eliminating direct API calls from UI.

### 3.2 Methods

#### 3.2.1 `retell.config.getWebhookConfiguration`
```php
public function getWebhookConfiguration(array $params): array
{
    $companyId = $params['company_id'];
    
    return [
        'webhook_url' => config('app.url') . '/api/mcp/retell/webhook',
        'webhook_secret' => $this->getOrGenerateWebhookSecret($companyId),
        'configured_events' => ['call_started', 'call_ended', 'call_analyzed'],
        'custom_headers' => [
            'X-Company-ID' => $companyId,
            'X-MCP-Version' => '2.0'
        ]
    ];
}
```

#### 3.2.2 `retell.config.updateWebhookSettings`
```php
public function updateWebhookSettings(array $params): array
{
    $companyId = $params['company_id'];
    $events = $params['events'] ?? ['call_ended'];
    
    // Store in database
    $config = RetellConfiguration::updateOrCreate(
        ['company_id' => $companyId],
        [
            'webhook_events' => $events,
            'webhook_url' => $this->generateWebhookUrl($companyId),
            'last_updated_by' => auth()->id()
        ]
    );
    
    // Update Retell.ai via API
    $retellService = $this->getRetellService($companyId);
    $result = $retellService->updateWebhook([
        'url' => $config->webhook_url,
        'events' => $events
    ]);
    
    return [
        'success' => $result['success'],
        'configuration' => $config,
        'test_available' => true
    ];
}
```

#### 3.2.3 `retell.config.getCustomFunctions`
```php
public function getCustomFunctions(array $params): array
{
    $companyId = $params['company_id'];
    
    // Get from database with defaults
    $functions = RetellCustomFunction::where('company_id', $companyId)
        ->orWhere('is_global', true)
        ->get();
    
    // Add built-in functions if not exists
    $this->ensureBuiltInFunctions($companyId);
    
    return [
        'functions' => $functions->map(function ($fn) {
            return [
                'name' => $fn->name,
                'type' => $fn->type, // 'external_api' or 'data_collection'
                'description' => $fn->description,
                'parameters' => $fn->parameter_schema,
                'url' => $fn->type === 'external_api' ? $fn->url : null,
                'enabled' => $fn->is_enabled,
                'last_used' => $fn->last_used_at
            ];
        }),
        'total' => $functions->count()
    ];
}
```

#### 3.2.4 `retell.config.updateCustomFunction`
```php
public function updateCustomFunction(array $params): array
{
    $functionId = $params['function_id'];
    $updates = $params['updates'];
    
    $function = RetellCustomFunction::findOrFail($functionId);
    
    // Validate schema if provided
    if (isset($updates['parameter_schema'])) {
        $this->validateJsonSchema($updates['parameter_schema']);
    }
    
    $function->update($updates);
    
    // Deploy to Retell if enabled
    if ($function->is_enabled) {
        $this->deployFunctionToRetell($function);
    }
    
    return [
        'success' => true,
        'function' => $function,
        'deployed' => $function->is_enabled
    ];
}
```

#### 3.2.5 `retell.config.testWebhook`
```php
public function testWebhook(array $params): array
{
    $companyId = $params['company_id'];
    
    $testPayload = [
        'event' => 'test',
        'test_id' => Str::uuid(),
        'timestamp' => now()->toIso8601String(),
        'call' => [
            'call_id' => 'test_' . Str::random(10),
            'from_number' => '+49123456789',
            'to_number' => '+49987654321'
        ]
    ];
    
    // Send test webhook
    $response = Http::timeout(5)
        ->withHeaders([
            'X-Retell-Signature' => $this->generateSignature($testPayload, $companyId)
        ])
        ->post($this->getWebhookUrl($companyId), $testPayload);
    
    return [
        'success' => $response->successful(),
        'status_code' => $response->status(),
        'response_time_ms' => $response->handlerStats()['total_time'] * 1000,
        'test_payload' => $testPayload
    ];
}
```

### 3.3 Database Schema
```sql
-- retell_configurations
CREATE TABLE retell_configurations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    webhook_url VARCHAR(255),
    webhook_secret VARCHAR(255),
    webhook_events JSON,
    custom_functions JSON,
    agent_settings JSON,
    last_tested_at TIMESTAMP NULL,
    test_status ENUM('success', 'failed', 'pending'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    UNIQUE KEY idx_company (company_id)
);

-- retell_custom_functions
CREATE TABLE retell_custom_functions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('external_api', 'data_collection') NOT NULL,
    description TEXT,
    url VARCHAR(255) NULL,
    method VARCHAR(10) DEFAULT 'POST',
    headers JSON,
    parameter_schema JSON NOT NULL,
    response_schema JSON,
    is_global BOOLEAN DEFAULT FALSE,
    is_enabled BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    INDEX idx_name (name),
    INDEX idx_company_enabled (company_id, is_enabled)
);
```

## 4. RetellCustomFunctionMCPServer

### 4.1 Purpose
Handles custom function execution during Retell calls via MCP gateway.

### 4.2 Gateway Endpoint
```php
// Route: POST /api/mcp/retell/custom-function
Route::post('/mcp/retell/custom-function', function (Request $request) {
    $server = app(RetellCustomFunctionMCPServer::class);
    
    return $server->executeFunction([
        'function_name' => $request->input('function'),
        'parameters' => $request->input('parameters'),
        'call_context' => $request->input('context')
    ]);
})->middleware(['throttle:api', 'mcp.auth']);
```

### 4.3 Methods

#### 4.3.1 `retell.function.execute`
```php
public function executeFunction(array $params): array
{
    $functionName = $params['function_name'];
    $parameters = $params['parameters'];
    $callContext = $params['call_context'];
    
    // Resolve company from call context
    $companyId = $this->resolveCompanyFromContext($callContext);
    
    // Log execution
    $this->logFunctionExecution($functionName, $companyId, $callContext['call_id']);
    
    switch ($functionName) {
        case 'collect_appointment_data':
            return $this->collectAppointmentData($parameters, $callContext);
            
        case 'check_availability':
            return $this->checkAvailability($parameters, $companyId);
            
        case 'find_next_slot':
            return $this->findNextAvailableSlot($parameters, $companyId);
            
        case 'calculate_duration':
            return $this->calculateServiceDuration($parameters, $companyId);
            
        default:
            // Check for custom functions
            return $this->executeCustomFunction($functionName, $parameters, $companyId);
    }
}
```

#### 4.3.2 Built-in Functions

```php
protected function collectAppointmentData(array $params, array $context): array
{
    // Validate required fields
    $required = ['datum', 'uhrzeit', 'name', 'telefonnummer', 'dienstleistung'];
    $missing = array_diff($required, array_keys($params));
    
    if (!empty($missing)) {
        return [
            'success' => false,
            'missing_fields' => $missing,
            'message' => 'Bitte alle Pflichtfelder angeben'
        ];
    }
    
    // Parse and normalize data
    $appointmentData = [
        'date' => $this->parseGermanDate($params['datum']),
        'time' => $this->parseTime($params['uhrzeit']),
        'customer_name' => $params['name'],
        'customer_phone' => $this->normalizePhoneNumber($params['telefonnummer']),
        'service' => $params['dienstleistung'],
        'email' => $params['email'] ?? null,
        'preferred_staff' => $params['mitarbeiter_wunsch'] ?? null,
        'notes' => $params['notizen'] ?? null
    ];
    
    // Store in call context for webhook
    Cache::put(
        "call_context:{$context['call_id']}",
        array_merge($context, ['appointment_data' => $appointmentData]),
        300 // 5 minutes
    );
    
    return [
        'success' => true,
        'data' => $appointmentData,
        'message' => 'Daten erfolgreich gesammelt'
    ];
}

protected function checkAvailability(array $params, int $companyId): array
{
    $date = $params['date'];
    $time = $params['time'] ?? null;
    $service = $params['service'];
    
    // Get branch from phone context
    $branchId = $this->resolveBranchFromContext($params['context']);
    
    // Use CalcomMCPServer for availability check
    $calcomMCP = app(CalcomMCPServer::class);
    
    $eventTypeId = $this->resolveEventTypeForService($service, $branchId);
    
    $availability = $calcomMCP->checkAvailability([
        'company_id' => $companyId,
        'event_type_id' => $eventTypeId,
        'date_from' => Carbon::parse($date)->format('Y-m-d'),
        'date_to' => Carbon::parse($date)->format('Y-m-d'),
        'timezone' => 'Europe/Berlin'
    ]);
    
    if ($time) {
        // Check specific time slot
        $isAvailable = $this->isTimeSlotAvailable($availability['available_slots'], $time);
        
        return [
            'available' => $isAvailable,
            'message' => $isAvailable 
                ? "Der Termin um {$time} Uhr ist verfügbar"
                : "Der Termin um {$time} Uhr ist leider nicht verfügbar"
        ];
    }
    
    // Return all available slots
    return [
        'available_slots' => array_map(function ($slot) {
            return Carbon::parse($slot['start'])->format('H:i');
        }, $availability['available_slots']),
        'message' => count($availability['available_slots']) > 0
            ? 'Es gibt verfügbare Termine'
            : 'Keine Termine an diesem Tag verfügbar'
    ];
}
```

## 5. AppointmentManagementMCPServer

### 5.1 Purpose
Handles appointment modifications and cancellations via phone.

### 5.2 Methods

#### 5.2.1 `appointments.find`
```php
public function findAppointments(array $params): array
{
    $phone = $this->normalizePhoneNumber($params['phone']);
    $companyId = $params['company_id'];
    
    // Find customer by phone
    $customer = Customer::where('phone', $phone)
        ->where('company_id', $companyId)
        ->first();
    
    if (!$customer) {
        return [
            'found' => false,
            'message' => 'Keine Termine unter dieser Nummer gefunden'
        ];
    }
    
    // Get upcoming appointments
    $appointments = Appointment::where('customer_id', $customer->id)
        ->where('appointment_date', '>=', now())
        ->where('status', 'scheduled')
        ->with(['service', 'staff', 'branch'])
        ->orderBy('appointment_date')
        ->limit(5)
        ->get();
    
    return [
        'found' => true,
        'count' => $appointments->count(),
        'appointments' => $appointments->map(function ($apt) {
            return [
                'id' => $apt->id,
                'date' => $apt->appointment_date->format('d.m.Y'),
                'time' => $apt->appointment_date->format('H:i'),
                'service' => $apt->service->name,
                'staff' => $apt->staff->name ?? 'Beliebiger Mitarbeiter',
                'branch' => $apt->branch->name,
                'can_modify' => $apt->appointment_date->diffInHours(now()) > 24
            ];
        })
    ];
}
```

#### 5.2.2 `appointments.change`
```php
public function changeAppointment(array $params): array
{
    $appointmentId = $params['appointment_id'];
    $newDate = $params['new_date'];
    $newTime = $params['new_time'];
    $reason = $params['reason'] ?? 'Kundenanfrage per Telefon';
    
    $appointment = Appointment::find($appointmentId);
    
    // Check if modification is allowed
    if ($appointment->appointment_date->diffInHours(now()) < 24) {
        return [
            'success' => false,
            'message' => 'Termine können nur bis 24 Stunden vorher geändert werden'
        ];
    }
    
    // Parse new datetime
    $newDateTime = Carbon::parse("{$newDate} {$newTime}");
    
    // Check availability via CalcomMCP
    $calcomMCP = app(CalcomMCPServer::class);
    $availabilityCheck = $calcomMCP->checkAvailability([
        'company_id' => $appointment->company_id,
        'event_type_id' => $appointment->calcom_event_type_id,
        'date_from' => $newDateTime->format('Y-m-d'),
        'date_to' => $newDateTime->format('Y-m-d'),
        'timezone' => 'Europe/Berlin'
    ]);
    
    // Verify slot is available
    if (!$this->isSlotAvailable($availabilityCheck, $newDateTime)) {
        return [
            'success' => false,
            'message' => 'Der gewünschte Termin ist nicht verfügbar'
        ];
    }
    
    // Update via CalcomMCP
    $updateResult = $calcomMCP->updateBooking([
        'company_id' => $appointment->company_id,
        'booking_id' => $appointment->calcom_booking_id,
        'start' => $newDateTime->toIso8601String(),
        'reschedule_reason' => $reason
    ]);
    
    if ($updateResult['success']) {
        // Update local record
        $appointment->update([
            'appointment_date' => $newDateTime,
            'rescheduled_at' => now(),
            'rescheduled_by' => 'phone_system',
            'reschedule_reason' => $reason
        ]);
        
        // Send confirmation
        $this->sendRescheduleConfirmation($appointment);
        
        return [
            'success' => true,
            'message' => "Ihr Termin wurde auf {$newDateTime->format('d.m.Y')} um {$newDateTime->format('H:i')} Uhr verschoben",
            'appointment' => $appointment
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Die Terminänderung konnte nicht durchgeführt werden'
    ];
}
```

#### 5.2.3 `appointments.cancel`
```php
public function cancelAppointment(array $params): array
{
    $appointmentId = $params['appointment_id'];
    $reason = $params['reason'] ?? 'Kundenanfrage per Telefon';
    
    $appointment = Appointment::find($appointmentId);
    
    // Cancel via CalcomMCP
    $calcomMCP = app(CalcomMCPServer::class);
    $cancelResult = $calcomMCP->cancelBooking([
        'company_id' => $appointment->company_id,
        'booking_id' => $appointment->calcom_booking_id,
        'cancellation_reason' => $reason
    ]);
    
    if ($cancelResult['success']) {
        // Update local record
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => 'phone_system'
        ]);
        
        // Log for no-show tracking
        $this->logCancellation($appointment, 'phone');
        
        return [
            'success' => true,
            'message' => 'Ihr Termin wurde erfolgreich storniert',
            'refund_policy' => $this->getRefundPolicy($appointment)
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Die Stornierung konnte nicht durchgeführt werden'
    ];
}
```

## 6. UI Components Specification

### 6.1 Retell Configuration Page

#### Component Structure
```typescript
// RetellConfigurationPage.tsx
interface RetellConfigurationPageProps {
  companyId: number;
}

const RetellConfigurationPage: React.FC<RetellConfigurationPageProps> = ({ companyId }) => {
  const [config, setConfig] = useState<RetellConfig | null>(null);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    loadConfiguration();
  }, [companyId]);
  
  const loadConfiguration = async () => {
    const response = await mcpClient.call('retell.config.getWebhookConfiguration', {
      company_id: companyId
    });
    setConfig(response.result);
    setLoading(false);
  };
  
  return (
    <div className="retell-configuration">
      <WebhookSettings config={config} onUpdate={handleWebhookUpdate} />
      <CustomFunctions companyId={companyId} />
      <TestingTools config={config} />
      <AgentVersionManager companyId={companyId} />
    </div>
  );
};
```

#### Webhook Settings Component
```php
// Filament Component
class WebhookSettings extends Component
{
    public function render()
    {
        return view('filament.components.retell.webhook-settings', [
            'webhookUrl' => $this->getWebhookUrl(),
            'webhookSecret' => $this->getWebhookSecret(),
            'events' => $this->getConfiguredEvents()
        ]);
    }
    
    protected function getWebhookUrl(): string
    {
        return config('app.url') . '/api/mcp/retell/webhook';
    }
}
```

#### Custom Functions Editor
```php
class CustomFunctionEditor extends Component
{
    public array $functions = [];
    
    public function mount()
    {
        $this->loadFunctions();
    }
    
    public function loadFunctions()
    {
        $response = app(RetellConfigurationMCPServer::class)->getCustomFunctions([
            'company_id' => $this->companyId
        ]);
        
        $this->functions = $response['functions'];
    }
    
    public function updateFunction($functionId, $updates)
    {
        $response = app(RetellConfigurationMCPServer::class)->updateCustomFunction([
            'function_id' => $functionId,
            'updates' => $updates
        ]);
        
        if ($response['success']) {
            $this->notify('success', 'Function updated successfully');
            $this->loadFunctions();
        }
    }
}
```

### 6.2 Agent Version Manager
```php
class AgentVersionManager extends Component
{
    public function getVersions()
    {
        return app(RetellMCPServer::class)->getAgentVersions([
            'agent_id' => $this->agentId,
            'company_id' => $this->companyId
        ]);
    }
    
    public function setPhoneNumberVersion($phoneId, $versionId)
    {
        return app(RetellMCPServer::class)->setPhoneNumberAgentVersion([
            'phone_id' => $phoneId,
            'agent_id' => $this->agentId,
            'version_id' => $versionId,
            'company_id' => $this->companyId
        ]);
    }
}
```

## 7. MCP Gateway Architecture

### 7.1 Central MCP Router
```php
// app/Http/Controllers/MCPGatewayController.php
class MCPGatewayController extends Controller
{
    protected array $servers = [];
    
    public function __construct()
    {
        $this->registerServers();
    }
    
    protected function registerServers()
    {
        $this->servers = [
            'retell' => RetellMCPServer::class,
            'retell.config' => RetellConfigurationMCPServer::class,
            'retell.function' => RetellCustomFunctionMCPServer::class,
            'calcom' => CalcomMCPServer::class,
            'appointments' => AppointmentManagementMCPServer::class,
            'webhook' => WebhookMCPServer::class,
            'database' => DatabaseMCPServer::class,
            'queue' => QueueMCPServer::class
        ];
    }
    
    public function handle(Request $request)
    {
        $method = $request->input('method');
        $params = $request->input('params', []);
        $id = $request->input('id');
        
        try {
            // Parse method to get server and action
            [$serverName, $action] = $this->parseMethod($method);
            
            // Get server instance
            $server = $this->getServer($serverName);
            
            // Execute method
            $result = $this->executeMethod($server, $action, $params);
            
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
```

### 7.2 Service Discovery
```php
// app/Services/MCP/MCPServiceRegistry.php
class MCPServiceRegistry
{
    protected array $services = [];
    protected array $health = [];
    
    public function register(string $name, string $class, array $metadata = [])
    {
        $this->services[$name] = [
            'class' => $class,
            'metadata' => $metadata,
            'registered_at' => now(),
            'health_check_url' => $metadata['health_check'] ?? null
        ];
    }
    
    public function discover(): array
    {
        return array_map(function ($service) {
            return [
                'name' => $service['name'],
                'version' => $service['metadata']['version'] ?? '1.0',
                'methods' => $this->getServiceMethods($service['class']),
                'health' => $this->health[$service['name']] ?? 'unknown'
            ];
        }, $this->services);
    }
    
    public function healthCheck()
    {
        foreach ($this->services as $name => $service) {
            try {
                $instance = app($service['class']);
                if (method_exists($instance, 'healthCheck')) {
                    $this->health[$name] = $instance->healthCheck();
                } else {
                    $this->health[$name] = ['status' => true, 'message' => 'No health check'];
                }
            } catch (\Exception $e) {
                $this->health[$name] = ['status' => false, 'message' => $e->getMessage()];
            }
        }
    }
}
```

## 8. Error Handling & Recovery

### 8.1 MCP Error Codes
```yaml
1000-1999: Retell Errors
  1001: Invalid API key
  1002: Agent not found
  1003: Phone number not configured
  1004: Webhook registration failed
  
2000-2999: Cal.com Errors
  2001: Invalid API key
  2002: Event type not found
  2003: Time slot not available
  2004: Booking creation failed
  
3000-3999: Internal Errors
  3001: Company not found
  3002: Branch not resolved
  3003: Service not mapped
  3004: Database error
  
4000-4999: Validation Errors
  4001: Missing required field
  4002: Invalid date format
  4003: Invalid phone number
  4004: Schema validation failed
```

### 8.2 Circuit Breaker Configuration
```php
// config/mcp.php
return [
    'circuit_breakers' => [
        'retell' => [
            'failure_threshold' => 5,
            'success_threshold' => 2,
            'timeout' => 60,
            'half_open_requests' => 3
        ],
        'calcom' => [
            'failure_threshold' => 5,
            'success_threshold' => 2,
            'timeout' => 60,
            'half_open_requests' => 3
        ]
    ],
    
    'retry' => [
        'max_attempts' => 3,
        'delay' => 1000, // ms
        'multiplier' => 2,
        'max_delay' => 10000
    ]
];
```

## 9. Monitoring & Observability

### 9.1 MCP Metrics
```php
// Prometheus metrics
mcp_request_total{server="retell", method="getAgent", status="success"} 1234
mcp_request_duration_seconds{server="calcom", method="createBooking"} 0.145
mcp_error_total{server="webhook", error_code="3002"} 5
mcp_circuit_breaker_state{service="calcom"} 0 # 0=closed, 1=open, 2=half-open
```

### 9.2 Structured Logging
```php
Log::channel('mcp')->info('MCP Request', [
    'correlation_id' => $correlationId,
    'server' => 'retell.config',
    'method' => 'updateWebhookSettings',
    'company_id' => $companyId,
    'duration_ms' => $duration,
    'cached' => false,
    'result' => 'success'
]);
```

## 10. Testing Strategy

### 10.1 MCP Server Unit Tests
```php
class RetellConfigurationMCPServerTest extends TestCase
{
    public function test_get_webhook_configuration()
    {
        $server = new RetellConfigurationMCPServer();
        
        $result = $server->getWebhookConfiguration([
            'company_id' => 1
        ]);
        
        $this->assertArrayHasKey('webhook_url', $result);
        $this->assertArrayHasKey('webhook_secret', $result);
        $this->assertArrayHasKey('configured_events', $result);
    }
    
    public function test_update_custom_function_validates_schema()
    {
        $server = new RetellConfigurationMCPServer();
        
        $this->expectException(ValidationException::class);
        
        $server->updateCustomFunction([
            'function_id' => 1,
            'updates' => [
                'parameter_schema' => 'invalid json'
            ]
        ]);
    }
}
```

### 10.2 Integration Tests
```php
class MCPIntegrationTest extends TestCase
{
    public function test_complete_retell_configuration_flow()
    {
        // 1. Get current configuration
        $config = $this->mcpCall('retell.config.getWebhookConfiguration', [
            'company_id' => $this->company->id
        ]);
        
        // 2. Update webhook events
        $updateResult = $this->mcpCall('retell.config.updateWebhookSettings', [
            'company_id' => $this->company->id,
            'events' => ['call_started', 'call_ended']
        ]);
        
        $this->assertTrue($updateResult['success']);
        
        // 3. Test webhook
        $testResult = $this->mcpCall('retell.config.testWebhook', [
            'company_id' => $this->company->id
        ]);
        
        $this->assertTrue($testResult['success']);
        $this->assertEquals(200, $testResult['status_code']);
    }
}
```

## 11. Migration Plan

### 11.1 Phase 1: Infrastructure (Week 1)
- [ ] Create new MCP servers
- [ ] Setup MCP gateway controller
- [ ] Implement service discovery
- [ ] Add health check endpoints

### 11.2 Phase 2: Retell Configuration (Week 2)
- [ ] Implement RetellConfigurationMCPServer
- [ ] Create database tables
- [ ] Build UI components
- [ ] Add test endpoints

### 11.3 Phase 3: Custom Functions (Week 3)
- [ ] Implement RetellCustomFunctionMCPServer
- [ ] Create gateway endpoint
- [ ] Add built-in functions
- [ ] Test with real calls

### 11.4 Phase 4: Appointment Management (Week 4)
- [ ] Implement AppointmentManagementMCPServer
- [ ] Add phone-based lookup
- [ ] Test modification flows
- [ ] Add security checks

### 11.5 Phase 5: Documentation & Testing (Week 5)
- [ ] Update mkdocs documentation
- [ ] Write integration tests
- [ ] Performance testing
- [ ] Security audit

## 12. Security Considerations

### 12.1 Authentication
```php
// MCP Authentication Middleware
class MCPAuthMiddleware
{
    public function handle($request, $next)
    {
        $token = $request->header('X-MCP-Token');
        $companyId = $request->input('params.company_id');
        
        if (!$this->validateToken($token, $companyId)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32700,
                    'message' => 'Invalid authentication'
                ]
            ], 401);
        }
        
        return $next($request);
    }
}
```

### 12.2 Rate Limiting
```php
// config/mcp.php
'rate_limits' => [
    'retell.config' => '60,1', // 60 requests per minute
    'retell.function' => '1000,1', // 1000 requests per minute
    'appointments' => '30,1', // 30 requests per minute
]
```

### 12.3 Input Validation
```php
class MCPRequestValidator
{
    protected array $rules = [
        'retell.config.updateWebhookSettings' => [
            'company_id' => 'required|integer|exists:companies,id',
            'events' => 'required|array|in:call_started,call_ended,call_analyzed'
        ],
        'appointments.change' => [
            'appointment_id' => 'required|uuid|exists:appointments,id',
            'new_date' => 'required|date|after:today',
            'new_time' => 'required|date_format:H:i'
        ]
    ];
}
```

## 13. Performance Optimization

### 13.1 Caching Strategy
```php
class MCPCacheManager
{
    protected array $ttls = [
        'retell.agent' => 300, // 5 minutes
        'calcom.eventTypes' => 300, // 5 minutes
        'appointments.availability' => 60, // 1 minute
    ];
    
    public function remember(string $key, callable $callback)
    {
        $ttl = $this->getTTL($key);
        
        return Cache::tags(['mcp', $this->getTag($key)])
            ->remember($key, $ttl, $callback);
    }
}
```

### 13.2 Query Optimization
```php
// Eager loading for appointment queries
$appointments = Appointment::with([
    'customer:id,name,phone',
    'service:id,name,duration_minutes',
    'staff:id,name',
    'branch:id,name'
])
->where('company_id', $companyId)
->where('appointment_date', '>=', now())
->limit(100)
->get();
```

## 14. Deployment Configuration

### 14.1 Environment Variables
```env
# MCP Configuration
MCP_GATEWAY_URL=https://api.askproai.de/api/mcp
MCP_AUTH_TOKEN=your-secure-token
MCP_SERVICE_DISCOVERY=true
MCP_HEALTH_CHECK_INTERVAL=60

# Retell MCP
RETELL_MCP_WEBHOOK_BASE_URL=https://api.askproai.de/api/mcp/retell/webhook
RETELL_MCP_CUSTOM_FUNCTION_URL=https://api.askproai.de/api/mcp/retell/custom-function

# Circuit Breaker
MCP_CIRCUIT_BREAKER_ENABLED=true
MCP_CIRCUIT_BREAKER_FAILURE_THRESHOLD=5
MCP_CIRCUIT_BREAKER_TIMEOUT=60
```

### 14.2 Nginx Configuration
```nginx
location /api/mcp {
    proxy_pass http://localhost:8000;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-MCP-Request-ID $request_id;
    
    # Timeouts for long-running MCP operations
    proxy_read_timeout 30s;
    proxy_connect_timeout 10s;
    
    # Buffer settings
    proxy_buffering on;
    proxy_buffer_size 4k;
    proxy_buffers 8 4k;
}
```

## 15. Documentation Updates Required

### 15.1 API Documentation
- Add MCP endpoint reference
- Document all MCP methods
- Provide example requests/responses
- Include error code reference

### 15.2 Integration Guide
- How to add new MCP servers
- Custom function development
- Testing MCP integrations
- Monitoring and debugging

### 15.3 User Guide
- Retell configuration UI
- Custom function management
- Webhook testing
- Troubleshooting

---

## Summary

This MCP-First architecture provides:

1. **Complete Abstraction**: UI never knows about external APIs
2. **Unified Protocol**: All communication via JSON-RPC 2.0
3. **Enhanced Reliability**: Circuit breakers, retries, caching
4. **Better Testing**: Mock MCP servers for testing
5. **Easier Maintenance**: Central error handling and monitoring

The implementation follows a phased approach with clear milestones and comprehensive testing at each stage.