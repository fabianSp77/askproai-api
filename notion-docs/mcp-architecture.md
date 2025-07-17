## MCP Architektur

### Komponenten-Übersicht

```
┌─────────────────────────────────────────────────────────┐
│                   Claude / AI Assistant                  │
└─────────────────────────┬───────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────┐
│                    MCP Orchestrator                      │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────┐ │
│  │   Router    │  │  Rate Limiter │  │ Circuit Breaker│ │
│  └─────────────┘  └──────────────┘  └────────────────┘ │
└─────────────────────────┬───────────────────────────────┘
                          │
        ┌─────────────────┴─────────────────┐
        │                                   │
┌───────▼────────┐              ┌───────────▼──────────┐
│ Internal Servers│              │  External Servers    │
│                │              │                      │
│ • CalcomMCP    │              │ • SequentialThinking │
│ • RetellMCP    │              │ • PostgresMCP        │
│ • DatabaseMCP  │              │ • NotionMCP          │
│ • StripeMCP    │              │ • GitHubMCP          │
│ • QueueMCP     │              │ • MemoryBankMCP      │
└────────────────┘              └──────────────────────┘
```

### Schichten

1. **Tool Layer**: Definiert verfügbare Tools und deren Schemas
2. **Execution Layer**: Führt Tools aus und verarbeitet Ergebnisse
3. **Security Layer**: Rate Limiting, Authentication, Circuit Breakers
4. **Monitoring Layer**: Metriken, Logging, Health Checks
5. **Integration Layer**: Externe APIs und Services

### Datenfluss

1. AI Assistant sendet Tool-Request
2. Orchestrator validiert und routet Request
3. Security Layer prüft Berechtigungen und Limits
4. MCP Server führt Tool aus
5. Result wird zurück an AI Assistant gesendet
6. Metriken werden gesammelt

### Multi-Tenancy

- Jeder Request ist tenant-isoliert
- Separate Rate Limits pro Tenant
- Tenant-spezifische Konfigurationen
- Isolierte Datenabfragen

### MCP Orchestrator

Der zentrale Orchestrator (`MCPOrchestrator`) koordiniert alle MCP-Operationen:

```php
class MCPOrchestrator
{
    // Hauptmethode für Tool-Ausführung
    public function executeForTenant(
        int $tenantId,
        string $service,
        string $tool,
        array $arguments = []
    ): array
    
    // Health Check für alle Server
    public function healthCheck(): array
    
    // Metriken abrufen
    public function getMetrics(): array
}
```

### Security Features

#### Rate Limiting
- Konfigurierbar pro Service und Tenant
- Adaptive Limits basierend auf Load
- Graceful Degradation bei Überlastung

#### Circuit Breaker
- Automatisches Failover bei Ausfällen
- Konfigurierbare Thresholds
- Self-Healing nach Recovery

#### Authentication & Authorization
- Tenant-basierte Isolation
- Role-Based Access Control
- API Key Management

### Performance Optimierung

#### Connection Pooling
- Persistent Connections für Datenbank
- Redis Connection Pool
- HTTP Client Reuse

#### Caching Strategy
- Result Caching mit TTL
- Query Cache für häufige Abfragen
- Distributed Cache mit Redis

#### Async Processing
- Queue-basierte Verarbeitung
- Parallel Execution wo möglich
- Batch Operations Support

### Monitoring & Observability

#### Metriken
- Request/Response Times
- Error Rates
- Throughput
- Resource Usage

#### Logging
- Structured Logging
- Correlation IDs
- Distributed Tracing Support

#### Alerting
- Threshold-basierte Alerts
- Anomaly Detection
- Integration mit Sentry

### Erweiterbarkeit

#### Neue Server hinzufügen
1. Interface implementieren
2. Bei Service Provider registrieren
3. Tools definieren
4. Tests schreiben

#### Custom Tools
- Schema-basierte Definitionen
- Automatic Validation
- Documentation Generation

### Best Practices

1. **Separation of Concerns**: Jeder Server hat klare Verantwortlichkeit
2. **Fail Fast**: Schnelle Fehlerkennung und -behandlung
3. **Graceful Degradation**: System bleibt teilweise funktionsfähig
4. **Monitoring First**: Alles wird gemessen und geloggt
5. **Security by Design**: Sicherheit in jeder Schicht