# AskProAI - Detaillierte Datenfluss-Analyse: Retell Webhooks bis Dashboard

## Executive Summary

Die Analyse zeigt kritische Probleme im Datenfluss von eingehenden Retell-Webhooks bis zur Dashboard-Anzeige:

1. **Webhook-Routing-Chaos**: 12+ verschiedene Routes für Retell-Webhooks
2. **Company/Branch Resolution**: Inkonsistente Auflösung führt zu Datenverlust
3. **Job Processing**: Asynchrone Verarbeitung ohne Echtzeit-Updates
4. **Live Data Updates**: Kein funktionierendes Real-Time System
5. **Cal.com Integration**: Zwei konkurrierende Services (V1 vs V2)

## 🔴 Kritische Probleme

### 1. Webhook Routing Chaos
```
PROBLEM: 12+ verschiedene Routes für denselben Zweck!

Routes gefunden:
- /api/retell/webhook (Original)
- /api/mcp/retell/webhook (MCP Version)
- /api/retell/webhook-debug (Debug ohne Signatur)
- /api/retell/webhook-nosig (Ohne Signatur)
- /api/retell/optimized-webhook (Optimierte Version)
- /api/retell/enhanced-webhook (Enhanced Version)
- /api/retell/mcp-webhook (MCP-basiert)
- /api/retell/realtime/webhook (Realtime)
- /api/test/webhook (Test)
- /api/test/mcp-webhook (MCP Test)
- /webhook (Unified Handler)
- /mcp/webhook/retell (MCP Webhook)

FOLGE: Retell weiß nicht, welche URL konfiguriert werden soll!
```

### 2. Signatur-Verifikation Bypass
```php
// WebhookProcessor.php:446-457
protected function verifyRetellSignature(array $payload, array $headers): bool
{
    // TEMPORARY: Bypass signature verification for Retell webhooks
    Log::warning('RETELL WEBHOOK SIGNATURE BYPASS - TEMPORARY', [
        'has_signature' => isset($headers['x-retell-signature']),
        'event' => $payload['event'] ?? 'unknown',
    ]);
    
    return true; // Temporarily allow all Retell webhooks
}

SICHERHEITSPROBLEM: Jeder kann Fake-Webhooks senden!
```

### 3. Company/Branch Resolution Inkonsistenzen
```
PROBLEM: 3 verschiedene Resolver mit unterschiedlicher Logik!

1. PhoneNumberResolver::resolveFromWebhook()
   - Prüft: metadata.askproai_branch_id
   - Fallback: to_number → phone_numbers table
   - Fallback: agent_id → branches table
   - Fallback: from_number → customer history

2. WebhookCompanyResolver::resolveFromWebhook()
   - Prüft: metadata.company_id
   - Fallback: to_number → phone resolution
   - Fallback: from_number → customer lookup
   - Fallback: agent_id → retell_agents table

3. MCPContextResolver (in MCP Services)
   - Eigene Logik, nicht synchron mit anderen

FOLGE: Dieselbe Telefonnummer kann zu verschiedenen Companies aufgelöst werden!
```

### 4. Job Processing ohne Real-Time Updates
```
PROBLEM: Webhooks werden asynchron verarbeitet ohne Live-Updates

Flow:
1. Webhook empfangen → WebhookProcessor
2. Job erstellt → ProcessRetellWebhookJob (Queue: webhooks)
3. ODER: ProcessRetellCallEndedJob (Queue: webhooks)
4. Job verarbeitet Call-Daten (1-3 Sekunden später)
5. Dashboard pollt alle 2 Sekunden → Zeigt veraltete Daten!

KEINE Events/Broadcasting für Real-Time Updates!
```

### 5. Live Dashboard nicht funktionsfähig
```php
// LiveCallsWidget.php
public function loadActiveCalls(): void
{
    // Holt nur Calls OHNE end_timestamp
    $this->activeCalls = Call::query()
        ->whereNull('end_timestamp')
        ->where('created_at', '>', now()->subHours(2))
        // ...
}

PROBLEM: 
- call_started Events kommen NICHT an (werden nicht verarbeitet)
- Nur call_ended wird gespeichert → Keine Live-Anrufe!
- Polling alle 2 Sekunden auf leere Datenbank
```

## 📊 Datenfluss-Diagramm

```mermaid
graph TB
    subgraph "Retell.ai"
        R[Retell Call] -->|Webhook| W{Welche Route?}
    end
    
    subgraph "Webhook Routes Chaos"
        W --> R1[/api/retell/webhook]
        W --> R2[/api/mcp/retell/webhook]
        W --> R3[/api/retell/webhook-debug]
        W --> R4[12+ weitere Routes...]
    end
    
    subgraph "Controller Layer"
        R1 --> RC[RetellWebhookController]
        R2 --> RMC[RetellWebhookMCPController]
        R3 --> RDC[RetellWebhookDebugController]
        RC --> WP[WebhookProcessor]
        RMC --> MCP[MCPOrchestrator]
    end
    
    subgraph "Company Resolution Chaos"
        WP --> CR1[PhoneNumberResolver]
        WP --> CR2[WebhookCompanyResolver]
        MCP --> CR3[MCPContextResolver]
        CR1 --> |Unterschiedliche Ergebnisse| DB1[(branches)]
        CR2 --> |Unterschiedliche Ergebnisse| DB2[(companies)]
        CR3 --> |Unterschiedliche Ergebnisse| DB3[(phone_numbers)]
    end
    
    subgraph "Job Processing"
        WP --> J1[ProcessRetellWebhookJob]
        WP --> J2[ProcessRetellCallEndedJob]
        J1 --> |1-3s Delay| CALL[(calls table)]
        J2 --> |1-3s Delay| CALL
    end
    
    subgraph "Dashboard Layer"
        D[LiveCallsWidget] -->|Poll 2s| CALL
        D --> |Keine Live-Daten!| UI[Dashboard UI]
        CALL --> |Keine call_started!| EMPTY[Leere Anzeige]
    end
    
    subgraph "Missing Real-Time"
        RT[❌ Kein Broadcasting]
        RT[❌ Keine WebSockets]
        RT[❌ Keine Server-Sent Events]
    end
```

## 🔧 Sofortmaßnahmen erforderlich

### 1. Webhook Route Konsolidierung
```php
// NUR EINE Route behalten:
Route::post('/api/retell/webhook', [UnifiedRetellController::class, 'handle'])
    ->middleware(['verify.retell.signature'])
    ->name('retell.webhook');

// ALLE anderen Routes entfernen oder auf diese umleiten!
```

### 2. Signatur-Verifikation aktivieren
```php
// FIX in WebhookProcessor.php
protected function verifyRetellSignature(array $payload, array $headers): bool
{
    $signature = $headers['x-retell-signature'][0] ?? null;
    $apiKey = config('services.retell.api_key');
    
    if (!$signature || !$apiKey) {
        return false;
    }
    
    $body = json_encode($payload);
    $expectedSignature = hash_hmac('sha256', $body, $apiKey);
    
    return hash_equals($expectedSignature, $signature);
}
```

### 3. Einheitlicher Company Resolver
```php
// Neuer Service: UnifiedCompanyResolver
class UnifiedCompanyResolver
{
    public function resolve(array $webhookData): ?Company
    {
        // 1. Phone number lookup (cached)
        // 2. Agent ID lookup (cached)  
        // 3. Customer history (cached)
        // 4. Return consistent result
    }
}
```

### 4. Real-Time Updates implementieren
```php
// In ProcessRetellWebhookJob:
public function handle()
{
    // Process webhook...
    
    // Broadcast update
    broadcast(new CallUpdated($call))->toOthers();
    
    // Update cache for polling fallback
    Cache::put("live_call_{$call->id}", $call->toArray(), 300);
}
```

### 5. Dashboard Real-Time fähig machen
```javascript
// In LiveCallsWidget Blade:
Echo.channel('calls')
    .listen('CallUpdated', (e) => {
        // Update call in UI immediately
        this.updateCall(e.call);
    });
```

## 📋 Priorisierte Aufgabenliste

### Sofort (Kritisch)
1. **Webhook Route aufräumen** - NUR eine Route behalten
2. **Signatur-Verifikation aktivieren** - Sicherheitslücke schließen
3. **call_started Events verarbeiten** - Für Live-Anzeige

### Diese Woche
4. **Company Resolver vereinheitlichen** - Konsistente Zuordnung
5. **Real-Time Broadcasting** - Pusher/WebSockets einrichten
6. **Dashboard Update** - Echo.js für Live-Updates

### Nächste Woche  
7. **Job Konsolidierung** - Ein Job statt drei verschiedene
8. **Cal.com Service** - Nur V2 verwenden, V1 entfernen
9. **Monitoring** - Webhook-Verarbeitung überwachen

## 🚨 Warum der Testanruf nicht funktioniert

1. **Webhook kommt an** aber an welcher Route? (12+ Möglichkeiten)
2. **Company Resolution** schlägt fehl oder ordnet falsch zu
3. **Job Processing** verzögert um 1-3 Sekunden
4. **call_started wird ignoriert** - keine Live-Daten
5. **Dashboard pollt vergeblich** - keine aktiven Calls in DB

## Empfehlung

Das System benötigt eine **grundlegende Überarbeitung** des Webhook-Flows:
- Ein klarer, dokumentierter Pfad
- Konsistente Company/Branch Resolution
- Real-Time Updates von Anfang bis Ende
- Monitoring und Logging auf jedem Schritt

Ohne diese Änderungen wird das Live-Dashboard **niemals zuverlässig funktionieren**.