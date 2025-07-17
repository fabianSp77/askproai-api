# 🔐 Authentication Flow Diagrams

## Authentication System Overview

### Multi-Layer Authentication Architecture
```mermaid
graph TD
    subgraph "Authentication Layers"
        L1[Session-Based Auth<br/>Admin Panel]
        L2[API Token Auth<br/>REST API]
        L3[Webhook Signature<br/>External Services]
        L4[OAuth 2.0<br/>Third-party Integration]
    end
    
    subgraph "User Types"
        ADMIN[Super Admin]
        COMPANY[Company Admin]
        STAFF[Staff Member]
        API[API Client]
        SERVICE[External Service]
    end
    
    subgraph "Auth Methods"
        PASSWORD[Password + 2FA]
        TOKEN[Bearer Token]
        SIGNATURE[HMAC Signature]
        OAUTH[OAuth Flow]
    end
    
    ADMIN --> L1
    COMPANY --> L1
    STAFF --> L1
    API --> L2
    SERVICE --> L3
    
    L1 --> PASSWORD
    L2 --> TOKEN
    L3 --> SIGNATURE
    L4 --> OAUTH
```

## Web Authentication Flow

### Login Process
```mermaid
sequenceDiagram
    participant U as User
    participant B as Browser
    participant CF as Cloudflare
    participant APP as Laravel
    participant DB as Database
    participant REDIS as Redis
    participant 2FA as 2FA Service
    
    U->>B: Enter credentials
    B->>CF: POST /login
    CF->>CF: Check rate limit
    CF->>APP: Forward request
    
    APP->>APP: Validate CSRF token
    APP->>APP: Validate input
    APP->>DB: Find user by email
    DB-->>APP: User record
    
    APP->>APP: Verify password hash
    
    alt Password correct
        APP->>APP: Check if 2FA enabled
        
        alt 2FA enabled
            APP->>REDIS: Store temp auth
            APP-->>B: Redirect to 2FA
            B->>U: Show 2FA form
            U->>B: Enter 2FA code
            B->>APP: Submit code
            APP->>2FA: Verify code
            2FA-->>APP: Valid/Invalid
            
            alt Valid 2FA
                APP->>REDIS: Create session
                APP->>DB: Update last_login
                APP-->>B: Set cookie + redirect
            else Invalid 2FA
                APP-->>B: Error: Invalid code
            end
        else No 2FA
            APP->>REDIS: Create session
            APP->>DB: Update last_login
            APP-->>B: Set cookie + redirect
        end
    else Password incorrect
        APP->>DB: Log failed attempt
        APP-->>B: Error: Invalid credentials
    end
```

### Session Management
```
┌─────────────────────────────────────────────────────────────┐
│                    Session Lifecycle                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ 1. Session Creation                                          │
│    ┌─────────────────────────────────────┐                 │
│    │ Session ID: uuid-v4                  │                 │
│    │ User ID: 123                         │                 │
│    │ IP Address: 192.168.1.1             │                 │
│    │ User Agent: Mozilla/5.0...          │                 │
│    │ Created: 2024-01-15 10:00:00        │                 │
│    │ Expires: 2024-01-15 12:00:00        │                 │
│    └─────────────────────────────────────┘                 │
│                                                              │
│ 2. Session Storage (Redis)                                   │
│    Key: laravel_session:uuid-v4                            │
│    TTL: 7200 seconds (2 hours)                             │
│                                                              │
│ 3. Cookie Settings                                           │
│    Name: askproai_session                                   │
│    HttpOnly: true                                           │
│    Secure: true (HTTPS only)                               │
│    SameSite: lax                                           │
│    Domain: .askproai.de                                    │
│                                                              │
│ 4. Session Validation (Each Request)                        │
│    • Check session exists in Redis                         │
│    • Validate IP hasn't changed                           │
│    • Validate User Agent match                            │
│    • Extend TTL on activity                               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## API Authentication

### API Token Flow
```mermaid
sequenceDiagram
    participant C as Client
    participant API as API Server
    participant AUTH as Auth Service
    participant DB as Database
    participant CACHE as Redis Cache
    
    C->>API: GET /api/resource
    Note over C: Header: Authorization: Bearer {token}
    
    API->>API: Extract token
    API->>CACHE: Check token cache
    
    alt Token in cache
        CACHE-->>API: User data
    else Token not cached
        API->>AUTH: Validate token
        AUTH->>DB: Lookup token
        DB-->>AUTH: Token details
        
        alt Token valid
            AUTH->>AUTH: Check expiry
            AUTH->>AUTH: Check scopes
            AUTH->>CACHE: Cache user data
            AUTH-->>API: User authenticated
        else Token invalid
            AUTH-->>API: Unauthorized
        end
    end
    
    alt Authenticated
        API->>API: Check permissions
        API->>API: Process request
        API-->>C: 200 OK + Data
    else Not authenticated
        API-->>C: 401 Unauthorized
    end
```

### API Key Management
```mermaid
graph TD
    subgraph "API Key Lifecycle"
        CREATE[Generate Key]
        HASH[Hash & Store]
        ASSIGN[Assign Scopes]
        USE[Client Uses]
        ROTATE[Rotate Key]
        REVOKE[Revoke Key]
    end
    
    subgraph "Key Properties"
        PREFIX[Prefix: ask_]
        RANDOM[32 Random Bytes]
        CHECKSUM[Checksum]
        FORMAT[ask_1234...wxyz]
    end
    
    subgraph "Storage"
        PLAIN[Show Once Only]
        HASHED[Bcrypt Hash in DB]
        CACHED[Redis for Active]
    end
    
    CREATE --> PREFIX
    PREFIX --> RANDOM
    RANDOM --> CHECKSUM
    CHECKSUM --> FORMAT
    
    FORMAT --> PLAIN
    FORMAT --> HASH
    HASH --> HASHED
    
    USE --> CACHED
    ROTATE --> CREATE
    REVOKE --> CACHED
```

## Webhook Authentication

### Signature Verification Flow
```mermaid
sequenceDiagram
    participant EXT as External Service
    participant CF as Cloudflare
    participant APP as Application
    participant VERIFY as Signature Verifier
    
    EXT->>CF: POST /webhook
    Note over EXT: Headers:<br/>X-Service-Signature: sha256=xxx<br/>X-Timestamp: 1234567890
    Note over EXT: Body: {"event": "call.ended"}
    
    CF->>APP: Forward request
    
    APP->>VERIFY: Extract signature
    VERIFY->>VERIFY: Get webhook secret
    VERIFY->>VERIFY: Recreate signature
    Note over VERIFY: HMAC-SHA256(timestamp + body, secret)
    
    VERIFY->>VERIFY: Compare signatures
    
    alt Signatures match
        VERIFY->>VERIFY: Check timestamp
        alt Timestamp recent (< 5 min)
            VERIFY-->>APP: Valid webhook
            APP->>APP: Process event
            APP-->>EXT: 200 OK
        else Timestamp old
            VERIFY-->>APP: Replay attack
            APP-->>EXT: 401 Unauthorized
        end
    else Signatures don't match
        VERIFY-->>APP: Invalid signature
        APP-->>EXT: 401 Unauthorized
    end
```

### Webhook Security Implementation
```
┌─────────────────────────────────────────────────────────────┐
│                 Webhook Security Layers                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ 1. IP Whitelisting (Cloudflare)                            │
│    • Retell.ai: 34.xxx.xxx.0/24                           │
│    • Stripe: 54.xxx.xxx.0/24                              │
│    • Cal.com: 185.xxx.xxx.0/24                            │
│                                                              │
│ 2. Signature Verification                                    │
│    ┌────────────────────────────────────┐                  │
│    │ Retell Signature:                  │                  │
│    │ Header: x-retell-signature         │                  │
│    │ Format: v={timestamp},d={hash}    │                  │
│    │ Secret: Same as API key            │                  │
│    └────────────────────────────────────┘                  │
│                                                              │
│    ┌────────────────────────────────────┐                  │
│    │ Stripe Signature:                  │                  │
│    │ Header: stripe-signature           │                  │
│    │ Format: t={time},v1={hash}        │                  │
│    │ Secret: whsec_xxxxx                │                  │
│    └────────────────────────────────────┘                  │
│                                                              │
│ 3. Replay Protection                                         │
│    • Timestamp must be < 5 minutes old                     │
│    • Store processed event IDs                             │
│    • Reject duplicate events                               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Permission System

### Role-Based Access Control (RBAC)
```mermaid
graph TD
    subgraph "Roles"
        SA[Super Admin]
        CA[Company Admin]
        BM[Branch Manager]
        ST[Staff]
        API[API User]
    end
    
    subgraph "Resources"
        COMP[Companies]
        BRANCH[Branches]
        APPT[Appointments]
        CUST[Customers]
        CALL[Calls]
        BILL[Billing]
    end
    
    subgraph "Permissions"
        C[Create]
        R[Read]
        U[Update]
        D[Delete]
    end
    
    SA -->|All| COMP
    SA -->|All| BRANCH
    SA -->|All| APPT
    
    CA -->|Read/Update| COMP
    CA -->|All| BRANCH
    CA -->|All| APPT
    CA -->|All| CUST
    CA -->|Read| BILL
    
    BM -->|Read| BRANCH
    BM -->|All| APPT
    BM -->|All| CUST
    BM -->|Read| CALL
    
    ST -->|Read| BRANCH
    ST -->|Read/Update Own| APPT
    ST -->|Read| CUST
```

### Permission Matrix
```
┌─────────────────────────────────────────────────────────────┐
│                    Permission Matrix                         │
├─────────────────┬────────┬─────────┬──────────┬────────────┤
│ Resource        │ Super  │ Company │ Branch   │ Staff      │
│                 │ Admin  │ Admin   │ Manager  │            │
├─────────────────┼────────┼─────────┼──────────┼────────────┤
│ Companies       │ CRUD   │ RU      │ R        │ -          │
│ Branches        │ CRUD   │ CRUD    │ R        │ R          │
│ Staff           │ CRUD   │ CRUD    │ RU       │ R          │
│ Appointments    │ CRUD   │ CRUD    │ CRUD     │ R(own)     │
│ Customers       │ CRUD   │ CRUD    │ CRUD     │ R          │
│ Calls           │ CRUD   │ CRUD    │ R        │ R          │
│ Billing         │ CRUD   │ RU      │ R        │ -          │
│ Settings        │ CRUD   │ RU      │ R        │ -          │
│ API Keys        │ CRUD   │ CRD     │ -        │ -          │
│ Webhooks        │ CRUD   │ RU      │ -        │ -          │
└─────────────────┴────────┴─────────┴──────────┴────────────┘

Legend: C=Create, R=Read, U=Update, D=Delete, -=No Access
```

## Multi-Factor Authentication

### 2FA Flow
```mermaid
sequenceDiagram
    participant U as User
    participant APP as Application
    participant QR as QR Generator
    participant AUTH as Authenticator App
    participant VALID as Validator
    
    rect rgb(240, 240, 240)
        Note over U,VALID: 2FA Setup
        U->>APP: Enable 2FA
        APP->>APP: Generate secret
        APP->>QR: Create QR code
        QR-->>U: Display QR
        U->>AUTH: Scan QR code
        AUTH->>AUTH: Store secret
        AUTH->>U: Show code
        U->>APP: Enter code
        APP->>VALID: Verify code
        VALID-->>APP: Valid
        APP->>APP: Enable 2FA
        APP->>APP: Generate backup codes
        APP-->>U: Show backup codes
    end
    
    rect rgb(240, 248, 255)
        Note over U,VALID: 2FA Login
        U->>APP: Username/Password
        APP->>APP: Verify credentials
        APP-->>U: Request 2FA
        U->>AUTH: Open app
        AUTH-->>U: Current code
        U->>APP: Enter code
        APP->>VALID: Verify TOTP
        VALID-->>APP: Valid
        APP-->>U: Login success
    end
```

### 2FA Backup & Recovery
```
┌─────────────────────────────────────────────────────────────┐
│                  2FA Recovery Options                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ 1. Backup Codes                                             │
│    ┌─────────────────────────────────┐                     │
│    │ • 8 single-use codes            │                     │
│    │ • 8 characters each             │                     │
│    │ • Stored hashed                 │                     │
│    │ • Download as PDF               │                     │
│    └─────────────────────────────────┘                     │
│                                                              │
│ 2. SMS Fallback                                             │
│    • Send code to registered phone                         │
│    • Rate limited: 3 per hour                              │
│    • Logged for security                                   │
│                                                              │
│ 3. Admin Override                                           │
│    • Super admin can disable 2FA                           │
│    • Requires admin 2FA                                    │
│    • Audit logged                                          │
│    • Email notification sent                               │
│                                                              │
│ 4. Account Recovery                                         │
│    • Email verification                                    │
│    • Security questions                                    │
│    • 24-hour waiting period                               │
│    • Manual review for high-value accounts                 │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## OAuth 2.0 Integration

### OAuth Flow (Future Implementation)
```mermaid
sequenceDiagram
    participant U as User
    participant APP as AskProAI
    participant OAUTH as OAuth Provider
    participant API as Provider API
    
    U->>APP: Click "Connect Google"
    APP->>OAUTH: Redirect to authorize
    Note over OAUTH: /oauth/authorize?<br/>client_id=xxx&<br/>redirect_uri=xxx&<br/>scope=calendar
    
    U->>OAUTH: Login & approve
    OAUTH->>APP: Redirect with code
    Note over APP: /callback?code=xxx
    
    APP->>OAUTH: Exchange code for token
    Note over APP: POST /oauth/token<br/>code=xxx&<br/>client_secret=xxx
    
    OAUTH-->>APP: Access & refresh tokens
    APP->>APP: Store encrypted tokens
    
    APP->>API: Use access token
    API-->>APP: User calendar data
    
    Note over APP: Token expires
    APP->>OAUTH: Use refresh token
    OAUTH-->>APP: New access token
```

## Security Headers

### Security Headers Configuration
```
┌─────────────────────────────────────────────────────────────┐
│                  Security Headers                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Strict-Transport-Security:                                  │
│   max-age=31536000; includeSubDomains; preload             │
│                                                              │
│ Content-Security-Policy:                                     │
│   default-src 'self';                                      │
│   script-src 'self' 'unsafe-inline' cdn.jsdelivr.net;      │
│   style-src 'self' 'unsafe-inline' fonts.googleapis.com;   │
│   font-src 'self' fonts.gstatic.com;                       │
│   img-src 'self' data: https:;                             │
│   connect-src 'self' api.stripe.com;                       │
│                                                              │
│ X-Frame-Options: DENY                                       │
│ X-Content-Type-Options: nosniff                             │
│ X-XSS-Protection: 1; mode=block                            │
│ Referrer-Policy: strict-origin-when-cross-origin           │
│ Permissions-Policy: geolocation=(), microphone=()          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Session Security

### Session Hijacking Prevention
```mermaid
graph TD
    subgraph "Session Security Measures"
        IP[IP Binding]
        UA[User Agent Binding]
        REGEN[Session Regeneration]
        TIMEOUT[Idle Timeout]
        ABSOLUTE[Absolute Timeout]
    end
    
    subgraph "Detection"
        IP_CHANGE[IP Changed]
        UA_CHANGE[UA Changed]
        CONCURRENT[Multiple Locations]
        SUSPICIOUS[Suspicious Activity]
    end
    
    subgraph "Response"
        INVALIDATE[Invalidate Session]
        NOTIFY[Notify User]
        LOG[Security Log]
        CHALLENGE[Re-authenticate]
    end
    
    IP --> IP_CHANGE
    UA --> UA_CHANGE
    
    IP_CHANGE --> INVALIDATE
    UA_CHANGE --> CHALLENGE
    CONCURRENT --> NOTIFY
    SUSPICIOUS --> LOG
    
    INVALIDATE --> NOTIFY
    CHALLENGE --> LOG
```

---

> 📝 **Note**: Authentication system implements defense in depth with multiple security layers and fallback mechanisms.