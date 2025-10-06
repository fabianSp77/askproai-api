# Memory Profiling Implementation Guide

Complete step-by-step guide to deploy memory profiling infrastructure.

## Quick Start (5 minutes)

### 1. Register Service Providers

Edit `config/app.php`:

```php
'providers' => ServiceProvider::defaultProviders()->merge([
    // ... existing providers

    // Memory Profiling
    App\Providers\MemoryDebugServiceProvider::class,
    App\Providers\FilamentMemoryTrackerProvider::class,
])->toArray(),
```

### 2. Register Middleware

Edit `bootstrap/app.php` (Laravel 11) or `app/Http/Kernel.php` (Laravel 10):

```php
// Laravel 11 (bootstrap/app.php)
->withMiddleware(function (Middleware $middleware) {
    $middleware->append([
        \App\Http\Middleware\MemoryCheckpoint::class,
        \App\Http\Middleware\ProductionMemoryProfiler::class,
    ]);
})

// Laravel 10 (app/Http/Kernel.php)
protected $middleware = [
    // ... existing middleware
    \App\Http\Middleware\MemoryCheckpoint::class,
    \App\Http\Middleware\ProductionMemoryProfiler::class,
];
```

### 3. Register Shutdown Handler

Edit `bootstrap/app.php` (add before return):

```php
use App\Debug\MemoryDumper;

MemoryDumper::registerShutdownHandler();

return $app;
```

### 4. Enable Profiling

```bash
# Make scripts executable
chmod +x scripts/enable-memory-profiling.sh
chmod +x scripts/disable-memory-profiling.sh
chmod +x scripts/analyze-memory-dumps.php

# Enable light profiling (1% sampling)
./scripts/enable-memory-profiling.sh light

# Clear config cache
php artisan config:clear

# Create dump directory
mkdir -p storage/logs/memory-dumps
chmod 775 storage/logs/memory-dumps
```

### 5. Monitor

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep -i memory

# Check for dumps
ls -lh storage/logs/memory-dumps/

# Analyze dumps
php scripts/analyze-memory-dumps.php --recent 20 --summary
```

---

## Profiling Modes

### Light Mode (Production Safe - Default)

- 1% request sampling
- Checkpoint tracking only
- Minimal overhead (<5ms per request)
- Safe for continuous production use

```bash
./scripts/enable-memory-profiling.sh light
```

### Aggressive Mode (Debugging)

- 100% request sampling
- Full model, query, and scope tracking
- Higher overhead (~20-50ms per request)
- Use for short debugging sessions only

```bash
./scripts/enable-memory-profiling.sh aggressive

# Run for 10 minutes, then disable
sleep 600 && ./scripts/disable-memory-profiling.sh
```

### Targeted Mode (Precision Debugging)

- No automatic sampling
- Requires header: `X-Force-Memory-Profile: true`
- Full profiling when header present
- Perfect for controlled testing

```bash
./scripts/enable-memory-profiling.sh targeted

# Test with curl
curl -H "X-Force-Memory-Profile: true" \
     -H "Cookie: laravel_session=YOUR_SESSION" \
     https://your-app.com/filament/admin
```

---

## Data Collection Strategy

### Phase 1: Baseline (Day 1)

**Goal**: Understand normal memory patterns

```bash
# Enable light profiling
./scripts/enable-memory-profiling.sh light

# Let run for 24 hours
# Collect at least 100 samples

# Analyze baseline
php scripts/analyze-memory-dumps.php --recent 100 --summary > baseline-report.txt
```

### Phase 2: Reproduction (Day 2-3)

**Goal**: Capture OOM events

```bash
# Enable aggressive profiling during high-traffic periods
./scripts/enable-memory-profiling.sh aggressive

# Monitor for OOM events
tail -f storage/logs/laravel.log | grep -E "memory|OOM"

# When OOM occurs, dumps will be automatically saved
```

### Phase 3: Comparison (Day 3-4)

**Goal**: Compare successful vs failed requests

```bash
# Analyze all dumps
php scripts/analyze-memory-dumps.php --details > all-dumps.txt

# Search for patterns
php scripts/analyze-memory-dumps.php --pattern "newEloquentBuilder"
php scripts/analyze-memory-dumps.php --pattern "globalScopes"
php scripts/analyze-memory-dumps.php --pattern "session"
```

### Phase 4: Targeted Testing (Day 4-5)

**Goal**: Validate hypothesis

```bash
# Use reproducibility tests
php artisan test --filter MemoryReproductionTest

# Profile specific scenarios
curl -H "X-Force-Memory-Profile: true" \
     -H "Cookie: [SESSION_WITH_LARGE_PERMISSIONS]" \
     https://your-app.com/filament/admin
```

---

## Reading the Data

### Memory Checkpoint Logs

Look for entries like:

```json
{
  "peak_mb": 1847.50,
  "checkpoints": [
    {"label": "request_start", "current_mb": 425.25, "delta_mb": 0},
    {"label": "before_providers", "current_mb": 642.75, "delta_mb": 217.50},
    {"label": "response_ready", "current_mb": 1823.00, "delta_mb": 1180.25},
    {"label": "request_end", "current_mb": 1847.50, "delta_mb": 24.50}
  ],
  "largest_jump": {
    "label": "response_ready",
    "delta_mb": 1180.25
  }
}
```

**Interpretation**:
- Largest jump: 1180MB during response building
- Focus investigation on middleware/controller between `before_providers` and `response_ready`

### Model Instantiation Logs

```json
{
  "peak_mb": 1923.75,
  "model_counts": {
    "App\\Models\\User::retrieved": {"count": 1247, "first_seen_mb": 450, "last_seen_mb": 1850},
    "App\\Models\\Permission::retrieved": {"count": 8934, "first_seen_mb": 650, "last_seen_mb": 1920}
  }
}
```

**Interpretation**:
- 8,934 Permission models instantiated
- Likely N+1 or missing eager loading
- Check for global scopes on Permission model

### Memory Dump Analysis

```bash
php scripts/analyze-memory-dumps.php --summary
```

**Look for**:

1. **Session Bloat**
   ```
   Session Statistics:
     Max: 125.34 MB
   Most common large session keys:
     permissions_cache: appears in 45 dumps
   ```
   → **Action**: Reduce session data, use cache instead

2. **Memory Accumulation**
   ```
   Memory Statistics:
     Average: 1654.23 MB
     Variance: 12.45 (low)
   INSIGHTS:
     ✅ Memory usage is consistent (low variance)
     → Issue is deterministic, not random
   ```
   → **Action**: Issue is reproducible, focus on specific code path

3. **Hot Path Identification**
   ```
   Most common stack frames:
     Illuminate\Database\Eloquent\Model::newEloquentBuilder: 234 occurrences
     Filament\Panel::boot: 189 occurrences
   ```
   → **Action**: Investigate Panel boot process and model queries

---

## Specific Issue Diagnosis

### Scenario 1: Session Data Too Large

**Symptoms**:
- `session_size_mb` consistently high (>50MB)
- Memory grows with session complexity

**Diagnosis**:
```bash
php scripts/analyze-memory-dumps.php --pattern "session"
```

**Solutions**:
- Move permissions to cache with short TTL
- Paginate navigation items
- Use database session driver with lazy loading

### Scenario 2: Global Scope Explosion

**Symptoms**:
- High model instantiation counts
- Error at `newEloquentBuilder`
- Memory spikes during query execution

**Diagnosis**:
```php
// Enable global scope profiling
config(['app.debug_memory' => true]);

// Check logs for scope stats
tail -f storage/logs/laravel.log | grep "global scope"
```

**Solutions**:
- Remove unnecessary global scopes
- Make scopes conditional (check context before applying)
- Use database views instead of scopes for complex filters

### Scenario 3: Filament Panel Overhead

**Symptoms**:
- Memory spike during panel boot
- Consistent OOM on Filament routes only

**Diagnosis**:
```bash
# Look for panel-specific logs
grep "Filament panel memory" storage/logs/laravel.log
```

**Solutions**:
- Lazy-load navigation items
- Cache resource discovery
- Defer authorization checks until needed
- Split into multiple panels

### Scenario 4: Query Result Set Too Large

**Symptoms**:
- Memory spike after specific queries
- Large `delta_mb` in query logs

**Diagnosis**:
```bash
# Check for large query deltas
grep "Large memory jump after query" storage/logs/laravel.log
```

**Solutions**:
- Add pagination to queries
- Use `cursor()` instead of `get()` for large datasets
- Implement chunking for bulk operations
- Add query result caching

---

## Emergency Procedures

### If Production OOMs During Profiling

```bash
# 1. Immediately disable profiling
./scripts/disable-memory-profiling.sh
php artisan config:clear

# 2. Increase PHP memory limit temporarily
# Edit php.ini or .env
echo "memory_limit=3072M" | sudo tee -a /etc/php/8.2/fpm/php.ini
sudo systemctl restart php8.2-fpm

# 3. Collect dumps before they're lost
cp -r storage/logs/memory-dumps /backup/emergency-dumps-$(date +%s)

# 4. Analyze offline
php scripts/analyze-memory-dumps.php --recent 50 > emergency-analysis.txt
```

### Circuit Breaker

The profiling system has built-in circuit breaker:

```php
// Auto-disables if overhead detected
config(['memory-profiling.safety.auto_disable_on_overhead' => true]);

// Or manually trigger emergency disable via cache
cache()->put('memory_profiling:emergency_disable', true, 3600);
```

---

## Expected Results

After 1-2 days of profiling, you should have:

1. ✅ **Baseline memory patterns** documented
2. ✅ **OOM event captures** with full state dumps
3. ✅ **Identified hot paths** causing memory spikes
4. ✅ **Reproducible test case** demonstrating the issue
5. ✅ **Specific optimization targets** with measured impact

---

## Next Steps

1. **Deploy profiling** in light mode
2. **Monitor for 24 hours** to establish baseline
3. **Capture OOM events** with aggressive mode during specific time windows
4. **Analyze dumps** to identify culprit
5. **Implement fixes** based on findings
6. **Re-profile** to verify improvement
7. **Keep light profiling** enabled long-term for regression detection

---

## Performance Impact

| Mode | Overhead | Memory | Use Case |
|------|----------|--------|----------|
| Light | <5ms | <1MB | Continuous production monitoring |
| Aggressive | 20-50ms | ~5MB | Short debugging sessions |
| Targeted | Variable | Variable | Controlled testing |
| None | 0ms | 0MB | Normal operation |

---

## Support Tools

- **Real-time monitoring**: `tail -f storage/logs/laravel.log | grep memory`
- **Dump analysis**: `php scripts/analyze-memory-dumps.php`
- **Quick disable**: `./scripts/disable-memory-profiling.sh`
- **Reproducibility tests**: `php artisan test --filter Memory`
