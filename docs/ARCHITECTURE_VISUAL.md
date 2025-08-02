# 🏗️ AskProAI Architecture - Visual Guide

## 🌐 System Overview

```mermaid
graph TB
    subgraph "External Services"
        Phone[📞 Customer Phone]
        Retell[🤖 Retell.ai<br/>Voice AI]
        Cal[📅 Cal.com<br/>Calendar]
        Stripe[💳 Stripe<br/>Payments]
        SMS[📱 Twilio<br/>SMS]
    end
    
    subgraph "AskProAI Platform"
        subgraph "API Gateway"
            API[🌐 REST API<br/>Laravel]
            WH[🔗 Webhook<br/>Handler]
            Auth[🔐 Auth<br/>Service]
        end
        
        subgraph "Core Services"
            AS[📅 Appointment<br/>Service]
            CS[👤 Customer<br/>Service]
            RS[🤖 Retell<br/>Service]
            QS[📋 Queue<br/>Service]
        end
        
        subgraph "Data Layer"
            DB[(💾 MySQL<br/>Database)]
            Redis[(⚡ Redis<br/>Cache/Queue)]
            S3[☁️ S3<br/>Storage]
        end
        
        subgraph "Admin UI"
            Filament[🎨 Filament<br/>Admin Panel]
            Horizon[📊 Horizon<br/>Queue Monitor]
        end
    end
    
    %% Connections
    Phone -->|Call| Retell
    Retell -->|Webhook| WH
    WH --> QS
    QS --> AS
    AS --> CS
    AS --> Cal
    AS --> DB
    CS --> DB
    RS --> Retell
    API --> Auth
    API --> AS
    API --> CS
    Filament --> API
    AS --> SMS
    AS --> Redis
```

## 🔄 Request Flow

```mermaid
sequenceDiagram
    participant C as Customer
    participant R as Retell.ai
    participant W as Webhook
    participant Q as Queue
    participant A as AppointmentService
    participant D as Database
    participant Cal as Cal.com
    participant E as Email/SMS
    
    C->>R: 📞 Calls Business
    R->>R: 🤖 AI Conversation
    R->>W: 📤 Send Call Data
    W->>W: 🔐 Verify Signature
    W->>Q: 📋 Queue Job
    Q->>A: ⚙️ Process Appointment
    A->>D: 💾 Create/Update Records
    A->>Cal: 📅 Book Calendar Slot
    A->>E: 📧 Send Confirmation
    E->>C: ✅ Receive Confirmation
```

## 🏛️ Domain Model

```mermaid
classDiagram
    class Company {
        +id: UUID
        +name: string
        +settings: JSON
        +retell_api_key: string
        +calcom_api_key: string
    }
    
    class Branch {
        +id: UUID
        +company_id: UUID
        +name: string
        +phone: string
        +address: string
        +working_hours: JSON
    }
    
    class Staff {
        +id: UUID
        +branch_id: UUID
        +name: string
        +email: string
        +calendar_link: string
    }
    
    class Service {
        +id: UUID
        +company_id: UUID
        +name: string
        +duration: int
        +price: decimal
    }
    
    class Customer {
        +id: UUID
        +company_id: UUID
        +phone: string
        +name: string
        +email: string
    }
    
    class Appointment {
        +id: UUID
        +customer_id: UUID
        +staff_id: UUID
        +service_id: UUID
        +start_time: datetime
        +status: enum
    }
    
    class Call {
        +id: UUID
        +company_id: UUID
        +phone_number: string
        +duration: int
        +transcript: text
        +recording_url: string
    }
    
    Company "1" --> "*" Branch
    Company "1" --> "*" Service
    Company "1" --> "*" Customer
    Branch "1" --> "*" Staff
    Customer "1" --> "*" Appointment
    Staff "1" --> "*" Appointment
    Service "1" --> "*" Appointment
    Company "1" --> "*" Call
    Appointment "1" --> "0..1" Call
```

## 🔧 Service Layer Architecture

```mermaid
graph TB
    subgraph "Controllers"
        AC[AppointmentController]
        CC[CustomerController]
        WC[WebhookController]
    end
    
    subgraph "Services"
        AS[AppointmentService]
        CS[CustomerService]
        RS[RetellService]
        CalS[CalcomService]
        ES[EmailService]
        MCPS[MCPService]
    end
    
    subgraph "Repositories"
        AR[AppointmentRepository]
        CR[CustomerRepository]
        SR[StaffRepository]
    end
    
    subgraph "External APIs"
        RetellAPI[Retell API]
        CalAPI[Cal.com API]
        TwilioAPI[Twilio API]
    end
    
    AC --> AS
    CC --> CS
    WC --> RS
    
    AS --> AR
    AS --> CalS
    AS --> ES
    CS --> CR
    RS --> RetellAPI
    CalS --> CalAPI
    ES --> TwilioAPI
    
    AS --> MCPS
    CS --> MCPS
    RS --> MCPS
```

## 🔐 Security Layers

```mermaid
graph LR
    subgraph "Request Flow"
        Req[📨 Request]
        MW1[🛡️ Rate Limit]
        MW2[🔐 Auth]
        MW3[🏢 Tenant Scope]
        MW4[✅ Validation]
        MW5[🚨 Threat Detection]
        Ctrl[🎯 Controller]
    end
    
    Req --> MW1
    MW1 --> MW2
    MW2 --> MW3
    MW3 --> MW4
    MW4 --> MW5
    MW5 --> Ctrl
    
    MW1 -.->|Block| Reject1[❌ 429 Too Many]
    MW2 -.->|Block| Reject2[❌ 401 Unauthorized]
    MW3 -.->|Block| Reject3[❌ 403 Forbidden]
    MW4 -.->|Block| Reject4[❌ 422 Invalid]
    MW5 -.->|Block| Reject5[❌ 403 Threat]
```

## 📊 Database Schema (Core Tables)

```mermaid
erDiagram
    companies ||--o{ branches : has
    companies ||--o{ customers : has
    companies ||--o{ services : offers
    companies ||--o{ calls : receives
    
    branches ||--o{ staff : employs
    branches ||--o{ working_hours : defines
    
    staff ||--o{ appointments : handles
    staff ||--o{ staff_services : provides
    
    customers ||--o{ appointments : books
    
    services ||--o{ appointments : used_in
    services ||--o{ staff_services : offered_by
    
    appointments ||--o| calls : created_from
    
    companies {
        uuid id PK
        string name
        string subdomain UK
        json settings
        timestamp created_at
    }
    
    branches {
        uuid id PK
        uuid company_id FK
        string name
        string phone
        string address
        json working_hours
    }
    
    customers {
        uuid id PK
        uuid company_id FK
        string phone UK
        string name
        string email
    }
    
    appointments {
        uuid id PK
        uuid customer_id FK
        uuid staff_id FK
        uuid service_id FK
        datetime start_time
        datetime end_time
        enum status
        uuid call_id FK
    }
```

## 🚀 Deployment Architecture

```mermaid
graph TB
    subgraph "Internet"
        CF[☁️ Cloudflare<br/>CDN/WAF]
    end
    
    subgraph "Netcup Server"
        Nginx[🌐 Nginx<br/>Reverse Proxy]
        
        subgraph "Application"
            PHP[🐘 PHP-FPM<br/>8.3]
            Laravel[🚀 Laravel<br/>Application]
        end
        
        subgraph "Services"
            MySQL[(💾 MySQL<br/>8.0)]
            Redis[(⚡ Redis<br/>7.0)]
            Horizon[📊 Horizon<br/>Queue Worker]
        end
        
        subgraph "Monitoring"
            Prometheus[📈 Prometheus]
            Grafana[📊 Grafana]
        end
    end
    
    CF --> Nginx
    Nginx --> PHP
    PHP --> Laravel
    Laravel --> MySQL
    Laravel --> Redis
    Laravel --> Horizon
    Horizon --> Redis
    Prometheus --> Laravel
    Prometheus --> MySQL
    Prometheus --> Redis
    Grafana --> Prometheus
```

## 💡 MCP Server Integration

```mermaid
graph LR
    subgraph "Service Layer"
        Service[🔧 Service<br/>UsesMCPServers]
    end
    
    subgraph "MCP Discovery"
        Discovery[🔍 MCPAutoDiscovery]
        Registry[📚 ServerRegistry]
    end
    
    subgraph "MCP Servers"
        MCP1[📅 CalcomMCP]
        MCP2[🤖 RetellMCP]
        MCP3[💾 DatabaseMCP]
        MCP4[📧 EmailMCP]
        MCP5[💳 StripeMCP]
    end
    
    Service --> Discovery
    Discovery --> Registry
    Registry --> MCP1
    Registry --> MCP2
    Registry --> MCP3
    Registry --> MCP4
    Registry --> MCP5
    
    Service -.->|"executeMCPTask()"| MCP1
    Service -.->|Auto-Select| MCP2
```

---

## 🎯 Quick Navigation

- [Back to Main](../CLAUDE.md)
- [API Documentation](./API_GUIDE.md)
- [Testing Guide](./TESTING_GUIDE.md)
- [Deployment Guide](./DEPLOYMENT.md)

---

<div align="center">
<i>Diagrams are auto-generated from code structure</i>
</div>