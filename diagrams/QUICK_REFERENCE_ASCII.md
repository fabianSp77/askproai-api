# 🎨 ASCII Art Quick Reference Diagrams

## System Overview
```
┌─────────────────────────────────────────────────────────────────┐
│                         AskProAI Platform                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  📞 Phone ──> 🤖 Retell.ai ──> 🪝 Webhook ──> 📅 Appointment   │
│                                                                  │
│  Key Components:                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐            │
│  │   Laravel   │  │   Horizon   │  │    Redis    │            │
│  │  Framework  │  │   Queue     │  │  Cache/Q    │            │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘            │
│         │                 │                 │                    │
│         └─────────────────┴─────────────────┘                   │
│                           │                                      │
│                    ┌──────▼──────┐                              │
│                    │   MariaDB   │                              │
│                    │  Database   │                              │
│                    └─────────────┘                              │
└─────────────────────────────────────────────────────────────────┘
```

## Request Flow
```
User Request Flow:
─────────────────

[Browser] ──HTTPS──> [Cloudflare] ──> [Nginx] ──> [PHP-FPM] ──> [Laravel]
                           │                            │             │
                           ├─ WAF Protection           │             │
                           ├─ DDoS Shield              │             ├─> [Redis]
                           └─ CDN Cache                │             └─> [MariaDB]
                                                       │
                                                       └─> Static Files
```

## Database Relations
```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Companies  │────<│   Branches  │────<│    Staff    │
└─────────────┘     └─────────────┘     └─────────────┘
       │                    │                   │
       │                    │                   │
       ▼                    ▼                   ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Customers  │     │Appointments │>────│  Services   │
└─────────────┘     └─────────────┘     └─────────────┘
       │                    ▲
       │                    │
       ▼                    │
┌─────────────┐            │
│    Calls    │────────────┘
└─────────────┘
```

## Authentication Flow
```
Login Process:
─────────────

     ┌──────┐
     │ User │
     └──┬───┘
        │ 1. Credentials
        ▼
   ┌─────────┐
   │  Login  │──── 2. Verify ───> Database
   │  Form   │                         │
   └────┬────┘                         │
        │ 3. Valid?                    │
        ▼                              ▼
   ┌─────────┐                  ┌─────────┐
   │   2FA   │<─── 4. Check ────│  User   │
   │  Check  │                  │ Record  │
   └────┬────┘                  └─────────┘
        │ 5. Create Session
        ▼
   ┌─────────┐
   │  Redis  │──── 6. Store ───> Session
   │  Cache  │
   └─────────┘
```

## Call Processing Pipeline
```
Phone Call to Appointment:
─────────────────────────

1. INCOMING CALL        2. AI PROCESSING       3. DATA EXTRACTION
   ┌─────────┐            ┌─────────┐            ┌─────────┐
   │ Phone   │            │ Retell  │            │ Extract │
   │ Network │ ─────────> │   AI    │ ─────────> │  Data   │
   └─────────┘            └─────────┘            └─────────┘
                                                       │
                                                       ▼
6. CONFIRMATION         5. BOOKING              4. AVAILABILITY
   ┌─────────┐            ┌─────────┐            ┌─────────┐
   │  Email  │ <───────── │ Create  │ <───────── │ Cal.com │
   │  Send   │            │ Appoint │            │  Check  │
   └─────────┘            └─────────┘            └─────────┘
```

## Deployment Pipeline
```
CI/CD Flow:
──────────

[Dev] ──push──> [Git] ──trigger──> [CI] ──test──> [Build] ──deploy──> [Prod]
                                     │                          │
                                     ├─ Lint                   ├─> Staging
                                     ├─ Test                   └─> Production
                                     └─ Security
```

## Network Topology
```
┌──────────────────────────── INTERNET ────────────────────────────┐
│                                                                   │
│    Users                 CDN                    Services          │
│      │                    │                        │              │
└──────┼────────────────────┼────────────────────────┼──────────────┘
       │                    │                        │
       ▼                    ▼                        ▼
┌─────────────┐      ┌─────────────┐         ┌─────────────┐
│  Cloudflare │      │   Retell.ai │         │   Stripe    │
│     WAF     │      │   Cal.com   │         │    SMTP     │
└──────┬──────┘      └─────────────┘         └─────────────┘
       │
   ┌───▼───┐         ┌─────────────┐         ┌─────────────┐
   │ Nginx │ ──────> │   Laravel   │ ──────> │   MariaDB   │
   │  :443 │         │  PHP 8.3    │         │    :3306    │
   └───────┘         └─────────────┘         └─────────────┘
                            │
                     ┌──────▼──────┐
                     │    Redis    │
                     │    :6379    │
                     └─────────────┘
```

## Status Codes Reference
```
API Response Codes:
──────────────────

2xx Success           4xx Client Error        5xx Server Error
─────────────         ─────────────────       ─────────────────
200 OK                400 Bad Request         500 Internal Error
201 Created           401 Unauthorized        502 Bad Gateway
204 No Content        403 Forbidden           503 Unavailable
                      404 Not Found           504 Timeout
                      422 Validation Err
                      429 Rate Limited
```

## Queue Priority Levels
```
Queue Processing:
────────────────

HIGH PRIORITY (< 1 min)          DEFAULT (< 5 min)           LOW (< 1 hour)
┌─────────────────┐              ┌─────────────────┐         ┌─────────────────┐
│ • Payments      │              │ • Call Summary  │         │ • Reports       │
│ • Appointments  │              │ • Welcome Email │         │ • Analytics     │
│ • Auth Emails   │              │ • Notifications │         │ • Cleanup       │
└─────────────────┘              └─────────────────┘         └─────────────────┘
         │                                │                            │
         └────────────────────────────────┴────────────────────────────┘
                                         │
                                    ┌────▼────┐
                                    │ Horizon │
                                    │ Workers │
                                    └─────────┘
```

## Performance Metrics
```
Target Response Times:
─────────────────────

API Endpoints         < 200ms   ████████████████░░░░
Webhook Processing    < 500ms   ████████████████████
Page Load            < 1000ms   ████████████████████
Database Queries      < 100ms   ████████░░░░░░░░░░░░
Cache Hit Rate         > 90%    ██████████████████░░
```

## Security Layers
```
Security Stack:
──────────────

Level 1: Cloudflare     [DDoS Protection, WAF, Rate Limiting]
           │
Level 2: Network        [Firewall, IP Whitelist, Port Security]
           │
Level 3: Application    [CSRF, XSS Protection, Input Validation]
           │
Level 4: Authentication [2FA, Session Management, API Keys]
           │
Level 5: Data           [Encryption at Rest, TLS in Transit]
```

## Monitoring Dashboard
```
┌─────────────────────────────────────────────────────────────────┐
│                      System Health Monitor                       │
├──────────────┬──────────────┬──────────────┬───────────────────┤
│ CPU Usage    │ Memory       │ Disk Usage   │ Network           │
│ ████░░░ 45%  │ ██████░ 72% │ ███░░░░ 35% │ In:  125 MB/s    │
│              │              │              │ Out: 89 MB/s      │
├──────────────┴──────────────┴──────────────┴───────────────────┤
│ Services     Status    Uptime    Requests/min   Errors         │
│ ─────────    ──────    ──────    ────────────   ──────         │
│ Web          ● UP      99.99%    1,234           2              │
│ API          ● UP      99.98%    5,678           12             │
│ Database     ● UP      100.0%    8,901           0              │
│ Redis        ● UP      100.0%    12,345          0              │
│ Queue        ● UP      99.95%    3,456           5              │
└─────────────────────────────────────────────────────────────────┘
```

## Common Commands
```
Essential Commands:
──────────────────

Development                     Production                    Debugging
───────────                     ──────────                    ─────────
php artisan serve              php artisan migrate           php artisan tinker
npm run dev                    php artisan optimize          tail -f storage/logs/*
php artisan test               php artisan horizon           php artisan queue:listen
                               php artisan queue:restart      php artisan cache:clear

Database                       Deployment                    Monitoring
────────                       ──────────                    ──────────
php artisan migrate:fresh      git pull origin main          php artisan horizon:status
php artisan db:seed            composer install --no-dev     php artisan queue:monitor
php artisan tinker             npm run build                 htop
```

---

> 💡 **Tip**: These ASCII diagrams are terminal-friendly and can be used in documentation, README files, or code comments.