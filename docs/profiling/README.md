# Memory Profiling & Debugging System

Comprehensive production-safe memory profiling infrastructure for Laravel applications experiencing intermittent OOM issues.

## Problem Statement

**Symptom**: Intermittent memory exhaustion (2GB limit) at `Model.php:1605` (newEloquentBuilder)
**Challenge**: Same request succeeds sometimes, fails others (non-deterministic)
**Environment**: Production Laravel + Filament on ARM64
**Constraint**: Must debug without impacting users

## Solution Overview

Multi-layered profiling system with:

1. **Automatic memory tracking** with minimal overhead
2. **Production-safe sampling** to avoid user impact
3. **Detailed state capture** when memory is critical
4. **Systematic analysis tools** to identify root cause
5. **Reproducibility testing** to make errors consistent

---

## 🚀 Quick Start (5 Minutes)

```bash
# 1. Enable profiling (1% sampling, production-safe)
./scripts/enable-memory-profiling.sh light

# 2. Clear cache
php artisan config:clear

# 3. Monitor logs
tail -f storage/logs/laravel.log | grep -i memory

# 4. Analyze after 1 hour
php scripts/analyze-memory-dumps.php --summary
```

**Full setup guide**: [QUICK_START_CHECKLIST.md](QUICK_START_CHECKLIST.md)

---

## 📊 What Gets Profiled

### 1. Request Memory Checkpoints
Tracks memory at key points in request lifecycle:
- Request start
- Before service providers boot
- Response ready
- Request end

**Identifies**: Which phase consumes most memory

### 2. Model Instantiation Tracking
Counts every model creation/retrieval:
- Per-model instance counts
- Memory at first/last instantiation
- Event type (retrieved, created, updated)

**Identifies**: Model inflation, N+1 queries, excessive object creation

### 3. Query Memory Impact
Monitors memory before/after each query:
- Query SQL and bindings
- Execution time
- Memory delta

**Identifies**: Large result sets, inefficient queries

### 4. Global Scope Profiling
Tracks global scope application:
- Which models use which scopes
- How often scopes are applied
- Closure vs class-based scopes

**Identifies**: Global scope explosion, unnecessary filtering

### 5. Session Size Analysis
Monitors session data:
- Total session size
- Largest session keys
- Session growth patterns

**Identifies**: Session bloat, permission caching issues

### 6. Critical Memory Dumps
Full state capture when memory >90%:
- Object counts (if php-meminfo available)
- Included files and sizes
- Call stack
- Session breakdown
- Cache state

**Identifies**: Exact state when approaching OOM

---

## 🎯 Profiling Modes

| Mode | Sampling | Features | Overhead | Use Case |
|------|----------|----------|----------|----------|
| **Light** | 1% | Checkpoints only | <5ms | Continuous production monitoring |
| **Aggressive** | 100% | All features | 20-50ms | Short debugging sessions |
| **Targeted** | Header-based | All features | Variable | Controlled testing |

### Switch Modes

```bash
# Light (default, always safe)
./scripts/enable-memory-profiling.sh light

# Aggressive (debugging only, short duration)
./scripts/enable-memory-profiling.sh aggressive

# Targeted (manual trigger via header)
./scripts/enable-memory-profiling.sh targeted

# Disable
./scripts/disable-memory-profiling.sh
```

---

## 📈 Analysis Tools

### 1. Dump Analyzer

```bash
# Summary of recent dumps
php scripts/analyze-memory-dumps.php --summary

# Detailed view of recent dumps
php scripts/analyze-memory-dumps.php --recent 20 --details

# Search for specific patterns
php scripts/analyze-memory-dumps.php --pattern "session"
php scripts/analyze-memory-dumps.php --pattern "Permission"
php scripts/analyze-memory-dumps.php --pattern "newEloquentBuilder"
```

**Output includes**:
- Memory statistics (avg, min, max, variance)
- Context breakdown (where OOMs happen)
- Session size analysis
- Most common stack frames
- Pattern detection (session bloat, memory leaks, variance)
- Actionable insights

### 2. Reproducibility Tests

```bash
# Run systematic tests to identify trigger
php artisan test --filter MemoryReproductionTest

# Specific test scenarios:
# - Fresh vs existing session
# - Session data size variations
# - Repeated requests (accumulation)
# - Permission count scaling
# - Cache state variations
# - Time-based patterns
```

### 3. Real-time Monitoring

```bash
# Watch for high memory events
tail -f storage/logs/laravel.log | grep -E "High memory|memory state dump"

# Monitor dump creation
watch -n 5 'ls -lth storage/logs/memory-dumps/ | head -10'

# Live memory statistics
tail -f storage/logs/laravel.log | grep "Memory profile sample"
```

---

## 🔬 Diagnostic Workflow

### Phase 1: Baseline (Day 1)

**Goal**: Understand normal behavior

```bash
# Enable light profiling
./scripts/enable-memory-profiling.sh light

# Run for 24 hours, collect ≥100 samples

# Analyze baseline
php scripts/analyze-memory-dumps.php --recent 100 --summary > baseline.txt
```

**Key metrics**:
- Average memory usage
- Peak memory usage
- Variance (low = consistent, high = state-dependent)
- Common execution paths

### Phase 2: Capture OOM (Day 2-3)

**Goal**: Catch failures with full state

```bash
# Switch to aggressive during high-traffic hours
./scripts/enable-memory-profiling.sh aggressive

# Wait for OOM event (automatic dump)

# Immediately analyze
php scripts/analyze-memory-dumps.php --recent 1 --details

# Return to light mode
./scripts/enable-memory-profiling.sh light
```

**Look for**:
- Largest memory checkpoint jump
- Highest model instantiation counts
- Largest session keys
- Most frequent stack frames

### Phase 3: Compare Success vs Failure (Day 3-4)

**Goal**: Find the difference

```bash
# Analyze all dumps
php scripts/analyze-memory-dumps.php --details > all-dumps.txt

# Look for patterns in failures
grep -A 20 "usage_percent.*9[0-9]" all-dumps.txt

# Compare variance
php scripts/analyze-memory-dumps.php --summary
```

**Questions to answer**:
1. What's different in session data?
2. Are more models instantiated?
3. Is there a specific query causing spike?
4. Is there a time pattern?

### Phase 4: Reproduce (Day 4-5)

**Goal**: Make error deterministic

```bash
# Run reproducibility tests
php artisan test --filter MemoryReproductionTest

# Try targeted profiling with specific conditions
curl -H "X-Force-Memory-Profile: true" \
     -H "Cookie: [PROBLEMATIC_SESSION]" \
     https://app.com/endpoint
```

**Hypothesis testing**:
- Session size hypothesis → Test with varying session data
- Permission count hypothesis → Test with varying permission sets
- Cache state hypothesis → Test with fresh/warm/stale cache
- Time-based hypothesis → Test at different times

### Phase 5: Fix & Verify (Day 5+)

**Goal**: Implement solution and validate

```bash
# After implementing fix, re-enable profiling
./scripts/enable-memory-profiling.sh light

# Monitor for 24 hours

# Compare to baseline
php scripts/analyze-memory-dumps.php --summary > post-fix.txt
diff baseline.txt post-fix.txt
```

---

## 🎯 Common Root Causes & Solutions

### 1. Session Bloat

**Symptoms**:
- Session size >50MB in dumps
- Memory scales with user permissions/roles
- Largest session keys: `permissions_cache`, `navigation`

**Solution**:
```php
// Move to cache instead of session
cache()->remember("user.{$userId}.permissions", 300, fn() =>
    $user->getAllPermissions()
);

// Don't serialize entire navigation in session
session()->forget(['navigation', 'permissions_cache']);
```

### 2. Global Scope Explosion

**Symptoms**:
- High model instantiation counts
- Error at `newEloquentBuilder`
- Global scope profiler shows many scope applications

**Solution**:
```php
// Make scopes conditional
protected static function booted()
{
    // Bad: Always applied
    static::addGlobalScope(new TenantScope);

    // Good: Only when needed
    if (app()->bound('tenant')) {
        static::addGlobalScope(new TenantScope);
    }
}

// Or remove and use explicit local scopes
$query->tenant($tenantId);
```

### 3. N+1 Query Problem

**Symptoms**:
- High model counts in logs
- Many small query memory jumps
- Same query pattern repeated

**Solution**:
```php
// Bad
$users = User::all(); // 1 query
foreach ($users as $user) {
    $user->permissions; // N queries
}

// Good
$users = User::with('permissions')->get(); // 2 queries
```

### 4. Large Result Sets

**Symptoms**:
- Large memory jump after specific query
- Query logs show "Large memory jump"

**Solution**:
```php
// Bad
$all = Model::all(); // Loads everything into memory

// Good
Model::chunk(100, function ($models) {
    // Process in batches
});

// Or use cursor for large datasets
foreach (Model::cursor() as $model) {
    // Process one at a time
}
```

### 5. Filament Panel Overhead

**Symptoms**:
- OOM only on Filament routes
- Panel memory tracker shows high delta

**Solution**:
```php
// Lazy-load resources
public function panel(Panel $panel): Panel
{
    return $panel
        ->resources([
            // Only register resources used in this panel
        ])
        ->discoverResources(false); // Disable auto-discovery
}

// Cache navigation
protected function boot(): void
{
    Filament::serving(function () {
        Filament::registerNavigationItems(
            cache()->remember('filament.navigation', 3600, fn() =>
                $this->buildNavigation()
            )
        );
    });
}
```

---

## 🛡️ Safety Features

### Automatic Protection

1. **Circuit Breaker**: Auto-disables after N consecutive failures
2. **Overhead Detection**: Disables if profiling adds >50ms
3. **Emergency Kill Switch**: Via cache key `memory_profiling:emergency_disable`
4. **Memory Threshold Guards**: Stops profiling at 90% memory usage
5. **Sampling Rate**: Limits to 1% by default

### Manual Controls

```bash
# Emergency disable
./scripts/disable-memory-profiling.sh

# Or via cache (survives config cache)
php artisan tinker --execute="cache()->put('memory_profiling:emergency_disable', true, 3600);"

# Check status
php artisan tinker --execute="echo config('memory-profiling.enabled') ? 'ON' : 'OFF';"
```

---

## 📦 Architecture

```
app/
├── Http/Middleware/
│   ├── MemoryCheckpoint.php              # Request lifecycle tracking
│   └── ProductionMemoryProfiler.php      # Sampling & safety controls
├── Providers/
│   ├── MemoryDebugServiceProvider.php    # Model & query tracking
│   └── FilamentMemoryTrackerProvider.php # Filament-specific profiling
└── Debug/
    ├── MemoryDumper.php                  # Critical state capture
    └── GlobalScopeProfiler.php           # Scope analysis

config/
└── memory-profiling.php                  # Configuration

scripts/
├── enable-memory-profiling.sh            # Activation
├── disable-memory-profiling.sh           # Deactivation
└── analyze-memory-dumps.php              # Analysis tool

tests/Feature/
└── MemoryReproductionTest.php            # Systematic reproduction

docs/profiling/
├── README.md                             # This file
├── QUICK_START_CHECKLIST.md              # Step-by-step setup
├── IMPLEMENTATION_GUIDE.md               # Detailed guide
└── BLACKFIRE_SETUP.md                    # External profiler setup
```

---

## 🔧 Advanced Options

### Blackfire.io Integration

For deeper profiling with production-safe overhead:

See: [BLACKFIRE_SETUP.md](BLACKFIRE_SETUP.md)

```bash
# Install Blackfire
# See docs for ARM64-compatible installation

# Profile specific request
blackfire curl https://app.com/filament/admin

# Compare success vs failure
blackfire compare success.json failure.json
```

### Custom Instrumentation

Add your own profiling points:

```php
use App\Debug\MemoryDumper;

// In suspect code location
if (memory_get_usage(true) > 1536 * 1024 * 1024) {
    MemoryDumper::dump('custom_checkpoint_name');
}

// Or force dump for specific condition
if ($user->permissions()->count() > 1000) {
    MemoryDumper::dump('high_permission_user');
}
```

### PHP Extensions (Optional)

For even deeper analysis:

```bash
# php-meminfo: Object-level memory tracking
sudo pecl install meminfo
echo "extension=meminfo.so" | sudo tee -a /etc/php/8.2/mods-available/meminfo.ini
sudo phpenmod meminfo

# tideways_xhprof: Heap dumps
sudo pecl install tideways_xhprof
echo "extension=tideways_xhprof.so" | sudo tee -a /etc/php/8.2/mods-available/tideways.ini
sudo phpenmod tideways

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

## 📚 Documentation Index

- **[Quick Start Checklist](QUICK_START_CHECKLIST.md)** - Step-by-step deployment (10 min)
- **[Implementation Guide](IMPLEMENTATION_GUIDE.md)** - Detailed usage and configuration
- **[Blackfire Setup](BLACKFIRE_SETUP.md)** - External profiler integration
- **This README** - Overview and reference

---

## 🆘 Troubleshooting

### No dumps being created?

```bash
# Check profiling is enabled
grep MEMORY_PROFILING .env

# Check directory permissions
ls -ld storage/logs/memory-dumps/

# Check for errors
tail -100 storage/logs/laravel.log | grep -i error

# Increase sampling to 100% temporarily
echo "MEMORY_PROFILING_SAMPLE_RATE=1.0" >> .env
php artisan config:clear
```

### Profiling causing performance issues?

```bash
# Immediately disable
./scripts/disable-memory-profiling.sh

# Or reduce sampling
echo "MEMORY_PROFILING_SAMPLE_RATE=0.001" >> .env  # 0.1%
php artisan config:clear
```

### Dumps too large/filling disk?

```bash
# Clean old dumps (keeps last 7 days)
find storage/logs/memory-dumps/ -name "*.json" -mtime +7 -delete

# Only dump when very critical
echo "MEMORY_DUMP_THRESHOLD=1950" >> .env
php artisan config:clear
```

---

## 📊 Expected Timeline

| Phase | Duration | Outcome |
|-------|----------|---------|
| Setup | 10 minutes | Profiling active |
| Baseline | 24 hours | Normal patterns documented |
| OOM Capture | 1-3 days | Failure state captured |
| Analysis | 2-4 hours | Root cause identified |
| Fix Implementation | Varies | Solution deployed |
| Verification | 24 hours | Improvement confirmed |

**Total**: 3-5 days from setup to verified fix

---

## 🎯 Success Indicators

You'll know profiling is working when you have:

✅ Memory dumps in `storage/logs/memory-dumps/`
✅ Memory-related logs in `storage/logs/laravel.log`
✅ Analysis showing memory patterns
✅ Ability to identify largest memory jumps
✅ Session/model/query breakdown available
✅ Reproducible test cases (if applicable)
✅ Clear optimization targets identified

---

## 🚀 Production Deployment Strategy

1. **Stage 1**: Deploy with profiling **disabled**
2. **Stage 2**: Enable **light mode** (1% sampling)
3. **Stage 3**: Monitor for 24 hours, establish baseline
4. **Stage 4**: Wait for natural OOM event (automatic capture)
5. **Stage 5**: If needed, enable **aggressive mode** during specific time window
6. **Stage 6**: Analyze, implement fix, verify
7. **Stage 7**: Keep **light mode** running indefinitely for regression detection

**Never** start with aggressive mode in production.

---

## 📞 Support

**Immediate issues**:
```bash
# Disable everything
./scripts/disable-memory-profiling.sh && php artisan config:clear

# Check status
php artisan tinker --execute="var_dump(config('memory-profiling'));"
```

**Questions**:
- Check the implementation guide first
- Review analysis output for insights
- Compare dumps to identify patterns

---

**Version**: 1.0
**Last Updated**: 2025-10-03
**Status**: Production Ready ✅
