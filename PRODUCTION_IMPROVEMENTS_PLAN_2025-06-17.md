# 🚨 PRODUCTION-CRITICAL IMPROVEMENTS PLAN

*Erstellt: 17. Juni 2025*
*Priorität: HÖCHSTE*

## Übersicht

Dieser Plan adressiert kritische Produktiv-Probleme, die die Stabilität und Verfügbarkeit von AskProAI beeinträchtigen könnten.

## PHASE 1: Error Handling & Resilience (HEUTE)

### 1.1 RetryableHttpClient Trait

**Problem**: Keine Retry-Logik bei API-Fehlern
**Impact**: Kompletter Ausfall bei temporären Netzwerkproblemen
**Lösung**: Zentraler Trait für alle HTTP-Calls

```php
trait RetryableHttpClient {
    protected function httpWithRetry() {
        return Http::timeout(10)
            ->retry(3, 100, function ($exception) {
                return $exception instanceof ConnectionException;
            })
            ->throw();
    }
}
```

**Implementierung in**:
- [ ] CalcomService
- [ ] CalcomV2Service
- [ ] RetellService
- [ ] RetellV2Service
- [ ] StripeService

### 1.2 Circuit Breaker Pattern

**Problem**: Kaskadierender Ausfall bei API-Problemen
**Impact**: System wird unresponsive
**Lösung**: Circuit Breaker mit Fallback

```php
class ApiCircuitBreaker {
    private array $failures = [];
    private array $lastFailure = [];
    private int $threshold = 5;
    private int $timeout = 60; // seconds
    
    public function call(string $service, callable $operation, ?callable $fallback = null) {
        if ($this->isOpen($service)) {
            if ($fallback) {
                return $fallback();
            }
            throw new ServiceUnavailableException("Service {$service} is currently unavailable");
        }
        
        try {
            $result = $operation();
            $this->recordSuccess($service);
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($service);
            throw $e;
        }
    }
}
```

### 1.3 Comprehensive Error Logging

**Problem**: Fehler gehen verloren oder haben keinen Kontext
**Impact**: Debugging unmöglich
**Lösung**: Strukturiertes Logging mit Context

```php
class ProductionLogger {
    public function logError(\Throwable $e, array $context = []) {
        $defaultContext = [
            'trace_id' => $this->getTraceId(),
            'company_id' => $this->getCurrentCompanyId(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
        
        Log::error($e->getMessage(), array_merge($defaultContext, $context, [
            'exception' => [
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]
        ]));
    }
}
```

## PHASE 2: Automatisierung (HEUTE NACHMITTAG)

### 2.1 Retell Agent Auto-Creation

**Problem**: Manuelle Agent-Erstellung für jede Branch
**Impact**: 60+ Minuten Setup-Zeit
**Lösung**: Automatische Provisionierung

```php
class RetellAgentProvisioner {
    public function createAgentForBranch(Branch $branch): array {
        $retellService = new RetellV2Service($branch->company->retell_api_key);
        
        $agentConfig = [
            'name' => "{$branch->company->name} - {$branch->name}",
            'voice_id' => $branch->voice_preference ?? 'default',
            'language' => $branch->language ?? 'de-DE',
            'webhook_url' => route('webhook.unified'),
            'functions' => $this->getAgentFunctions($branch),
            'prompt' => $this->generatePrompt($branch),
        ];
        
        $agent = $retellService->createAgent($agentConfig);
        
        // Store agent info
        $branch->update([
            'retell_agent_id' => $agent['id'],
            'retell_agent_status' => 'active',
        ]);
        
        return $agent;
    }
}
```

### 2.2 Webhook Auto-Registration

**Problem**: Manuelle Webhook-Registrierung
**Impact**: Fehleranfällig, zeitaufwendig
**Lösung**: Automatische Registration

```php
class WebhookAutoRegistrar {
    public function registerAllWebhooks(Company $company): array {
        $results = [];
        
        // Cal.com Webhooks
        if ($company->calcom_api_key) {
            $calcom = new CalcomV2Service($company->calcom_api_key);
            $results['calcom'] = $calcom->registerWebhook([
                'subscriberUrl' => route('webhook.unified'),
                'eventTriggers' => ['BOOKING_CREATED', 'BOOKING_CANCELLED', 'BOOKING_RESCHEDULED'],
                'active' => true,
                'payloadTemplate' => null,
            ]);
        }
        
        // Retell Webhooks (when API available)
        if ($company->retell_api_key) {
            // Future implementation
        }
        
        return $results;
    }
}
```

## PHASE 3: Performance & Monitoring (MORGEN)

### 3.1 Query Optimization

**Problem**: N+1 Queries überall
**Impact**: Langsame Ladezeiten
**Lösung**: Eager Loading & Query Builder

```php
// Vorher: 100+ Queries
$appointments = Appointment::all();
foreach ($appointments as $appointment) {
    echo $appointment->customer->name; // N+1!
}

// Nachher: 1 Query
$appointments = Appointment::with(['customer', 'staff', 'branch', 'service'])
    ->select(['id', 'customer_id', 'staff_id', 'start_time', 'status'])
    ->where('company_id', $companyId)
    ->get();
```

### 3.2 Caching Strategy

**Problem**: Keine konsistente Cache-Nutzung
**Impact**: Unnötige API-Calls
**Lösung**: Cache Layer mit TTL

```php
class CachedCalcomService {
    public function getAvailability($eventTypeId, $startDate, $endDate) {
        $cacheKey = "availability:{$eventTypeId}:{$startDate}:{$endDate}";
        
        return Cache::remember($cacheKey, 300, function() use ($eventTypeId, $startDate, $endDate) {
            return $this->calcomService->getAvailability($eventTypeId, $startDate, $endDate);
        });
    }
}
```

### 3.3 Real-time Monitoring

**Problem**: Keine Sichtbarkeit in Systemzustand
**Impact**: Probleme werden zu spät erkannt
**Lösung**: Health Checks & Metrics

```php
class SystemHealthChecker {
    public function check(): array {
        return [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'calcom_api' => $this->checkCalcomApi(),
            'retell_api' => $this->checkRetellApi(),
            'queue_size' => $this->getQueueSize(),
            'error_rate' => $this->getErrorRate(),
        ];
    }
}
```

## Prüfschritte nach jeder Implementierung

### Nach Error Handling:
1. [ ] Simuliere API-Ausfall → System bleibt stabil
2. [ ] Provoziere Timeout → Retry funktioniert
3. [ ] Circuit Breaker Test → Fallback wird verwendet
4. [ ] Check Logs → Alle Fehler mit Context

### Nach Automatisierung:
1. [ ] Neue Branch anlegen → Agent automatisch erstellt
2. [ ] Webhook Test → Automatisch registriert
3. [ ] End-to-End Test → Anruf → Termin funktioniert

### Nach Performance:
1. [ ] Query Count vor/nach Optimierung vergleichen
2. [ ] Response Time messen (sollte <200ms sein)
3. [ ] Cache Hit Rate prüfen (sollte >80% sein)
4. [ ] Monitoring Dashboard zeigt alle Metriken

## Zeitplan

**Tag 1 (Heute)**:
- 14:00-16:00: Error Handling implementieren
- 16:00-18:00: Automatisierung beginnen
- 18:00-19:00: Tests & Dokumentation

**Tag 2 (Morgen)**:
- 09:00-11:00: Automatisierung fertigstellen
- 11:00-13:00: Performance Optimierung
- 14:00-16:00: Monitoring Setup
- 16:00-17:00: Finaler Test aller Komponenten

## Erfolgskriterien

- [ ] 0 ungefangene Exceptions in 24h
- [ ] API Verfügbarkeit >99.9%
- [ ] Setup-Zeit <3 Minuten
- [ ] Response Time <200ms
- [ ] Alle kritischen Fehler geloggt mit Context

---
*Dieser Plan ist verbindlich und wird schrittweise abgearbeitet.*