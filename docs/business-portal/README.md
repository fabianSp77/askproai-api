# 📚 Business Portal Documentation Hub

> **Comprehensive documentation for the AskProAI Business Portal**  
> Version 2.0 | Last Updated: 2025-07-10

## 🚀 Quick Navigation

### Main Documentation
- [**📖 Complete Business Portal Documentation**](../../BUSINESS_PORTAL_DOCUMENTATION.md) - Start here!

### Module Deep Dives
1. [**📊 Dashboard Module**](./01-DASHBOARD-MODULE.md) - Real-time metrics and insights
2. [**📞 Calls Module**](./02-CALLS-MODULE.md) - Call management and analytics
3. [**🔌 API Architecture**](./03-API-ARCHITECTURE.md) - Technical API guide

### Additional Resources
- [**Appointments Module**](./04-APPOINTMENTS-MODULE.md) - Coming soon
- [**Team Management**](./05-TEAM-MODULE.md) - Coming soon
- [**Analytics & Reporting**](./06-ANALYTICS-MODULE.md) - Coming soon
- [**Billing & Payments**](./07-BILLING-MODULE.md) - Coming soon
- [**Settings & Configuration**](./08-SETTINGS-MODULE.md) - Coming soon
- [**Security Guide**](./09-SECURITY-GUIDE.md) - Coming soon
- [**Performance Optimization**](./10-PERFORMANCE-GUIDE.md) - Coming soon

## 🎯 Documentation Goals

This documentation serves as:
1. **Single Source of Truth** - All Business Portal information in one place
2. **Developer Reference** - Technical implementation details
3. **API Documentation** - Complete API reference
4. **Best Practices** - Coding standards and patterns
5. **Troubleshooting Guide** - Common issues and solutions

## 📋 Documentation Structure

```
business-portal/
├── README.md                          # This file - Documentation hub
├── 01-DASHBOARD-MODULE.md            # Dashboard module deep dive
├── 02-CALLS-MODULE.md                # Calls module complete guide
├── 03-API-ARCHITECTURE.md           # API design and architecture
├── 04-APPOINTMENTS-MODULE.md        # Appointments management
├── 05-TEAM-MODULE.md                 # Team and permissions
├── 06-ANALYTICS-MODULE.md            # Analytics and reporting
├── 07-BILLING-MODULE.md              # Billing and payments
├── 08-SETTINGS-MODULE.md             # Settings and configuration
├── 09-SECURITY-GUIDE.md              # Security best practices
└── 10-PERFORMANCE-GUIDE.md           # Performance optimization
```

## 🔍 How to Use This Documentation

### For New Developers
1. Start with the [main documentation](../../BUSINESS_PORTAL_DOCUMENTATION.md)
2. Review the [API Architecture](./03-API-ARCHITECTURE.md)
3. Explore specific modules as needed

### For Specific Tasks
- **Building a new feature?** Check the relevant module documentation
- **Working with APIs?** See [API Architecture](./03-API-ARCHITECTURE.md)
- **Debugging issues?** Check troubleshooting sections in each module
- **Optimizing performance?** Review performance guides

### For Context in AI Tools
When using Claude or other AI assistants:
1. Share the main documentation file for overview
2. Include specific module docs for detailed work
3. Reference API architecture for backend changes
4. Use code examples from documentation

## 🛠️ Technology Stack Quick Reference

### Frontend
- **React 18.2** with TypeScript
- **Tailwind CSS** for styling
- **shadcn/ui** components
- **Recharts** for data visualization
- **Vite** build tool

### Backend
- **Laravel 11** PHP framework
- **MySQL 8.0** database
- **Redis** for caching/queues
- **Horizon** queue management
- **Filament 3.x** admin panel

### Integrations
- **Retell.ai** - AI phone system
- **Cal.com** - Calendar integration
- **Stripe** - Payment processing
- **DeepL** - Translations

## 📞 Key Endpoints

### Production
- Business Portal: `https://business.askproai.de`
- API Base: `https://api.askproai.de/business/api`
- Admin Panel: `https://api.askproai.de/admin`

### API Versions
- v1: `/business/api/v1/*` (Legacy)
- v2: `/business/api/*` (Current)

## 🔐 Access & Permissions

### Portal User Roles
- **Admin** - Full access to all features
- **Manager** - Team and analytics access
- **User** - Basic access to calls/appointments
- **Viewer** - Read-only access

### Key Permissions
- `calls.view_own` / `calls.view_all`
- `appointments.create` / `appointments.edit`
- `team.manage`
- `billing.manage`
- `analytics.export`

## 📈 Performance Targets

- Page Load: < 1 second
- API Response: < 200ms (p95)
- Real-time Updates: < 500ms
- Dashboard Refresh: < 2 seconds

## 🚨 Quick Troubleshooting

### Common Issues
1. **Authentication Problems** → Check session configuration
2. **Slow Performance** → Review caching strategy
3. **Missing Data** → Verify tenant scope
4. **API Errors** → Check rate limits

### Debug Commands
```bash
# Check application health
php artisan about

# Clear all caches
php artisan optimize:clear

# Monitor queues
php artisan horizon

# View logs
tail -f storage/logs/laravel.log
```

## 🤝 Contributing to Documentation

### Guidelines
1. Keep documentation up-to-date with code changes
2. Include code examples where helpful
3. Add troubleshooting sections for common issues
4. Update version numbers and dates
5. Maintain consistent formatting

### Documentation Standards
- Use clear, concise language
- Include practical examples
- Add diagrams for complex flows
- Keep technical accuracy
- Update table of contents

## 📝 Changelog

### Recent Updates
- **2025-07-10**: Initial comprehensive documentation created
- **2025-07-10**: Added Dashboard, Calls, and API modules
- **2025-07-10**: Structured for optimal AI context usage

### Upcoming Documentation
- Appointments Module Guide
- Team Management Deep Dive
- Analytics & Reporting Guide
- Complete Security Documentation
- Performance Tuning Guide

---

<center>

**Need help?** Contact the development team or check the main [CLAUDE.md](../../CLAUDE.md) for system-wide documentation.

</center>