# Troubleshooting Guide Template

> 📋 **Component**: {ComponentName}  
> 📅 **Last Updated**: {DATE}  
> 👥 **Maintained By**: {TEAM/PERSON}  
> 🆘 **Emergency Contact**: {ONCALL_CONTACT}

## Quick Reference

### Critical Issues (Fix Immediately)
1. [System Down](#system-down) - Complete outage
2. [Data Loss Risk](#data-loss-risk) - Potential data corruption
3. [Security Breach](#security-breach) - Unauthorized access

### Common Issues (Frequent)
1. [Authentication Errors](#authentication-errors)
2. [Performance Issues](#performance-issues)
3. [Integration Failures](#integration-failures)
4. [Queue Processing Issues](#queue-processing-issues)

### Known Issues (Workarounds Available)
1. [Issue with Workaround 1](#known-issue-1)
2. [Issue with Workaround 2](#known-issue-2)

## Diagnostic Tools

### Health Check Commands
```bash
# System health check
php artisan health:check

# Service-specific health check
php artisan {service}:health

# Database connectivity
php artisan db:ping

# Queue status
php artisan queue:monitor

# Cache status
php artisan cache:status
```

### Log Locations
| Component | Log File | Command to View |
|-----------|----------|-----------------|
| Application | storage/logs/laravel.log | `tail -f storage/logs/laravel.log` |
| Queue | storage/logs/horizon.log | `tail -f storage/logs/horizon.log` |
| Webhooks | storage/logs/webhooks.log | `tail -f storage/logs/webhooks.log` |
| API Calls | storage/logs/api.log | `tail -f storage/logs/api.log` |

### Monitoring Dashboards
- **Horizon**: http://localhost/horizon
- **Telescope**: http://localhost/telescope
- **Health**: http://localhost/health
- **Metrics**: http://localhost/metrics

## Common Issues and Solutions

### Authentication Errors

#### Issue: "Invalid API Key"
**Symptoms**:
- 401 Unauthorized responses
- "Invalid API key" in error message

**Diagnosis**:
```bash
# Check if API key exists
php artisan tinker
>>> Company::find(1)->api_key
```

**Solutions**:
1. Verify API key in request headers
2. Check key hasn't expired
3. Ensure correct environment
4. Regenerate key if needed

**Prevention**:
- Implement key rotation policy
- Monitor key usage
- Set up alerts for auth failures

#### Issue: "Token Expired"
**Symptoms**:
- Previously working requests fail
- 401 errors after period of time

**Solutions**:
```php
// Refresh token
$newToken = $authService->refreshToken($oldToken);

// Or re-authenticate
$token = $authService->authenticate($credentials);
```

### Performance Issues

#### Issue: "Slow API Response"
**Symptoms**:
- Response times > 1 second
- Timeouts on client side
- High server CPU/memory

**Diagnosis**:
```bash
# Check slow query log
tail -f storage/logs/slow-queries.log

# Monitor real-time performance
php artisan performance:monitor

# Database query analysis
php artisan db:analyze
```

**Solutions**:
1. **Add Database Indexes**:
   ```sql
   CREATE INDEX idx_company_created ON appointments(company_id, created_at);
   ```

2. **Implement Caching**:
   ```php
   $result = Cache::remember('expensive-query', 3600, function () {
       return DB::table('large_table')->get();
   });
   ```

3. **Optimize Queries**:
   ```php
   // Bad - N+1 problem
   $users = User::all();
   foreach ($users as $user) {
       echo $user->posts->count();
   }
   
   // Good - Eager loading
   $users = User::with('posts')->get();
   ```

#### Issue: "Memory Exhausted"
**Symptoms**:
- "Allowed memory size exhausted" errors
- Process crashes
- Slow gradual performance degradation

**Solutions**:
1. **Increase Memory Limit** (temporary):
   ```php
   ini_set('memory_limit', '512M');
   ```

2. **Process in Chunks**:
   ```php
   Model::chunk(1000, function ($records) {
       // Process records
   });
   ```

3. **Use Lazy Collections**:
   ```php
   Model::lazy()->each(function ($model) {
       // Process one at a time
   });
   ```

### Integration Failures

#### Issue: "External API Timeout"
**Symptoms**:
- Timeout exceptions
- 504 Gateway Timeout
- Partial data sync

**Quick Fix**:
```bash
# Reset circuit breaker
php artisan circuit-breaker:reset {service-name}

# Force retry failed jobs
php artisan queue:retry all
```

**Long-term Solutions**:
1. Implement circuit breaker pattern
2. Add retry logic with exponential backoff
3. Use asynchronous processing
4. Cache external API responses

#### Issue: "Webhook Not Received"
**Symptoms**:
- Expected webhooks don't arrive
- Data not updating
- No entries in webhook_events table

**Debugging Steps**:
1. **Verify Webhook URL**:
   ```bash
   curl -X POST https://your-domain.com/webhooks/test \
        -H "Content-Type: application/json" \
        -d '{"test": true}'
   ```

2. **Check Webhook Logs**:
   ```sql
   SELECT * FROM webhook_events 
   WHERE created_at > NOW() - INTERVAL 1 HOUR
   ORDER BY created_at DESC;
   ```

3. **Verify Signature**:
   ```php
   // Test signature verification
   $payload = '{"test": true}';
   $signature = hash_hmac('sha256', $payload, $secret);
   echo "Expected signature: " . $signature;
   ```

### Queue Processing Issues

#### Issue: "Jobs Not Processing"
**Symptoms**:
- Jobs stuck in queue
- Increasing job count
- No job execution logs

**Diagnosis**:
```bash
# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Check Horizon status
php artisan horizon:status
```

**Solutions**:
1. **Restart Queue Workers**:
   ```bash
   php artisan queue:restart
   php artisan horizon:terminate
   php artisan horizon
   ```

2. **Clear Stuck Jobs**:
   ```bash
   # Retry all failed jobs
   php artisan queue:retry all
   
   # Clear specific queue
   php artisan queue:clear redis --queue=high-priority
   ```

3. **Fix Memory Leaks**:
   ```bash
   # Restart workers periodically
   php artisan queue:work --timeout=3600 --max-jobs=1000
   ```

## System Recovery Procedures

### Database Recovery
```bash
# 1. Check database status
php artisan db:check

# 2. Run migrations if needed
php artisan migrate --force

# 3. Verify data integrity
php artisan db:verify

# 4. Restore from backup if needed
php artisan backup:restore --latest
```

### Cache Corruption
```bash
# 1. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Rebuild caches
php artisan optimize

# 3. Verify cache is working
php artisan cache:test
```

### Emergency Rollback
```bash
# 1. Deploy previous version
git checkout {previous-tag}

# 2. Rollback database
php artisan migrate:rollback --step=5

# 3. Clear caches
php artisan optimize:clear

# 4. Restart services
sudo systemctl restart php-fpm
sudo systemctl restart horizon
```

## Debug Mode Operations

### Enable Debug Mode
```php
// Temporarily in code
config(['app.debug' => true]);
\DB::enableQueryLog();

// After operations
$queries = \DB::getQueryLog();
dd($queries);
```

### Detailed Logging
```php
// Enable verbose logging
Log::channel('debug')->info('Detailed operation', [
    'input' => $request->all(),
    'user' => auth()->user(),
    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
]);
```

### Performance Profiling
```php
$start = microtime(true);

// Operation to profile
$result = $service->expensiveOperation();

$duration = microtime(true) - $start;
Log::info("Operation took {$duration} seconds");
```

## Monitoring and Alerting

### Key Metrics to Monitor
| Metric | Threshold | Alert Level |
|--------|-----------|-------------|
| API Response Time | > 500ms | Warning |
| API Response Time | > 2000ms | Critical |
| Error Rate | > 1% | Warning |
| Error Rate | > 5% | Critical |
| Queue Depth | > 1000 | Warning |
| Queue Depth | > 5000 | Critical |
| CPU Usage | > 70% | Warning |
| CPU Usage | > 90% | Critical |
| Memory Usage | > 80% | Warning |
| Memory Usage | > 95% | Critical |

### Setting Up Alerts
```php
// config/monitoring.php
'alerts' => [
    'channels' => ['slack', 'email'],
    'recipients' => [
        'critical' => ['oncall@askproai.de'],
        'warning' => ['team@askproai.de'],
    ],
    'rules' => [
        'high_error_rate' => [
            'threshold' => 5, // percentage
            'window' => 300, // seconds
            'level' => 'critical',
        ],
    ],
],
```

## Escalation Procedures

### Level 1: Development Team
- Response time: 1 hour (business hours)
- Can handle: Most application issues
- Contact: dev-team@askproai.de

### Level 2: Senior Engineers
- Response time: 30 minutes
- Can handle: Complex issues, architecture decisions
- Contact: senior-dev@askproai.de

### Level 3: On-Call Engineer
- Response time: 15 minutes (24/7)
- Can handle: Critical outages, security issues
- Contact: +49-XXX-ONCALL

### Level 4: CTO/Management
- Response time: Immediate for critical
- Can handle: Business decisions, vendor escalation
- Contact: management@askproai.de

## Prevention Strategies

### Regular Maintenance
- [ ] Weekly: Review error logs
- [ ] Monthly: Performance analysis
- [ ] Quarterly: Dependency updates
- [ ] Yearly: Architecture review

### Automated Testing
```yaml
# .github/workflows/health-check.yml
- name: Run Health Checks
  run: |
    php artisan health:check
    php artisan test --parallel
    php artisan performance:test
```

### Capacity Planning
- Monitor growth trends
- Plan for 3x current load
- Regular load testing
- Auto-scaling policies

## Related Documentation
- [Operations Manual](../operations/manual.md)
- [Emergency Procedures](../emergency/procedures.md)
- [Performance Guide](../performance/optimization.md)
- [Security Playbook](../security/playbook.md)

---

> 🔄 **Auto-Updated**: This documentation is automatically checked for updates. Last verification: {TIMESTAMP}