# MCP Quick Reference Card

## 🚀 Essential Commands

### MCP Shortcuts
```bash
# Appointment Management
php artisan mcp book       # or: mcp b     - Book appointment
php artisan mcp cancel     # or: mcp c     - Cancel appointment

# Call Management  
php artisan mcp calls      # or: mcp i     - Import calls
php artisan mcp call-stats                  - View call statistics

# Customer Management
php artisan mcp customer   # or: mcp f     - Find customer
php artisan mcp customer-history            - View history

# Synchronization
php artisan mcp sync                        - Sync menu
php artisan mcp sync-calcom # or: mcp s    - Sync Cal.com  
php artisan mcp gh-notion                   - Sync GitHub-Notion

# Memory Bank
php artisan mcp remember   # or: mcp r     - Save to memory
php artisan mcp recall                      - Search memory

# Health & Testing
php artisan mcp:health                      - Check all servers
php artisan mcp:health --json              - JSON output
php artisan mcp check-integrations # or: h - Test integrations

# Discovery & Help
php artisan mcp discover                    - AI finds best server
php artisan mcp list                        - Show shortcuts
php artisan mcp list --list-all            - Show all shortcuts
```

### Developer Assistant
```bash
# Code Generation
php artisan dev generate   # or: dev gen    - Generate from description
php artisan dev bp --type=service --name=X  - Generate boilerplate

# Code Analysis
php artisan dev analyze --file=path/to/file - Analyze code
php artisan dev similar --file=path/to/file - Find similar code
php artisan dev explain --file=path/to/file - Explain code

# Development Help
php artisan dev suggest                     - Get suggestions
php artisan dev pattern                     - Manage patterns
php artisan dev help                        - Show help
```

## 📊 MCP Dashboard

**URL**: `/admin/mcp-servers`

### Features
- Real-time server status
- Quick actions (Test, Restart, Sync)
- Performance metrics
- Integration monitoring
- Recent activities

## 🔧 Available MCP Servers

### Core Services
- **appointment** - Booking management
- **customer** - Customer data
- **calcom** - Calendar integration
- **retell** - Phone AI integration
- **stripe** - Payment processing

### Infrastructure
- **database** - Safe DB queries
- **queue** - Job management
- **webhook** - Event processing
- **memory_bank** - Context storage

### Integrations
- **github** - Repository management
- **notion** - Documentation
- **figma** - Design assets

## 💡 Common Workflows

### Book Appointment
```bash
php artisan mcp b
# Enter: Phone, Service, Date, Time
```

### Import Recent Calls
```bash
php artisan mcp i
# How many? 50
```

### Find Customer
```bash
php artisan mcp f
# Enter: Phone or name
```

### Generate Service Class
```bash
php artisan dev bp --type=service --name=EmailNotification
# View? yes
# Save? yes
```

### Daily Workflow
```bash
# Morning: Check system health
php artisan mcp:health

# Import overnight calls
php artisan mcp i

# Generate daily report  
php artisan mcp daily-report

# Check for issues
php artisan dev suggest
```

## 🎯 Boilerplate Types

```bash
dev bp --type=TYPE --name=NAME

Types:
- filament-resource    # Admin panel resource
- mcp-server          # MCP server class
- service             # Service + interface
- repository          # Repository pattern
- test               # PHPUnit test
- migration          # Database migration
- api-endpoint       # API controller
- job                # Queue job
- event-listener     # Event system
- notification       # Email/SMS notification
```

## ⚡ Pro Tips

1. **Use aliases**: `mcp b` instead of `mcp book`
2. **Chain commands**: `mcp i && mcp daily-report`
3. **JSON output**: Add `--json` for automation
4. **Discovery**: Let AI find the right server with `mcp discover`
5. **Batch operations**: Many commands support `--limit` parameter

## 🔍 Troubleshooting

```bash
# Clear all caches
php artisan optimize:clear

# Check specific server
php artisan mcp exec --server=retell --tool=health_check

# View recent errors
tail -f storage/logs/laravel.log | grep -i error

# Test webhook
php artisan mcp exec --server=webhook --tool=test_webhook
```

## 📱 Widget Access

**Admin Dashboard Widgets**:
- MCP Quick Actions Widget
- Developer Assistant Widget
- Documentation Health Widget

**Keyboard Shortcuts** (in admin panel):
- `Ctrl+K` - Command palette
- `Ctrl+/` - Search documentation
- `Ctrl+Shift+M` - MCP dashboard

---

**Need help?** Run `php artisan mcp discover` and describe what you want to do!