# Memory Profiling Quick Start Checklist

Use this checklist to deploy memory profiling in **under 10 minutes**.

## ✅ Pre-Flight Checks

- [ ] PHP version 8.1+ confirmed
- [ ] Laravel application running
- [ ] Write access to `storage/logs/`
- [ ] Ability to edit `.env` file
- [ ] Ability to restart PHP-FPM (if needed)

## ✅ Installation (5 minutes)

### Step 1: Code Integration

- [ ] Verify files created:
  ```bash
  ls -la app/Http/Middleware/MemoryCheckpoint.php
  ls -la app/Http/Middleware/ProductionMemoryProfiler.php
  ls -la app/Providers/MemoryDebugServiceProvider.php
  ls -la app/Debug/MemoryDumper.php
  ls -la config/memory-profiling.php
  ```

### Step 2: Register Components

- [ ] Add to `config/app.php` providers array:
  ```php
  App\Providers\MemoryDebugServiceProvider::class,
  App\Providers\FilamentMemoryTrackerProvider::class,
  ```

- [ ] Add to middleware (choose your Laravel version):

  **Laravel 11** - Edit `bootstrap/app.php`:
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->append([
          \App\Http\Middleware\MemoryCheckpoint::class,
          \App\Http\Middleware\ProductionMemoryProfiler::class,
      ]);
  })
  ```

  **Laravel 10** - Edit `app/Http/Kernel.php`:
  ```php
  protected $middleware = [
      // ... existing
      \App\Http\Middleware\MemoryCheckpoint::class,
      \App\Http\Middleware\ProductionMemoryProfiler::class,
  ];
  ```

- [ ] Add to `bootstrap/app.php` (before return statement):
  ```php
  use App\Debug\MemoryDumper;
  MemoryDumper::registerShutdownHandler();
  ```

### Step 3: Configuration

- [ ] Make scripts executable:
  ```bash
  chmod +x scripts/enable-memory-profiling.sh
  chmod +x scripts/disable-memory-profiling.sh
  chmod +x scripts/analyze-memory-dumps.php
  ```

- [ ] Create dump directory:
  ```bash
  mkdir -p storage/logs/memory-dumps
  chmod 775 storage/logs/memory-dumps
  ```

- [ ] Enable profiling:
  ```bash
  ./scripts/enable-memory-profiling.sh light
  ```

- [ ] Clear cache:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  ```

### Step 4: Verification

- [ ] Check configuration loaded:
  ```bash
  php artisan tinker
  > config('memory-profiling.enabled')
  => true
  ```

- [ ] Test request profiling:
  ```bash
  curl -I http://localhost/filament/admin
  # Check logs
  tail -n 50 storage/logs/laravel.log | grep -i memory
  ```

- [ ] Verify dumps are being created:
  ```bash
  # Make a few requests
  for i in {1..5}; do curl -s http://localhost/filament/admin > /dev/null; done

  # Check for dumps (may take a few minutes with 1% sampling)
  ls -lh storage/logs/memory-dumps/
  ```

## ✅ First Analysis (2 minutes)

- [ ] Wait 10-30 minutes for data collection

- [ ] Run analysis:
  ```bash
  php scripts/analyze-memory-dumps.php --summary
  ```

- [ ] Review baseline metrics:
  - [ ] Average memory usage noted: _______ MB
  - [ ] Peak memory usage noted: _______ MB
  - [ ] Variance noted: _______ (low < 100)

## ✅ Targeted Debugging (3 minutes)

If OOM is happening **right now**:

- [ ] Switch to aggressive mode:
  ```bash
  ./scripts/enable-memory-profiling.sh aggressive
  php artisan config:clear
  ```

- [ ] Trigger the problematic request:
  ```bash
  # If you know the URL that fails
  curl -H "X-Force-Memory-Profile: true" \
       -H "Cookie: YOUR_SESSION_COOKIE" \
       https://your-app.com/filament/admin
  ```

- [ ] Check for OOM dump:
  ```bash
  # OOM dumps are created automatically
  ls -lt storage/logs/memory-dumps/ | head -5
  ```

- [ ] Analyze immediately:
  ```bash
  php scripts/analyze-memory-dumps.php --recent 1 --details
  ```

- [ ] Disable aggressive mode:
  ```bash
  ./scripts/disable-memory-profiling.sh
  ```

## ✅ Common Issues

### Issue: No dumps being created

**Check**:
- [ ] Profiling enabled: `grep MEMORY_PROFILING_ENABLED .env`
- [ ] Directory writable: `touch storage/logs/memory-dumps/test.txt`
- [ ] Middleware registered: `php artisan route:list --columns=middleware`
- [ ] Logs for errors: `grep -i error storage/logs/laravel.log | tail -20`

**Fix**:
```bash
# Ensure permissions
chmod -R 775 storage/logs/
chown -R www-data:www-data storage/logs/

# Increase sampling to 100% temporarily
echo "MEMORY_PROFILING_SAMPLE_RATE=1.0" >> .env
php artisan config:clear
```

### Issue: Profiling causing performance problems

**Fix**:
```bash
# Immediately disable
./scripts/disable-memory-profiling.sh

# Or reduce sampling
echo "MEMORY_PROFILING_SAMPLE_RATE=0.001" >> .env  # 0.1%
php artisan config:clear
```

### Issue: Dumps too large, filling disk

**Fix**:
```bash
# Clean old dumps
find storage/logs/memory-dumps/ -name "*.json" -mtime +7 -delete

# Reduce dump threshold
echo "MEMORY_DUMP_THRESHOLD=1950" >> .env  # Only dump when very close to OOM
php artisan config:clear
```

## ✅ Production Deployment Checklist

Before deploying to production:

- [ ] Tested in staging environment
- [ ] Confirmed <5ms overhead in light mode
- [ ] Emergency disable script tested: `./scripts/disable-memory-profiling.sh`
- [ ] Disk space checked (allow ~1GB for dumps)
- [ ] Log rotation configured for `laravel.log`
- [ ] Team knows how to disable if needed
- [ ] Monitoring alerts set for high memory usage

Deploy:
- [ ] Start with light mode (1% sampling)
- [ ] Monitor for 24 hours
- [ ] Review baseline analysis
- [ ] Only enable aggressive mode during controlled time windows
- [ ] Keep light mode running long-term

## ✅ Success Criteria

After deployment, you should have:

- [ ] Baseline memory patterns documented
- [ ] Ability to capture OOM events automatically
- [ ] Memory progression tracked across requests
- [ ] Session size analysis available
- [ ] Model instantiation counts tracked
- [ ] Query memory impact logged
- [ ] Reproducible test cases (if applicable)

## ✅ Next Steps After Data Collection

When you have sufficient data:

1. [ ] Run comprehensive analysis:
   ```bash
   php scripts/analyze-memory-dumps.php --recent 100 --summary > analysis.txt
   ```

2. [ ] Search for patterns:
   ```bash
   php scripts/analyze-memory-dumps.php --pattern "session"
   php scripts/analyze-memory-dumps.php --pattern "Permission"
   php scripts/analyze-memory-dumps.php --pattern "newEloquentBuilder"
   ```

3. [ ] Review largest memory jumps in checkpoints

4. [ ] Identify optimization targets

5. [ ] Implement fixes

6. [ ] Re-profile to verify improvement

7. [ ] Keep monitoring enabled for regression detection

---

## 🆘 Emergency Contact Points

**Immediate disable**:
```bash
./scripts/disable-memory-profiling.sh && php artisan config:clear
```

**Check if profiling is active**:
```bash
php artisan tinker --execute="echo config('memory-profiling.enabled') ? 'ENABLED' : 'DISABLED';"
```

**Quick status check**:
```bash
echo "Profiling: $(grep MEMORY_PROFILING_ENABLED .env)"
echo "Sample rate: $(grep MEMORY_PROFILING_SAMPLE_RATE .env)"
echo "Recent dumps: $(ls -1 storage/logs/memory-dumps/ 2>/dev/null | wc -l)"
echo "Latest dump: $(ls -t storage/logs/memory-dumps/ 2>/dev/null | head -1)"
```

---

## Time Estimate

- **Initial setup**: 5 minutes
- **First data collection**: 30-60 minutes (automatic)
- **First analysis**: 2 minutes
- **Targeted debugging**: 3-10 minutes per session
- **Total to actionable insights**: 1-2 hours

---

**Status**: Ready to deploy ✅

Start with: `./scripts/enable-memory-profiling.sh light`
