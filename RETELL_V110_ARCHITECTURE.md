# Retell Agent V110 - Architecture Documentation

**Version:** V110
**Letzte Aktualisierung:** 2025-11-10

---

## Table of Contents

1. [High-Level Architecture](#1-high-level-architecture)
2. [Conversation Flow Diagram](#2-conversation-flow-diagram)
3. [Booking Flow Detailed](#3-booking-flow-detailed)
4. [Error Handling Flow](#4-error-handling-flow)
5. [Data Flow](#5-data-flow)
6. [Node Type Reference](#6-node-type-reference)

---

## 1. High-Level Architecture

```mermaid
graph TB
    subgraph "Retell Cloud"
        A[Voice Input] --> B[Speech-to-Text]
        B --> C[Retell LLM]
        C --> D[Conversation Flow Engine]
        D --> E[Text-to-Speech]
        E --> F[Voice Output]
    end

    subgraph "AskPro API Gateway"
        G[Webhook Endpoint]
        H[Function Router]
        I[Business Logic]
        J[Database]
    end

    subgraph "External Services"
        K[Cal.com API]
        L[SMS Gateway]
        M[Email Service]
    end

    D -->|Function Calls| G
    G --> H
    H --> I
    I --> J
    I --> K
    I --> L
    I --> M

    I -->|Response| D

    style D fill:#4CAF50,stroke:#333,stroke-width:2px
    style I fill:#2196F3,stroke:#333,stroke-width:2px
```

---

## 2. Conversation Flow Diagram

### Complete Flow Overview

```mermaid
graph TD
    Start[Call Start] --> Greeting[node_greeting]
    Greeting --> InitCtx[func_initialize_context<br/>get_current_context]
    InitCtx --> CheckCust[func_check_customer<br/>⭐ NEU: Customer Recognition]
    CheckCust --> IntentRouter[intent_router<br/>SILENT Classification]

    IntentRouter -->|Booking Intent| ExtractVars[node_extract_booking_variables]
    IntentRouter -->|Check Appointments| GetAppts[func_get_appointments]
    IntentRouter -->|Reschedule| CollectReschedule[node_collect_reschedule_info]
    IntentRouter -->|Cancel| CollectCancel[node_collect_cancel_info]
    IntentRouter -->|Services Info| GetServices[func_get_services]

    ExtractVars --> CollectMissing[node_collect_missing_booking_data<br/>Smart Data Collection]
    CollectMissing --> CheckAvail[func_check_availability]
    CheckAvail --> PresentResult[node_present_result]

    PresentResult -->|Available| CollectFinal[node_collect_final_booking_data]
    PresentResult -->|Not Available| PresentAlt[node_present_alternatives<br/>⭐ Near-Match Logic]
    PresentResult -->|No Alternatives| NoAvail[node_no_availability]

    PresentAlt -->|Selected| ExtractAlt[node_extract_alternative_selection]
    ExtractAlt --> UpdateTime[node_update_time]
    UpdateTime --> CollectFinal

    CollectFinal --> StartBook[func_start_booking<br/>Step 1: Validate]
    StartBook -->|Success| ConfirmBook[func_confirm_booking<br/>Step 2: Execute]
    StartBook -->|Error| ValFail[node_booking_validation_failed]

    ConfirmBook -->|Success| BookSuccess[node_booking_success]
    ConfirmBook -->|Error| BookFail[node_booking_failed]

    BookFail --> CollectPhone[node_collect_callback_phone<br/>⭐ NEU: Phone Collection]
    NoAvail --> OfferCallback[node_offer_callback]
    OfferCallback --> CollectPhone
    PresentAlt -->|Declined| OfferCallback

    CollectPhone --> ReqCallback[func_request_callback]
    ReqCallback --> CallbackConf[node_callback_confirmed]

    BookSuccess --> AskMore[node_ask_anything_else]
    CallbackConf --> Goodbye[node_goodbye]
    AskMore -->|Yes| IntentRouter
    AskMore -->|No| Goodbye

    GetAppts --> ShowAppts[node_show_appointments]
    ShowAppts --> AskMore

    CollectReschedule --> FuncReschedule[func_reschedule_appointment]
    FuncReschedule --> RescheduleSuccess[node_reschedule_success]
    RescheduleSuccess --> AskMore

    CollectCancel --> FuncCancel[func_cancel_appointment]
    FuncCancel --> CancelSuccess[node_cancel_success]
    CancelSuccess --> AskMore

    GetServices --> ShowServices[node_show_services]
    ShowServices --> AskMore

    Goodbye --> End[Call End]

    style CheckCust fill:#FFD700,stroke:#333,stroke-width:3px
    style PresentAlt fill:#FFD700,stroke:#333,stroke-width:3px
    style CollectPhone fill:#FFD700,stroke:#333,stroke-width:3px
    style IntentRouter fill:#E91E63,stroke:#333,stroke-width:2px
    style StartBook fill:#4CAF50,stroke:#333,stroke-width:2px
    style ConfirmBook fill:#4CAF50,stroke:#333,stroke-width:2px
```

### Legend

- 🟡 **Yellow Nodes** = V110 New Features
- 🔴 **Pink Node** = Silent Router (no speech)
- 🟢 **Green Nodes** = Two-Step Booking

---

## 3. Booking Flow Detailed

### Happy Path (Available)

```mermaid
sequenceDiagram
    participant User
    participant Agent
    participant IntentRouter as intent_router<br/>(SILENT)
    participant Extract as node_extract_booking_variables
    participant Collect as node_collect_missing_data
    participant CheckAvail as func_check_availability
    participant Present as node_present_result
    participant CollectFinal as node_collect_final_booking_data
    participant StartBook as func_start_booking
    participant ConfirmBook as func_confirm_booking
    participant Success as node_booking_success

    User->>Agent: "Termin morgen 10 Uhr"
    Agent->>IntentRouter: Classify intent (SILENT)
    IntentRouter-->>Extract: → BOOKING intent
    Extract->>Extract: Extract: service, date, time
    Extract->>Collect: Check missing data

    alt Service from check_customer
        Collect->>Collect: Service already known (confidence 0.8)
        Collect->>Agent: "Wann hätten Sie Zeit?"
    else Service unknown
        Collect->>Agent: "Welche Dienstleistung?"
        User->>Agent: "Herrenhaarschnitt"
        Collect->>Agent: "Wann hätten Sie Zeit?"
    end

    User->>Agent: "Morgen 10 Uhr"
    Collect->>CheckAvail: Call API

    Note over CheckAvail: "Einen Moment, ich prüfe..."
    CheckAvail-->>Present: available=true

    Present->>Agent: "Der Termin ist frei! Soll ich buchen?"
    User->>Agent: "Ja bitte"

    Present->>CollectFinal: Check name/contact

    alt Data from check_customer
        CollectFinal->>CollectFinal: Name+Phone already known
    else New customer
        CollectFinal->>Agent: "Darf ich noch Ihren Namen erfragen?"
        User->>Agent: "Max Müller"
    end

    CollectFinal->>StartBook: All data complete
    Note over StartBook: "Einen Moment, ich validiere..."
    StartBook-->>ConfirmBook: status=validating

    Note over ConfirmBook: "Ich buche den Termin..."
    ConfirmBook-->>Success: success=true

    Success->>Agent: "Ihr Termin ist gebucht für morgen 10 Uhr."
    Agent->>User: "Kann ich sonst noch helfen?"
```

### Near-Match Path (Alternative ±30 min)

```mermaid
sequenceDiagram
    participant User
    participant Agent
    participant CheckAvail as func_check_availability
    participant Present as node_present_result
    participant PresentAlt as node_present_alternatives<br/>⭐ Near-Match Logic
    participant ExtractAlt as node_extract_alternative_selection
    participant UpdateTime as node_update_time

    User->>Agent: "Termin morgen 10 Uhr"
    Agent->>CheckAvail: Check availability

    CheckAvail-->>Present: available=false<br/>alternatives=[9:45, 10:15]<br/>distance_minutes=[-15, +15]<br/>near_match=true

    Present->>PresentAlt: Has alternatives

    Note over PresentAlt: NEAR-MATCH LOGIC ACTIVE<br/>abs(distance) <= 30 min

    PresentAlt->>Agent: "Um 10 Uhr ist schon belegt,<br/>aber ich kann Ihnen 9:45<br/>oder 10:15 anbieten.<br/>Was passt Ihnen besser?"

    Note over Agent: ✅ POSITIVE FRAMING<br/>"kann Ihnen anbieten"<br/>❌ NOT "leider nicht verfügbar"

    User->>Agent: "10:15 geht"

    PresentAlt->>ExtractAlt: User selected alternative
    ExtractAlt->>ExtractAlt: Extract: selected_alternative_time="10:15"
    ExtractAlt->>UpdateTime: Update variables
    UpdateTime->>UpdateTime: {{appointment_time}} = "10:15"
    UpdateTime->>Agent: "Perfekt! Soll ich den Termin<br/>für morgen 10:15 buchen?"
```

### Error with Callback Flow

```mermaid
sequenceDiagram
    participant User
    participant Agent
    participant ConfirmBook as func_confirm_booking
    participant BookFail as node_booking_failed
    participant CollectPhone as node_collect_callback_phone<br/>⭐ NEU
    participant ReqCallback as func_request_callback
    participant CallbackConf as node_callback_confirmed

    User->>Agent: "Ja bitte buchen"
    Agent->>ConfirmBook: Execute booking

    Note over ConfirmBook: Cal.com Timeout<br/>⚠️ ERROR

    ConfirmBook-->>BookFail: success=false

    BookFail->>Agent: "Es tut mir leid, es gab ein<br/>technisches Problem.<br/>Ich informiere unsere Mitarbeiter<br/>und wir rufen Sie zurück."

    BookFail->>CollectPhone: Check customer_phone

    alt customer_phone vorhanden
        CollectPhone->>CollectPhone: SILENT transition
        CollectPhone->>Agent: "Wir melden uns innerhalb<br/>30 Minuten bei Ihnen."
    else customer_phone FEHLT
        CollectPhone->>Agent: "Unter welcher Nummer können<br/>wir Sie am besten erreichen?"
        User->>Agent: "0172 345 6789"
        CollectPhone->>Agent: "Vielen Dank! Wir rufen Sie unter<br/>0172 345 6789 zurück."
        Note over CollectPhone: ✅ Phone zur Bestätigung wiederholt
    end

    CollectPhone->>ReqCallback: Create callback

    Note over ReqCallback: Multi-Channel Notification:<br/>Email + SMS + WhatsApp + Portal

    ReqCallback-->>CallbackConf: success=true

    CallbackConf->>Agent: "Perfekt! Unsere Mitarbeiter sind<br/>informiert und wir melden uns<br/>innerhalb 30 Minuten unter<br/>0172 345 6789. Sie erhalten<br/>auch eine SMS."

    Note over Agent: ✅ EXPLIZIT: "Mitarbeiter informiert"<br/>✅ Phone wiederholt<br/>✅ Zeitrahmen genannt<br/>✅ SMS erwähnt
```

---

## 4. Error Handling Flow

```mermaid
graph TD
    Error[Error Occurred] --> CheckType{Error Type?}

    CheckType -->|Validation Error| ValError[node_booking_validation_failed]
    CheckType -->|Technical Error| TechError[node_booking_failed]
    CheckType -->|No Availability| NoAvail[node_no_availability]

    ValError --> ExplainError[Explain specific error]
    ExplainError --> OfferFix[Offer correction]
    OfferFix --> RetryCollect[Return to data collection]

    TechError --> Apologize[Apologize professionally]
    Apologize --> InformStaff["Ich informiere unsere Mitarbeiter"]
    InformStaff --> CheckPhone{customer_phone<br/>vorhanden?}

    CheckPhone -->|Yes| ConfirmCallback["Wir rufen unter [phone] zurück"]
    CheckPhone -->|No| AskPhone[Ask for phone number]
    AskPhone --> RepeatPhone[Repeat phone for confirmation]
    RepeatPhone --> ConfirmCallback

    ConfirmCallback --> CreateCallback[func_request_callback]
    CreateCallback --> SendNotif[Multi-Channel Notification]
    SendNotif --> ConfirmToUser[Confirm to customer]
    ConfirmToUser --> EndCall[End call]

    NoAvail --> OfferSearch[Offer different timeframe]
    OfferSearch --> UserChoice{User decision?}
    UserChoice -->|New timeframe| RetrySearch[Search again]
    UserChoice -->|Callback| CreateCallback
    UserChoice -->|Decline| EndCall

    style TechError fill:#FF5722,stroke:#333,stroke-width:2px
    style CreateCallback fill:#4CAF50,stroke:#333,stroke-width:2px
    style CheckPhone fill:#FFD700,stroke:#333,stroke-width:2px
```

---

## 5. Data Flow

### Call Initialization

```mermaid
graph LR
    A[Call Start] --> B[get_current_context]
    B --> C{Response}
    C -->|Success| D[Set Variables:<br/>{{current_date}}<br/>{{current_time}}<br/>{{day_name}}]
    D --> E[check_customer]
    E --> F{Customer Found?}
    F -->|Yes| G[Set Variables:<br/>{{customer_name}}<br/>{{customer_phone}}<br/>{{customer_email}}<br/>{{predicted_service}}<br/>{{service_confidence}}<br/>{{preferred_staff}}]
    F -->|No| H[Variables empty]
    G --> I[intent_router]
    H --> I

    style D fill:#4CAF50,stroke:#333,stroke-width:2px
    style G fill:#FFD700,stroke:#333,stroke-width:2px
```

### Variable Propagation

```mermaid
graph TD
    subgraph "Initial Variables (from check_customer)"
        V1[customer_name]
        V2[customer_phone]
        V3[customer_email]
        V4[predicted_service]
        V5[service_confidence]
    end

    subgraph "Extracted Variables"
        E1[service_name<br/>pre-filled if confidence >= 0.8]
        E2[appointment_date]
        E3[appointment_time]
    end

    subgraph "Alternative Selection"
        A1[selected_alternative_time]
        A2[selected_alternative_date]
    end

    subgraph "Booking Variables"
        B1[booking_token]
        B2[appointment_id]
        B3[booking_uid]
    end

    V1 --> E1
    V4 --> E1
    E1 --> B1
    E2 --> B1
    E3 --> B1
    A1 --> E3
    B1 --> B2
    B1 --> B3

    style E1 fill:#FFD700,stroke:#333,stroke-width:2px
    style V4 fill:#FFD700,stroke:#333,stroke-width:2px
```

---

## 6. Node Type Reference

### Conversation Nodes

**Purpose:** Dialogue with user, collect verbal information

**Properties:**
- `type: "conversation"`
- `instruction: { type: "prompt", text: "..." }`
- Can have multiple edges with conditions

**Examples:**
- node_greeting
- node_collect_missing_booking_data
- node_present_alternatives

### Function Nodes

**Purpose:** Call external APIs or backend functions

**Properties:**
- `type: "function"`
- `tool_id: "tool-name"`
- `parameter_mapping: { ... }`
- `speak_during_execution: true/false`
- `wait_for_result: true/false`

**Examples:**
- func_check_availability
- func_start_booking
- func_confirm_booking

### Extract Dynamic Variables Nodes

**Purpose:** Extract structured data from user input

**Properties:**
- `type: "extract_dynamic_variables"`
- `variables: [...]`
- Automatically populates {{variables}}

**Examples:**
- node_extract_booking_variables
- node_extract_alternative_selection

### End Nodes

**Purpose:** Terminate the call

**Properties:**
- `type: "end"`
- No outgoing edges

**Example:**
- node_goodbye

---

## Edge Transition Types

### Type 1: prompt (User Message Condition)

```json
{
  "type": "prompt",
  "prompt": "User wants to book appointment"
}
```

**Use:** When routing based on user intent or message content

### Type 2: equation (Variable Condition)

```json
{
  "type": "equation",
  "equations": [
    {"left": "customer_name", "operator": "exists"}
  ],
  "operator": "&&"
}
```

**Use:** When checking if variables are populated

### Type 3: always (Unconditional)

```json
{
  "type": "always"
}
```

**Use:** For mandatory transitions (e.g., after success message)

---

## Node Execution Order

### Priority Rules

1. **Equation edges** evaluated first
2. **Prompt edges** evaluated if no equation matches
3. **Always edges** as fallback

### Example

```json
{
  "edges": [
    {
      "type": "equation",
      "condition": "{{service_name}} exists",
      "destination": "func_check_availability"
    },
    {
      "type": "prompt",
      "prompt": "User provides service",
      "destination": "node_extract_booking_variables"
    }
  ]
}
```

**Execution:**
1. Check if `service_name` variable exists → If yes, go to check_availability
2. If no, evaluate prompt condition → If user mentioned service, extract it
3. If neither matches, stay in current node

---

## Performance Characteristics

### Node Execution Times

| Node Type | Typical Duration | Max Duration |
|-----------|------------------|--------------|
| Conversation | 50-200ms | 500ms |
| Function (fast) | 100-500ms | 5s |
| Function (slow) | 2-5s | 30s |
| Extract Variables | 50-150ms | 300ms |
| End | Immediate | - |

### Critical Path Timing (Happy Path)

```
Call Start → greeting (100ms)
→ init context (200ms)
→ check customer (300ms)
→ intent router (100ms)
→ extract vars (150ms)
→ collect data (USER: 5s)
→ check availability (1s)
→ present result (100ms)
→ collect final (USER: 3s)
→ start booking (400ms)
→ confirm booking (4s)
→ success (100ms)
→ goodbye (100ms)

Total Agent Time: ~6.5s
Total Call Time: ~14.5s (with user responses)
```

**Target: <25s total call duration** ✅

---

## Scalability Considerations

### Concurrent Calls

**Current:** Single-threaded per call (Retell handles concurrency)

**Backend Capacity:**
- **check_availability:** 60 calls/min
- **booking functions:** 30 calls/min
- **Redis cache:** 10,000 ops/sec

### Bottlenecks

1. **Cal.com API** (30s timeout)
   - Mitigation: Two-step booking (validate fast, execute slow)

2. **Redis Cache** (shared resource)
   - Mitigation: Key namespacing per company

3. **Database Connections** (limited pool)
   - Mitigation: Connection pooling + read replicas

---

## Security Architecture

### Authentication Flow

```mermaid
sequenceDiagram
    participant Retell as Retell API
    participant Gateway as API Gateway
    participant Verifier as Signature Verifier
    participant Handler as Function Handler

    Retell->>Gateway: POST /webhooks/retell/function<br/>X-Retell-Signature: abc123
    Gateway->>Verifier: Verify signature
    Verifier->>Verifier: HMAC-SHA256(payload, secret)

    alt Signature Valid
        Verifier-->>Gateway: ✅ Valid
        Gateway->>Handler: Process request
        Handler-->>Gateway: Response
        Gateway-->>Retell: 200 OK + Response
    else Signature Invalid
        Verifier-->>Gateway: ❌ Invalid
        Gateway-->>Retell: 401 Unauthorized
    end
```

### Data Protection

**PII Handling:**
- `customer_name` → Stored encrypted at rest
- `customer_phone` → Stored encrypted, hashed for lookup
- `customer_email` → Stored encrypted
- `call transcripts` → Retained 90 days, then deleted

**Compliance:**
- GDPR: Right to deletion implemented
- DSGVO: Consent tracking for recordings

---

**Version:** V110 Architecture Documentation
**Last Updated:** 2025-11-10
**Diagrams:** 8 Mermaid diagrams
**Coverage:** Complete system architecture
