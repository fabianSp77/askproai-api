# 🧠 Claude Context Summary - AskProAI

> **Purpose**: Immediate context for Claude at session start
> **Last Updated**: 2025-06-28
> **Reading Time**: < 3 minutes

## 🎯 Project Overview
AskProAI is an AI-powered appointment booking platform that connects phone calls (Retell.ai) to calendar systems (Cal.com). Multi-tenant SaaS for German service businesses.

## 🚀 NEW: Best Practices 2025 Implementation

### ✅ What's Already Implemented
1. **Automatic MCP Server Discovery**
   - `MCPAutoDiscoveryService` - Finds best MCP server for any task
   - `UsesMCPServers` trait - Add to any service for auto-MCP
   - Command: `php artisan mcp:discover "your task"`

2. **Complete Data Flow Tracking**
   - `DataFlowLogger` - Tracks all external API calls
   - Correlation IDs for end-to-end debugging
   - Auto-generates sequence diagrams
   - Command: `php artisan dataflow:list`

3. **System Self-Understanding**
   - `SystemUnderstandingService` - Analyzes existing code
   - `ImpactAnalyzer` - Predicts deployment risks
   - Prevents breaking changes automatically
   - Command: `php artisan analyze:impact`

4. **Code Quality Automation**
   - Laravel Pint (formatting) - `composer pint`
   - PHPStan Level 8 (analysis) - `composer stan`
   - Git hooks for pre-commit checks
   - Documentation auto-update reminders

## 🛠️ Available MCP Servers

### Internal (Project-Specific)
```
CalcomMCPServer         - Calendar operations
RetellMCPServer         - AI phone integration
DatabaseMCPServer       - Safe DB queries
WebhookMCPServer        - Event processing
QueueMCPServer          - Job management
StripeMCPServer         - Payments
KnowledgeMCPServer      - Knowledge base
AppointmentMCPServer    - Bookings
CustomerMCPServer       - CRM operations
CompanyMCPServer        - Multi-tenant
BranchMCPServer         - Locations
RetellConfigurationMCP  - AI config
RetellCustomFunctionMCP - Custom AI
AppointmentManagementMCP- Advanced booking
SentryMCPServer         - Error tracking
```

### External (Claude Tools)
- `mcp__context7__resolve-library-id` - Find library docs
- `mcp__context7__get-library-docs` - Get API documentation
- `TodoWrite` / `TodoRead` - Task management
- `sequential_thinking` - Problem solving (active)
- `taskmaster_ai` - Complex projects (set MCP_TASKMASTER_ENABLED=true)

## 📊 Data Flow Architecture
```
[Customer Phone] → [Retell.ai] → [Webhook] → [AskProAI]
                                                ↓
[Cal.com] ← [Appointment] ← [Processing] ← [Queue Job]
```

**Key Points:**
- All flows tracked with correlation IDs
- Every external call logged in `api_call_logs`
- Webhook deduplication via `webhook_events`
- Circuit breakers on all external services

## 🔥 Common Issues & Quick Fixes

### Top 3 Problems
1. **"No calls imported"**
   ```bash
   php artisan horizon  # Start queue
   # Then click "Anrufe abrufen" in admin
   ```

2. **"Database access denied"**
   ```bash
   rm -f bootstrap/cache/config.php
   php artisan config:cache
   ```

3. **"Webhook signature invalid"**
   ```bash
   # Check .env: RETELL_WEBHOOK_SECRET
   # Verify in Retell dashboard
   ```

## 🎨 Development Workflow

### Before ANY Task
```bash
# 1. Discover best MCP server
php artisan mcp:discover "describe your task"

# 2. Understand existing code
php artisan analyze:component ComponentName

# 3. Start data flow tracking
php artisan dataflow:start
```

### During Development
- Use `$this->executeMCPTask()` in services with UsesMCPServers trait
- Track external calls with DataFlowLogger
- Check impact with `php artisan analyze:impact`

### Before Committing
```bash
# Quality checks (automatic via git hooks)
composer quality  # Runs all checks

# Documentation check
php artisan docs:check-updates
```

## 📁 Key File Locations
```
/app/Services/MCP/          - All MCP servers
/app/Services/Analysis/     - Code analyzers
/app/Services/DataFlow/     - Flow tracking
/app/Traits/UsesMCPServers.php - Auto-MCP trait
/.githooks/                 - Git automation
/CLAUDE.md                  - Full documentation
/ERROR_PATTERNS.md          - Error solutions
/BEST_PRACTICES_IMPLEMENTATION.md - New features
```

## 🚨 Critical Credentials
```bash
# Database
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# SSH
ssh hosting215275@hosting215275.ae83d.netcup.net
```

## 🎯 Current Focus Areas
1. **Maximum MCP utilization** - Always check MCP servers first
2. **Data flow transparency** - Track every external call
3. **Zero breaking changes** - Analyze before modifying
4. **Automatic quality** - Let tools catch errors

## 📝 Quick Commands Reference
```bash
# MCP Operations
php artisan mcp:discover "task description"
php artisan mcp:health
php artisan mcp:list

# Data Flow Analysis
php artisan dataflow:list
php artisan dataflow:diagram <correlation-id>

# Code Analysis
php artisan analyze:impact --git
php artisan analyze:component App\\Services\\BookingService

# Documentation
php artisan docs:check-updates --auto-fix
php artisan docs:health

# Quality
composer quality       # All checks
composer pint         # Format
composer stan         # Analyze
composer test         # Tests
```

## 🔗 Essential Links
- **Main Docs**: [CLAUDE.md](./CLAUDE.md)
- **Quick Ref**: [CLAUDE_QUICK_REFERENCE.md](./CLAUDE_QUICK_REFERENCE.md)
- **Errors**: [ERROR_PATTERNS.md](./ERROR_PATTERNS.md)
- **Best Practices**: [BEST_PRACTICES_IMPLEMENTATION.md](./BEST_PRACTICES_IMPLEMENTATION.md)
- **5-Min Setup**: [5-MINUTEN_ONBOARDING_PLAYBOOK.md](./5-MINUTEN_ONBOARDING_PLAYBOOK.md)

---

💡 **Remember**: The system now automatically discovers MCP servers, tracks data flows, and prevents breaking changes. Focus on building features - the tools handle the rest!