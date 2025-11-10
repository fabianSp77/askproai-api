# Retell AI Integration - Quick Reference

**Last Updated**: 2025-11-06
**Version**: V83

---

## 🚀 Quick Start

### Webhook URLs
```
Function Calls:  POST /api/webhooks/retell/function
Call Events:     POST /api/webhooks/retell
Cal.com Sync:    POST /api/calcom/webhook
```

### Essential Functions (17 Total)

| Function | Latency | Use Case |
|----------|---------|----------|
| `initialize_call` | 50-150ms | Call start context |
| `check_customer` | 30-80ms | Customer recognition |
| `check_availability` | 300-800ms | Slot checking |
| `start_booking` | 200-500ms | Validate booking |
| `confirm_booking` | 500-1500ms | Execute booking |
| `get_alternatives` | 400-1000ms | Alternative times |
| `cancel_appointment` | 400-800ms | Cancellation |
| `get_available_services` | 20-50ms | List services |

---

## 📊 Architecture at a Glance

```
Customer Call → Twilio → Retell AI → Laravel Backend → Cal.com
                                    ↓
                               PostgreSQL + Redis
```

**Multi-Tenancy**: `call_id → Call → phone_number_id → company_id`

---

## 🔑 Key Integrations

### Cal.com V2 API
```php
GET  /slots/available   → Check availability
POST /bookings          → Create booking
DELETE /bookings/{uid}  → Cancel booking
PATCH /bookings/{uid}/reschedule
```

**Authentication**: Bearer token
**Cache**: 5min TTL, event-driven invalidation
**Circuit Breaker**: 5 failures → 60s recovery

---

## 🛡️ Security

| Layer | Method |
|-------|--------|
| Call Events | HMAC signature (X-Retell-Signature) |
| Function Calls | Throttling (100/min, no signature for latency) |
| Multi-Tenant | CompanyScope + RLS |
| Cal.com | HMAC signature (X-Cal-Signature-256) |

---

## ⚡ Performance

**Optimizations**:
- ✅ Parallel API queries (50% faster)
- ✅ Redis caching (70% API reduction)
- ✅ Eager loading (80% query reduction)
- ✅ Circuit breaker (fail fast)

**Benchmarks**:
- Availability check: 300-800ms (cache: 20-50ms)
- Complete booking: 500-1500ms
- Total call: 2-4 minutes

---

## 🐛 Common Issues & Fixes

| Issue | Fix | Location |
|-------|-----|----------|
| call_id missing | Multi-layer fallback | `getCanonicalCallId()` |
| Race condition | Retry 5x with backoff | `getCallContext()` |
| Year bug | Add year if past | `DateTimeParser` |
| Cache collision | Include company_id + team_id | Cache keys |
| Cal.com timeout | Circuit breaker + cache | `CalcomService` |

---

## 📈 Monitoring

**Health Check**: `GET /api/health/calcom`

**Key Metrics**:
- Booking conversion rate: ~85%
- Average call duration: 2-4 min
- Cal.com cache hit rate: 70%
- Function error rate: <2%

**Logs**: `/storage/logs/laravel.log`
**Function Tracking**: `retell_call_sessions.function_calls` (JSONB)

---

## 🔧 Troubleshooting Commands

```bash
# Check recent calls
php artisan tinker
>>> Call::latest()->take(5)->get(['id', 'retell_call_id', 'status', 'appointment_made']);

# Check circuit breaker
redis-cli
> GET circuit:calcom_api:failure_count

# Clear cache
php artisan cache:clear

# View function calls for specific call
>>> $session = RetellCallSession::where('call_id', 'call_xxx')->first();
>>> $session->function_calls;
```

---

## 📞 Test Call Flow

1. **Call arrives** → `call_inbound` webhook
2. **Call starts** → `call_started` webhook + availability injected
3. **Agent calls** `initialize_call` → Customer context
4. **Customer requests** booking
5. **Agent calls** `check_availability` → Slots returned
6. **Agent calls** `start_booking` → Validation
7. **Agent confirms** → `confirm_booking` → Cal.com booking
8. **Call ends** → `call_ended` → Cost calculation
9. **Analyzed** → `call_analyzed` → Customer linking

---

## 🚨 Emergency Procedures

### Cal.com Down
1. Circuit breaker opens (5 failures)
2. Return cached availability
3. Agent suggests callback
4. Manual follow-up

### Database Slow
1. Check connection pool
2. Review slow query log
3. Add missing indexes
4. Scale vertically if needed

### High Error Rate (>5%)
1. Check `/api/health/calcom`
2. Review `laravel.log` for patterns
3. Verify webhook signatures
4. Check Redis connectivity

---

## 📝 Quick Code Snippets

### Add New Function
```php
// 1. routes/api.php
Route::post('/retell/my-function', [RetellFunctionCallHandler::class, 'myFunction']);

// 2. RetellFunctionCallHandler.php
private function myFunction(array $params, ?string $callId): JsonResponse
{
    $context = $this->getCallContext($callId);
    // ... logic ...
    return response()->json(['success' => true, 'data' => $result]);
}

// 3. handleFunctionCall() match statement
'my_function' => $this->myFunction($parameters, $callId),

// 4. Retell dashboard: Add function definition with URL
```

### Query Call Analytics
```sql
-- Booking conversion by day
SELECT
  DATE(created_at) as date,
  COUNT(*) as total_calls,
  COUNT(*) FILTER (WHERE appointment_made = true) as bookings,
  ROUND(100.0 * COUNT(*) FILTER (WHERE appointment_made = true) / COUNT(*), 2) as conversion_rate
FROM calls
WHERE created_at >= NOW() - INTERVAL '30 days'
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Top 5 most used functions
SELECT
  fc->>'function_name' as function_name,
  COUNT(*) as call_count,
  AVG((fc->>'duration_ms')::int) as avg_latency_ms
FROM retell_call_sessions,
  jsonb_array_elements(function_calls) as fc
GROUP BY fc->>'function_name'
ORDER BY call_count DESC
LIMIT 5;
```

---

## 🔗 Important Files

| File | Purpose |
|------|---------|
| `app/Http/Controllers/RetellFunctionCallHandler.php` | Main function router (6152 lines) |
| `app/Http/Controllers/RetellWebhookController.php` | Call event handler |
| `app/Services/CalcomService.php` | Cal.com API client |
| `app/Services/Retell/AppointmentCreationService.php` | Booking logic |
| `config/services.php` | API credentials |
| `routes/api.php` | Webhook routes |

---

## 📚 Documentation Links

- **Full Architecture**: `RETELL_INTEGRATION_ARCHITECTURE.json`
- **Detailed Guide**: `RETELL_INTEGRATION_GUIDE.md`
- **Project Docs**: `claudedocs/03_API/Retell_AI/`
- **E2E Guides**: `docs/e2e/`

---

## 💡 Pro Tips

1. **Always use getCanonicalCallId()** - handles all edge cases
2. **Cache aggressively** - 5min TTL for availability
3. **Eager load relationships** - prevent N+1 queries
4. **Log with context** - include call_id, company_id, function_name
5. **Monitor circuit breaker** - alerts for Cal.com issues
6. **Test with Retell test mode** - free calls for debugging
7. **Sanitize logs** - GDPR compliance (no PII)
8. **Use two-step booking** - better UX than single-step

---

**For detailed information, see full documentation in `RETELL_INTEGRATION_GUIDE.md`**
