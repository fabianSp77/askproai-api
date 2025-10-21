# Callback Request System - Quick Reference
**Version:** 2025-10-18

---

## 🎯 Datei-Navigation

| Komponente | Dateipfad | Zeilen | Zweck |
|-----------|-----------|--------|-------|
| **Model** | `app/Models/CallbackRequest.php` | 42-333 | Kern-Entity |
| **Model** | `app/Models/CallbackEscalation.php` | - | Eskalations-Tracking |
| **Migration** | `database/migrations/2025_10_01_060203_...` | - | Schema |
| **Resource** | `app/Filament/Resources/CallbackRequestResource.php` | 1-896 | Admin UI |
| **Resource Pages** | `app/Filament/Resources/CallbackRequestResource/Pages/` | - | CRUD Pages |
| **Service** | `app/Services/Appointments/CallbackManagementService.php` | 26-369 | Business Logic |
| **Service** | `app/Services/Callbacks/CallbackAssignmentService.php` | 20-279 | Assignment Logic |
| **Job** | `app/Jobs/EscalateOverdueCallbacksJob.php` | 22-223 | Hourly Escalation |
| **Observer** | `app/Observers/CallbackRequestObserver.php` | 8-116 | Validation Hooks |
| **Event** | `app/Events/Appointments/CallbackRequested.php` | 18-66 | Creation Event |
| **Listener** | `app/Listeners/Appointments/AssignCallbackToStaff.php` | 22-226 | Auto-Assignment |
| **Widget** | `app/Filament/Widgets/CallbacksByBranchWidget.php` | - | Stats Dashboard |
| **Widget** | `app/Filament/Widgets/OverdueCallbacksWidget.php` | - | Overdue Table |
| **Controller** | `app/Http/Controllers/RetellFunctionCallHandler.php:2778` | - | Creation via API |

---

## 🔄 Prozessfluss-Übersicht

```
┌─────────────────────────────────────────┐
│  1. CALLBACK REQUEST ERSTELLUNG        │
├─────────────────────────────────────────┤
│ Via:                                    │
│ • RetellFunctionCallHandler (Anrufer)  │
│ • Filament Admin Form                  │
│ • CallbackManagementService::create    │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  2. OBSERVER PROCESSING                │
├─────────────────────────────────────────┤
│ • Input Sanitization (XSS)             │
│ • E.164 Phone Validation               │
│ • expires_at Auto-set                  │
│ • status Auto-set to 'pending'         │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  3. EVENT FIRED: CallbackRequested     │
├─────────────────────────────────────────┤
│ Event Properties:                       │
│ • callbackRequest                      │
│ • preferredTime (optional)             │
│ • topic (optional)                     │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  4. AUTO-ASSIGNMENT (Async)            │
├─────────────────────────────────────────┤
│ Listener: AssignCallbackToStaff        │
│ Queue: callbacks                        │
│ Strategy:                              │
│ • Previous relationship                │
│ • Topic expertise                      │
│ • Least loaded                         │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  5. ADMIN ACTIONS                      │
├─────────────────────────────────────────┤
│ Status Flow:                            │
│ pending → assigned                     │
│        → contacted                     │
│        → completed ✓                   │
│        → escalated (overdue)           │
│        → expired (if > expiry)         │
│        → cancelled                     │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  6. ESCALATION JOB (Hourly)            │
├─────────────────────────────────────────┤
│ Job: EscalateOverdueCallbacksJob       │
│ Trigger: expires_at < now()            │
│ Action:                                │
│ • Find escalation target               │
│ • Reassign to target                   │
│ • Fire CallbackEscalated event         │
│ • 4h cooldown prevents re-escalation   │
└─────────────────────────────────────────┘
```

---

## 📊 Status Übergänge

```
START
  │
  └─→ pending
       ├─→ assigned (auto or manual)
       │    ├─→ contacted (marked by staff)
       │    │    ├─→ completed ✓ (END)
       │    │    └─→ escalated (overdue)
       │    │         └─→ assigned (new staff)
       │    │
       │    └─→ escalated (overdue)
       │         └─→ assigned (new staff)
       │
       ├─→ expired (END: expires_at < now)
       │
       └─→ cancelled (END: user action)
```

---

## 🎨 UI Components Mapping

### Navigation
```
Admin Panel
└─ CRM
   └─ Rückrufanfragen (CallbackRequestResource)
      ├─ List View (ListCallbackRequests Page)
      ├─ Create (CreateCallbackRequest Page)
      ├─ View (ViewCallbackRequest Page)
      └─ Edit (EditCallbackRequest Page)
```

### Dashboard Widgets
```
Dashboard
├─ CallbacksByBranchWidget
│  └─ Stats: Pending, Overdue, Completed, Avg Response Time
│
└─ OverdueCallbacksWidget
   └─ Table: Overdue callbacks with quick actions
```

### Form Layout
```
Tabs
├─ Kontaktdaten (Customer selection, branch, phone)
├─ Details (Service, priority, time window, notes)
└─ Zuweisung (Preferred staff, assigned staff, status, expires_at)
```

---

## 🔍 Key Queries

### Find Pending Callbacks
```php
CallbackRequest::where('status', 'pending')->get();
```

### Find Overdue Callbacks
```php
CallbackRequest::overdue()->get();
// Scope: expires_at < now() AND status NOT IN [completed, expired, cancelled]
```

### Find by Priority
```php
CallbackRequest::byPriority('urgent')->get();
```

### Count by Status
```php
CallbackRequest::where('status', 'contacted')->count();
```

### Find by Branch
```php
CallbackRequest::where('branch_id', $branchId)->get();
```

### With Escalations
```php
CallbackRequest::with('escalations')->where('id', $id)->first();
```

---

## 🔧 Configuration Keys

```php
// config/callbacks.php (if exists) or check services.php

config('callbacks.auto_assign', true)
config('callbacks.expiration_hours.urgent', 2)
config('callbacks.expiration_hours.high', 4)
config('callbacks.expiration_hours.normal', 24)
config('callbacks.max_contact_attempts', 3)
config('callbacks.escalation_cooldown_hours', 4)
```

---

## 💾 Cache Keys

| Key | Duration | Invalidation | Purpose |
|-----|----------|--------------|---------|
| `callback_stats_widget` | 5min | On status change | Widget stats |
| `nav_badge_callbacks_pending` | - | Event-driven | Navigation badge |
| `overdue_callbacks_count` | 5min | Event-driven | Count only |
| `callback.last_assigned_staff_id` | 24h | Expire | Round-robin state |

---

## 📱 Admin Actions

### Individual Actions
```
assign          → Manual assignment
autoAssign      → Automatic (strategy: round_robin|load_based)
markContacted   → Status: assigned → contacted
markCompleted   → Status: contacted → completed (+ optional notes)
escalate        → Escalate with reason
view            → Detail view
edit            → Edit all fields
```

### Bulk Actions
```
bulkAssign      → Assign all selected to same staff
bulkAutoAssign  → Auto-assign all selected
bulkComplete    → Mark all as completed
bulkDelete      → Soft delete
bulkRestore     → Restore from trash
bulkForceDelete → Permanent delete
```

---

## 🚀 Common Tasks

### Create Callback via Code
```php
$callback = app(CallbackManagementService::class)->createRequest([
    'customer_id' => $customerId,
    'branch_id' => $branchId,
    'phone_number' => '+491234567890',
    'customer_name' => 'Max Mustermann',
    'priority' => CallbackRequest::PRIORITY_HIGH,
    'service_id' => $serviceId,
]);
```

### Auto-Assign Callback
```php
$service = app(CallbackAssignmentService::class);
$staff = $service->autoAssign($callback, 'load_based');
// Returns: Staff|null
```

### Mark as Contacted
```php
CallbackManagementService->markContacted($callback);
// Or directly:
$callback->markContacted();
```

### Escalate Callback
```php
CallbackManagementService->escalate(
    $callback,
    'Kunde nicht erreichbar'
);
```

### Get Overdue
```php
$overdue = CallbackManagementService->getOverdueCallbacks($branch);
// Or query directly:
$overdue = CallbackRequest::overdue()->get();
```

---

## 📊 Database Indexes

```sql
idx_company                              -- All queries start with company_id
idx_status_priority_expires              -- Widget queries
idx_assigned_status                      -- Staff assignment queries
idx_company_customer                     -- Customer lookups
idx_company_created                      -- Timeline queries
```

**Performance:** Query plans optimized for list views and widgets

---

## 🔐 Security Checklist

- [x] Multi-tenant isolation (company_id)
- [x] Input sanitization (XSS prevention)
- [x] Phone validation (E.164 format)
- [x] Authorization policy (CallbackRequestPolicy)
- [x] Soft deletes (audit trail)
- [x] Enum validation (invalid states blocked)
- [x] Observer pattern (audit on change)

---

## ⚡ Performance Tips

1. **Widget Slow?**
   ```
   → Check: Cache is working
   → Clear: php artisan cache:clear
   → Verify: 5min cache TTL
   ```

2. **Assignment Slow?**
   ```
   → Check: Database indexes
   → Check: Staff count vs callbacks
   → Verify: Query plans
   ```

3. **Page Load Slow?**
   ```
   → Check: Eager loading (with)
   → Check: Pagination size
   → Check: Filter complexity
   ```

---

## 🧪 Testing

**Test Files:**
```
tests/Unit/CallbackManagementServiceTest.php
tests/Unit/EscalateOverdueCallbacksJobTest.php
tests/Feature/CallbackFlowIntegrationTest.php
tests/Feature/Security/MultiTenantIsolationTest.php
```

**Run Tests:**
```bash
vendor/bin/pest tests/Unit/CallbackManagementServiceTest.php
vendor/bin/pest tests/Feature/CallbackFlowIntegrationTest.php
```

---

## 📞 Support

**Issues?**
- Check logs: `storage/logs/laravel.log`
- Check DB: `callback_requests` table
- Check cache: `php artisan cache:clear`
- Check queue: `php artisan queue:work`

**Common Errors:**
- `no eligible staff` → Check branch/service assignments
- `assignment failed` → Check queue worker
- `validation error` → Check phone format (E.164)

---

**Quick Links:**
- [Full Analysis](./CALLBACK_REQUEST_SYSTEM_ANALYSIS_2025-10-18.md)
- [Admin Panel](https://api.askproai.de/admin/callback-requests)
- [Model](../../../app/Models/CallbackRequest.php)
- [Service](../../../app/Services/Appointments/CallbackManagementService.php)
