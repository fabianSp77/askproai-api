# 💳 Payment Processing Flow Diagrams

## Stripe Integration Overview

### Complete Payment Flow
```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend
    participant A as API
    participant S as Stripe
    participant W as Webhook
    participant Q as Queue
    participant DB as Database
    participant E as Email
    
    %% Initiate Payment
    U->>F: Click "Add Credits"
    F->>F: Select amount (€50)
    F->>A: POST /api/topup
    A->>A: Validate user & amount
    
    %% Create Stripe Session
    A->>S: Create Checkout Session
    Note over S: Price: €50<br/>Mode: payment<br/>Success URL: /success<br/>Cancel URL: /cancel
    S-->>A: Session object + URL
    A-->>F: Redirect URL
    F->>U: Redirect to Stripe
    
    %% Stripe Checkout
    U->>S: Enter payment details
    S->>S: Validate card
    alt 3D Secure Required
        S->>U: 3D Secure challenge
        U->>S: Complete verification
    end
    S->>S: Process payment
    
    %% Success Flow
    S->>U: Redirect to success_url
    S->>W: POST /api/stripe/webhook
    Note over W: Event: checkout.session.completed
    W->>Q: Queue ProcessStripeWebhook
    
    %% Background Processing
    Q->>DB: Create BalanceTopup
    Q->>DB: Update PrepaidBalance
    Q->>E: Send receipt
    E->>U: Email receipt
```

### Payment State Machine
```mermaid
stateDiagram-v2
    [*] --> Initiated: User starts topup
    
    Initiated --> SessionCreated: Stripe session created
    SessionCreated --> Redirected: User redirected
    
    Redirected --> Processing: Card details entered
    Processing --> Authenticating: 3D Secure required
    Processing --> Captured: Direct capture
    
    Authenticating --> Captured: Auth success
    Authenticating --> Failed: Auth failed
    
    Captured --> WebhookPending: Payment success
    WebhookPending --> WebhookReceived: Webhook arrives
    
    WebhookReceived --> BalanceUpdating: Process topup
    BalanceUpdating --> Completed: Balance updated
    
    Completed --> [*]: Receipt sent
    Failed --> [*]: User notified
    
    Redirected --> Cancelled: User cancels
    Cancelled --> [*]: No charge
```

## Prepaid Balance System

### Balance Management Flow
```mermaid
graph TD
    subgraph "Balance Components"
        BALANCE[Prepaid Balance]
        TOPUPS[Balance Topups]
        CHARGES[Call Charges]
        BONUS[Bonus Credits]
    end
    
    subgraph "Balance Operations"
        ADD[Add Credits]
        DEDUCT[Deduct for Calls]
        REFUND[Refund Credits]
        TRANSFER[Transfer Between Branches]
    end
    
    subgraph "Balance Tracking"
        CURRENT[Current Balance: €125.50]
        RESERVED[Reserved: €5.00]
        AVAILABLE[Available: €120.50]
    end
    
    subgraph "Notifications"
        LOW[Low Balance Alert]
        ZERO[Zero Balance Alert]
        TOPUP_REMIND[Topup Reminder]
    end
    
    TOPUPS --> ADD
    ADD --> BALANCE
    BALANCE --> DEDUCT
    DEDUCT --> CHARGES
    
    BALANCE --> CURRENT
    CURRENT --> AVAILABLE
    RESERVED --> AVAILABLE
    
    AVAILABLE -->|< €20| LOW
    AVAILABLE -->|= €0| ZERO
    LOW --> TOPUP_REMIND
```

### Auto Top-up Flow
```mermaid
sequenceDiagram
    participant S as System
    participant B as Balance Monitor
    participant C as Config
    participant P as Payment Service
    participant DB as Database
    participant N as Notification
    
    S->>B: Check balance after call
    B->>DB: Get current balance
    DB-->>B: Balance: €8.50
    
    B->>C: Get auto-topup settings
    C-->>B: Enabled: Yes<br/>Threshold: €10<br/>Amount: €50
    
    B->>B: Balance < Threshold?
    Note over B: €8.50 < €10 ✓
    
    B->>P: Initiate auto-topup
    P->>P: Use saved payment method
    P->>DB: Create pending topup
    
    alt Payment succeeds
        P->>DB: Update balance
        DB-->>P: New balance: €58.50
        P->>N: Send success email
    else Payment fails
        P->>DB: Mark topup failed
        P->>N: Send failure alert
        N->>N: Disable auto-topup
    end
```

## Billing Calculations

### Call Cost Calculation
```mermaid
graph LR
    subgraph "Input Data"
        DURATION[Duration: 180 sec]
        RATE[Rate: €0.15/min]
        MINIMUM[Min charge: €0.50]
    end
    
    subgraph "Calculation"
        MINUTES[180s ÷ 60 = 3 min]
        COST[3 × €0.15 = €0.45]
        CHECK{Cost > Minimum?}
        FINAL[Final: €0.50]
    end
    
    subgraph "Modifiers"
        PEAK[Peak hours: +20%]
        VOLUME[Volume discount: -10%]
        PROMO[Promo code: -€5]
    end
    
    DURATION --> MINUTES
    RATE --> COST
    MINUTES --> COST
    COST --> CHECK
    CHECK -->|No| MINIMUM
    CHECK -->|Yes| COST
    MINIMUM --> FINAL
    
    PEAK -.-> FINAL
    VOLUME -.-> FINAL
    PROMO -.-> FINAL
```

### Billing Rules Engine
```
┌─────────────────────────────────────────────────────────────┐
│                    Billing Rules Engine                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Rule Priority Order:                                       │
│  1. Promotional Codes    (Highest)                         │
│  2. Contract Overrides                                     │
│  3. Volume Discounts                                       │
│  4. Time-based Rates                                       │
│  5. Standard Rates       (Lowest)                          │
│                                                             │
│  ┌─────────────────────────────────────────────┐          │
│  │ Example Calculation:                         │          │
│  │                                              │          │
│  │ Base cost:           €0.45                  │          │
│  │ Peak hours (+20%):   €0.09                  │          │
│  │ Subtotal:            €0.54                  │          │
│  │ Volume disc (-10%):  -€0.05                 │          │
│  │ Promo code:          -€5.00                 │          │
│  │ ─────────────────────────────                │          │
│  │ Final charge:        €0.00 (min)            │          │
│  └─────────────────────────────────────────────┘          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Webhook Security

### Stripe Webhook Verification
```mermaid
sequenceDiagram
    participant S as Stripe
    participant N as Nginx
    participant M as Middleware
    participant C as Controller
    participant SV as Signature Verifier
    
    S->>N: POST /api/stripe/webhook
    Note over S: Headers:<br/>Stripe-Signature: t=123,v1=abc
    
    N->>M: Forward request
    M->>M: Get raw body
    M->>M: Extract signature header
    
    M->>SV: Verify signature
    SV->>SV: Extract timestamp
    SV->>SV: Check replay (< 5 min)
    SV->>SV: Compute expected signature
    SV->>SV: Compare signatures
    
    alt Invalid signature
        SV-->>M: Invalid
        M-->>S: 401 Unauthorized
    else Valid signature
        SV-->>M: Valid
        M->>C: Process webhook
        C-->>S: 200 OK
    end
```

### Webhook Event Processing
```mermaid
graph TD
    subgraph "Stripe Events"
        E1[checkout.session.completed]
        E2[payment_intent.succeeded]
        E3[payment_intent.failed]
        E4[invoice.paid]
        E5[customer.subscription.updated]
    end
    
    subgraph "Event Router"
        ROUTER{Event Type?}
    end
    
    subgraph "Handlers"
        H1[HandleCheckoutComplete]
        H2[HandlePaymentSuccess]
        H3[HandlePaymentFailure]
        H4[HandleInvoicePaid]
        H5[HandleSubscriptionUpdate]
    end
    
    subgraph "Actions"
        A1[Update Balance]
        A2[Send Receipt]
        A3[Log Transaction]
        A4[Notify Admin]
    end
    
    E1 --> ROUTER
    E2 --> ROUTER
    E3 --> ROUTER
    E4 --> ROUTER
    E5 --> ROUTER
    
    ROUTER -->|checkout| H1
    ROUTER -->|payment_success| H2
    ROUTER -->|payment_fail| H3
    ROUTER -->|invoice| H4
    ROUTER -->|subscription| H5
    
    H1 --> A1
    H1 --> A2
    H2 --> A3
    H3 --> A4
```

## Transaction Management

### Transaction Lifecycle
```mermaid
stateDiagram-v2
    [*] --> Pending: Transaction created
    
    Pending --> Processing: Payment initiated
    Processing --> Authorized: Card authorized
    
    Authorized --> Captured: Funds captured
    Authorized --> Voided: Cancelled before capture
    
    Captured --> Settled: Funds in account
    Captured --> Refunding: Refund requested
    
    Refunding --> RefundPending: Refund processing
    RefundPending --> Refunded: Refund complete
    
    Settled --> [*]: Transaction complete
    Refunded --> [*]: Money returned
    Voided --> [*]: No charge
    
    Processing --> Failed: Payment failed
    Failed --> [*]: No charge
```

### Transaction Audit Trail
```
┌─────────────────────────────────────────────────────────────┐
│                  Transaction Audit Log                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Transaction: TXN-2024-001234                                │
│ ─────────────────────────────                               │
│                                                             │
│ 2024-01-15 10:00:00 | CREATED      | Amount: €50.00       │
│ 2024-01-15 10:00:01 | STRIPE_SENT  | Session: cs_xxx      │
│ 2024-01-15 10:00:15 | USER_INPUT   | Card entered         │
│ 2024-01-15 10:00:30 | 3DS_REQUIRED | Bank verification    │
│ 2024-01-15 10:00:45 | 3DS_COMPLETE | Verification OK      │
│ 2024-01-15 10:00:46 | AUTHORIZED   | Payment authorized   │
│ 2024-01-15 10:00:47 | CAPTURED     | Funds captured       │
│ 2024-01-15 10:00:48 | WEBHOOK_RECV | Event: completed     │
│ 2024-01-15 10:00:49 | BALANCE_UPD  | +€50.00             │
│ 2024-01-15 10:00:50 | EMAIL_SENT   | Receipt sent         │
│ 2024-01-15 10:00:51 | COMPLETED    | Transaction done     │
│                                                             │
│ Related Records:                                            │
│ - Balance Topup: #5678                                      │
│ - Stripe Charge: ch_xxx                                    │
│ - Email Job: #9012                                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Invoice Generation

### Invoice Creation Flow
```mermaid
graph TD
    subgraph "Triggers"
        T1[Monthly Schedule]
        T2[Manual Request]
        T3[Balance Topup]
    end
    
    subgraph "Data Collection"
        PERIOD[Billing Period]
        TRANS[Transactions]
        CALLS[Call Charges]
        TOPUPS[Topups]
    end
    
    subgraph "Invoice Generation"
        CALC[Calculate Totals]
        TAX[Apply Tax]
        FORMAT[Format Invoice]
        PDF[Generate PDF]
    end
    
    subgraph "Delivery"
        SAVE[Save to Storage]
        EMAIL[Email Customer]
        PORTAL[Show in Portal]
    end
    
    T1 --> PERIOD
    T2 --> PERIOD
    T3 --> TRANS
    
    PERIOD --> TRANS
    PERIOD --> CALLS
    PERIOD --> TOPUPS
    
    TRANS --> CALC
    CALLS --> CALC
    TOPUPS --> CALC
    
    CALC --> TAX
    TAX --> FORMAT
    FORMAT --> PDF
    
    PDF --> SAVE
    PDF --> EMAIL
    SAVE --> PORTAL
```

### Invoice Data Structure
```
┌─────────────────────────────────────────────────────────────┐
│                      INVOICE #2024-001234                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ From:                        To:                            │
│ AskProAI GmbH               Zahnarztpraxis Meyer           │
│ Beispielstr. 123            Kundenstr. 456                 │
│ 10115 Berlin                80331 München                   │
│                                                             │
│ Invoice Date: 2024-01-31                                    │
│ Period: January 2024                                        │
│ ─────────────────────────────────────────────────────────  │
│                                                             │
│ Line Items:                                                 │
│ ┌────────────────────────────┬──────┬────────┬─────────┐  │
│ │ Description                │ Qty  │ Rate   │ Amount  │  │
│ ├────────────────────────────┼──────┼────────┼─────────┤  │
│ │ Incoming Calls            │ 150  │ €0.15  │ €22.50  │  │
│ │ Peak Hour Surcharge       │  30  │ €0.03  │  €0.90  │  │
│ │ SMS Notifications         │  75  │ €0.08  │  €6.00  │  │
│ │ Balance Topup 2024-01-15  │   1  │ €50.00 │ €50.00  │  │
│ └────────────────────────────┴──────┴────────┴─────────┘  │
│                                                             │
│                              Subtotal:         €79.40      │
│                              VAT (19%):        €15.09      │
│                              ─────────────────────────      │
│                              Total:            €94.49      │
│                                                             │
│ Payment Method: Prepaid Balance                             │
│ Balance After: €155.51                                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Refund Processing

### Refund Flow
```mermaid
sequenceDiagram
    participant A as Admin
    participant S as System
    participant ST as Stripe
    participant DB as Database
    participant C as Customer
    
    A->>S: Request refund
    S->>DB: Validate transaction
    DB-->>S: Transaction found
    
    S->>S: Check refund eligibility
    Note over S: - Not already refunded<br/>- Within refund period<br/>- Sufficient balance
    
    alt Eligible for refund
        S->>ST: Create refund
        ST-->>S: Refund initiated
        S->>DB: Update transaction
        S->>DB: Adjust balance
        S->>C: Send refund email
        
        Note over ST,C: 5-10 days for bank
    else Not eligible
        S-->>A: Show error reason
    end
```

## Reporting & Analytics

### Financial Dashboard Data Flow
```mermaid
graph LR
    subgraph "Data Sources"
        DB[(Database)]
        CACHE[(Redis Cache)]
        STRIPE[Stripe API]
    end
    
    subgraph "Metrics Calculation"
        REVENUE[Revenue Calculator]
        USAGE[Usage Analyzer]
        FORECAST[Forecast Engine]
    end
    
    subgraph "Dashboard Widgets"
        W1[Daily Revenue]
        W2[Call Volume]
        W3[Top Customers]
        W4[Balance Status]
        W5[Growth Trend]
    end
    
    DB --> REVENUE
    DB --> USAGE
    CACHE --> REVENUE
    STRIPE --> REVENUE
    
    REVENUE --> W1
    USAGE --> W2
    USAGE --> W3
    REVENUE --> W4
    FORECAST --> W5
```

---

> 📝 **Note**: All monetary values shown are examples. Actual rates and fees are configured per company.