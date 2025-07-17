# 📊 AskProAI Visual Documentation Guide

> **Quick Navigation**: [Architecture](#system-architecture) | [Call Flow](#call-flow) | [Payment](#payment-processing) | [Email](#email-system) | [Deployment](#deployment-pipeline) | [Database](#database-schema) | [Network](#network-topology) | [Auth](#authentication-flow)

## 🎯 Overview

This guide contains visual diagrams for understanding AskProAI's system architecture and data flows. Each diagram is available in:
- **Mermaid format** - For web viewing and documentation
- **ASCII art** - For terminal and quick reference
- **PlantUML** - For high-quality exports

## 📐 Diagram Types & When to Use

| Diagram Type | Use Case | Best For |
|-------------|----------|----------|
| **Flowchart** | Process flows, decision trees | Call routing, booking logic |
| **Sequence** | Time-based interactions | API calls, webhooks |
| **Architecture** | System components | Infrastructure overview |
| **ER Diagram** | Database relationships | Schema documentation |
| **State** | Status transitions | Appointment lifecycle |
| **Gantt** | Timeline & scheduling | Deployment planning |

---

## 🏗️ System Architecture Overview

### Mermaid Diagram
```mermaid
graph TB
    subgraph "External Services"
        PHONE[☎️ Phone Network<br/>Telekom/Vodafone]
        RETELL[🤖 Retell.ai<br/>AI Voice Service]
        CALCOM[📅 Cal.com<br/>Calendar API]
        STRIPE[💳 Stripe<br/>Payment Processing]
        SMTP[📧 SMTP<br/>Email Service]
    end
    
    subgraph "Frontend Layer"
        ADMIN[👨‍💼 Admin Panel<br/>Filament 3.x]
        API[🔌 REST API<br/>Laravel API]
        WEBHOOK[🪝 Webhooks<br/>Endpoints]
    end
    
    subgraph "Application Layer"
        LARAVEL[🚀 Laravel 11<br/>Core Application]
        HORIZON[⚡ Horizon<br/>Queue Manager]
        CACHE[💾 Redis<br/>Cache & Sessions]
    end
    
    subgraph "Data Layer"
        DB[(🗄️ MariaDB<br/>Primary Database)]
        BACKUP[(💿 Backup<br/>Daily Snapshots)]
    end
    
    PHONE --> RETELL
    RETELL --> WEBHOOK
    WEBHOOK --> LARAVEL
    LARAVEL --> HORIZON
    LARAVEL --> DB
    LARAVEL --> CACHE
    LARAVEL --> CALCOM
    LARAVEL --> STRIPE
    LARAVEL --> SMTP
    ADMIN --> LARAVEL
    API --> LARAVEL
    DB --> BACKUP
    
    style LARAVEL fill:#e3f2fd
    style DB fill:#fff3e0
    style RETELL fill:#e8f5e9
```

### ASCII Art Version
```
┌─────────────────────────────────────────────────────────────────┐
│                        EXTERNAL SERVICES                         │
├─────────────┬──────────────┬──────────────┬───────────┬────────┤
│   Phone     │  Retell.ai   │   Cal.com    │  Stripe   │  SMTP  │
│  Network    │ Voice AI     │  Calendar    │ Payment   │ Email  │
└──────┬──────┴──────┬───────┴──────┬───────┴─────┬─────┴───┬────┘
       │             │               │              │         │
       └─────────────┘               │              │         │
                │                    │              │         │
┌───────────────▼────────────────────▼──────────────▼─────────▼───┐
│                         FRONTEND LAYER                           │
├─────────────────┬─────────────────┬─────────────────────────────┤
│  Admin Panel    │   REST API      │    Webhook Endpoints        │
│  (Filament)     │   (Laravel)     │    (Signature Verified)     │
└────────┬────────┴────────┬────────┴────────┬────────────────────┘
         │                 │                 │
         └─────────────────┴─────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                     APPLICATION LAYER                            │
├─────────────────┬─────────────────┬─────────────────────────────┤
│   Laravel 11    │    Horizon      │      Redis Cache            │
│   Core App      │  Queue Manager  │   Sessions & Cache          │
└────────┬────────┴────────┬────────┴────────┬────────────────────┘
         │                 │                 │
         └─────────────────┴─────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                        DATA LAYER                                │
├─────────────────────────┬───────────────────────────────────────┤
│      MariaDB           │           Daily Backups                │
│   Primary Database     │         (Encrypted)                    │
└────────────────────────┴───────────────────────────────────────┘
```

---

## 📞 Call Flow (Phone to Appointment)

### Detailed Sequence Diagram
```mermaid
sequenceDiagram
    participant C as Customer
    participant P as Phone Network
    participant R as Retell.ai
    participant W as Webhook
    participant Q as Queue
    participant A as AskProAI
    participant CA as Cal.com
    participant D as Database
    participant E as Email Service
    
    C->>P: Dials business number
    P->>R: Route call via SIP
    R->>R: Load AI Agent & Greeting
    R->>C: "Hello, how can I help?"
    
    C->>R: "I need an appointment"
    R->>R: NLP Processing
    R->>C: "What service do you need?"
    C->>R: "Dental cleaning"
    R->>C: "When would you prefer?"
    C->>R: "Next Tuesday morning"
    
    R->>W: POST /api/retell/webhook
    Note over W: Verify signature
    W->>Q: Queue ProcessCallJob
    W-->>R: 200 OK
    
    Q->>A: Process Call Data
    A->>D: Lookup/Create Customer
    A->>D: Get Branch Info
    A->>CA: Check availability
    CA-->>A: Available slots
    
    A->>CA: Create booking
    CA-->>A: Booking confirmed
    A->>D: Save appointment
    A->>E: Queue confirmation email
    
    E->>C: Email confirmation
    R->>C: "Appointment confirmed!"
```

### ASCII Flow Diagram
```
Customer                     System Flow                      Services
────────                     ───────────                      ────────
   │                                                          
   ├─[1]─> Dial Number ──────> Phone Network
   │                               │
   │                               ├─[2]─> Retell.ai
   │                               │          │
   │ <─────[3]─ AI Greeting <──────┘          │
   │                                          │
   ├─[4]─> "Need appointment" ───────────────>│
   │                                          │
   │ <─────[5]─ "What service?" <─────────────┤
   │                                          │
   ├─[6]─> "Dental cleaning" ────────────────>│
   │                                          │
   │ <─────[7]─ "When?" <─────────────────────┤
   │                                          │
   ├─[8]─> "Tuesday morning" ────────────────>│
   │                                          │
   │                                          ├─[9]─> Webhook
   │                                          │        │
   │                                          │        ├─[10]─> Queue
   │                                          │        │         │
   │                                          │        │    ├─[11]─> Process
   │                                          │        │    │        │
   │                                          │        │    │    ├─> Cal.com
   │                                          │        │    │    ├─> Database
   │                                          │        │    │    └─> Email
   │                                          │        │    │
   │ <─────[12]─ "Confirmed!" <───────────────┴────────┴────┘
   │
   └─[13]─> 📧 Receives Email
```

---

## 💳 Payment Processing Flow

### Stripe Integration Flow
```mermaid
graph TD
    subgraph "Payment Initiation"
        U[User] -->|Clicks Pay| UI[Payment UI]
        UI -->|Creates Session| API[API Endpoint]
    end
    
    subgraph "Stripe Processing"
        API -->|Create Checkout| STRIPE[Stripe API]
        STRIPE -->|Session URL| API
        API -->|Redirect| U
        U -->|Enters Card| STRIPE
        STRIPE -->|3D Secure?| CHECK{Verification}
        CHECK -->|Yes| VERIFY[Bank Verification]
        CHECK -->|No| PROCESS[Process Payment]
        VERIFY --> PROCESS
    end
    
    subgraph "Webhook Processing"
        PROCESS -->|payment.success| WEBHOOK[Webhook Endpoint]
        WEBHOOK -->|Verify Signature| SIG{Valid?}
        SIG -->|No| REJECT[Reject]
        SIG -->|Yes| QUEUE[Queue Job]
    end
    
    subgraph "Database Updates"
        QUEUE -->|ProcessPayment| JOB[Payment Job]
        JOB -->|Update| BALANCE[Prepaid Balance]
        JOB -->|Create| TOPUP[Balance Topup]
        JOB -->|Log| TRANS[Transaction]
        JOB -->|Email| RECEIPT[Send Receipt]
    end
    
    style STRIPE fill:#7c4dff
    style BALANCE fill:#4caf50
    style REJECT fill:#f44336
```

### Payment States
```mermaid
stateDiagram-v2
    [*] --> Initiated: User starts payment
    Initiated --> Processing: Stripe checkout
    Processing --> Verifying: 3D Secure required
    Processing --> Captured: Direct capture
    Verifying --> Captured: Verification success
    Verifying --> Failed: Verification failed
    Captured --> Completed: Webhook received
    Completed --> Applied: Balance updated
    Applied --> [*]: Receipt sent
    Failed --> [*]: Notify user
    
    note right of Verifying
        3D Secure adds
        10-30 seconds
    end note
    
    note right of Completed
        Webhook usually
        within 2 seconds
    end note
```

---

## 📧 Email System Flow

### Email Processing Pipeline
```mermaid
graph LR
    subgraph "Triggers"
        T1[Appointment Created]
        T2[Call Ended]
        T3[Payment Received]
        T4[Reminder Due]
    end
    
    subgraph "Email Jobs"
        T1 --> J1[SendConfirmation]
        T2 --> J2[SendCallSummary]
        T3 --> J3[SendReceipt]
        T4 --> J4[SendReminder]
    end
    
    subgraph "Queue Processing"
        J1 --> Q[Laravel Queue]
        J2 --> Q
        J3 --> Q
        J4 --> Q
        Q --> H[Horizon Worker]
    end
    
    subgraph "Email Service"
        H --> SMTP[SMTP Server]
        SMTP --> D{Delivered?}
        D -->|Yes| LOG[Log Success]
        D -->|No| RETRY[Retry Queue]
        RETRY -->|Max 3| FAIL[Failed Jobs]
    end
```

### Email Template Structure
```
┌─────────────────────────────────────────────┐
│            EMAIL TEMPLATE SYSTEM            │
├─────────────────────────────────────────────┤
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │         Base Template                │   │
│  │  - Header (Logo, Company)           │   │
│  │  - Content Block                    │   │
│  │  - Footer (Unsubscribe, Contact)    │   │
│  └──────────────┬──────────────────────┘   │
│                 │                           │
│    ┌────────────┼────────────┐             │
│    │            │            │             │
│  ┌─▼──────┐ ┌──▼──────┐ ┌──▼──────┐      │
│  │Confirm │ │Summary  │ │Receipt  │      │
│  │Template│ │Template │ │Template │      │
│  └────────┘ └─────────┘ └─────────┘      │
│                                             │
│  Variables:                                 │
│  {{ customer_name }}                        │
│  {{ appointment_date }}                     │
│  {{ branch_address }}                       │
│  {{ call_transcript }}                      │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 🚀 Deployment Pipeline

### CI/CD Flow
```mermaid
graph TD
    subgraph "Development"
        DEV[Developer] -->|git push| GIT[GitHub]
    end
    
    subgraph "CI Pipeline"
        GIT -->|Webhook| CI[GitHub Actions]
        CI -->|1. Lint| LINT[PHP CS Fixer]
        CI -->|2. Test| TEST[PHPUnit]
        CI -->|3. Build| BUILD[NPM Build]
        CI -->|4. Security| SEC[Audit]
    end
    
    subgraph "Deployment"
        SEC -->|All Pass| DEPLOY{Deploy?}
        DEPLOY -->|main branch| PROD[Production]
        DEPLOY -->|develop| STAGE[Staging]
        
        PROD -->|1| BACKUP[Backup DB]
        BACKUP -->|2| PULL[Git Pull]
        PULL -->|3| DEPS[Install Deps]
        DEPS -->|4| MIGRATE[Run Migrations]
        MIGRATE -->|5| CACHE[Build Cache]
        CACHE -->|6| RESTART[Restart Services]
    end
    
    subgraph "Validation"
        RESTART -->|Health Check| HEALTH{OK?}
        HEALTH -->|Yes| COMPLETE[✅ Done]
        HEALTH -->|No| ROLLBACK[🔄 Rollback]
    end
```

### Deployment Timeline
```
Time  Action                      Duration   Status
────  ──────                      ────────   ──────
0:00  Start deployment           -          🟡
0:00  Create backup              0:30       🟢
0:30  Pull latest code           0:10       🟢
0:40  Install dependencies       1:00       🟢
1:40  Build frontend assets      0:30       🟢
2:10  Run database migrations    0:20       🟢
2:30  Clear & rebuild caches     0:10       🟢
2:40  Restart PHP-FPM           0:05       🟢
2:45  Restart Horizon           0:05       🟢
2:50  Health checks             0:20       🟢
3:10  Deployment complete        -          ✅

Total deployment time: ~3 minutes
Zero downtime achieved ✓
```

---

## 🗄️ Database Schema

### Core Entity Relationships
```mermaid
erDiagram
    COMPANY ||--o{ BRANCH : has
    COMPANY ||--o{ CUSTOMER : has
    COMPANY ||--o{ PREPAID_BALANCE : has
    
    BRANCH ||--o{ PHONE_NUMBER : has
    BRANCH ||--o{ WORKING_HOUR : has
    BRANCH ||--o{ STAFF : employs
    BRANCH ||--o{ SERVICE : offers
    
    STAFF ||--o{ STAFF_EVENT_TYPE : assigned
    STAFF_EVENT_TYPE }o--|| CALCOM_EVENT_TYPE : maps
    
    CUSTOMER ||--o{ APPOINTMENT : books
    APPOINTMENT }o--|| STAFF : with
    APPOINTMENT }o--|| SERVICE : for
    APPOINTMENT }o--|| BRANCH : at
    
    CALL ||--|| CUSTOMER : from
    CALL ||--o| APPOINTMENT : creates
    CALL }o--|| BRANCH : to
    
    PREPAID_BALANCE ||--o{ BALANCE_TOPUP : has
    PREPAID_BALANCE ||--o{ CALL_CHARGE : deducts
    
    COMPANY {
        int id PK
        string name
        string tenant_id UK
        json settings
        datetime created_at
    }
    
    BRANCH {
        int id PK
        int company_id FK
        string name
        string address
        int calcom_event_type_id
    }
    
    CUSTOMER {
        int id PK
        int company_id FK
        string phone UK
        string name
        string email
        int call_count
    }
    
    APPOINTMENT {
        int id PK
        int company_id FK
        int branch_id FK
        int customer_id FK
        int staff_id FK
        datetime start_time
        datetime end_time
        string status
        int calcom_booking_id
    }
```

### Database Indexes
```sql
-- Performance Critical Indexes
CREATE INDEX idx_company_tenant ON companies(tenant_id);
CREATE INDEX idx_appointments_composite ON appointments(company_id, branch_id, start_time);
CREATE INDEX idx_customers_phone ON customers(company_id, phone);
CREATE INDEX idx_calls_created ON calls(company_id, created_at);

-- Foreign Key Indexes (Auto-created)
-- All foreign key columns automatically indexed
```

---

## 🌐 Network Topology

### Infrastructure Layout
```
┌─────────────────────────────────────────────────────────────┐
│                        INTERNET                              │
└────────────────┬─────────────────────┬──────────────────────┘
                 │                     │
        ┌────────▼────────┐   ┌───────▼────────┐
        │   Cloudflare    │   │   Retell.ai    │
        │   CDN & WAF     │   │  Phone System  │
        │ *.askproai.de   │   │                │
        └────────┬────────┘   └────────────────┘
                 │
        ┌────────▼────────────────────────────┐
        │         Load Balancer               │
        │    api.askproai.de:443             │
        └────────┬────────────────────────────┘
                 │
        ┌────────▼────────────────────────────┐
        │      Nginx Reverse Proxy            │
        │   - SSL Termination                 │
        │   - Rate Limiting                   │
        │   - Static File Serving             │
        └────────┬────────────────────────────┘
                 │
     ┌───────────┴───────────┬─────────────────┐
     │                       │                 │
┌────▼──────┐      ┌─────────▼──────┐   ┌─────▼──────┐
│  PHP-FPM  │      │    Laravel     │   │   Redis    │
│  Workers  │◄────►│  Application   │◄─►│   Cache    │
│  Pool=50  │      │                │   │  Sessions  │
└───────────┘      └────────┬───────┘   └────────────┘
                            │
                   ┌────────▼───────┐
                   │    MariaDB     │
                   │  Primary DB    │
                   │  Max Conn=200  │
                   └────────────────┘
```

### Port Mapping
```
Service         Internal    External    Protocol
─────────       ────────    ────────    ────────
Nginx           80          -           HTTP
Nginx           443         443         HTTPS
PHP-FPM         9000        -           FastCGI
MariaDB         3306        -           MySQL
Redis           6379        -           Redis
Horizon         -           -           Internal
```

---

## 🔐 Authentication Flow

### Multi-Layer Auth System
```mermaid
sequenceDiagram
    participant U as User
    participant B as Browser
    participant N as Nginx
    participant L as Laravel
    participant S as Session
    participant D as Database
    
    U->>B: Enter credentials
    B->>N: POST /login
    N->>L: Forward request
    
    L->>L: Validate CSRF token
    L->>D: Check credentials
    D-->>L: User found
    
    L->>L: Hash password check
    L->>S: Create session
    S->>B: Set cookie
    
    B->>N: GET /admin
    N->>L: Forward + cookie
    L->>S: Validate session
    S-->>L: Session valid
    L->>L: Check permissions
    L->>B: Return admin panel
    
    Note over L,S: Session timeout: 120 min
    Note over B,S: Remember me: 30 days
```

### Permission Matrix
```
Role            Dashboard   Appointments   Customers   Settings   API
────            ─────────   ────────────   ─────────   ────────   ───
Super Admin     Full        Full           Full        Full       Full
Company Admin   Full        Full           Full        Full       Limited
Branch Manager  View        Full           Full        Limited    None
Staff           View        Own Only       View        None       None
API User        None        None           None        None       Full
```

---

## 🛠️ Maintenance & Tools

### Diagram Maintenance Workflow
```mermaid
graph LR
    A[Update Code] -->|Document| B[Update Diagrams]
    B -->|Mermaid| C[Update .md files]
    B -->|ASCII| D[Update terminal docs]
    C --> E[Preview in VS Code]
    D --> F[Test in terminal]
    E --> G{Correct?}
    F --> G
    G -->|No| B
    G -->|Yes| H[Commit changes]
    H --> I[Tag version]
```

### Tools for Diagram Creation

| Tool | Purpose | Installation |
|------|---------|--------------|
| **Mermaid Live Editor** | Online diagram editor | [mermaid.live](https://mermaid.live) |
| **VS Code Mermaid Preview** | Local preview | Install "Markdown Preview Mermaid Support" |
| **PlantUML** | Professional diagrams | `brew install plantuml` |
| **ASCII Flow** | ASCII diagrams | [asciiflow.com](https://asciiflow.com) |
| **draw.io** | Complex diagrams | [app.diagrams.net](https://app.diagrams.net) |

### Best Practices
1. **Keep diagrams simple** - Focus on clarity over complexity
2. **Version control** - Always commit diagram source files
3. **Use consistent style** - Same colors, shapes, and notation
4. **Update regularly** - Diagrams should match current code
5. **Include legends** - Explain symbols and abbreviations

---

## 📚 Quick Reference Card

```
┌─────────────────────────────────────────────────────┐
│              ASKPROAI QUICK REFERENCE               │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Key Flows:                                         │
│  1. Phone → Retell → Webhook → Queue → Appointment │
│  2. Payment → Stripe → Webhook → Balance Update    │
│  3. Deploy → Test → Migrate → Cache → Restart      │
│                                                     │
│  Critical Endpoints:                                │
│  • /api/retell/webhook-simple (Call processing)    │
│  • /api/stripe/webhook (Payment processing)        │
│  • /health (System health check)                   │
│                                                     │
│  Key Services:                                      │
│  • ProcessRetellCallEndedJob (Call → Appointment)  │
│  • ProcessStripeWebhookJob (Payment processing)    │
│  • SendAppointmentConfirmation (Email service)     │
│                                                     │
│  Database Tables:                                   │
│  • companies (Tenant data)                         │
│  • appointments (Core business data)               │
│  • calls (Phone interaction logs)                  │
│  • prepaid_balances (Account credits)              │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 🔄 Update History

| Date | Changes | Author |
|------|---------|--------|
| 2025-07-10 | Initial creation | Claude AI |
| - | Added all core diagrams | - |
| - | Added ASCII alternatives | - |

> 💡 **Tip**: Use VS Code with Mermaid preview extension for best editing experience