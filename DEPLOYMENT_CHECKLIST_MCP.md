# MCP Deployment Checklist

## Pre-Deployment Checks

### 1. Code Quality ✓
- [ ] All tests passing (`php artisan test`)
- [ ] No PHP errors (`php -l app/**/*.php`)
- [ ] Code style check (`./vendor/bin/pint --test`)
- [ ] Static analysis (`./vendor/bin/phpstan analyse`)

### 2. Database ✓
- [ ] Backup current database
  ```bash
  php artisan askproai:backup --type=full --encrypt
  ```
- [ ] Test migrations on staging
  ```bash
  php artisan migrate:smart --analyze
  ```
- [ ] Verify no data loss queries
- [ ] Check index performance

### 3. Dependencies ✓
- [ ] Composer dependencies up-to-date
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
- [ ] NPM packages built
  ```bash
  npm run build
  ```
- [ ] No security vulnerabilities
  ```bash
  composer audit
  npm audit
  ```

### 4. Configuration ✓
- [ ] Environment variables set (.env.mcp)
- [ ] Cache cleared
  ```bash
  php artisan optimize:clear
  ```
- [ ] Queue workers configured
- [ ] Redis/Cache servers running

### 5. External Services ✓
- [ ] Cal.com API accessible
- [ ] Retell.ai webhook registered
- [ ] Monitoring endpoints configured
- [ ] Backup storage accessible

## Migration Steps

### Phase 1: Preparation (5 minutes)
```bash
# 1. Enable maintenance mode
php artisan down --message="System upgrade in progress" --retry=60

# 2. Stop queue workers
php artisan horizon:terminate

# 3. Clear all caches
php artisan optimize:clear

# 4. Backup database
php artisan askproai:backup --type=critical --encrypt
```

### Phase 2: Code Deployment (3 minutes)
```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Run migrations
php artisan migrate:smart --online

# 4. Update caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

### Phase 3: Service Activation (2 minutes)
```bash
# 1. Warm caches
php artisan cache:warm

# 2. Start queue workers
php artisan horizon

# 3. Run health checks
php artisan health:check

# 4. Disable maintenance mode
php artisan up
```

## Rollback Plan

### Immediate Rollback (< 5 minutes)
```bash
# 1. Enable maintenance mode
php artisan down

# 2. Restore code
git checkout [previous-tag]
composer install --no-dev
npm ci && npm run build

# 3. Rollback migrations
php artisan migrate:rollback --step=5

# 4. Clear caches
php artisan optimize:clear

# 5. Restart services
php artisan horizon:terminate
php artisan horizon

# 6. Disable maintenance mode
php artisan up
```

### Database Rollback
```bash
# 1. Restore from backup
php artisan askproai:restore --latest --type=critical

# 2. Verify data integrity
php artisan db:verify

# 3. Clear caches
php artisan cache:clear
```

## Post-Deployment Verification

### 1. System Health (5 minutes)
- [ ] Admin dashboard accessible (/admin)
- [ ] API endpoints responding (/api/health)
- [ ] Queue processing (Horizon dashboard)
- [ ] No error spikes in logs

### 2. Functionality Tests (10 minutes)
- [ ] Create test appointment
- [ ] Process test webhook
- [ ] Verify Cal.com sync
- [ ] Check phone resolution

### 3. Performance Metrics (5 minutes)
- [ ] Response times < 200ms (p95)
- [ ] Database queries < 50ms
- [ ] Queue latency < 1s
- [ ] Memory usage stable

### 4. Monitoring (Continuous)
```bash
# Check application logs
tail -f storage/logs/laravel.log

# Monitor queue performance
php artisan horizon:snapshot

# View metrics dashboard
open http://localhost:3000/d/askproai-mcp

# Check circuit breakers
php artisan circuit-breaker:status
```

### 5. User Communication
- [ ] Update status page
- [ ] Notify key customers
- [ ] Document any issues
- [ ] Update changelog

## Emergency Contacts

- **Lead Developer**: [Contact Info]
- **DevOps**: [Contact Info]
- **Database Admin**: [Contact Info]
- **On-Call**: [Contact Info]

## Deployment Log Template

```markdown
## Deployment [Date] [Version]

**Start Time**: 
**End Time**: 
**Duration**: 
**Deployed By**: 

### Changes
- 

### Issues Encountered
- None / [List issues]

### Rollback Required
- No / Yes (Reason: )

### Post-Deployment Notes
- 
```

## Success Criteria

✅ All health checks passing
✅ No error rate increase
✅ Performance metrics stable
✅ Queue processing normal
✅ User reports positive

## Notes

- Always deploy during low-traffic periods
- Have rollback plan ready
- Monitor for 30 minutes post-deployment
- Document any deviations from plan