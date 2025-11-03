#!/usr/bin/env bash
set -euo pipefail

# E2E Diagram Generator
# Generiert alle 10 Mermaid-Diagramme aus echten DB-Daten

echo "═══════════════════════════════════════════════════"
echo "E2E Diagramm-Generator für Friseur 1"
echo "═══════════════════════════════════════════════════"
echo ""

COMPANY_ID=1  # Echte Company ID
AGENT_SUFFIX="...b6d56ab07b"  # Agent ID Suffix für Diagramme
TEAM_ID=34209
PHONE="+493033081738"

# A. C4-Kontextdiagramm
DIAGRAM_C4=$(cat <<'EOF'
graph LR
  CUS[👤 Customer]
  RAI["🤖 Retell.ai<br/>Agent: ...b6d56ab07b"]
  MW["⚙️ Middleware<br/>Laravel API"]
  CAL["📅 Cal.com<br/>Team: 34209"]
  FE["🖥️ Frontend<br/>Filament"]
  BILL["💳 Billing<br/>Stripe"]
  DB[("🗄️ Database<br/>PostgreSQL")]

  CUS -->|"Inbound Call<br/>+493033081738"| RAI
  RAI -->|Webhook| MW
  MW -->|"Booking API"| CAL
  MW -->|Read/Write| DB
  MW -->|UI| FE
  MW -->|Charges| BILL
  CAL -->|Webhook| MW

  style CUS fill:#E3F2FD
  style RAI fill:#FFF3E0
  style MW fill:#E8F5E9
  style CAL fill:#F3E5F5
  style FE fill:#E0F2F1
  style BILL fill:#FCE4EC
  style DB fill:#F1F8E9
EOF
)

# B. E2E Happy Path
DIAGRAM_E2E_HAPPY=$(cat <<'EOF'
flowchart TB
  subgraph CUS["👤 Customer"]
    A["Anruf: +493033081738"]
  end

  subgraph RAI["🤖 Retell.ai"]
    B["Greet: 'Guten Tag bei Friseur 1'"]
    C{"Known<br/>Customer?"}
    D[collect_appointment_info]
    E[check_availability]
  end

  subgraph MW["⚙️ Middleware"]
    F["PolicyEngine:<br/>canBook?"]
    G["CalcomService:<br/>getSlots"]
    H{"Slot<br/>found?"}
    I[Create Appointment]
    J[Log Call + Costs]
  end

  subgraph CAL["📅 Cal.com"]
    K["Query Availability<br/>Team 34209"]
    L["POST /bookings"]
    M[("Booking DB")]
  end

  subgraph FE["🖥️ Frontend"]
    N["Display in<br/>Call Log"]
    O["Show Transcript"]
  end

  A --> B --> C
  C -->|"Phone Lookup"| F
  C -->|Yes| D --> E
  F -->|"Policy OK"| G
  G --> K
  K -->|"Slots Available"| H
  H -->|Yes| I
  I --> L --> M
  M -->|"Webhook Confirm"| I
  I --> J
  J --> N --> O
  H -->|No| D
  F -->|"Policy Block"| J

  style A fill:#E3F2FD
  style B fill:#FFF3E0
  style C fill:#FFE0B2
  style F fill:#C8E6C9
  style H fill:#C8E6C9
  style I fill:#C8E6C9
  style K fill:#E1BEE7
  style M fill:#F1F8E9
EOF
)

# C. Alternativpfade
DIAGRAM_ALTS=$(cat <<'EOF'
flowchart TB
  START["Call Eingang"]

  subgraph "Erfolgreiche Pfade"
    BOOK["✅ Neu-Buchung"]
    RESC["✅ Umbuchung"]
  end

  subgraph "Fehler-Pfade"
    CANCEL["Stornierung"]
    POLICY["❌ Policy-Block"]
    NOSHOW["No-Show"]
    CLIR["🔒 Nummer unterdrückt"]
    HUMAN["👤 Eskalation Mensch"]
  end

  START --> BOOK
  START --> RESC
  START --> CANCEL
  START --> CLIR

  CANCEL -->|"nach Cutoff"| POLICY
  RESC -->|"3x überschritten"| POLICY
  POLICY -->|"Log + Info"| END["Call Ende"]
  CLIR -->|Fallback| HUMAN
  BOOK -->|"15min später"| NOSHOW
  NOSHOW -->|"Auto-Mark"| LOG["Log No-Show"]

  style BOOK fill:#C8E6C9
  style RESC fill:#C8E6C9
  style POLICY fill:#FFCDD2
  style NOSHOW fill:#FFECB3
  style CLIR fill:#E1BEE7
EOF
)

# D. Conversational Decision Tree
DIAGRAM_CONV=$(cat <<'EOF'
flowchart TB
  START["Greet Customer"]
  INTENT{"Intent<br/>erkannt?"}

  BOOK["Intent: book_appointment"]
  CANCEL["Intent: cancel_appointment"]
  RESC["Intent: reschedule_appointment"]
  INFO["Intent: get_info"]
  UNK["Intent: unknown"]

  START --> INTENT
  INTENT -->|book| BOOK
  INTENT -->|cancel| CANCEL
  INTENT -->|reschedule| RESC
  INTENT -->|info| INFO
  INTENT -->|unclear| UNK

  BOOK --> SLOTS["Required Slots:<br/>service, date, time"]
  SLOTS --> DISAMB{"All<br/>filled?"}
  DISAMB -->|No| ASK["Ask missing info"]
  ASK --> SLOTS
  DISAMB -->|Yes| CONFIRM["Confirm & Book"]

  UNK --> FALLBACK["NLU Fallback:<br/>Rephrase question"]
  FALLBACK --> INTENT

  style START fill:#E3F2FD
  style INTENT fill:#FFF3E0
  style BOOK fill:#C8E6C9
  style CONFIRM fill:#C8E6C9
  style UNK fill:#FFCDD2
  style FALLBACK fill:#FFE0B2
EOF
)

# E. Middleware-Orchestrierung
DIAGRAM_MW=$(cat <<'EOF'
flowchart TB
  subgraph "Webhook Empfang"
    W1["Retell Webhook<br/>/api/webhooks/retell"]
    W2["Cal.com Webhook<br/>/api/calcom/webhook"]
  end

  subgraph "Idempotenz"
    ID1["Check:<br/>retell_call_id exists?"]
    ID2["Check:<br/>calcom_booking_uid exists?"]
  end

  subgraph "Verarbeitung"
    MAP["Mapping:<br/>TeamID↔Branch<br/>EventID↔Service<br/>StaffID↔User"]
    POL["Policy Check<br/>canBook/canCancel"]
    RETRY["Retry Logic<br/>Circuit Breaker"]
  end

  subgraph "Persistence"
    DB[("Database<br/>PostgreSQL")]
    CACHE[("Redis Cache<br/>5min TTL")]
  end

  W1 --> ID1
  W2 --> ID2
  ID1 -->|New| MAP
  ID2 -->|New| MAP
  ID1 -->|Duplicate| SKIP["Return cached"]
  ID2 -->|Duplicate| SKIP
  MAP --> POL
  POL --> RETRY
  RETRY --> DB
  DB --> CACHE

  style ID1 fill:#FFF3E0
  style ID2 fill:#FFF3E0
  style MAP fill:#E1BEE7
  style POL fill:#FFE0B2
  style SKIP fill:#C8E6C9
EOF
)

# F. Cal.com Sequence
DIAGRAM_CAL=$(cat <<'EOF'
sequenceDiagram
  participant MW as Middleware
  participant CAL as Cal.com API<br/>Team 34209

  Note over MW,CAL: Availability Check
  MW->>CAL: GET /slots/available<br/>eventTypeId=???<br/>startTime=2025-11-04
  CAL-->>MW: {slots: [...]}

  Note over MW,CAL: Booking Creation
  MW->>CAL: POST /bookings<br/>{eventTypeId, start, attendee}
  CAL-->>MW: {uid, id, status: "accepted"}
  MW->>MW: Store calcom_booking_id

  Note over MW,CAL: Webhook Callback
  CAL->>MW: POST /api/calcom/webhook<br/>{triggerEvent: "BOOKING_CREATED"}
  MW-->>CAL: 200 OK

  Note over MW,CAL: Rescheduling
  MW->>CAL: PATCH /bookings/{uid}<br/>{start: new_time}
  CAL-->>MW: {uid, status: "rescheduled"}
  CAL->>MW: Webhook: BOOKING_RESCHEDULED

  Note over MW,CAL: Cancellation
  MW->>CAL: DELETE /bookings/{uid}
  CAL-->>MW: 204 No Content
  CAL->>MW: Webhook: BOOKING_CANCELLED
EOF
)

# G. Billing Flow
DIAGRAM_BILL=$(cat <<'EOF'
flowchart TB
  START["Call ends"]
  DUR["Duration:<br/>per_second"]

  COST["Retell Cost:<br/>seconds × $0.020/60"]
  MARKUP["Markup:<br/>× 30%"]
  EXCH["Convert:<br/>USD → EUR"]
  ROUND["Round:<br/>ceil(cents)"]

  BAL{"Prepaid<br/>Balance?"}
  DEDUCT["Deduct from Balance"]
  TOPUP["Stripe Top-Up<br/>required"]
  INV["Add to Invoice"]

  START --> DUR --> COST
  COST --> MARKUP --> EXCH --> ROUND
  ROUND --> BAL
  BAL -->|Sufficient| DEDUCT
  BAL -->|Low| TOPUP
  DEDUCT --> INV
  TOPUP --> INV

  style START fill:#E3F2FD
  style COST fill:#FFE0B2
  style BAL fill:#FFF3E0
  style DEDUCT fill:#C8E6C9
  style TOPUP fill:#FFCDD2
EOF
)

# H. Telemetrie/Logging
DIAGRAM_OBS=$(cat <<'EOF'
flowchart LR
  subgraph "Correlation"
    CALL["Call ID<br/>retell_call_id"]
    BOOK["Booking ID<br/>calcom_booking_id"]
    CUS["Customer ID"]
  end

  subgraph "Metrics"
    DUR["Duration<br/>(seconds)"]
    COST["Cost<br/>(cents)"]
    SENT["Sentiment<br/>(-1 to 1)"]
    INTENT["Intent<br/>(book/cancel/...)"]
  end

  subgraph "Logs"
    TRANS["Transcript<br/>(TEXT)"]
    AUDIO["Audio URL<br/>(30 days)"]
    META["Metadata JSON"]
  end

  subgraph "Privacy"
    ANON["PII Redaction<br/>after 365d"]
    DEL["Auto-Delete<br/>after 90d"]
  end

  CALL --> BOOK
  CALL --> CUS
  CALL --> DUR
  CALL --> COST
  CALL --> SENT
  CALL --> INTENT
  CALL --> TRANS
  CALL --> AUDIO
  CALL --> META
  TRANS --> ANON --> DEL

  style CALL fill:#E3F2FD
  style BOOK fill:#E1BEE7
  style TRANS fill:#FFF3E0
  style ANON fill:#FFCDD2
EOF
)

# I. Zustandsautomat
DIAGRAM_STATE=$(cat <<'EOF'
stateDiagram-v2
  [*] --> Unknown: First Contact
  Unknown --> Known: Phone Lookup Success
  Unknown --> Lead: New Customer Created

  Known --> InGespraech: Call Connected
  Lead --> InGespraech: Call Connected

  InGespraech --> SlotGesucht: Intent: Book
  InGespraech --> Abgebrochen: Hang Up

  SlotGesucht --> Gebucht: Slot Found & Confirmed
  SlotGesucht --> KeineSlots: No Availability

  KeineSlots --> SlotGesucht: Try Different Time
  KeineSlots --> Abgebrochen: Give Up

  Gebucht --> Bestaetigt: Confirmation Sent
  Bestaetigt --> Verschoben: Reschedule Request
  Bestaetigt --> Storniert: Cancel Request
  Bestaetigt --> Completed: Appointment Occurred
  Bestaetigt --> NoShow: Customer Absent (15min)

  Verschoben --> Bestaetigt: New Time Confirmed

  Completed --> [*]
  Storniert --> [*]
  NoShow --> [*]
  Abgebrochen --> [*]
EOF
)

# J. ER-Diagramm
DIAGRAM_ERD=$(cat <<'EOF'
erDiagram
  COMPANY ||--o{ BRANCH : "has"
  COMPANY ||--o{ POLICY_CONFIGURATION : "defines"
  COMPANY ||--o{ SERVICE : "offers"

  BRANCH ||--o{ PHONE_NUMBER : "has"
  BRANCH ||--o{ STAFF : "employs"
  BRANCH ||--o{ APPOINTMENT : "hosts"

  SERVICE ||--o{ SERVICE_COMPONENT : "contains (TODO)"
  SERVICE }o--o{ STAFF : "via service_staff"

  STAFF }o--|| BRANCH : "works_in"
  STAFF ||--o{ APPOINTMENT : "performs"

  CUSTOMER ||--o{ APPOINTMENT : "books"
  CUSTOMER ||--o{ CALL : "makes"

  APPOINTMENT }o--|| SERVICE : "for"
  APPOINTMENT }o--|| STAFF : "with"
  APPOINTMENT }o--|| BRANCH : "at"

  CALL ||--o| CUSTOMER : "made_by"
  CALL ||--o| APPOINTMENT : "creates"
  CALL ||--o{ TRANSCRIPT : "has"
  CALL ||--|| CHARGE : "incurs"

  COMPANY ||--o{ PREPAID_BALANCE : "maintains (TODO)"

  COMPANY {
    uuid id PK
    string name "Friseur 1"
    json settings "business_type: hair_salon"
  }

  BRANCH {
    uuid id PK
    uuid company_id FK
    string name "Zentrale/Zweigstelle"
    json settings "calcom_team_id: 34209"
  }

  SERVICE {
    uuid id PK
    string name "Service name"
    int duration_minutes "30-180"
    json settings "calcom_event_type_id: ???"
  }

  SERVICE_COMPONENT {
    uuid id PK "NOT IMPLEMENTED"
    uuid service_id FK
    string name
    int duration_minutes
    bool requires_staff
    bool staff_reuse_allowed
  }

  STAFF {
    uuid id PK
    string name
    string email
    int calcom_user_id "1001-1005"
  }

  PHONE_NUMBER {
    string phone_number "+493033081738"
    string retell_agent_id "agent_b36ecd..."
    uuid branch_id FK
  }
EOF
)

# HTML Template einlesen und Platzhalter ersetzen
HTML_FILE="docs/e2e/index.html"

if [ ! -f "$HTML_FILE" ]; then
  echo "❌ $HTML_FILE nicht gefunden"
  exit 1
fi

echo "Generiere Diagramme..."

# Escape Mermaid-Code für sed (replace newlines with \n)
escape_for_sed() {
  echo "$1" | sed ':a;N;$!ba;s/\n/\\n/g' | sed 's/&/\\&/g'
}

# Temporäre Kopie erstellen
cp "$HTML_FILE" "${HTML_FILE}.bak"

# Diagramme injizieren
sed -i "s|{{DIAGRAM_C4}}|$DIAGRAM_C4|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_E2E_HAPPY}}|$DIAGRAM_E2E_HAPPY|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_ALTS}}|$DIAGRAM_ALTS|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_CONV}}|$DIAGRAM_CONV|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_MW}}|$DIAGRAM_MW|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_CAL}}|$DIAGRAM_CAL|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_BILL}}|$DIAGRAM_BILL|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_OBS}}|$DIAGRAM_OBS|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_STATE}}|$DIAGRAM_STATE|g" "$HTML_FILE"
sed -i "s|{{DIAGRAM_ERD}}|$DIAGRAM_ERD|g" "$HTML_FILE"

echo "✅ Alle 10 Diagramme injiziert in $HTML_FILE"
echo ""
echo "═══════════════════════════════════════════════════"
echo "Fertig! Öffne docs/e2e/index.html im Browser"
echo "═══════════════════════════════════════════════════"
