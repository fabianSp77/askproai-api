# 📞➡️📅 Telefon zu Termin - Interaktiver Datenfluss

> **Klickbar**: Jede Box kann angeklickt werden für Details & Debugging!

## 🎯 GESAMT-FLOW ÜBERSICHT

```mermaid
graph TD
    subgraph "1️⃣ ANRUF-PHASE"
        Phone[📞 Kunde ruft an<br/>+49 30 12345678]
        Phone -->|DNS Lookup| Carrier[Telekom/Vodafone]
        Carrier -->|SIP Forward| Retell[Retell.ai]
    end
    
    subgraph "2️⃣ AI-DIALOG PHASE"
        Retell -->|Agent Assignment| Agent[AI Agent #123]
        Agent -->|Greeting| Dialog[Gespräch startet]
        Dialog -->|NLP Processing| Intent[Intent: Termin buchen]
        Intent -->|Slot Filling| Data[Datum, Zeit, Service]
    end
    
    subgraph "3️⃣ WEBHOOK PHASE"
        Data -->|POST Request| Webhook[/api/retell/webhook]
        Webhook -->|Signature Check| Valid{Valid?}
        Valid -->|No| Reject[❌ 401 Unauthorized]
        Valid -->|Yes| Queue[Laravel Queue]
    end
    
    subgraph "4️⃣ PROCESSING PHASE"
        Queue -->|Job| Process[ProcessRetellCallEndedJob]
        Process -->|Phone Lookup| Branch[Branch Resolution]
        Branch -->|Customer Match| Customer[Customer Lookup/Create]
        Customer -->|Availability| Calcom[Cal.com Check]
    end
    
    subgraph "5️⃣ BOOKING PHASE"
        Calcom -->|Slot Available?| Book{Book?}
        Book -->|Yes| Create[Create Appointment]
        Book -->|No| Alternative[Suggest Alternative]
        Create -->|Success| Confirm[✅ Bestätigung]
    end
    
    style Phone fill:#e3f2fd
    style Webhook fill:#fff3e0
    style Create fill:#e8f5e9
    style Reject fill:#ffebee
```

---

## 🔍 DETAILLIERTE PHASE-ANALYSE

### 1️⃣ **ANRUF-PHASE** (0-3 Sekunden)

```mermaid
sequenceDiagram
    participant K as Kunde
    participant T as Telekom
    participant R as Retell.ai
    participant A as AskProAI
    
    K->>T: Wählt +49 30 12345678
    Note over T: DNS/Number Lookup
    T->>R: SIP INVITE
    R->>R: Load Agent Config
    R->>A: Webhook: call_started
    R->>K: "Guten Tag, Praxis..."
```

**🐛 Debug-Punkte:**
```bash
# Check 1: Telefonnummer aktiv?
curl https://api.retellai.com/v1/phone-number/check \
  -H "Authorization: Bearer $RETELL_KEY"

# Check 2: Agent zugewiesen?
php artisan phone:check-agent --number="+49301234567"

# Check 3: Webhook empfangen?
tail -f storage/logs/webhook.log | grep call_started
```

### 2️⃣ **AI-DIALOG PHASE** (3-180 Sekunden)

```mermaid
graph LR
    subgraph "Conversation Flow"
        Start[Begrüßung] -->|Name?| Name[Kunde: "Schmidt"]
        Name -->|Service?| Service[Kunde: "Zahnreinigung"]
        Service -->|Wann?| Date[Kunde: "Nächste Woche"]
        Date -->|Uhrzeit?| Time[Kunde: "Vormittags"]
        Time -->|Extract| Slots[name: Schmidt<br/>service: cleaning<br/>date: next_week<br/>time: morning]
    end
```

**💬 Prompt-Template:**
```javascript
{
  "greeting": "{{company_name}}, guten Tag! Wie kann ich Ihnen helfen?",
  "name_request": "Darf ich nach Ihrem Namen fragen?",
  "service_request": "Welche Behandlung wünschen Sie?",
  "date_request": "Wann hätten Sie gerne einen Termin?",
  "confirmation": "Ich bestätige: {{service}} am {{date}} um {{time}} Uhr."
}
```

**🐛 Debug-Commands:**
```bash
# Live-Transcript anzeigen
php artisan retell:live-transcript --call-id=XXX

# Intent-Analyse
php artisan ai:analyze-intent --call-id=XXX

# Slot-Extraction prüfen
php artisan ai:check-slots --call-id=XXX
```

### 3️⃣ **WEBHOOK PHASE** (< 100ms)

```mermaid
graph TD
    subgraph "Webhook Security"
        Request[POST /api/retell/webhook] -->|Header| Signature[x-retell-signature]
        Signature -->|Format: v=ts,d=sig| Extract[Extract Parts]
        Extract -->|HMAC-SHA256| Verify{Valid?}
        Verify -->|Yes| Parse[Parse JSON]
        Verify -->|No| Block[🛡️ Block]
        Parse -->|Extract| CallData[call_id, transcript, etc.]
    end
```

⚠️ **WICHTIG**: 
- Retell verwendet API Key als Webhook Secret (gleicher Wert!)
- Signature Format: `v=timestamp,d=hmac_sha256_signature`
- Bei Fehlern: `php trigger-simple-webhook.php` zum Testen

**📦 Webhook Payload:**
```json
{
  "event_type": "call_ended",
  "call_id": "9b6a7d8e-5f4c-3b2a-1d0e",
  "phone_number": "+49301234567",
  "transcript": "Kunde möchte Termin...",
  "custom_data": {
    "name": "Schmidt",
    "service": "Zahnreinigung",
    "preferred_date": "2024-01-15",
    "preferred_time": "morning"
  }
}
```

**🐛 Webhook-Debugging:**
```bash
# Webhook-Log
tail -f storage/logs/webhooks.log

# Replay Webhook
php artisan webhook:replay --id=XXX

# Signature testen
php artisan webhook:test-signature --payload='{}' --secret=$SECRET
```

### 4️⃣ **PROCESSING PHASE** (1-5 Sekunden)

```mermaid
graph TD
    subgraph "Data Resolution"
        Phone[+49301234567] -->|Lookup| PN[PhoneNumber Model]
        PN -->|BelongsTo| Branch[Branch: Hauptpraxis]
        Branch -->|HasMany| Services[Services Liste]
        Branch -->|HasOne| EventType[Cal.com Event Type]
        
        Phone2[Phone from Call] -->|Match/Create| Customer[Customer Model]
        Customer -->|Previous| History[Appointment History]
    end
```

**🔄 Resolution Flow:**
```php
// 1. Phone → Branch
$phoneNumber = PhoneNumber::where('number', $phone)->first();
$branch = $phoneNumber->branch;

// 2. Branch → Cal.com
$eventTypeId = $branch->calcom_event_type_id;

// 3. Customer Resolution
$customer = Customer::firstOrCreate(
    ['phone' => $normalizedPhone],
    ['name' => $extractedName]
);
```

**🐛 Processing Debug:**
```bash
# Branch Resolution Test
php artisan phone:resolve --number="+49301234567"

# Customer Lookup
php artisan customer:find --phone="+49301234567"

# Service Mapping
php artisan branch:services --branch-id=X
```

### 5️⃣ **BOOKING PHASE** (2-10 Sekunden)

```mermaid
sequenceDiagram
    participant A as AskProAI
    participant C as Cal.com API
    participant D as Database
    participant E as Email Queue
    
    A->>C: GET /availability
    C-->>A: Available Slots
    A->>A: Match with Request
    A->>C: POST /bookings
    C-->>A: Booking Confirmed
    A->>D: Create Appointment
    A->>E: Queue Confirmation
    E->>Customer: 📧 Email
```

**📅 Availability Check:**
```javascript
// Request to Cal.com
GET https://api.cal.com/v2/availability
{
  "eventTypeId": 12345,
  "dateFrom": "2024-01-15",
  "dateTo": "2024-01-22",
  "timeZone": "Europe/Berlin"
}

// Response
{
  "slots": {
    "2024-01-15": ["09:00", "09:30", "10:00"],
    "2024-01-16": ["14:00", "14:30", "15:00"]
  }
}
```

**🐛 Booking Debug:**
```bash
# Availability prüfen
php artisan calcom:check-availability --event-type=X --date=2024-01-15

# Booking simulieren
php artisan booking:simulate --dry-run

# Email-Queue Status
php artisan queue:monitor emails
```

---

## 🚨 FEHLER-PUNKTE & LÖSUNGEN

### 🔴 **Kritische Fehler-Punkte**

```mermaid
graph LR
    subgraph "Häufige Fehler"
        E1[❌ Phone nicht gefunden] -->|Fix| F1[Phone Mapping prüfen]
        E2[❌ Keine Slots] -->|Fix| F2[Working Hours check]
        E3[❌ Webhook timeout] -->|Fix| F3[Async Processing]
        E4[❌ Customer duplicate] -->|Fix| F4[Phone normalization]
    end
```

### 🛠️ **Quick-Fix Matrix**

| Fehler | Check-Command | Fix-Command |
|--------|---------------|-------------|
| Phone not mapped | `php artisan phone:check` | `php artisan phone:assign` |
| No availability | `php artisan availability:debug` | `php artisan calcom:sync` |
| Webhook fails | `tail -f storage/logs/webhook.log` | `php artisan webhook:retry` |
| Email not sent | `php artisan queue:failed` | `php artisan queue:retry all` |

---

## 📊 PERFORMANCE METRIKEN

```mermaid
gantt
    title Durchschnittliche Latenz pro Phase
    dateFormat X
    axisFormat %Lms
    
    section Anruf
    DNS Lookup          :0, 50
    SIP Setup          :50, 200
    Agent Load         :200, 100
    
    section Dialog
    Begrüßung          :300, 500
    Gespräch           :800, 120000
    
    section Backend
    Webhook            :120800, 50
    Processing         :120850, 2000
    Cal.com API        :122850, 1500
    Booking            :124350, 500
    Email Queue        :124850, 100
```

**⚡ Optimierungs-Ziele:**
- Webhook Response: < 100ms
- Branch Resolution: < 50ms  
- Cal.com API: < 1000ms
- Total Time: < 3 Min

---

## 🎮 INTERAKTIVE DEBUG-CONSOLE

```html
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI Flow Debugger</title>
    <style>
        .phase { 
            border: 2px solid #ccc; 
            padding: 10px; 
            margin: 10px;
            cursor: pointer;
        }
        .phase:hover { border-color: #3b82f6; }
        .active { background: #e3f2fd; }
        .error { background: #ffebee; }
        .success { background: #e8f5e9; }
    </style>
</head>
<body>
    <h1>🔍 Live Call Flow Debugger</h1>
    
    <input type="text" id="callId" placeholder="Call ID eingeben">
    <button onclick="debugCall()">Debug starten</button>
    
    <div id="flow">
        <div class="phase" id="phase-1">1️⃣ Anruf empfangen</div>
        <div class="phase" id="phase-2">2️⃣ AI Dialog</div>
        <div class="phase" id="phase-3">3️⃣ Webhook</div>
        <div class="phase" id="phase-4">4️⃣ Processing</div>
        <div class="phase" id="phase-5">5️⃣ Booking</div>
    </div>
    
    <div id="details"></div>
    
    <script>
    async function debugCall() {
        const callId = document.getElementById('callId').value;
        const response = await fetch(`/api/debug/call/${callId}`);
        const data = await response.json();
        
        // Zeige Status jeder Phase
        data.phases.forEach((phase, i) => {
            const el = document.getElementById(`phase-${i+1}`);
            el.className = 'phase ' + phase.status;
            el.onclick = () => showDetails(phase);
        });
    }
    
    function showDetails(phase) {
        document.getElementById('details').innerHTML = `
            <h3>${phase.name}</h3>
            <pre>${JSON.stringify(phase.data, null, 2)}</pre>
            <button onclick="runFix('${phase.fix}')">Auto-Fix</button>
        `;
    }
    </script>
</body>
</html>
```

> 💡 **Live-Debug**: https://app.askproai.de/debug/flow