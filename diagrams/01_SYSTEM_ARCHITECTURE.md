# 🏗️ System Architecture Diagrams

## High-Level Architecture

### Complete System Overview
```mermaid
graph TB
    subgraph "User Layer"
        CUST[👤 Customers]
        ADMIN[👨‍💼 Admins]
        STAFF[👥 Staff]
    end
    
    subgraph "External Services"
        subgraph "Communication"
            PHONE[☎️ Phone Networks]
            EMAIL[📧 Email Provider]
            SMS[💬 SMS Gateway]
        end
        
        subgraph "AI & Calendar"
            RETELL[🤖 Retell.ai]
            CALCOM[📅 Cal.com]
            OPENAI[🧠 OpenAI]
        end
        
        subgraph "Infrastructure"
            STRIPE[💳 Stripe]
            CF[🛡️ Cloudflare]
            S3[📦 S3 Storage]
        end
    end
    
    subgraph "Application Layer"
        subgraph "Frontend"
            WEB[🌐 Web App<br/>Laravel Blade]
            ADMIN_UI[🎛️ Admin Panel<br/>Filament 3.x]
            API_DOCS[📚 API Docs<br/>Swagger]
        end
        
        subgraph "Backend Services"
            APP[🚀 Laravel 11]
            QUEUE[⚡ Horizon]
            SCHEDULER[⏰ Cron]
            WEBSOCKET[🔌 WebSocket]
        end
        
        subgraph "Caching Layer"
            REDIS1[💾 Redis Sessions]
            REDIS2[💾 Redis Cache]
            REDIS3[💾 Redis Queue]
        end
    end
    
    subgraph "Data Layer"
        DB[(🗄️ MariaDB)]
        BACKUP[(💿 Backups)]
        LOGS[📝 Log Files]
    end
    
    %% User connections
    CUST -->|Calls| PHONE
    CUST -->|Views| WEB
    ADMIN -->|Manages| ADMIN_UI
    STAFF -->|Uses| ADMIN_UI
    
    %% External service connections
    PHONE --> RETELL
    RETELL -->|Webhook| APP
    APP --> CALCOM
    APP --> STRIPE
    APP --> EMAIL
    CF --> WEB
    CF --> ADMIN_UI
    
    %% Internal connections
    WEB --> APP
    ADMIN_UI --> APP
    APP --> QUEUE
    APP --> DB
    APP --> REDIS2
    QUEUE --> REDIS3
    APP --> LOGS
    DB --> BACKUP
    
    %% Styling
    style APP fill:#e3f2fd
    style DB fill:#fff3e0
    style RETELL fill:#e8f5e9
    style STRIPE fill:#f3e5f5
```

### Microservices Communication Pattern
```mermaid
graph LR
    subgraph "Service Mesh"
        subgraph "Core Services"
            AUTH[Auth Service]
            BOOKING[Booking Service]
            CUSTOMER[Customer Service]
            BILLING[Billing Service]
        end
        
        subgraph "Integration Services"
            RETELL_SVC[Retell Service]
            CALCOM_SVC[CalCom Service]
            STRIPE_SVC[Stripe Service]
            EMAIL_SVC[Email Service]
        end
        
        subgraph "Support Services"
            LOGGER[Logging Service]
            MONITOR[Monitoring]
            CACHE[Cache Service]
        end
    end
    
    subgraph "Message Bus"
        EVENTS[Event Bus<br/>Redis Pub/Sub]
    end
    
    %% Service interactions
    AUTH <--> CACHE
    BOOKING --> EVENTS
    BOOKING --> CALCOM_SVC
    BOOKING --> CUSTOMER
    CUSTOMER --> EVENTS
    BILLING --> STRIPE_SVC
    BILLING --> EVENTS
    
    EVENTS --> EMAIL_SVC
    EVENTS --> LOGGER
    EVENTS --> MONITOR
    
    %% External APIs
    RETELL_SVC -.-> EXT_RETELL[Retell API]
    CALCOM_SVC -.-> EXT_CAL[Cal.com API]
    STRIPE_SVC -.-> EXT_STRIPE[Stripe API]
    EMAIL_SVC -.-> EXT_SMTP[SMTP Server]
```

## Infrastructure Architecture

### Network Topology
```
┌─────────────────────────────────────────────────────────────────┐
│                           INTERNET                               │
│                                                                  │
│  Customers ──┐                                    ┌── Retell.ai │
│              │                                    │              │
│  Admins ─────┼─────────── HTTPS ─────────────────┼── Cal.com   │
│              │                                    │              │
│  Staff ──────┘                                    └── Stripe    │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                    ┌──────────▼──────────┐
                    │    Cloudflare       │
                    │  - DDoS Protection  │
                    │  - SSL/TLS          │
                    │  - CDN              │
                    │  - Rate Limiting    │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │   Load Balancer     │
                    │  (High Availability) │
                    └────┬─────────┬──────┘
                         │         │
              ┌──────────▼───┐ ┌──▼──────────┐
              │  Web Server  │ │ Web Server  │
              │   (Nginx)    │ │  (Nginx)    │
              │   - Active   │ │ - Standby   │
              └──────┬───────┘ └─────────────┘
                     │
        ┌────────────┼────────────┬──────────────┐
        │            │            │              │
┌───────▼──────┐ ┌──▼───────┐ ┌──▼──────┐ ┌────▼─────┐
│   PHP-FPM    │ │ Laravel  │ │ Horizon │ │  Redis   │
│ Worker Pool  │ │   App    │ │ Queue   │ │ Cluster  │
│              │ │          │ │ Worker  │ │          │
└──────────────┘ └────┬─────┘ └─────────┘ └──────────┘
                      │
              ┌───────▼────────┐
              │    MariaDB     │
              │ Primary/Replica │
              │    Cluster     │
              └────────────────┘
```

### Container Architecture (Docker)
```mermaid
graph TD
    subgraph "Docker Host"
        subgraph "Application Containers"
            NGINX[nginx:alpine<br/>Port 80/443]
            PHP[php:8.3-fpm<br/>Laravel App]
            HORIZON[php:8.3-cli<br/>Queue Worker]
            CRON[php:8.3-cli<br/>Scheduler]
        end
        
        subgraph "Service Containers"
            REDIS[redis:7-alpine<br/>Port 6379]
            MARIADB[mariadb:11<br/>Port 3306]
            MAILHOG[mailhog<br/>Port 1025/8025]
        end
        
        subgraph "Volumes"
            APP_VOL[/var/www/html]
            DB_VOL[/var/lib/mysql]
            REDIS_VOL[/data]
        end
        
        subgraph "Networks"
            FRONTEND[frontend]
            BACKEND[backend]
        end
    end
    
    NGINX -->|frontend| PHP
    PHP -->|backend| REDIS
    PHP -->|backend| MARIADB
    HORIZON -->|backend| REDIS
    CRON -->|backend| PHP
    
    PHP -.-> APP_VOL
    MARIADB -.-> DB_VOL
    REDIS -.-> REDIS_VOL
```

## Security Architecture

### Security Layers
```mermaid
graph TD
    subgraph "Perimeter Security"
        CF[Cloudflare WAF]
        FW[Firewall Rules]
        DDOS[DDoS Protection]
    end
    
    subgraph "Application Security"
        AUTH[Authentication]
        AUTHZ[Authorization]
        CSRF[CSRF Protection]
        XSS[XSS Prevention]
        SQL[SQL Injection Prevention]
    end
    
    subgraph "Data Security"
        ENC_TRANSIT[Encryption in Transit<br/>TLS 1.3]
        ENC_REST[Encryption at Rest<br/>AES-256]
        BACKUP_ENC[Encrypted Backups]
    end
    
    subgraph "Monitoring"
        IDS[Intrusion Detection]
        AUDIT[Audit Logging]
        SIEM[Security Monitoring]
    end
    
    CF --> FW
    FW --> AUTH
    AUTH --> AUTHZ
    AUTHZ --> APP[Application]
    
    APP --> ENC_TRANSIT
    APP --> ENC_REST
    
    IDS --> SIEM
    AUDIT --> SIEM
    APP --> AUDIT
```

### API Security Flow
```mermaid
sequenceDiagram
    participant C as Client
    participant CF as Cloudflare
    participant API as API Gateway
    participant AUTH as Auth Service
    participant APP as Application
    participant DB as Database
    
    C->>CF: HTTPS Request + API Key
    CF->>CF: Rate Limit Check
    CF->>CF: WAF Rules
    CF->>API: Forward Request
    
    API->>API: Validate API Key Format
    API->>AUTH: Verify API Key
    AUTH->>DB: Lookup Key
    DB-->>AUTH: Key Details
    AUTH-->>API: Valid + Permissions
    
    API->>API: Check Permissions
    API->>APP: Process Request
    APP->>DB: Query Data
    DB-->>APP: Results
    APP-->>API: Response
    API-->>C: JSON Response
    
    Note over API: Log all requests
    Note over AUTH: Track API usage
```

## Scalability Architecture

### Horizontal Scaling Strategy
```
Current State (Single Server)          Target State (Multi-Server)
─────────────────────────────          ───────────────────────────

┌─────────────────┐                    ┌─────────────────────────┐
│  Single Server  │                    │    Load Balancer        │
│                 │                    └───────┬─────────────────┘
│  - Nginx        │                            │
│  - PHP-FPM      │         ┌──────────────────┼──────────────────┐
│  - Laravel      │         │                  │                  │
│  - Redis        │    ┌────▼────┐        ┌────▼────┐       ┌────▼────┐
│  - MariaDB      │    │ App #1  │        │ App #2  │       │ App #3  │
│                 │    │ PHP-FPM │        │ PHP-FPM │       │ PHP-FPM │
└─────────────────┘    └────┬────┘        └────┬────┘       └────┬────┘
                            │                  │                  │
                            └──────────┬───────┴──────────────────┘
                                       │
                            ┌──────────▼──────────┐
                            │   Shared Services  │
                            │  - Redis Cluster   │
                            │  - MariaDB Cluster │
                            │  - S3 Storage      │
                            └────────────────────┘
```

### Caching Strategy
```mermaid
graph LR
    subgraph "Cache Layers"
        L1[Browser Cache<br/>Static Assets]
        L2[CDN Cache<br/>Cloudflare]
        L3[Application Cache<br/>Redis]
        L4[Query Cache<br/>MariaDB]
    end
    
    subgraph "Cache Keys"
        K1[company:1:settings]
        K2[branch:5:services]
        K3[availability:2024-01-15]
        K4[user:123:permissions]
    end
    
    REQUEST[Request] --> L1
    L1 -->|Miss| L2
    L2 -->|Miss| L3
    L3 -->|Miss| L4
    L4 -->|Miss| DB[(Database)]
    
    L3 -.-> K1
    L3 -.-> K2
    L3 -.-> K3
    L3 -.-> K4
```

## Deployment Architecture

### Blue-Green Deployment
```
┌─────────────────────────────────────────────────────────┐
│                    Load Balancer                         │
│                  (Routing Control)                       │
└────────────────────┬─────────────┬──────────────────────┘
                     │             │
         100% ───────┘             └─────── 0%
                     │             │
           ┌─────────▼─────────┐   │   ┌─────────────────┐
           │   BLUE (Active)   │   │   │  GREEN (New)    │
           │   Environment     │   │   │  Environment    │
           │                   │   │   │                 │
           │  - Version 1.5    │   │   │  - Version 1.6  │
           │  - Serving Users  │   │   │  - Testing      │
           │  - Stable         │   │   │  - Validation   │
           └───────────────────┘   │   └─────────────────┘
                                   │
                        Switch when ready
                                   │
         0% ───────┐               ▼             100%
                   │               │               │
           ┌───────▼───────────┐   │   ┌─────────▼───────┐
           │   BLUE (Old)      │   │   │  GREEN (Active) │
           │   Environment     │   │   │  Environment    │
           │                   │   │   │                 │
           │  - Version 1.5    │   │   │  - Version 1.6  │
           │  - Standby        │   │   │  - Serving Users│
           │  - Rollback Ready │   │   │  - Monitoring   │
           └───────────────────┘   │   └─────────────────┘
```

## Monitoring Architecture

### Observability Stack
```mermaid
graph TD
    subgraph "Application"
        APP[Laravel App]
        QUEUE[Queue Workers]
        CRON[Schedulers]
    end
    
    subgraph "Metrics Collection"
        PROM[Prometheus]
        LOKI[Loki]
        TEMPO[Tempo]
    end
    
    subgraph "Visualization"
        GRAFANA[Grafana]
        ALERTS[Alert Manager]
    end
    
    subgraph "Metrics"
        M1[Response Times]
        M2[Error Rates]
        M3[Queue Depth]
        M4[API Usage]
    end
    
    APP -->|Metrics| PROM
    APP -->|Logs| LOKI
    APP -->|Traces| TEMPO
    
    QUEUE -->|Metrics| PROM
    CRON -->|Metrics| PROM
    
    PROM --> GRAFANA
    LOKI --> GRAFANA
    TEMPO --> GRAFANA
    
    GRAFANA --> ALERTS
    
    GRAFANA -.-> M1
    GRAFANA -.-> M2
    GRAFANA -.-> M3
    GRAFANA -.-> M4
```

---

> 📝 **Note**: These diagrams represent the current and planned architecture of AskProAI. Update as the system evolves.