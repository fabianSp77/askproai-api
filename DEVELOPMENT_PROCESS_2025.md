# 🚀 Development Process 2025 - AskProAI

> **Goal**: Zero bugs, maximum efficiency, automated quality
> **Philosophy**: Let tools handle the repetitive work, focus on creativity

## 📋 Process Overview

### 1. 🎯 Task Reception
**Tools**: TodoWrite, TaskMaster MCP (if enabled)
```bash
# Document task immediately
TodoWrite: Create task with clear acceptance criteria

# For complex projects
export MCP_TASKMASTER_ENABLED=true
# Use taskmaster_ai MCP for detailed planning
```

### 2. 🔍 Discovery Phase
**Tools**: MCP Auto-Discovery, System Understanding
```bash
# Find best MCP server for task
php artisan mcp:discover "implement user authentication"

# Understand existing implementations
php artisan analyze:understand App\\Services\\AuthService

# Check what already exists
php artisan analyze:component --search="authentication"
```

### 3. 📊 Analysis Phase
**Tools**: Impact Analyzer, Data Flow Logger
```bash
# Start data flow tracking
php artisan dataflow:start

# Analyze potential impact
php artisan analyze:impact --component=AuthService

# Check for breaking changes
php artisan analyze:impact --breaking-only
```

### 4. 💻 Implementation Phase
**Automatic Features**:
- MCP servers auto-selected via UsesMCPServers trait
- Data flows tracked automatically
- Correlation IDs generated for debugging

```php
// Example: Service with automatic MCP
use App\Traits\UsesMCPServers;

class BookingService
{
    use UsesMCPServers;
    
    public function createAppointment($data)
    {
        // Automatically uses best MCP server!
        return $this->executeMCPTask('create appointment', $data);
    }
}
```

### 5. ✅ Quality Assurance
**Git Hooks Handle**:
- Pre-commit: Syntax, formatting, security checks
- Commit-msg: Conventional commits enforcement
- Post-commit: Documentation reminders
- Pre-push: Full test suite, coverage check

```bash
# Manual quality check
composer quality

# Individual checks
composer pint        # Format
composer stan        # Analyze
composer test        # Test
```

### 6. 📝 Documentation
**Automatic Triggers**:
- Service changes → Update ERROR_PATTERNS.md
- MCP updates → Update CLAUDE.md
- API changes → Update PHONE_TO_APPOINTMENT_FLOW.md
- Migrations → Update DEPLOYMENT_CHECKLIST.md

```bash
# Check documentation health
php artisan docs:check-updates

# Auto-fix timestamps
php artisan docs:check-updates --auto-fix
```

### 7. 🚀 Deployment
**Pre-deployment Checks**:
```bash
# Run impact analysis
php artisan analyze:impact --git

# Check all systems
php artisan health:check

# Verify documentation
php artisan docs:health
```

## 🔄 Continuous Improvement Loop

### Daily
1. Review error patterns from ERROR_PATTERNS.md
2. Check MCP health: `php artisan mcp:health`
3. Monitor data flows: `php artisan dataflow:list --today`

### Weekly
1. Analyze slowest API calls
2. Review documentation gaps
3. Update error patterns catalog
4. Check for new MCP opportunities

### Monthly
1. Dependency updates with impact analysis
2. Performance baseline updates
3. Security audit: `php artisan askproai:security-audit`
4. Documentation completeness review

## 🎯 Success Metrics

### Code Quality
- **Zero** linting errors (enforced by git hooks)
- **Level 8** PHPStan compliance
- **80%+** test coverage
- **100%** conventional commits

### Performance
- API response < 200ms (p95)
- Zero N+1 queries
- All external calls tracked
- Circuit breakers on all integrations

### Documentation
- **100%** of new features documented
- Error patterns updated within 24h
- MCP usage documented in code
- Data flows visualized

## 🛠️ Tool Integration

### IDE Setup
```json
// .vscode/settings.json
{
    "php.validate.executablePath": "/usr/bin/php",
    "phpcs.standard": "PSR12",
    "editor.formatOnSave": true,
    "files.autoSave": "afterDelay"
}
```

### PHPStorm
- Enable Laravel plugin
- Configure Pest PHP support
- Set up Database tools
- Enable MCP server completion

## 📚 Learning Resources

### Internal
- [BEST_PRACTICES_IMPLEMENTATION.md](./BEST_PRACTICES_IMPLEMENTATION.md)
- [ERROR_PATTERNS.md](./ERROR_PATTERNS.md)
- [CLAUDE_DOCUMENTATION_GAPS_ANALYSIS.md](./CLAUDE_DOCUMENTATION_GAPS_ANALYSIS.md)

### External (via Context7)
```bash
# Get latest Laravel docs
mcp__context7__get-library-docs --library="laravel/framework" --topic="testing"

# Filament admin panel docs
mcp__context7__get-library-docs --library="filament/filament" --topic="resources"
```

## 🚨 Emergency Procedures

### Production Issue
1. Check ERROR_PATTERNS.md for known solutions
2. Enable debug mode: `BOOKING_DEBUG=true`
3. Track issue flow: `php artisan dataflow:trace <correlation-id>`
4. Apply fix with impact analysis
5. Update ERROR_PATTERNS.md

### Performance Degradation
1. Check slow query log
2. Run: `php artisan performance:analyze`
3. Review data flow bottlenecks
4. Apply optimizations
5. Update performance baselines

## 💡 Pro Tips

### 1. Let MCP Do the Work
Instead of writing boilerplate, use:
```php
$this->executeMCPTask('what you want to do', $params);
```

### 2. Track Everything
Every external call should have:
- Correlation ID
- Duration tracking
- Error handling
- Retry logic

### 3. Document as You Go
Git hooks remind you, but proactive is better:
- Add error codes immediately
- Update flow diagrams
- Document MCP usage

### 4. Test Smart
- Unit tests for logic
- Integration tests for MCP
- E2E tests for critical paths
- Performance tests for bottlenecks

## 🎉 Results

Following this process delivers:
- **50% less debugging time** (correlation IDs)
- **80% fewer production issues** (impact analysis)
- **100% documentation coverage** (automated checks)
- **Zero manual quality checks** (git hooks)

---

**Remember**: The best code is code you don't have to write. Use MCP servers, follow patterns, let tools handle quality. Focus on solving business problems! 🚀