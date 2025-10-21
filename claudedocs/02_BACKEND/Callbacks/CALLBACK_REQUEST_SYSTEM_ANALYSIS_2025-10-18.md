# Callback Request System - Umfassende Analyse
**Erstellt:** 2025-10-18
**Seite:** https://api.askproai.de/admin/callback-requests
**Komponenten:** 15+ Klassen
**Status:** Production-Ready

---

## 1. System-Übersicht

Das **Callback Request System** verwaltet automatische Rückrufanfragen von Kunden, wenn eine direkte Terminbuchung nicht möglich ist. Das System ist event-driven, multi-tenant und verfügt über intelligente Auto-Assignment sowie SLA-Management.

### Kernfunktionalität
```
Kundenwunsch → Callback Request erstellt → Auto-Assignment
→ Staff kontaktiert Kunde → Status aktualisiert → Termin gebucht/Eskalation
```

---

## 2. Datenmodell

### CallbackRequest Model (app/Models/CallbackRequest.php)

**Status-Lifecycle:**
```
pending → assigned → contacted → completed
                  ↘ escalation ↗
                  → expired
                  → cancelled
```

**Prioritäten:**
- `normal`: 24h Ablauf
- `high`: 4h Ablauf
- `urgent`: 1-2h Ablauf

**Wichtige Attribute:**
```php
- id: Primary Key
- company_id: Multi-tenant isolation
- customer_id: Nullable (walk-ins)
- branch_id: Target branch
- service_id: Requested service (optional)
- staff_id: Preferred staff (optional)
- phone_number: E.164 Format (+49...)
- customer_name: Contact name
- preferred_time_window: JSON array
- status: Current state
- priority: normal|high|urgent
- assigned_to: Assigned staff UUID
- expires_at: SLA deadline
- contacted_at: When contacted
- completed_at: When completed
- metadata: Retell call data
- notes: Staff notes
```

### CallbackEscalation Model

Tracks all escalations with:
- escalation_reason
- escalated_from (Staff UUID)
- escalated_to (Staff UUID)
- escalated_at (Timestamp)

---

## 3. Prozessfluss

### 3.1 Erstellung

#### Option A: Via Retell AI (Anonyme Anrufer)
```php
// app/Http/Controllers/RetellFunctionCallHandler.php:2778
RetellFunctionCallHandler::createAnonymousCallbackRequest()
  ↓
CallbackRequest::create([
  'company_id', 'branch_id', 'phone_number', 'customer_name',
  'preferred_time_window', 'priority', 'status' => 'pending',
  'expires_at' => calculated based on priority
])
```

**Trigger:** Anonyme Anrufer können nicht direkt buchen → Callback angeboten

#### Option B: Via Admin Panel
```
Filament Resource → Form → Create → Event fired
```

#### Option C: Via Service
```php
CallbackManagementService::createRequest(array $data)
  → DB Transaction
  → Enum Validation
  → Event: CallbackRequested
  → Auto-assign wenn konfiguriert
```

### 3.2 Auto-Assignment

**Trigger:** Event `CallbackRequested`

**Listener:** `AssignCallbackToStaff` (Queued, callbacks queue)

**Strategien (in Prioritätsreihenfolge):**

1. **Previous Relationship**
   ```php
   Staff mit bisherigen Appointments für Kunde
   ```

2. **Topic Expertise**
   ```php
   Staff mit Spezialisierung im Service
   ```

3. **Least Loaded**
   ```php
   Staff mit wenigsten aktiven Callbacks in letzten 24h
   ```

4. **Fallback:** Keine Zuweisung (manual assignment required)

**Services:**
- `CallbackAssignmentService::autoAssign()` - Single callback
- `CallbackAssignmentService::bulkAutoAssign()` - Multiple callbacks
- `CallbackAssignmentService::reassign()` - Change assignment

### 3.3 Contact Workflow

**Admin Actions (Filament):**

1. **Mark as Contacted**
   ```php
   CallbackRequest::markContacted()
   → status = 'contacted'
   → contacted_at = now()
   → Cache invalidation
   ```

2. **Mark as Completed**
   ```php
   CallbackRequest::markCompleted()
   → status = 'completed'
   → completed_at = now()
   → Optional: Add completion notes
   ```

3. **Escalate**
   ```php
   CallbackRequest::escalate(reason, escalateTo)
   → Create CallbackEscalation
   → Auto-reassign if escalation target found
   → Fire CallbackEscalated event
   ```

### 3.4 Escalation Management

**Job:** `EscalateOverdueCallbacksJob` (Scheduled Hourly)

**Criteria for Escalation:**
- `expires_at < now()` AND status not completed/expired/cancelled
- No recent escalation within cooldown period (4h default)

**Escalation Reasons:**
```php
- 'sla_breach': Abgelaufen
- 'multiple_attempts_failed': 3+ Kontakt-Versuche
- 'other': Custom reasons
```

**Cooldown Logic:** Verhindert Doppel-Eskalation innerhalb 4 Stunden

---

## 4. Service-Layer

### CallbackManagementService

**Verantwortlichkeiten:**
- Callback creation with transactional safety
- Staff assignment
- Contact tracking
- Completion workflow
- Escalation management
- Overdue detection

**Key Methods:**
```php
createRequest(array $data): CallbackRequest
assignToStaff(CallbackRequest, Staff): void
markContacted(CallbackRequest): void
markCompleted(CallbackRequest, string $notes): void
escalate(CallbackRequest, string $reason): CallbackEscalation
getOverdueCallbacks(Branch): Collection
```

**Expiration Calculation:**
```php
urgent: config('callbacks.expiration_hours.urgent', 2) hours
high:   config('callbacks.expiration_hours.high', 4) hours
normal: config('callbacks.expiration_hours.normal', 24) hours
```

### CallbackAssignmentService

**Strategien:**

**Round-Robin:**
```php
roundRobinSelection($staff)
  → Cache: callback.last_assigned_staff_id
  → Rotiert durch Staff-Liste
  → TTL: 24h
```

**Load-Based:**
```php
loadBasedSelection($staff)
  → Query: COUNT active callbacks per staff
  → Filter: status IN [pending, assigned]
  → Wählt Staff mit minimalem Load
```

**Reassignment:**
```php
reassign(CallbackRequest, reason, strategy)
  → Excludes current assignee
  → Logs via metadata
  → Updates callback record
```

---

## 5. Events & Listeners

### CallbackRequested Event

**Fired:** CallbackManagementService::createRequest()

**Properties:**
```php
public readonly CallbackRequest $callbackRequest
public readonly ?string $preferredTime
public readonly ?string $topic
```

**Listeners:**
1. `AssignCallbackToStaff` (Queue: callbacks)
2. SendCallbackConfirmation (optional)
3. CRM integrations (optional)

**Context Methods:**
```php
getPriority(): string
  → Detects if customer has active appointments

getContext(): array
  → Logs customer_id, call_id, topic, etc.
```

### CallbackEscalated Event

**Properties:**
```php
CallbackRequest $callback
string $reason
string $type (auto|manual)
?string $escalatedTo
```

---

## 6. Observer Pattern

### CallbackRequestObserver (app/Observers/CallbackRequestObserver.php)

**Lifecycle Hooks:**

**creating() / updating():**
- Input sanitization (XSS prevention)
- Phone number validation (E.164 format)

**saving():**
- Auto-set `expires_at` if not provided
- Auto-set `assigned_at` when assigned
- Auto-set `status = assigned` when assigned

**Example Validation:**
```php
E.164 Format: +[1-9]\d{1,14}$
Example: +491234567890
Min digits: 7, Max digits: 15
```

---

## 7. UI Layer - Filament Resource

### Pages

- **ListCallbackRequests** (app/Filament/Resources/CallbackRequestResource/Pages/ListCallbackRequests.php)
- **CreateCallbackRequest**
- **EditCallbackRequest**
- **ViewCallbackRequest**

### Table Features

**Columns:**
```
ID | Customer Name (+ Phone) | Status | Priority | Branch
Service | Assigned To | Expires At | Created At | Escalations Count
```

**Status Badge Colors:**
```php
pending   → warning (gelb)
assigned  → info (blau)
contacted → primary (blau)
completed → success (grün)
expired   → danger (rot)
cancelled → gray
```

**Filters:**
- Status (Multi-select)
- Priority (Multi-select)
- Branch (Dropdown)
- Overdue (Ternary)
- Created At (Date range)
- Trash (Soft-deleted)

**Default Sort:** created_at DESC

### Actions

#### Row Actions (ActionGroup)

**assign**
- Show: When not assigned
- Form: Staff dropdown
- Confirmation required

**autoAssign**
- Show: When not assigned
- Strategy: round_robin | load_based
- Notification: Shows assigned staff name

**markContacted**
- Show: When status = assigned
- Confirmation required
- Success notification

**markCompleted**
- Show: When status = contacted
- Form: Completion notes textarea
- Appends notes to record

**escalate**
- Show: When status ≠ completed
- Form: Reason select + Details textarea
- Options:
  ```
  no_response
  technical_issue
  customer_complaint
  urgent_request
  complex_case
  other
  ```

#### Bulk Actions

**bulkAssign**
- Select staff for all selected
- Confirmation required

**bulkAutoAssign**
- Choose strategy
- Shows assigned/failed count

**bulkComplete**
- Mark all as completed
- Confirmation required

### Form Tabs

**Tabs → "Kontaktdaten":**
- Customer (searchable select with create option)
- Branch (required)
- Phone number (tel input)
- Customer name (text input)

**Tabs → "Details":**
- Service (select)
- Priority (select)
- Preferred time window (KeyValue)
- Notes (textarea)

**Tabs → "Zuweisung":**
- Preferred staff (select)
- Assigned staff (select)
- Status (select)
- Expires at (datetime picker)

### Infolist

**Sections:**

**Hauptinformationen:**
- ID | Status Badge | Priority Badge
- Customer Name | Phone | Email
- Service | Preferred Staff | Preferred Time Window
- Notes (Markdown)

**Bearbeitung:**
- Assigned to | Contacted at | Completed at

**Zeitplanung:**
- Created at | Expires at | Is Overdue (badge)

**Eskalationen:**
- RepeatableEntry: reason | from | to | escalated_at

---

## 8. Widgets

### CallbacksByBranchWidget (Stats)

**Metrics:**
```
Ausstehende Rückrufe [pending_count]
  → 7-day trend chart
  → Link to filtered list

Überfällige Rückrufe [overdue_count]
  → 7-day trend chart
  → Link to filtered list

Heute abgeschlossen [completed_today]
  → 7-day trend chart
  → Link to filtered list

Ø Reaktionszeit [avg_response_time]
  → 7-day trend chart
  → Color based on hours
```

**Colors:**
```php
response_hours < 2   → success (grün)
response_hours < 6   → info (blau)
response_hours < 24  → warning (gelb)
response_hours >= 24 → danger (rot)
```

**Caching:** 5 minutes (cache key: `callback_stats_widget`)

**Performance:** Single optimized query with aggregation

### OverdueCallbacksWidget (Table)

**Polling Interval:** 30 seconds

**Columns:**
```
Customer Name (+ Service) | Phone (copyable)
Priority Badge | Expires at (danger color)
Branch Badge | Assigned To | Status Badge
```

**Actions:** assign | mark_contacted | escalate

**Query:** ORDER BY priority DESC, expires_at ASC LIMIT 100

**Cache Management:**
```php
Cache::forget('overdue_callbacks_query')
Cache::forget('overdue_callbacks_count')
```

---

## 9. Navigation Badge

**Label:** `CallbackRequestResource::getNavigationBadge()`

**Value:** Count of `status = pending` callbacks

**Caching:**
```php
getCachedBadge(function() {
  return CallbackRequest::where('status', 'pending')->count()
})
```

**Color Logic:**
```php
count > 10 → 'danger' (rot)
count > 5  → 'warning' (gelb)
count ≤ 5  → 'info' (blau)
```

---

## 10. Sicherheit & Validierung

### Multi-Tenant Isolation

**Model Trait:** `BelongsToCompany`
- Automatischer company_id filter
- RLS (Row Level Security) via companyscope column

**Authorization:** `CallbackRequestPolicy`
- view_any, view, create, update, delete, restore, forceDelete

### Input Validation

**Observer:**
```php
sanitizeUserInput(CallbackRequest)
  → strip_tags($customer_name)
  → htmlspecialchars(..., ENT_QUOTES)
  → Gleiches für notes

validatePhoneNumber(CallbackRequest)
  → E.164 format: /^\+[1-9]\d{1,14}$/
  → 7-15 digits
```

### Enum Validation

**On save():**
```php
priority ∈ [normal, high, urgent]
status ∈ [pending, assigned, contacted, completed, expired, cancelled]
```

**Exception:** `InvalidArgumentException`

---

## 11. Database Schema

### Migration: 2025_10_01_060203_create_callback_requests_table

**Indexes:**
```
idx_company: company_id
idx_status_priority_expires: (company_id, status, priority, expires_at)
idx_assigned_status: (company_id, assigned_to, status)
idx_company_customer: (company_id, customer_id)
branch_id
idx_company_created: (company_id, created_at)
```

**Soft Deletes:** Yes
**Timestamps:** created_at, updated_at

---

## 12. Cache Strategy

**Keys:**
```
callback_stats_widget        → Widget stats (5min)
overdue_callbacks_count      → Count only (5min)
nav_badge_callbacks_pending  → Navigation (on invalidate)
callback.last_assigned_staff_id → Round-robin (24h)
```

**Invalidation:**
- On status change
- On soft delete
- Bulk actions

---

## 13. Performance Optimizations

### Query Optimization

**Widget Queries:**
```php
Single SELECT with aggregation (no loop queries)
7-day trends: GROUP BY date with 7 queries max
Eager loading: with(['branch', 'service', 'assignedTo'])
```

**Assignment Queries:**
```php
withCount(['callbackRequests' => function...])
Single query per strategy
```

### Caching Strategy

**5-minute cache:** Stats, badge
**24-hour cache:** Round-robin state
**Event-based invalidation:** Status changes

### Limits

**Overdue Widget:** LIMIT 100 (memory protection)
**Pagination:** Default 25 items

---

## 14. Configuration

### Configurable Values

```php
config('callbacks.auto_assign', true)
config('callbacks.expiration_hours.urgent', 2)
config('callbacks.expiration_hours.high', 4)
config('callbacks.expiration_hours.normal', 24)
config('callbacks.max_contact_attempts', 3)
config('callbacks.escalation_cooldown_hours', 4)
```

---

## 15. Logging & Monitoring

### Log Patterns

```php
✅ Created callback request
⚠️ Callback assigned to staff
📞 Callback marked as contacted
✅ Callback completed
⚠️ Callback escalated
❌ Failed to create callback request
```

### Monitored Events

- Creation (with customer_name, phone, priority)
- Assignment (staff_id, name)
- Contact attempts (metadata tracking)
- Escalations (reason, target staff)
- Failures (error message, trace)

---

## 16. Verbesserungspotenziale

### Short-term (Quick Wins)

1. **SMS Notifications**
   ```
   - On callback assigned (to customer)
   - On staff available (to staff)
   ```

2. **Email Integration**
   ```
   - Callback confirmation email
   - Escalation alerts
   ```

3. **Bulk Scheduling**
   ```
   - Preferred callback time (smart scheduling)
   - Avoid manual rescheduling
   ```

### Medium-term (Architecture)

1. **Advanced Reporting**
   ```
   - Callback success rate
   - Staff performance metrics
   - SLA compliance dashboard
   ```

2. **Customer Self-Service**
   ```
   - Reschedule own callback
   - Cancel callback request
   - Callback history view
   ```

3. **Availability Check**
   ```
   - Real-time staff availability
   - Working hours integration
   - Leave/absence handling
   ```

### Long-term (Strategic)

1. **Predictive Assignment**
   ```
   - ML-based staff selection
   - Customer-staff affinity
   - Historical success rates
   ```

2. **Chatbot Integration**
   ```
   - Self-service callback booking
   - Rescheduling via chatbot
   - Multi-language support
   ```

3. **Advanced Analytics**
   ```
   - Customer satisfaction tracking
   - Callback conversion metrics
   - ROI analysis
   ```

---

## 17. Troubleshooting

### Common Issues

**No staff available for assignment**
- Check: Staff active status
- Check: Branch assignment
- Check: Service expertise matching
- Action: Manual assignment via admin

**Callback not marked as contacted**
- Check: Current status must be "assigned"
- Check: User permissions (Policy)
- Action: Refresh page and retry

**Escalation not triggering**
- Check: `expires_at` is in past
- Check: Not in escalation cooldown (4h)
- Check: Queue worker running
- Action: Check logs in `storage/logs/laravel.log`

**Performance degradation in widget**
- Check: Database indexes
- Check: Cache hit rate
- Action: Clear cache: `php artisan cache:clear`

---

## 18. Related Documentation

- `callbackRequests` Filament Resource: Line 1-897
- `CallbackManagementService`: Backend logic
- `CallbackAssignmentService`: Assignment strategies
- `EscalateOverdueCallbacksJob`: Scheduled escalation
- `RetellFunctionCallHandler`: Integration point

---

## 19. Metrics

**Current State (2025-10-18):**
- Status Badges: Cached ✓
- Query Optimization: Indexed ✓
- Multi-Tenant: RLS secured ✓
- Input Validation: XSS protected ✓
- Event-driven: Async queued ✓

**Performance Targets:**
- Page load: < 2s (optimized widgets)
- Auto-assignment: < 500ms
- Escalation job: < 5min per batch

---

**Document Version:** 1.0
**Last Updated:** 2025-10-18
**Status:** Complete ✓
