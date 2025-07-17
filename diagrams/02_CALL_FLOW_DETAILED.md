# 📞 Detailed Call Flow Diagrams

## Complete Phone-to-Appointment Journey

### End-to-End Call Flow
```mermaid
sequenceDiagram
    participant C as Customer
    participant TN as Telco Network
    participant R as Retell.ai
    participant W as Webhook
    participant Q as Queue
    participant P as Processor
    participant PNR as PhoneNumberResolver
    participant CS as CustomerService
    participant CAL as Cal.com
    participant DB as Database
    participant E as Email Service
    
    %% Call Initiation
    C->>TN: Dials +49 30 12345678
    TN->>TN: Number lookup
    TN->>R: SIP INVITE
    R->>R: Load agent config
    R->>W: call_started event
    W->>Q: Queue event
    R->>C: "Guten Tag, Praxis Meyer"
    
    %% Conversation
    C->>R: "Ich möchte einen Termin"
    R->>R: Intent recognition
    R->>C: "Gerne! Für welche Behandlung?"
    C->>R: "Zahnreinigung"
    R->>C: "Wann passt es Ihnen?"
    C->>R: "Nächste Woche Dienstag"
    R->>R: Slot extraction
    R->>C: "Vormittags oder nachmittags?"
    C->>R: "Vormittags bitte"
    R->>C: "Ihr Name bitte?"
    C->>R: "Schmidt"
    
    %% Call End & Processing
    R->>W: call_ended event
    Note over W: Signature verification
    W->>Q: ProcessRetellCallEndedJob
    Q->>P: Execute job
    
    %% Data Resolution
    P->>PNR: Resolve phone to branch
    PNR->>DB: Lookup phone mapping
    DB-->>PNR: Branch found
    PNR-->>P: Branch details
    
    P->>CS: Find/create customer
    CS->>DB: Search by phone
    DB-->>CS: Customer exists/created
    CS-->>P: Customer object
    
    %% Availability Check
    P->>CAL: Check availability
    Note over CAL: Date: Next Tuesday<br/>Time: Morning<br/>Service: Cleaning
    CAL->>CAL: Query calendar
    CAL-->>P: Available slots
    
    %% Booking Creation
    P->>CAL: Create booking
    CAL-->>P: Booking confirmed
    P->>DB: Save appointment
    P->>Q: Queue email job
    
    %% Confirmation
    R->>C: "Termin bestätigt für Dienstag 9:30"
    Q->>E: Send confirmation
    E->>C: Email confirmation
```

### Call State Machine
```mermaid
stateDiagram-v2
    [*] --> Incoming: Call received
    
    Incoming --> Connected: Agent answers
    Connected --> Greeting: Play greeting
    Greeting --> Listening: Wait for input
    
    Listening --> Processing: Customer speaks
    Processing --> Responding: AI responds
    Responding --> Listening: Continue dialog
    
    Processing --> IntentDetected: Appointment request
    IntentDetected --> CollectingInfo: Gather details
    
    CollectingInfo --> CollectingService: What service?
    CollectingService --> CollectingDate: When?
    CollectingDate --> CollectingTime: What time?
    CollectingTime --> CollectingName: Your name?
    CollectingName --> Confirming: All info collected
    
    Confirming --> BookingSlot: Check availability
    BookingSlot --> Booked: Slot available
    BookingSlot --> Alternative: Slot unavailable
    Alternative --> CollectingDate: Suggest other time
    
    Booked --> EndCall: Confirm & goodbye
    EndCall --> [*]: Call completed
    
    Listening --> EndCall: Customer hangs up
    Connected --> EndCall: Error/timeout
```

## Data Extraction & Processing

### NLP Processing Pipeline
```mermaid
graph TD
    subgraph "Raw Input"
        AUDIO[🎤 Audio Stream]
        TRANS[📝 Transcript]
    end
    
    subgraph "Retell.ai Processing"
        STT[Speech to Text]
        NLP[NLP Engine]
        INTENT[Intent Classification]
        ENTITY[Entity Extraction]
    end
    
    subgraph "Extracted Entities"
        NAME[👤 Name: Schmidt]
        SERVICE[🦷 Service: Zahnreinigung]
        DATE[📅 Date: Next Tuesday]
        TIME[⏰ Time: Morning]
        PHONE[📞 Phone: +49301234567]
    end
    
    subgraph "Structured Output"
        JSON[{<br/>"name": "Schmidt",<br/>"service": "cleaning",<br/>"date": "2024-01-23",<br/>"time": "morning",<br/>"phone": "+49301234567"<br/>}]
    end
    
    AUDIO --> STT
    STT --> TRANS
    TRANS --> NLP
    NLP --> INTENT
    NLP --> ENTITY
    
    ENTITY --> NAME
    ENTITY --> SERVICE
    ENTITY --> DATE
    ENTITY --> TIME
    ENTITY --> PHONE
    
    NAME --> JSON
    SERVICE --> JSON
    DATE --> JSON
    TIME --> JSON
    PHONE --> JSON
```

### Webhook Processing Detail
```mermaid
sequenceDiagram
    participant R as Retell.ai
    participant N as Nginx
    participant M as Middleware
    participant C as Controller
    participant V as Validator
    participant Q as Queue
    participant J as Job
    
    R->>N: POST /api/retell/webhook
    Note over R: Headers:<br/>x-retell-signature: xxx<br/>Content-Type: application/json
    
    N->>M: Forward request
    M->>M: Extract signature
    M->>M: Compute HMAC
    M->>M: Compare signatures
    
    alt Invalid signature
        M-->>R: 401 Unauthorized
    else Valid signature
        M->>C: Pass to controller
        C->>V: Validate payload
        
        alt Invalid payload
            V-->>R: 422 Unprocessable
        else Valid payload
            C->>Q: Dispatch job
            Q-->>C: Job queued
            C-->>R: 200 OK
            
            Note over Q,J: Async processing
            Q->>J: Process job
            J->>J: Extract data
            J->>J: Create appointment
        end
    end
```

## Phone Number Resolution

### Branch Mapping Logic
```mermaid
graph TD
    subgraph "Input"
        PHONE[📞 +49 30 12345678]
    end
    
    subgraph "Resolution Process"
        NORM[Normalize Number]
        CHECK_EXACT[Check Exact Match]
        CHECK_BRANCH[Check Branch Default]
        CHECK_COMPANY[Check Company Default]
        FALLBACK[Use System Default]
    end
    
    subgraph "Database Lookups"
        DB_PHONE[(phone_numbers)]
        DB_BRANCH[(branches)]
        DB_COMPANY[(companies)]
    end
    
    subgraph "Result"
        BRANCH[🏢 Branch: Hauptpraxis]
        COMPANY[🏢 Company: Zahnarzt Meyer]
    end
    
    PHONE --> NORM
    NORM --> CHECK_EXACT
    CHECK_EXACT -->|Found| DB_PHONE
    DB_PHONE -->|branch_id| BRANCH
    
    CHECK_EXACT -->|Not Found| CHECK_BRANCH
    CHECK_BRANCH --> DB_BRANCH
    DB_BRANCH -->|default_number| CHECK_COMPANY
    
    CHECK_COMPANY --> DB_COMPANY
    DB_COMPANY -->|primary_branch| BRANCH
    
    CHECK_COMPANY -->|Not Found| FALLBACK
    FALLBACK --> BRANCH
    
    BRANCH --> COMPANY
```

### Multi-Tenant Call Routing
```
┌────────────────────────────────────────────────────────┐
│                   Incoming Calls                        │
└─────────┬──────────────┬──────────────┬────────────────┘
          │              │              │
    +49301234567   +49302345678   +49303456789
          │              │              │
┌─────────▼──────────────▼──────────────▼────────────────┐
│              Phone Number Resolution                    │
│                                                         │
│  1. Normalize number format                            │
│  2. Query phone_numbers table                          │
│  3. Get branch_id and company_id                      │
└─────────┬──────────────┬──────────────┬────────────────┘
          │              │              │
     Company A      Company B      Company C
     Branch 1       Branch 1       Branch 2
          │              │              │
┌─────────▼──────────────▼──────────────▼────────────────┐
│              Tenant Isolation                          │
│                                                         │
│  - Scope all queries by company_id                     │
│  - Separate data storage                               │
│  - Individual settings & preferences                   │
└─────────────────────────────────────────────────────────┘
```

## AI Agent Configuration

### Retell Agent Flow
```mermaid
graph LR
    subgraph "Agent Configuration"
        PROMPT[System Prompt]
        VOICE[Voice Settings]
        LANG[Language: DE]
        TOOLS[Function Tools]
    end
    
    subgraph "Conversation Flow"
        START[Start Call]
        GREET[Greeting]
        LISTEN[Listen]
        PROCESS[Process Input]
        RESPOND[Generate Response]
        ACTION[Execute Action]
    end
    
    subgraph "Available Actions"
        BOOK[Book Appointment]
        CHECK[Check Availability]
        CANCEL[Cancel Appointment]
        INFO[Provide Info]
    end
    
    PROMPT --> START
    VOICE --> START
    LANG --> START
    
    START --> GREET
    GREET --> LISTEN
    LISTEN --> PROCESS
    PROCESS --> RESPOND
    RESPOND --> LISTEN
    
    PROCESS -->|Intent: Booking| ACTION
    ACTION --> BOOK
    ACTION --> CHECK
    ACTION --> CANCEL
    ACTION --> INFO
    
    TOOLS -.-> BOOK
    TOOLS -.-> CHECK
```

### Dynamic Variable Extraction
```mermaid
flowchart TD
    subgraph "Conversation Context"
        CONV[Full Transcript]
        TURN[Current Turn]
        HISTORY[Dialog History]
    end
    
    subgraph "Extraction Rules"
        RULE1[Name Patterns]
        RULE2[Date Patterns]
        RULE3[Service Keywords]
        RULE4[Time Preferences]
    end
    
    subgraph "Extraction Process"
        APPLY[Apply Rules]
        VALIDATE[Validate Format]
        NORMALIZE[Normalize Values]
        MERGE[Merge Results]
    end
    
    subgraph "Output Variables"
        VAR1[customer_name]
        VAR2[service_type]
        VAR3[preferred_date]
        VAR4[preferred_time]
        VAR5[phone_number]
    end
    
    CONV --> APPLY
    TURN --> APPLY
    HISTORY --> APPLY
    
    RULE1 --> APPLY
    RULE2 --> APPLY
    RULE3 --> APPLY
    RULE4 --> APPLY
    
    APPLY --> VALIDATE
    VALIDATE --> NORMALIZE
    NORMALIZE --> MERGE
    
    MERGE --> VAR1
    MERGE --> VAR2
    MERGE --> VAR3
    MERGE --> VAR4
    MERGE --> VAR5
```

## Error Handling & Recovery

### Call Error States
```mermaid
graph TD
    subgraph "Error Types"
        E1[Network Error]
        E2[AI Timeout]
        E3[No Available Slots]
        E4[Customer Hung Up]
        E5[Invalid Input]
    end
    
    subgraph "Recovery Actions"
        R1[Retry Connection]
        R2[Fallback Response]
        R3[Suggest Alternative]
        R4[Save Partial Data]
        R5[Ask for Clarification]
    end
    
    subgraph "Fallback Flow"
        F1[Transfer to Human]
        F2[Schedule Callback]
        F3[Send SMS Link]
        F4[Email Follow-up]
    end
    
    E1 --> R1
    R1 -->|Fails| F1
    
    E2 --> R2
    R2 --> F2
    
    E3 --> R3
    R3 -->|No alternatives| F3
    
    E4 --> R4
    R4 --> F4
    
    E5 --> R5
    R5 -->|Still unclear| F1
```

### Webhook Retry Logic
```mermaid
sequenceDiagram
    participant R as Retell
    participant W as Webhook
    participant Q as Queue
    participant P as Processor
    participant DLQ as Dead Letter Queue
    
    R->>W: Send webhook
    W->>Q: Queue job
    
    loop Retry up to 3 times
        Q->>P: Process job
        alt Success
            P-->>Q: Mark complete
        else Failure
            P-->>Q: Mark failed
            Note over Q: Wait exponentially<br/>1s, 4s, 16s
            Q->>Q: Retry job
        end
    end
    
    alt All retries failed
        Q->>DLQ: Move to DLQ
        DLQ->>DLQ: Store for manual review
    end
```

## Performance Optimization

### Call Processing Timeline
```mermaid
gantt
    title Call Processing Performance (Target Times)
    dateFormat X
    axisFormat %Lms
    
    section Network
    Call routing     :done, routing, 0, 100
    SIP setup       :done, sip, 100, 200
    
    section AI Processing
    Agent load      :done, agent, 200, 50
    Greeting        :active, greet, 250, 300
    Conversation    :crit, conv, 550, 60000
    
    section Backend
    Webhook receive :done, webhook, 60550, 50
    Queue dispatch  :done, queue, 60600, 20
    Phone resolve   :active, phone, 60620, 30
    Customer lookup :active, customer, 60650, 50
    Calendar check  :active, calendar, 60700, 800
    Create booking  :active, booking, 61500, 200
    Send email      :done, email, 61700, 100
    
    section Targets
    Total target    :milestone, 0, 0
```

### Optimization Points
```
┌─────────────────────────────────────────────────────────────┐
│                    Optimization Checklist                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ □ Phone Resolution Cache                                    │
│   - Cache phone→branch mapping (5 min TTL)                 │
│   - Reduces DB queries by 80%                              │
│                                                             │
│ □ Customer Lookup Index                                    │
│   - Index on (company_id, phone)                          │
│   - Query time: 200ms → 5ms                               │
│                                                             │
│ □ Calendar Prefetch                                        │
│   - Prefetch common slots during call                      │
│   - Parallel processing saves 500ms                        │
│                                                             │
│ □ Webhook Async Processing                                  │
│   - Return 200 immediately                                 │
│   - Process in background queue                            │
│                                                             │
│ □ Connection Pooling                                        │
│   - Reuse Cal.com connections                             │
│   - Saves 100ms per request                               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

> 📝 **Note**: Times and metrics shown are targets. Actual performance may vary based on load and infrastructure.