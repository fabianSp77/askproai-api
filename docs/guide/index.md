# Introduction

Welcome to the **AskPro API Gateway** documentation. This platform provides a comprehensive solution for AI-powered appointment management and service desk operations.

## What is AskPro API Gateway?

AskPro API Gateway is a multi-tenant SaaS platform that combines:

- **AI Voice Agent** (Retell.ai) for intelligent phone conversations
- **Appointment Management** (Cal.com) for scheduling
- **Service Gateway** for ticket/case management
- **Multi-Tenant Architecture** for enterprise deployments

## Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    VOICE LAYER                          │
│              Retell.ai Voice Agent                      │
└────────────────────────┬────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────┐
│                  API GATEWAY                            │
│  Laravel 11 | Filament 3 | Multi-Tenant Isolation      │
└────────────────────────┬────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         ▼               ▼               ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│ Appointment │  │   Service   │  │   Customer  │
│  Management │  │   Gateway   │  │   Portal    │
└─────────────┘  └─────────────┘  └─────────────┘
```

## Key Features

### For End Users
- Natural voice conversations for booking appointments
- Service desk ticket creation via phone
- Automated confirmations and reminders

### For Operators
- Real-time dashboard with SLA monitoring
- Case management with escalation rules
- Multi-channel output (Email, Webhook, Hybrid)

### For Developers
- RESTful API with OpenAPI documentation
- Webhook integrations for external systems
- Comprehensive test coverage (104+ tests)

## Getting Started

1. **[Quick Start](/guide/quick-start)** - Set up your first integration
2. **[Architecture](/guide/architecture)** - Understand the system design
3. **[Service Gateway](/guide/service-gateway)** - Configure case management

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 11, PHP 8.2 |
| Admin UI | Filament 3 |
| Database | PostgreSQL / MySQL |
| Cache | Redis |
| Voice AI | Retell.ai |
| Scheduling | Cal.com |
| Queue | Laravel Horizon |
