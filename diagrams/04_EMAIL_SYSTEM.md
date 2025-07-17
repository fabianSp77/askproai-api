# 📧 Email System Flow Diagrams

## Email System Architecture

### Complete Email Pipeline
```mermaid
graph TD
    subgraph "Email Triggers"
        T1[Appointment Confirmed]
        T2[Call Ended]
        T3[Payment Received]
        T4[Account Created]
        T5[Reminder Due]
        T6[Low Balance]
    end
    
    subgraph "Email Jobs"
        J1[SendAppointmentConfirmation]
        J2[SendCallSummary]
        J3[SendPaymentReceipt]
        J4[SendWelcomeEmail]
        J5[SendAppointmentReminder]
        J6[SendLowBalanceAlert]
    end
    
    subgraph "Queue System"
        Q1[High Priority Queue]
        Q2[Default Queue]
        Q3[Low Priority Queue]
        HORIZON[Horizon Workers]
    end
    
    subgraph "Email Service"
        BUILDER[Email Builder]
        TEMPLATE[Template Engine]
        SENDER[Mail Sender]
        SMTP[SMTP Server]
    end
    
    subgraph "Delivery"
        INBOX[Customer Inbox]
        TRACKING[Delivery Tracking]
        BOUNCE[Bounce Handler]
    end
    
    T1 --> J1
    T2 --> J2
    T3 --> J3
    T4 --> J4
    T5 --> J5
    T6 --> J6
    
    J1 --> Q1
    J2 --> Q2
    J3 --> Q1
    J4 --> Q2
    J5 --> Q3
    J6 --> Q1
    
    Q1 --> HORIZON
    Q2 --> HORIZON
    Q3 --> HORIZON
    
    HORIZON --> BUILDER
    BUILDER --> TEMPLATE
    TEMPLATE --> SENDER
    SENDER --> SMTP
    SMTP --> INBOX
    SMTP --> TRACKING
    SMTP --> BOUNCE
```

### Email Processing Sequence
```mermaid
sequenceDiagram
    participant E as Event
    participant Q as Queue
    participant W as Worker
    participant B as Builder
    participant T as Template
    participant M as Mailer
    participant S as SMTP
    participant R as Recipient
    
    E->>Q: Dispatch email job
    Note over Q: Job serialized to Redis
    
    Q->>W: Worker picks up job
    W->>B: Build email data
    B->>B: Gather variables
    B->>B: Load translations
    
    B->>T: Render template
    T->>T: Process Blade syntax
    T->>T: Apply layout
    T-->>B: HTML + Text content
    
    B->>M: Create message
    M->>M: Set headers
    M->>M: Attach files
    M->>M: Sign with DKIM
    
    M->>S: Send via SMTP
    S->>S: Queue for delivery
    S->>R: Deliver email
    
    alt Delivery successful
        S-->>M: 250 OK
        M-->>W: Mark complete
    else Delivery failed
        S-->>M: Error code
        M-->>W: Retry or fail
    end
```

## Email Templates

### Template Hierarchy
```
┌─────────────────────────────────────────────────────────────┐
│                    Master Layout                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Header                                              │   │
│  │  - Logo                                              │   │
│  │  - Company Name                                      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Content Block                                       │   │
│  │  @yield('content')                                   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Footer                                              │   │
│  │  - Contact Info                                      │   │
│  │  - Unsubscribe Link                                  │   │
│  │  - Legal Notice                                      │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
┌───────▼──────┐    ┌────────▼──────┐    ┌───────▼──────┐
│ Appointment  │    │ Call Summary  │    │   Payment    │
│ Confirmation │    │    Email      │    │   Receipt    │
└──────────────┘    └───────────────┘    └──────────────┘
```

### Template Data Flow
```mermaid
graph LR
    subgraph "Data Sources"
        APP[Appointment Data]
        CUST[Customer Info]
        BRANCH[Branch Details]
        TRANS[Transaction Data]
    end
    
    subgraph "Data Processing"
        MERGE[Merge Variables]
        TRANS_KEY[Translate Keys]
        FORMAT[Format Values]
        ESCAPE[Escape HTML]
    end
    
    subgraph "Template Variables"
        V1[{{customer_name}}]
        V2[{{appointment_date}}]
        V3[{{branch_address}}]
        V4[{{amount}}]
    end
    
    subgraph "Output"
        HTML[HTML Email]
        TEXT[Text Email]
    end
    
    APP --> MERGE
    CUST --> MERGE
    BRANCH --> MERGE
    TRANS --> MERGE
    
    MERGE --> TRANS_KEY
    TRANS_KEY --> FORMAT
    FORMAT --> ESCAPE
    
    ESCAPE --> V1
    ESCAPE --> V2
    ESCAPE --> V3
    ESCAPE --> V4
    
    V1 --> HTML
    V2 --> HTML
    V3 --> HTML
    V4 --> HTML
    
    V1 --> TEXT
    V2 --> TEXT
```

## Email Types & Priorities

### Email Priority Matrix
```mermaid
graph TD
    subgraph "High Priority - Immediate"
        H1[Payment Confirmations]
        H2[Password Resets]
        H3[Security Alerts]
        H4[Appointment Confirmations]
    end
    
    subgraph "Medium Priority - Within 5 min"
        M1[Call Summaries]
        M2[Welcome Emails]
        M3[Account Updates]
    end
    
    subgraph "Low Priority - Within 1 hour"
        L1[Newsletters]
        L2[Promotional]
        L3[Surveys]
        L4[Reports]
    end
    
    subgraph "Queue Assignment"
        HIGH[emails-high]
        MED[emails]
        LOW[emails-low]
    end
    
    H1 --> HIGH
    H2 --> HIGH
    H3 --> HIGH
    H4 --> HIGH
    
    M1 --> MED
    M2 --> MED
    M3 --> MED
    
    L1 --> LOW
    L2 --> LOW
    L3 --> LOW
    L4 --> LOW
```

### Email Lifecycle States
```mermaid
stateDiagram-v2
    [*] --> Triggered: Event occurs
    
    Triggered --> Queued: Job created
    Queued --> Processing: Worker picks up
    
    Processing --> Building: Prepare content
    Building --> Rendering: Apply template
    Rendering --> Sending: SMTP transmission
    
    Sending --> Sent: Delivery success
    Sending --> Retrying: Temporary failure
    Sending --> Failed: Permanent failure
    
    Retrying --> Sending: Retry attempt
    Retrying --> Failed: Max retries reached
    
    Sent --> Delivered: Recipient server accepts
    Sent --> Bounced: Recipient rejects
    
    Delivered --> Opened: User opens
    Opened --> Clicked: User clicks link
    
    Failed --> [*]: Log failure
    Bounced --> [*]: Update status
    Clicked --> [*]: Track engagement
```

## Call Summary Email

### Call Summary Generation
```mermaid
sequenceDiagram
    participant C as Call Ends
    participant P as Processor
    participant D as Data Gatherer
    participant T as Transcript Processor
    participant S as Summarizer
    participant E as Email Builder
    participant Q as Queue
    
    C->>P: Trigger summary
    P->>D: Gather call data
    
    D->>D: Get call details
    D->>D: Get customer info
    D->>D: Get appointment created
    D->>D: Get branch info
    
    D->>T: Process transcript
    T->>T: Clean transcript
    T->>T: Extract key points
    T->>S: Generate summary
    
    S->>S: Identify main topics
    S->>S: Extract action items
    S->>S: Format summary
    
    S->>E: Build email
    E->>E: Apply template
    E->>E: Add call recording link
    E->>E: Add appointment details
    
    E->>Q: Queue for sending
```

### Call Summary Template Structure
```
┌─────────────────────────────────────────────────────────────┐
│                   Call Summary Email                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Hello {{customer_name}},                                    │
│                                                             │
│ Thank you for calling {{company_name}} today.               │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐    │
│ │ Call Details:                                       │    │
│ │ • Date: {{call_date}}                              │    │
│ │ • Duration: {{duration}} minutes                    │    │
│ │ • Agent: AI Assistant                              │    │
│ └─────────────────────────────────────────────────────┘    │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐    │
│ │ Summary:                                            │    │
│ │ {{call_summary}}                                    │    │
│ └─────────────────────────────────────────────────────┘    │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐    │
│ │ Appointment Confirmed:                              │    │
│ │ • Service: {{service_name}}                         │    │
│ │ • Date: {{appointment_date}}                        │    │
│ │ • Time: {{appointment_time}}                        │    │
│ │ • Location: {{branch_address}}                      │    │
│ └─────────────────────────────────────────────────────┘    │
│                                                             │
│ [View Full Transcript] [Add to Calendar]                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Email Delivery & Tracking

### SMTP Flow
```mermaid
graph TD
    subgraph "Laravel Mail"
        MAIL[Mail Facade]
        DRIVER[SMTP Driver]
        TRANS[Transport Layer]
    end
    
    subgraph "SMTP Server"
        AUTH[Authentication]
        TLS[TLS Encryption]
        QUEUE[Server Queue]
        RELAY[Relay Service]
    end
    
    subgraph "Recipient Server"
        MX[MX Lookup]
        SPAM[Spam Filter]
        INBOX[User Inbox]
        FOLDER[Folder Rules]
    end
    
    subgraph "Tracking"
        OPEN[Open Tracking]
        CLICK[Click Tracking]
        BOUNCE[Bounce Processing]
        UNSUB[Unsubscribe]
    end
    
    MAIL --> DRIVER
    DRIVER --> TRANS
    TRANS --> AUTH
    AUTH --> TLS
    TLS --> QUEUE
    QUEUE --> RELAY
    
    RELAY --> MX
    MX --> SPAM
    SPAM -->|Pass| INBOX
    SPAM -->|Fail| FOLDER
    
    INBOX --> OPEN
    INBOX --> CLICK
    RELAY --> BOUNCE
    CLICK --> UNSUB
```

### Delivery Status Tracking
```
┌─────────────────────────────────────────────────────────────┐
│                  Email Delivery Timeline                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Email ID: MSG-2024-001234                                   │
│ To: customer@example.com                                    │
│ Subject: Appointment Confirmation                           │
│                                                             │
│ Timeline:                                                   │
│ ─────────                                                   │
│ 10:00:00 | TRIGGERED    | Appointment created              │
│ 10:00:01 | QUEUED       | Job ID: 5678                   │
│ 10:00:05 | PROCESSING   | Worker: horizon-1              │
│ 10:00:06 | RENDERED     | Template: appointment.blade    │
│ 10:00:07 | SENDING      | Via: smtp.udag.de             │
│ 10:00:08 | ACCEPTED     | Remote: 250 OK                │
│ 10:00:15 | DELIVERED    | To: recipient server          │
│ 10:05:32 | OPENED       | IP: 192.168.1.1              │
│ 10:05:45 | CLICKED      | Link: Add to calendar        │
│                                                             │
│ Status: ✅ Successfully delivered and engaged               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Error Handling

### Email Failure Recovery
```mermaid
graph TD
    subgraph "Failure Types"
        F1[SMTP Connection Failed]
        F2[Authentication Failed]
        F3[Recipient Not Found]
        F4[Mailbox Full]
        F5[Content Rejected]
    end
    
    subgraph "Recovery Actions"
        R1[Retry with backoff]
        R2[Check credentials]
        R3[Validate email]
        R4[Queue for later]
        R5[Review content]
    end
    
    subgraph "Fallback Options"
        FB1[Use backup SMTP]
        FB2[Send via API]
        FB3[SMS notification]
        FB4[Portal notification]
    end
    
    F1 --> R1
    R1 -->|Max retries| FB1
    
    F2 --> R2
    R2 -->|Still fails| FB2
    
    F3 --> R3
    R3 -->|Invalid| FB3
    
    F4 --> R4
    R4 -->|Timeout| FB4
    
    F5 --> R5
    R5 -->|Spam| FB4
```

### Retry Strategy
```mermaid
sequenceDiagram
    participant J as Job
    participant W as Worker
    participant M as Mailer
    participant L as Logger
    participant DLQ as Dead Letter Queue
    
    J->>W: Process email job
    W->>M: Attempt send
    
    loop Retry up to 3 times
        M->>M: Try sending
        alt Success
            M-->>W: Email sent
            W-->>J: Mark complete
        else Failure
            M-->>W: Error occurred
            W->>L: Log error
            Note over W: Wait time increases:<br/>1st: 10s<br/>2nd: 30s<br/>3rd: 90s
            W->>W: Wait and retry
        end
    end
    
    W->>DLQ: Move to DLQ
    DLQ->>L: Log permanent failure
    L->>L: Alert admin
```

## Email Optimization

### Performance Metrics
```
┌─────────────────────────────────────────────────────────────┐
│                  Email Performance Dashboard                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Daily Statistics (2024-01-15):                             │
│ ──────────────────────────────                             │
│                                                             │
│ Total Sent:        1,234        █████████████ 100%        │
│ Delivered:         1,198        ████████████░  97%        │
│ Opened:              876        █████████░░░░  71%        │
│ Clicked:             234        ███░░░░░░░░░░  19%        │
│ Bounced:              36        █░░░░░░░░░░░░   3%        │
│                                                             │
│ Average Times:                                              │
│ • Queue → Send:     2.3s                                   │
│ • Send → Deliver:   4.7s                                   │
│ • Deliver → Open:   35m                                    │
│                                                             │
│ By Type:                                                    │
│ • Confirmations:    456  ████████                         │
│ • Summaries:        321  ██████                           │
│ • Reminders:        234  ████                             │
│ • Receipts:         223  ████                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Batch Processing
```mermaid
graph LR
    subgraph "Individual Processing"
        I1[Email 1]
        I2[Email 2]
        I3[Email 3]
        SMTP1[SMTP×3]
    end
    
    subgraph "Batch Processing"
        B1[Collect Similar]
        B2[Single Connection]
        B3[Bulk Send]
        SMTP2[SMTP×1]
    end
    
    subgraph "Benefits"
        PERF[3x Faster]
        CONN[Fewer Connections]
        RATE[Better Deliverability]
    end
    
    I1 --> SMTP1
    I2 --> SMTP1
    I3 --> SMTP1
    
    I1 --> B1
    I2 --> B1
    I3 --> B1
    B1 --> B2
    B2 --> B3
    B3 --> SMTP2
    
    SMTP2 --> PERF
    SMTP2 --> CONN
    SMTP2 --> RATE
```

## Multi-Language Support

### Translation Flow
```mermaid
graph TD
    subgraph "Language Detection"
        PREF[User Preference]
        BROWSER[Browser Language]
        DEFAULT[Company Default]
    end
    
    subgraph "Translation Loading"
        LANG[Language File]
        CACHE[Translation Cache]
        FALLBACK[Fallback Language]
    end
    
    subgraph "Template Processing"
        KEY[Translation Keys]
        VALUE[Translated Values]
        REPLACE[Variable Replace]
    end
    
    subgraph "Available Languages"
        DE[🇩🇪 Deutsch]
        EN[🇬🇧 English]
        FR[🇫🇷 Français]
        ES[🇪🇸 Español]
    end
    
    PREF --> LANG
    BROWSER --> LANG
    DEFAULT --> LANG
    
    LANG --> CACHE
    CACHE --> KEY
    KEY --> VALUE
    VALUE --> REPLACE
    
    CACHE -->|Not found| FALLBACK
    FALLBACK --> VALUE
```

---

> 📝 **Note**: Email templates follow responsive design principles and are tested across major email clients.