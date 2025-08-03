# CLAUDE.md - AskProAI Context Guide

> 🚀 **Quick Jump**: [Commands](#commands) | [Current Focus](#current-focus) | [Architecture](#architecture) | [Troubleshooting](#troubleshooting)

## 🎯 Current Focus

### Active Issues
- **Admin Panel Navigation** ([#479](https://github.com/fabianSp77/askproai-api/issues/479)): Only emergency menu clickable
- **TestSprite Integration** ([#480](https://github.com/fabianSp77/askproai-api/issues/480)): MCP setup needed

### Quick Context
```yaml
Project: AskProAI - AI Phone Assistant + Appointment Booking
Stack: Laravel 10 + Filament 3 + Retell.ai + Cal.com
Environment: Production (https://api.askproai.de)
Working Dir: /var/www/api-gateway
```

## 🔧 Commands

<details>
<summary><strong>Essential Daily Commands</strong></summary>

```bash
# Database Access
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# Cache & Build
php artisan optimize:clear
npm run build
php artisan filament:clear-cached-components

# Queue & Monitoring
php artisan horizon
php artisan horizon:status
tail -f storage/logs/laravel.log

# Testing
php artisan test
php artisan test --filter=FeatureName
```
</details>

<details>
<summary><strong>MCP & Subagents</strong></summary>

### Available MCPs
- `CalcomMCP` - Calendar integration
- `RetellMCP` - AI phone service
- `DatabaseMCP` - Safe DB operations
- Context7 (`mcp__context7__*`) - Library docs

### Key Subagents
- `ui-auditor` - UI/UX analysis
- `performance-profiler` - Performance issues
- `engineering/rapid-prototyper` - Quick MVPs
- `test-writer-fixer` - Auto test fixes

[Full Agent List](./.claude/agents/README.md)
</details>

## 🏗️ Architecture

<details>
<summary><strong>Core Structure</strong></summary>

```
Company (Tenant)
├── Branches (Locations)
├── Staff (Employees)
├── Services (Offerings)
├── Customers
└── Appointments
```

### Key Services
- `RetellV2Service` - AI phone integration
- `CalcomV2Service` - Calendar API
- `AppointmentService` - Booking logic
- `CustomerService` - Customer management

### Integration Flow
1. Call → Retell.ai answers
2. AI extracts appointment details
3. Webhook → `/api/retell/webhook-simple`
4. Create/update customer
5. Book in Cal.com
6. Confirm to caller
</details>

## 🚨 Troubleshooting

<details>
<summary><strong>Common Issues</strong></summary>

### Admin Panel Navigation Not Working
```javascript
// Quick test in browser console
document.querySelectorAll('a[href*="/admin"]').forEach(link => {
    const style = window.getComputedStyle(link);
    console.log(`${link.textContent}: ${style.pointerEvents}`);
});
```

### Retell.ai Issues
```bash
# Check webhook processing
tail -f storage/logs/laravel.log | grep -i retell

# Manual import
php manual-retell-import.php

# Test webhook
php test-retell-real-data.php
```

### Database Issues
```bash
# Clear config cache
rm -f bootstrap/cache/config.php
php artisan config:cache

# Check connections
php artisan db:show
```
</details>

## 📚 Extended Documentation

### Quick References
- [Error Patterns](./docs/ERROR_PATTERNS.md) - Common errors & fixes
- [Deployment Guide](./docs/DEPLOYMENT_CHECKLIST.md) - Production deployment
- [API Documentation](./docs/API_DOCUMENTATION.md) - Endpoint reference

### Deep Dives
- [Retell Integration](./docs/integrations/RETELL_INTEGRATION.md)
- [Cal.com Setup](./docs/integrations/CALCOM_SETUP.md)
- [Multi-tenancy](./docs/architecture/MULTI_TENANCY.md)

### Historical/Archive
- [Resolved Issues Archive](./docs/archive/RESOLVED_ISSUES.md)
- [Legacy Documentation](./docs/archive/LEGACY.md)

---

## 🎯 Workflow

### New Task Checklist
1. Create task in `TodoWrite` tool
2. Check for relevant subagents
3. Verify with `analyze:impact` before changes
4. Run tests after implementation
5. Update docs if needed

### Before Deployment
```bash
composer quality       # All checks
php artisan test      # Test suite
php artisan docs:check-updates  # Doc health
```

---

*Last updated: 2025-08-03 | Keep under 40k chars | Archive old content to `/docs/archive/`*