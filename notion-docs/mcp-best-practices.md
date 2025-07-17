## MCP Best Practices & Team Guidelines

### 🎯 Entwicklungs-Guidelines

#### 1. Server-Auswahl
- **Nutze spezialisierte Server** statt generische
- **AppointmentMCP** für Termine, nicht DatabaseMCP
- **CustomerMCP** für Kundensuche, nicht raw queries

#### 2. Error Handling
```php
// ✅ Gut
try {
    $result = $mcp->executeForTenant(...);
    if (!$result['success']) {
        Log::warning('MCP operation failed', [
            'error' => $result['error'],
            'context' => $arguments
        ]);
        return $this->handleError($result['error']);
    }
} catch (MCPException $e) {
    return $this->handleSystemError($e);
}

// ❌ Schlecht
$result = $mcp->executeForTenant(...);
return $result['data'];
```

#### 3. Performance
- **Cache wo möglich** - besonders bei read-heavy Operations
- **Batch Operations** für mehrere ähnliche Requests
- **Async Processing** für nicht-kritische Operations

#### 4. Security
- **Immer Tenant-Scope nutzen**
- **Keine Hardcoded IDs**
- **Validate Input** vor MCP-Calls

### 🔄 Workflow Integration

#### Feature Development
1. **Requirement Check** via Notion MCP
2. **Code Generation** via Context7/Apidog
3. **Implementation** mit MCP Tools
4. **Testing** via MCP Test Suite
5. **Documentation** automatisch generiert

#### Daily Standups
```bash
# Morning Routine
php artisan mcp daily-report
php artisan mcp check-integrations
php artisan mcp:health --summary
```

### 📦 Neue MCP Server erstellen

#### Template
```php
namespace App\Services\MCP;

use App\Services\MCP\Contracts\MCPServerInterface;

class MyCustomMCPServer extends BaseMCPServer implements MCPServerInterface
{
    protected string $name = 'mycustom';
    protected string $version = '1.0.0';
    
    public function getTools(): array
    {
        return [
            [
                'name' => 'my_tool',
                'description' => 'Does something useful',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'param1' => ['type' => 'string'],
                    ],
                    'required' => ['param1']
                ]
            ]
        ];
    }
    
    public function executeTool(string $tool, array $arguments): array
    {
        return match($tool) {
            'my_tool' => $this->myTool($arguments),
            default => $this->unknownTool($tool)
        };
    }
}
```

#### Registration
```php
// In AppServiceProvider
$this->app->singleton(MyCustomMCPServer::class);
$this->app->tag([MyCustomMCPServer::class], 'mcp.servers');
```

### 🧪 Testing MCP Servers

```php
use Tests\TestCase;
use App\Services\MCP\AppointmentMCPServer;

class AppointmentMCPTest extends TestCase
{
    public function test_create_appointment()
    {
        $mcp = $this->app->make(AppointmentMCPServer::class);
        
        $result = $mcp->executeTool('create_appointment', [
            'customer_phone' => '+49 123 456789',
            'service_id' => 'test-service',
            'date' => '2025-01-20',
            'time' => '14:00'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('appointment_id', $result['data']);
    }
}
```

### 🚀 Deployment Checklist

- [ ] Alle MCP Server health checks grün
- [ ] Rate Limits konfiguriert
- [ ] Circuit Breakers aktiv
- [ ] Monitoring aktiviert
- [ ] Error Alerting eingerichtet
- [ ] Documentation aktuell
- [ ] Performance Baselines erstellt

### 📊 Monitoring & Alerts

#### Key Metrics
- **Error Rate** < 1%
- **P99 Latency** < 1000ms
- **Availability** > 99.9%
- **Queue Depth** < 1000

#### Alert Rules
```yaml
# In monitoring config
alerts:
  - name: MCP High Error Rate
    condition: error_rate > 5%
    for: 5 minutes
    notify: dev-team
    
  - name: MCP Circuit Breaker Open
    condition: circuit_breaker.status = open
    notify: on-call
    
  - name: MCP Queue Backup
    condition: queue.depth > 5000
    notify: ops-team
```

### 🤝 Team Collaboration

#### Code Reviews
- Check MCP usage patterns
- Verify error handling
- Review performance impact
- Ensure documentation

#### Knowledge Sharing
- Weekly MCP Tips in Team Meeting
- Internal Wiki mit Examples
- Pair Programming für neue Server
- Brown Bag Sessions

### 📚 Weiterführende Ressourcen

- [MCP Architecture Guide](/docs/MCP_ARCHITECTURE.md)
- [Complete API Reference](/docs/api/mcp)
- [Video Tutorials](https://internal.askproai.de/mcp-tutorials)
- [Team Chat: #mcp-support](slack://channel?team=askproai&id=mcp-support)

### 🏗️ Architektur-Prinzipien

#### Single Responsibility
Jeder MCP Server hat genau eine Verantwortlichkeit:
- CalcomMCP → Nur Kalender
- CustomerMCP → Nur Kunden
- StripeMCP → Nur Zahlungen

#### Loose Coupling
Server kommunizieren nur über definierte Interfaces:
- Keine direkten Abhängigkeiten
- Event-basierte Kommunikation
- Standardisierte Datenformate

#### High Cohesion
Verwandte Funktionen im gleichen Server:
- Alle Appointment-Tools in AppointmentMCP
- Alle Call-Tools in RetellMCP

### 🔐 Security Best Practices

#### Input Validation
```php
// Immer validieren
$validated = validator($arguments, [
    'phone' => 'required|string|regex:/^\+49/',
    'date' => 'required|date|after:today',
    'time' => 'required|date_format:H:i'
])->validate();
```

#### Tenant Isolation
```php
// Niemals tenant_id aus Request nehmen
$tenantId = Auth::user()->company_id;

// Immer Scope anwenden
$query->where('company_id', $tenantId);
```

#### API Key Management
- Rotiere Keys regelmäßig
- Nutze separate Keys für Dev/Prod
- Speichere Keys verschlüsselt
- Monitore Key-Usage

### 📈 Performance Guidelines

#### Caching Strategy
```php
// Cache teurer Operationen
$cacheKey = "mcp:availability:{$date}:{$serviceId}";
return Cache::remember($cacheKey, 300, function() {
    // Expensive operation
});
```

#### Batch Processing
```php
// Statt einzelne Requests
foreach ($items as $item) {
    $mcp->executeForTenant(...); // ❌
}

// Batch Request
$mcp->executeBatch($items); // ✅
```

#### Async When Possible
```php
// Für nicht-kritische Operations
dispatch(new ProcessMCPJob($arguments))
    ->onQueue('mcp-async')
    ->delay(now()->addSeconds(5));
```

### 🎨 Code Style

#### Naming Conventions
- Server: `{Service}MCPServer`
- Tools: `snake_case` (z.B. `create_appointment`)
- Methods: `camelCase` (z.B. `executeTool`)

#### Documentation
```php
/**
 * Create a new appointment
 * 
 * @param array $arguments {
 *     @type string $customer_phone Required. Phone number
 *     @type string $service_id Required. Service identifier
 *     @type string $date Required. Date in Y-m-d format
 *     @type string $time Required. Time in H:i format
 * }
 * @return array {
 *     @type bool $success
 *     @type array|null $data
 *     @type string|null $error
 * }
 */
```

#### Testing Standards
- Unit Tests für jeden Tool
- Integration Tests für Workflows
- Performance Tests für kritische Paths
- Mock externe Services