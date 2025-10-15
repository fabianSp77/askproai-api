# Cal.com Cache Optimization - Quick Implementation Guide

**Goal:** Reduce data staleness from 300s → 60s while maintaining 70%+ cache hit rate

---

## 1. IMMEDIATE CHANGES (5 minutes)

### Change 1: SmartAppointmentFinder TTL

**File:** `/var/www/api-gateway/app/Services/Appointments/SmartAppointmentFinder.php`

**Line 30:**
```php
// BEFORE
protected const CACHE_TTL = 45;

// AFTER
protected const CACHE_TTL = 60; // Balanced: 70-80% hit rate, max 60s staleness
```

---

### Change 2: CalcomService Adaptive TTL

**File:** `/var/www/api-gateway/app/Services/CalcomService.php`

**Lines 234-242:**
```php
// BEFORE
if ($totalSlots === 0) {
    $ttl = 60; // 1 minute for empty responses
} else {
    $ttl = 300; // 5 minutes for normal responses
}

// AFTER
if ($totalSlots === 0) {
    $ttl = 30; // Faster refresh for empty slots
} else {
    $ttl = 60; // Reduced staleness window (was 300s)
}
```

---

## 2. TESTING (2 minutes)

```bash
# Clear existing cache
php artisan cache:clear

# Test appointment availability
php artisan tinker
>>> $service = App\Models\Service::first();
>>> $finder = new App\Services\Appointments\SmartAppointmentFinder();
>>> $slots = $finder->findInTimeWindow($service, now(), now()->addDays(7));
>>> $slots->count()
```

---

## 3. MONITORING (After Deployment)

### Key Metrics to Watch

**Cache Performance:**
```bash
# Check Redis cache keys
redis-cli keys "appointment_finder:*" | wc -l
redis-cli keys "calcom:slots:*" | wc -l
```

**Application Logs:**
```bash
# Monitor cache hits/misses
tail -f storage/logs/laravel.log | grep "Cache hit\|Cache miss"

# Monitor Cal.com API calls
tail -f storage/logs/calcom.log | grep "Available Slots Response"
```

**Expected Results (24h after deployment):**
- Cache hit rate: 70-80%
- Staleness incidents: <2% of queries
- Average response time: <150ms
- API calls: ~1,200-1,500/day/service

---

## 4. ROLLBACK (If Needed)

If issues occur, revert immediately:

```bash
# Revert SmartAppointmentFinder.php Line 30
protected const CACHE_TTL = 45; // Original value

# Revert CalcomService.php Lines 234-242
if ($totalSlots === 0) {
    $ttl = 60;
} else {
    $ttl = 300; // Original value
}

# Clear cache and restart
php artisan cache:clear
php artisan queue:restart
```

---

## 5. SUCCESS CRITERIA

✅ **Deployment Successful If:**
- No increase in error rates
- Cache hit rate remains >70%
- Staleness complaints decrease
- API latency remains <200ms p95

❌ **Rollback If:**
- Error rate increases >5%
- Cache hit rate drops <60%
- API rate limits hit
- User complaints increase

---

## 6. NEXT STEPS (Future)

**Phase 2: Unified Cache (Week 2-3)**
- Create `CacheInvalidationService`
- Standardize cache key formats
- Update webhook handler

**Phase 3: Event-Based (Month 2)**
- Implement webhook-driven invalidation
- Add adaptive TTL logic
- Enable cache warming

**Full details:** See `CALCOM_CACHE_PERFORMANCE_ANALYSIS.md`

---

## 7. QUICK REFERENCE

| Metric | Before | After | Change |
|--------|--------|-------|--------|
| **SmartAppointmentFinder TTL** | 45s | 60s | +33% |
| **CalcomService TTL** | 300s | 60s | -80% |
| **Max Staleness** | 300s | 60s | -80% |
| **Cache Hit Rate** | 60-70% | 70-80% | +10% |
| **Staleness Risk** | 12.5% | 2.5% | -80% |

---

**Approval Required:** YES
**Risk Level:** LOW (reversible in <5 min)
**Estimated Impact:** HIGH (bessere UX, weniger Fehler)
