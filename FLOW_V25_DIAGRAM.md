# Retell Conversation Flow V25 - Visual Diagrams

## Complete Flow Architecture

```mermaid
graph TD
    START([BEGIN CALL])
    GREET[node_greeting<br/>Begrüßung]
    INTENT[intent_router<br/>Intent Erkennung]
    COLLECT[node_collect_booking_info<br/>Daten sammeln]
    CHECK[func_check_availability<br/>Verfügbarkeit prüfen]
    PRESENT[node_present_result<br/>Ergebnis zeigen]

    EXTRACT[node_extract_alternative_selection<br/>🆕 Alternative extrahieren]
    CONFIRM[node_confirm_alternative<br/>🆕 Bestätigen]

    BOOK[func_book_appointment<br/>Termin buchen]
    SUCCESS[node_booking_success<br/>Buchung erfolgreich]
    END([END CALL])

    START --> GREET
    GREET --> INTENT
    INTENT --> COLLECT
    COLLECT --> CHECK
    CHECK --> PRESENT

    PRESENT -->|"Original verfügbar<br/>User: 'Ja'"| BOOK
    PRESENT -->|"🆕 Alternative gewählt<br/>User: 'Um 06:55'"| EXTRACT
    PRESENT -->|"Lehnt ab<br/>User: 'Nein'"| COLLECT

    EXTRACT -->|"{{selected_alternative_time}}<br/>exists"| CONFIRM
    CONFIRM -->|"{{selected_alternative_time}}<br/>exists"| BOOK

    BOOK --> SUCCESS
    SUCCESS --> END

    style EXTRACT fill:#90EE90,stroke:#006400,stroke-width:3px
    style CONFIRM fill:#90EE90,stroke:#006400,stroke-width:3px
    style BOOK fill:#FFD700,stroke:#FF8C00,stroke-width:2px
    style PRESENT fill:#87CEEB,stroke:#4682B4,stroke-width:2px
```

## Before vs After Fix

### V24 - BROKEN FLOW

```mermaid
graph LR
    PRESENT[node_present_result<br/>Alternativen zeigen]
    BOOK[func_book_appointment<br/>❌ NICHT ERREICHBAR]
    LOOP[Infinite Loop<br/>❌ STUCK]

    PRESENT -->|"User: 'Um 06:55'"| LOOP
    LOOP -.->|"Versucht zurück<br/>zu check_availability"| LOOP
    BOOK -.->|"Wird NIE erreicht"| BOOK

    style LOOP fill:#FF6B6B,stroke:#C92A2A,stroke-width:3px
    style BOOK fill:#FFB6B6,stroke:#FA5252,stroke-width:2px
    style PRESENT fill:#FFE066,stroke:#FAB005,stroke-width:2px
```

### V25 - FIXED FLOW

```mermaid
graph LR
    PRESENT[node_present_result<br/>Alternativen zeigen]
    EXTRACT[node_extract_alternative_selection<br/>🆕 Zeit erfassen]
    CONFIRM[node_confirm_alternative<br/>🆕 Bestätigen]
    BOOK[func_book_appointment<br/>✅ BOOKING ERFOLGT]
    SUCCESS[node_booking_success<br/>✅ Bestätigung]

    PRESENT -->|"User: 'Um 06:55'"| EXTRACT
    EXTRACT -->|"{{selected_alternative_time}}<br/>= '06:55'"| CONFIRM
    CONFIRM -->|"Equation:<br/>variable exists"| BOOK
    BOOK -->|"Webhook ausgeführt"| SUCCESS

    style EXTRACT fill:#90EE90,stroke:#2F9E44,stroke-width:3px
    style CONFIRM fill:#90EE90,stroke:#2F9E44,stroke-width:3px
    style BOOK fill:#51CF66,stroke:#2B8A3E,stroke-width:3px
    style SUCCESS fill:#51CF66,stroke:#2B8A3E,stroke-width:3px
    style PRESENT fill:#74C0FC,stroke:#1971C2,stroke-width:2px
```

## Decision Flow at node_present_result

```mermaid
graph TD
    PRESENT[node_present_result<br/>Zeigt Verfügbarkeit]

    CASE1{Ursprüngliche Zeit<br/>verfügbar?}
    CASE2{User sagt<br/>'Ja'?}
    DIRECT_BOOK[func_book_appointment<br/>Direkte Buchung<br/>mit appointment_time]

    ALT_CASE{Alternative<br/>angeboten?}
    USER_SELECT{User wählt<br/>Alternative?}
    EXTRACT[node_extract_alternative_selection<br/>Erfasse: selected_alternative_time]
    CONFIRM[node_confirm_alternative<br/>Bestätige Auswahl]
    ALT_BOOK[func_book_appointment<br/>Buchung Alternative<br/>mit selected_alternative_time]

    DECLINE{User lehnt<br/>ab?}
    RESTART[node_collect_booking_info<br/>Neustart]

    PRESENT --> CASE1
    CASE1 -->|Ja| CASE2
    CASE2 -->|Ja| DIRECT_BOOK

    CASE1 -->|Nein| ALT_CASE
    ALT_CASE -->|Ja| USER_SELECT
    USER_SELECT -->|"'Um 06:55'"| EXTRACT
    EXTRACT --> CONFIRM
    CONFIRM --> ALT_BOOK

    CASE2 -->|Nein| DECLINE
    USER_SELECT -->|Nein| DECLINE
    DECLINE -->|Ja| RESTART

    style EXTRACT fill:#90EE90,stroke:#2F9E44,stroke-width:3px
    style CONFIRM fill:#90EE90,stroke:#2F9E44,stroke-width:3px
    style DIRECT_BOOK fill:#FFD700,stroke:#FF8C00,stroke-width:2px
    style ALT_BOOK fill:#FFD700,stroke:#FF8C00,stroke-width:2px
```

## State Variables Flow

```mermaid
graph LR
    subgraph "Sammeln"
        V1[customer_name]
        V2[service_name]
        V3[appointment_date]
        V4[appointment_time]
    end

    subgraph "🆕 Alternative Auswahl"
        V5[selected_alternative_time]
    end

    subgraph "Booking"
        B1{Welche Zeit?}
        P1[Parameter: uhrzeit]
    end

    V1 --> B1
    V2 --> B1
    V3 --> B1
    V4 -.->|"Original Path"| B1
    V5 -->|"🆕 Alternative Path"| B1

    B1 -->|"Verwendet eine<br/>der beiden"| P1

    style V5 fill:#90EE90,stroke:#2F9E44,stroke-width:3px
    style P1 fill:#FFD700,stroke:#FF8C00,stroke-width:2px
```

## Transition Conditions

```mermaid
graph TD
    PRESENT[node_present_result]

    T1{Transition 1<br/>Prompt}
    T2{Transition 2<br/>Prompt}
    T3{🆕 Transition 3<br/>Prompt}

    E1[func_book_appointment]
    E2[node_collect_booking_info]
    E3[🆕 node_extract_alternative_selection]

    PRESENT --> T3
    T3 -->|"'User selected<br/>alternative time slot'"| E3

    PRESENT --> T1
    T1 -->|"'User explicitly<br/>confirmed booking'"| E1

    PRESENT --> T2
    T2 -->|"'User wants<br/>different time'"| E2

    style T3 fill:#90EE90,stroke:#2F9E44,stroke-width:3px
    style E3 fill:#90EE90,stroke:#2F9E44,stroke-width:3px
```

## Edge Priority Order

```mermaid
graph TD
    PRESENT[node_present_result<br/>Evaluiert Edges in Reihenfolge]

    E1[🆕 PRIORITY 1<br/>edge_present_to_extract<br/>Alternative gewählt]
    E2[PRIORITY 2<br/>edge_present_to_book<br/>Original bestätigt]
    E3[PRIORITY 3<br/>edge_present_to_retry<br/>Lehnt ab]

    R1[node_extract_alternative_selection]
    R2[func_book_appointment]
    R3[node_collect_booking_info]

    PRESENT -->|"Prüft zuerst"| E1
    E1 -->|"Match?"| R1

    PRESENT -.->|"Wenn E1 nicht matched"| E2
    E2 -->|"Match?"| R2

    PRESENT -.->|"Wenn E1+E2 nicht matched"| E3
    E3 -->|"Match?"| R3

    style E1 fill:#90EE90,stroke:#2F9E44,stroke-width:4px
    style R1 fill:#90EE90,stroke:#2F9E44,stroke-width:3px
```

## Test Case Flow

### Test Case: Alternative Selection

```mermaid
sequenceDiagram
    participant User
    participant Agent
    participant Extract as node_extract
    participant Confirm as node_confirm
    participant Book as func_book
    participant DB as Database

    User->>Agent: Herrenhaarschnitt morgen 10 Uhr
    Agent->>Agent: Check Availability
    Agent->>User: Nicht verfügbar. Alternativen:<br/>06:55, 07:55, 08:55

    User->>Agent: Um 06:55

    rect rgb(144, 238, 144)
        Note over Agent,Extract: 🆕 V25 FIX
        Agent->>Extract: Transition to extract
        Extract->>Extract: Extract "06:55"
        Extract->>Extract: Set {{selected_alternative_time}}
        Extract->>Confirm: Equation: variable exists
        Confirm->>User: Perfekt! Einen Moment...
        Confirm->>Book: Trigger booking
    end

    Book->>DB: POST book_appointment<br/>uhrzeit="06:55"
    DB-->>Book: Success (appointment_id)
    Book->>User: ✅ Ihr Termin ist gebucht!
```

### Test Case: Direct Booking (No Alternative)

```mermaid
sequenceDiagram
    participant User
    participant Agent
    participant Book as func_book
    participant DB as Database

    User->>Agent: Herrenhaarschnitt morgen 14 Uhr
    Agent->>Agent: Check Availability
    Agent->>User: Termin um 14:00 ist verfügbar.<br/>Soll ich buchen?

    User->>Agent: Ja

    rect rgb(255, 215, 0)
        Note over Agent,Book: Existing Flow (V24)
        Agent->>Book: Direct transition
    end

    Book->>DB: POST book_appointment<br/>uhrzeit="14:00"
    DB-->>Book: Success
    Book->>User: ✅ Gebucht!
```

## Error Scenarios

### V24 - What Went Wrong

```mermaid
graph TD
    PRESENT[node_present_result<br/>'Alternativen: 06:55...']

    USER[User: 'Um 06:55']

    ATTEMPT1{Versucht:<br/>edge_present_to_book}
    CHECK1[Condition: 'User explicitly<br/>confirmed booking']
    MATCH1{Match?}

    ATTEMPT2{Versucht:<br/>edge_present_to_retry}
    CHECK2[Condition: 'User wants<br/>different time']
    MATCH2{Match?}

    STUCK[❌ STUCK<br/>Kein Match<br/>Node Prison]
    HALLUCINATE[❌ Agent halluziniert<br/>'Reserviert']

    PRESENT --> USER
    USER --> ATTEMPT1
    ATTEMPT1 --> CHECK1
    CHECK1 --> MATCH1
    MATCH1 -->|"Nein<br/>(User sagte nicht 'Ja')"| ATTEMPT2

    ATTEMPT2 --> CHECK2
    CHECK2 --> MATCH2
    MATCH2 -->|"Nein<br/>(User will diese Zeit)"| STUCK

    STUCK --> HALLUCINATE

    style STUCK fill:#FF6B6B,stroke:#C92A2A,stroke-width:3px
    style HALLUCINATE fill:#FF6B6B,stroke:#C92A2A,stroke-width:3px
```

### V25 - How It's Fixed

```mermaid
graph TD
    PRESENT[node_present_result<br/>'Alternativen: 06:55...']

    USER[User: 'Um 06:55']

    ATTEMPT1{🆕 Prüft zuerst:<br/>edge_present_to_extract}
    CHECK1[Condition: 'User selected<br/>alternative time slot']
    MATCH1{Match?}

    EXTRACT[✅ node_extract<br/>Erfasst Zeit]
    CONFIRM[✅ node_confirm<br/>Bestätigt]
    BOOK[✅ func_book<br/>Führt Buchung aus]
    SUCCESS[✅ SUCCESS]

    PRESENT --> USER
    USER --> ATTEMPT1
    ATTEMPT1 --> CHECK1
    CHECK1 --> MATCH1
    MATCH1 -->|"✅ JA<br/>(User wählte Alternative)"| EXTRACT

    EXTRACT --> CONFIRM
    CONFIRM --> BOOK
    BOOK --> SUCCESS

    style EXTRACT fill:#90EE90,stroke:#2F9E44,stroke-width:3px
    style CONFIRM fill:#90EE90,stroke:#2F9E44,stroke-width:3px
    style BOOK fill:#51CF66,stroke:#2B8A3E,stroke-width:3px
    style SUCCESS fill:#51CF66,stroke:#2B8A3E,stroke-width:3px
```

## Implementation Timeline

```mermaid
gantt
    title Flow V25 Implementation
    dateFormat YYYY-MM-DD

    section Analysis
    Identify Issue           :done, a1, 2025-11-01, 1d
    Research Best Practices  :done, a2, 2025-11-02, 2d

    section Development
    Design Solution          :done, d1, 2025-11-04, 4h
    Create Fix Script        :done, d2, 2025-11-04, 2h
    Write Documentation      :done, d3, 2025-11-04, 2h

    section Testing
    Apply Fix                :active, t1, 2025-11-04, 1h
    Test Scenarios           :t2, after t1, 2h
    Verify Production        :t3, after t2, 1d

    section Monitoring
    Monitor Metrics          :m1, after t3, 7d
    Optimize if Needed       :m2, after m1, 3d
```

## Node Type Reference

```mermaid
graph TD
    subgraph "Node Types in V25"
        N1[Conversation Node<br/>Dynamic: node_present_result<br/>Static: node_confirm_alternative]
        N2[🆕 Extract Dynamic Variable<br/>node_extract_alternative_selection]
        N3[Function Node<br/>func_check_availability<br/>func_book_appointment]
        N4[End Node<br/>node_end]
    end

    subgraph "Transition Types"
        T1[Prompt-Based<br/>'User selected alternative']
        T2[Equation-Based<br/>'{{variable}} exists']
    end

    N1 --> T1
    N2 --> T2
    N3 --> T2

    style N2 fill:#90EE90,stroke:#2F9E44,stroke-width:3px
```

## Legend

- 🆕 Green Boxes = New nodes/edges in V25
- Yellow Boxes = Function nodes (API calls)
- Blue Boxes = Conversation nodes
- Red Boxes = Problems/errors in V24
- Solid Lines = Active transitions
- Dotted Lines = Attempted/failed transitions

---

**Generated:** 2025-11-04
**Version:** V25
**Purpose:** Visual reference for conversation flow fix
