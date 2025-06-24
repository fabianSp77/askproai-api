# System Architecture

Generated on: 2025-06-23

## Complete System Overview

The AskProAI platform is a complex, enterprise-grade SaaS application with:
- **1,645 PHP files**
- **75 Eloquent models**  
- **216 service classes**
- **271 controllers**
- **277 database migrations**
- **89 active database tables**

## High-Level Architecture

```mermaid
graph TB
    subgraph "External Users"
        PHONE[fa:fa-phone Phone Callers<br/>24/7 AI Service]
        WEB[fa:fa-globe Web Users<br/>Admin & Portal]
        MOBILE[fa:fa-mobile Mobile Apps<br/>iOS & Android]
        WEBHOOK_EXT[fa:fa-plug External Services<br/>Webhooks]
    end

    subgraph "Edge & Security Layer"
        CF[Cloudflare<br/>CDN/WAF/DDoS]
        LB[Load Balancer<br/>SSL Termination]
        RATE[Rate Limiter<br/>Adaptive Throttling]
        THREAT[Threat Detection<br/>Security Monitor]
    end

    subgraph "API Gateway Layer"
        APIV1[REST API v1<br/>Legacy]
        APIV2[REST API v2<br/>Current]
        APIV3[REST API v3<br/>Beta]
        GRAPHQL[GraphQL<br/>Coming Soon]
        WEBSOCKET[WebSocket<br/>Real-time]
    end

    subgraph "Application Layer (271 Controllers)"
        subgraph "Core Controllers"
            AUTH[Auth Controllers<br/>8 files]
            APPT[Appointment Controllers<br/>15 files]
            CUST[Customer Controllers<br/>10 files]
            COMP[Company Controllers<br/>12 files]
        end
        
        subgraph "System Controllers"
            PHONE_CTRL[Phone Controllers<br/>8 files]
            FIN[Financial Controllers<br/>8 files]
            ANALYTICS[Analytics Controllers<br/>10 files]
            WEBHOOK[Webhook Controllers<br/>15 files]
        end
        
        ADMIN[Filament Admin<br/>50+ Resources]
    end

    subgraph "Service Layer (216 Services)"
        subgraph "Core Services (30)"
            AS[AppointmentService]
            CS[CustomerService]
            BS[BranchService]
            SS[StaffService]
        end
        
        subgraph "Integration Services (25)"
            CALSERV[CalcomV2Service]
            RETSERV[RetellService]
            STRIPE_SERV[StripeService]
            TWILIO_SERV[TwilioService]
        end
        
        subgraph "MCP Services (29)"
            MCP_ORCH[MCPOrchestrator]
            MCP_DB[DatabaseMCPServer]
            MCP_CAL[CalcomMCPServer]
            MCP_RET[RetellMCPServer]
        end
        
        subgraph "Support Services (132)"
            SEC[Security Services<br/>10 files]
            NOTIF[Notification Services<br/>12 files]
            ANAL[Analytics Services<br/>15 files]
            INFRA[Infrastructure Services<br/>20 files]
        end
    end

    subgraph "External Integrations"
        RETELL[Retell.ai<br/>AI Phone Agents]
        CALCOM[Cal.com<br/>v1 & v2 APIs]
        STRIPE[Stripe<br/>Payments & Billing]
        TWILIO[Twilio<br/>SMS & WhatsApp]
        GOOGLE[Google<br/>Calendar & Auth]
        SLACK[Slack<br/>Notifications]
        ZAPIER[Zapier<br/>Automation]
    end

    subgraph "Data Layer (75 Models)"
        subgraph "Primary Storage"
            MYSQL[(MySQL<br/>89 Tables<br/>1.2K Columns)]
            REDIS[(Redis<br/>Cache & Queue<br/>Real-time Slots)]
        end
        
        subgraph "Object Storage"
            S3[S3 Compatible<br/>Files & Recordings]
            CDN_STORAGE[CDN Storage<br/>Static Assets]
        end
        
        subgraph "Search & Analytics"
            ELASTIC[Elasticsearch<br/>Full-text Search]
            METRICS[(Metrics Store<br/>Time-series Data)]
        end
    end

    subgraph "Queue & Background Jobs"
        HORIZON[Laravel Horizon<br/>Queue Manager]
        WORKERS[Queue Workers<br/>Multiple Priorities]
        SCHEDULER[Task Scheduler<br/>Cron Jobs]
    end

    subgraph "Monitoring & Observability"
        PROMETHEUS[Prometheus<br/>Metrics]
        GRAFANA[Grafana<br/>Dashboards]
        SENTRY[Sentry<br/>Error Tracking]
        LOGS[Log Aggregation<br/>ELK Stack]
    end

    %% Main Flow
    PHONE -->|Calls| RETELL
    WEB --> CF --> LB --> RATE
    MOBILE --> CF --> LB --> RATE
    RATE --> THREAT --> APIV2
    
    %% Webhook Flow
    RETELL -->|Webhooks| WEBHOOK
    CALCOM -->|Webhooks| WEBHOOK
    STRIPE -->|Webhooks| WEBHOOK
    
    %% Service Layer
    APIV2 --> AS
    AS --> CALSERV --> CALCOM
    AS --> RETSERV --> RETELL
    
    %% Data Flow
    AS --> MYSQL
    AS --> REDIS
    AS --> HORIZON
    
    %% MCP Flow
    MCP_ORCH --> MCP_DB --> MYSQL
    MCP_ORCH --> MCP_CAL --> CALCOM
    MCP_ORCH --> MCP_RET --> RETELL
```

## Detailed Component Breakdown

### Frontend Layer
- **Admin Panel**: Filament 3.x with Livewire
- **Customer Portal**: Vue.js SPA (planned)
- **Mobile Apps**: React Native (in development)
- **Public Website**: Laravel Blade + Alpine.js

### API Layer (271 Controllers)
- **RESTful APIs**: Version 1, 2, and 3
- **GraphQL**: Coming in 2025 Q3
- **WebSocket**: Real-time updates
- **Webhooks**: 15 dedicated webhook handlers

### Service Layer (216 Services)
- **Core Business**: 30 services
- **Phone System**: 15 services
- **Calendar**: 25 services  
- **Financial**: 8 services
- **Analytics**: 15 services
- **MCP**: 29 services
- **Security**: 10 services
- **Infrastructure**: 20 services
- **Utilities**: 64 services

### Data Layer (75 Models, 89 Tables)
- **Multi-tenant**: Company-based isolation
- **Audit Trail**: Comprehensive logging
- **Soft Deletes**: On critical entities
- **Encryption**: Field-level for sensitive data
- **Caching**: Multi-layer caching strategy

### Integration Points
1. **Retell.ai**: AI phone agents, webhooks
2. **Cal.com**: Calendar API v1 & v2
3. **Stripe**: Payments and subscriptions
4. **Twilio**: SMS and WhatsApp
5. **Google**: Calendar and OAuth
6. **Slack/Teams**: Notifications
7. **Zapier**: Workflow automation

### Security Architecture
- **Authentication**: JWT + API Keys
- **Authorization**: Role-based (Spatie)
- **Encryption**: AES-256-CBC
- **Rate Limiting**: Adaptive throttling
- **Threat Detection**: Real-time monitoring
- **Audit Logging**: All actions tracked
- **Webhook Verification**: Signature validation

### Performance Architecture  
- **Caching**: Redis with tagged cache
- **Queue**: Horizon with priorities
- **Database**: Read replicas (planned)
- **CDN**: Cloudflare global network
- **Monitoring**: Prometheus + Grafana
- **APM**: New Relic (planned)

### Scalability Design
- **Horizontal Scaling**: Load balanced
- **Database Sharding**: Planned for Q3
- **Microservices**: MCP architecture
- **Event-Driven**: Laravel events
- **Async Processing**: Queue workers

## Data Flow Examples

### Phone to Appointment Flow
```mermaid
sequenceDiagram
    participant Customer
    participant Retell.ai
    participant Webhook
    participant PhoneResolver
    participant BookingService
    participant CalcomAPI
    participant Database
    participant NotificationService
    
    Customer->>Retell.ai: Calls phone number
    Retell.ai->>Customer: AI answers call
    Customer->>Retell.ai: "I want an appointment"
    Retell.ai->>Customer: Collects information
    Retell.ai->>Webhook: POST /api/retell/webhook
    Webhook->>PhoneResolver: Resolve phone to branch
    PhoneResolver->>Database: Find branch & settings
    Webhook->>BookingService: Create appointment
    BookingService->>CalcomAPI: Check availability
    CalcomAPI-->>BookingService: Available slots
    BookingService->>Database: Save appointment
    BookingService->>CalcomAPI: Create Cal.com event
    BookingService->>NotificationService: Send confirmations
    NotificationService->>Customer: Email/SMS confirmation
    Webhook-->>Retell.ai: Success response
    Retell.ai->>Customer: "Appointment confirmed!"
```

### Web Booking Flow
```mermaid
sequenceDiagram
    participant User
    participant WebApp
    participant API
    participant AuthService
    participant BookingService
    participant CalcomService
    participant Database
    participant Queue
    
    User->>WebApp: Access booking page
    WebApp->>API: GET /api/v2/services
    API->>Database: Fetch available services
    API-->>WebApp: Service list
    User->>WebApp: Select service & time
    WebApp->>API: POST /api/v2/bookings
    API->>AuthService: Validate token
    API->>BookingService: Process booking
    BookingService->>CalcomService: Check availability
    CalcomService-->>BookingService: Slot available
    BookingService->>Database: Create appointment
    BookingService->>Queue: Dispatch notifications
    API-->>WebApp: Booking confirmed
    Queue->>NotificationService: Process notifications
```

## Deployment Architecture

```mermaid
graph TB
    subgraph "Production Environment"
        subgraph "Web Servers"
            WEB1[Web Server 1<br/>PHP 8.2 + Nginx]
            WEB2[Web Server 2<br/>PHP 8.2 + Nginx]
            WEB3[Web Server 3<br/>PHP 8.2 + Nginx]
        end
        
        subgraph "Background Workers"
            WORK1[Worker 1<br/>High Priority]
            WORK2[Worker 2<br/>Default Queue]
            WORK3[Worker 3<br/>Low Priority]
        end
        
        subgraph "Data Stores"
            MYSQL_MASTER[(MySQL Master<br/>Write Operations)]
            MYSQL_REPLICA[(MySQL Replica<br/>Read Operations)]
            REDIS_MASTER[(Redis Master<br/>Cache/Queue)]
            REDIS_REPLICA[(Redis Replica<br/>Failover)]
        end
        
        subgraph "External Services"
            CDN[Cloudflare CDN]
            S3[S3 Storage]
            MONITORING[Monitoring Stack]
        end
    end
    
    LB[Load Balancer] --> WEB1
    LB --> WEB2
    LB --> WEB3
    
    WEB1 --> MYSQL_MASTER
    WEB2 --> MYSQL_REPLICA
    WEB3 --> MYSQL_REPLICA
    
    WEB1 --> REDIS_MASTER
    WEB2 --> REDIS_MASTER
    WEB3 --> REDIS_MASTER
```

## Technology Stack Summary

### Backend
- **Framework**: Laravel 10.x
- **PHP**: 8.2+
- **Database**: MySQL 8.0
- **Cache**: Redis 7.0
- **Queue**: Laravel Horizon
- **Search**: Elasticsearch 8.x

### Frontend
- **Admin**: Filament 3.x + Livewire
- **CSS**: Tailwind CSS 3.x
- **JS**: Alpine.js, Vue.js
- **Build**: Vite

### Infrastructure
- **Hosting**: DigitalOcean
- **CDN**: Cloudflare
- **Storage**: S3 Compatible
- **Monitoring**: Prometheus + Grafana
- **CI/CD**: GitHub Actions

### Third-Party Services
- **Phone AI**: Retell.ai
- **Calendar**: Cal.com
- **Payments**: Stripe
- **SMS**: Twilio
- **Email**: SendGrid
- **Analytics**: Mixpanel

This comprehensive architecture documentation reflects the true complexity of the AskProAI platform with its 1,645 PHP files, 216 services, 271 controllers, and 75 models.

