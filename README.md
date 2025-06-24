# AskProAI - AI-Powered Appointment Booking Platform

AskProAI is an enterprise-grade SaaS platform that automatically answers incoming customer calls and independently schedules appointments. Through seamless integration of AI phone services (Retell.ai) and online calendar systems (Cal.com), it provides a complete end-to-end solution for appointment-based businesses.

## 🚀 Key Features

### Core Functionality
- **24/7 AI Phone Assistant** - Automated call answering in 30+ languages
- **Intelligent Appointment Booking** - Natural conversation flow with context-aware responses
- **Multi-Tenant Architecture** - Complete data isolation for enterprise clients
- **Multi-Location Support** - Branch-level management with independent settings
- **Real-Time Availability** - Live calendar integration with instant booking confirmation

### Business Features
- **Customer Management** - Automatic customer creation and duplicate detection
- **Staff Assignment** - Smart routing to available staff members
- **Service Catalog** - Flexible service definitions with duration and pricing
- **Email Notifications** - Automated confirmations and reminders
- **Analytics Dashboard** - Call statistics, booking rates, and ROI metrics

### Technical Features
- **Webhook Processing** - Real-time event handling with signature verification
- **Queue Management** - Asynchronous job processing with Laravel Horizon
- **API Gateway** - RESTful API with rate limiting and authentication
- **Security Layer** - Field-level encryption, threat detection, and audit logging
- **Performance Optimization** - Query caching, eager loading, and database indexing

## 🛠 Technology Stack

### Backend
- **Framework**: Laravel 11.x
- **PHP Version**: 8.3.22
- **Database**: MySQL/MariaDB
- **Cache**: Redis
- **Queue**: Redis + Laravel Horizon
- **Search**: Laravel Scout (optional)

### Integrations
- **Phone AI**: Retell.ai (primary)
- **Calendar**: Cal.com v2 API
- **Payments**: Stripe (subscription billing)
- **Monitoring**: Sentry (error tracking)
- **Analytics**: Custom metrics + Prometheus

### Frontend
- **Admin Panel**: Filament 3.x (Laravel admin)
- **Styling**: Tailwind CSS
- **JavaScript**: Alpine.js + Livewire
- **Icons**: Heroicons

## 📋 System Requirements

- PHP >= 8.3
- MySQL >= 8.0 or MariaDB >= 10.6
- Redis >= 6.0
- Composer >= 2.0
- Node.js >= 18.0
- SSL certificate (required for webhooks)

## 🏗 Architecture Overview

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Customer  │────▶│  Retell.ai  │────▶│   Webhook   │
│    Phone    │     │  AI Agent   │     │  Processor  │
└─────────────┘     └─────────────┘     └─────────────┘
                                               │
                                               ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Cal.com   │◀────│   Booking   │◀────│    Queue    │
│  Calendar   │     │   Service   │     │   Worker    │
└─────────────┘     └─────────────┘     └─────────────┘
```

## 🚦 Production Status: 85% Ready

### ✅ What's Working
- Core booking flow (phone → appointment)
- Multi-tenant data isolation
- Cal.com integration (v2 API)
- Retell.ai phone system
- Admin dashboard (Filament)
- Email notifications
- Basic analytics
- Webhook processing

### 🚧 In Development
- WhatsApp integration
- SMS notifications
- Customer self-service portal
- Advanced analytics
- Mobile app API
- Voice callback system

### ⚠️ Known Issues
- Database needs consolidation (from 119 to ~25 tables)
- Some debug routes need removal
- Performance optimization needed for high load
- Documentation consolidation in progress

## 📚 Documentation

- **Setup Guide**: [docs/quickstart.md](docs/quickstart.md)
- **API Reference**: [docs/api/reference.md](docs/api/reference.md)
- **Architecture**: [docs/architecture/overview.md](docs/architecture/overview.md)
- **Deployment**: [docs/deployment/guide.md](docs/deployment/guide.md)
- **Troubleshooting**: [docs/troubleshooting.md](docs/troubleshooting.md)

## 🔗 Quick Links

### Production
- **Admin Dashboard**: https://api.askproai.de/admin
- **API Endpoint**: https://api.askproai.de/api/v2
- **Documentation**: https://api.askproai.de/docs

### Support
- **GitHub Issues**: [Report bugs or request features](https://github.com/askproai/api-gateway/issues)
- **Email Support**: support@askproai.de
- **Developer Docs**: See `/docs` directory

## 🤝 Contributing

Please read our [Contributing Guide](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 📄 License

This project is proprietary software. All rights reserved by AskProAI GmbH.

---

*Last updated: June 23, 2025*
