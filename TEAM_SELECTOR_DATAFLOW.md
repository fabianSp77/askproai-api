# Team-Selector Data Flow Diagrams

Visual representations of data flow through the Team-Selector system.

---

## 1. Team Retrieval Flow

```
┌───────────┐
│  User UI  │ Team Dropdown
└─────┬─────┘
      │
      │ GET /api/calcom/teams
      ▼
┌───────────────────────────────────┐
│  CalcomTeamController::index()    │
│  ├─ Auth check (Sanctum)          │
│  ├─ Get user's company_id         │
│  └─ Call service layer            │
└─────┬─────────────────────────────┘
      │
      ▼
┌───────────────────────────────────┐
│  CalcomTeamService                │
│  getTeamsForCompany($companyId)   │
└─────┬─────────────────────────────┘
      │
      │ Check cache first
      ▼
┌───────────────────────────────────┐
│  Redis Cache                      │
│  Key: company:1:calcom_teams:all  │
└─────┬─────────────────────────────┘
      │
      ├─ Cache HIT? ──Yes──> Return cached data (< 5ms)
      │
      └─ Cache MISS
          │
          ▼
    ┌───────────────────────────────┐
    │  PostgreSQL Database          │
    │  SELECT * FROM calcom_teams   │
    │  WHERE company_id = 1         │
    │  AND is_active = true         │
    └─────┬─────────────────────────┘
          │
          │ Store in cache (TTL: 1h)
          ▼
    ┌───────────────────────────────┐
    │  Return to User (50-100ms)    │
    └───────────────────────────────┘
```

---

## 2. Team Sync Flow

```
┌───────────┐
│  Admin UI │ Click "Sync Teams"
└─────┬─────┘
      │
      │ POST /api/calcom/teams/sync
      ▼
┌───────────────────────────────────┐
│  CalcomTeamController::sync()     │
│  ├─ Auth check (admin role)       │
│  ├─ Get user's company_id         │
│  └─ Call service layer            │
└─────┬─────────────────────────────┘
      │
      ▼
┌───────────────────────────────────┐
│  CalcomTeamService                │
│  syncTeamsForCompany($companyId)  │
└─────┬─────────────────────────────┘
      │
      │ Step 1: Fetch from Cal.com
      ▼
┌───────────────────────────────────┐
│  CalcomService::fetchTeams()      │
│  GET https://api.cal.com/v2/teams │
└─────┬─────────────────────────────┘
      │
      │ Response: Team data (JSON)
      ▼
┌───────────────────────────────────┐
│  CalcomTeamService                │
│  createOrUpdateTeam() for each    │
└─────┬─────────────────────────────┘
      │
      │ Step 2: Upsert to database
      ▼
┌───────────────────────────────────┐
│  PostgreSQL Database              │
│  INSERT INTO calcom_teams         │
│  ON CONFLICT (company_id,         │
│    calcom_team_id) DO UPDATE      │
└─────┬─────────────────────────────┘
      │
      │ Step 3: Mark as synced
      ▼
┌───────────────────────────────────┐
│  CalcomTeam::markAsSynced()       │
│  sync_status = 'synced'           │
│  last_synced_at = now()           │
└─────┬─────────────────────────────┘
      │
      │ Step 4: Invalidate cache
      ▼
┌───────────────────────────────────┐
│  CalcomTeamObserver::saved()      │
│  Cache::forget(...)               │
└─────┬─────────────────────────────┘
      │
      ▼
    Return sync results to admin
    { success: true, synced: 3 }
```

---

## 3. Event Type Retrieval Flow

```
┌───────────┐
│  User UI  │ Service Selector
└─────┬─────┘
      │
      │ GET /api/calcom/teams/{team}/event-types
      ▼
┌───────────────────────────────────────┐
│  CalcomTeamController                 │
│  getEventTypes(CalcomTeam $team)      │
│  ├─ Verify team.company_id matches    │
│  └─ Get event types from relationship │
└─────┬─────────────────────────────────┘
      │
      ▼
┌───────────────────────────────────────┐
│  PostgreSQL Database                  │
│  SELECT * FROM calcom_team_event_types│
│  WHERE calcom_team_id = X             │
│  AND is_active = true                 │
└─────┬─────────────────────────────────┘
      │
      │ Return event types
      ▼
┌───────────────────────────────────────┐
│  User UI                              │
│  Display services for selected team   │
└───────────────────────────────────────┘
```

---

## 4. Multi-Tenant Isolation Flow

```
Company A User                      Company B User
      │                                   │
      │ GET /api/calcom/teams             │ GET /api/calcom/teams
      ▼                                   ▼
┌──────────────────┐              ┌──────────────────┐
│  Auth Middleware │              │  Auth Middleware │
│  company_id = 1  │              │  company_id = 2  │
└────────┬─────────┘              └────────┬─────────┘
         │                                  │
         ▼                                  ▼
┌──────────────────────────────────────────────────────┐
│  CompanyScope Middleware (Global)                    │
│  Sets: DB::connection()->whereCompanyId(...)         │
└─────┬──────────────────────────────┬─────────────────┘
      │                              │
      ▼                              ▼
┌────────────────┐            ┌────────────────┐
│  BelongsToCompany          │  BelongsToCompany
│  Trait (Model) │            │  Trait (Model) │
│  Auto-scope:   │            │  Auto-scope:   │
│  company_id=1  │            │  company_id=2  │
└────────┬───────┘            └────────┬───────┘
         │                              │
         ▼                              ▼
┌────────────────┐            ┌────────────────┐
│  Cache Layer   │            │  Cache Layer   │
│  Key:          │            │  Key:          │
│  company:1:... │            │  company:2:... │
└────────┬───────┘            └────────┬───────┘
         │                              │
         ▼                              ▼
┌────────────────┐            ┌────────────────┐
│  PostgreSQL    │            │  PostgreSQL    │
│  WHERE         │            │  WHERE         │
│  company_id=1  │            │  company_id=2  │
└────────┬───────┘            └────────┬───────┘
         │                              │
         ▼                              ▼
    Team A1, A2                     Team B1, B2
    (Company A only)                (Company B only)

✅ ISOLATION VERIFIED: Users never see other companies' teams
```

---

## 5. Cache Invalidation Flow

```
┌───────────┐
│  Admin    │ Updates Team Name
└─────┬─────┘
      │
      │ PATCH /api/calcom/teams/{id}
      ▼
┌───────────────────────────────────┐
│  CalcomTeam Model                 │
│  $team->update(['name' => '...']) │
└─────┬─────────────────────────────┘
      │
      │ Eloquent Event: "saved"
      ▼
┌───────────────────────────────────┐
│  CalcomTeamObserver::saved()      │
│  Triggered automatically          │
└─────┬─────────────────────────────┘
      │
      │ Identify cache keys to clear
      ▼
┌───────────────────────────────────┐
│  Build cache key patterns:        │
│  ├─ company:{id}:calcom_teams:all │
│  ├─ company:{id}:calcom_teams:default│
│  ├─ company:{id}:calcom_team:{id}:*│
│  └─ branch:{branch_id}:calcom_teams│
└─────┬─────────────────────────────┘
      │
      │ Clear each key
      ▼
┌───────────────────────────────────┐
│  Redis Cache::forget()            │
│  Removes stale data               │
└─────┬─────────────────────────────┘
      │
      ▼
    Next request fetches fresh data
    Cache MISS → DB query → Cache store
```

---

## 6. Booking with Team Selection Flow

```
┌───────────┐
│  User UI  │
└─────┬─────┘
      │
      │ 1. Select Team
      ▼
┌───────────────────────────────────┐
│  GET /api/calcom/teams            │
│  Response: [Team A, Team B]       │
└─────┬─────────────────────────────┘
      │
      │ User selects "Team A"
      │ team_id = 5
      ▼
┌───────────────────────────────────┐
│  2. Fetch Event Types for Team    │
│  GET /api/calcom/teams/5/event-types│
│  Response: [Service 1, Service 2] │
└─────┬─────────────────────────────┘
      │
      │ User selects "Service 1"
      │ event_type_id = 123
      ▼
┌───────────────────────────────────┐
│  3. Check Availability            │
│  GET /api/retell/check-availability│
│  Params:                          │
│  ├─ team_id: 5                    │
│  ├─ event_type_id: 123            │
│  ├─ date: 2025-11-15              │
│  └─ duration: 30                  │
└─────┬─────────────────────────────┘
      │
      │ CalcomService uses team_id
      ▼
┌───────────────────────────────────┐
│  Cal.com API                      │
│  GET /slots/available             │
│  ?teamId=34209                    │
│  &eventTypeId=123                 │
│  &startTime=...                   │
└─────┬─────────────────────────────┘
      │
      │ Returns available slots
      ▼
┌───────────────────────────────────┐
│  4. User Selects Slot             │
│  slot: 2025-11-15 10:00           │
└─────┬─────────────────────────────┘
      │
      │ 5. Create Booking
      ▼
┌───────────────────────────────────┐
│  POST /api/retell/book-appointment│
│  Params:                          │
│  ├─ team_id: 5                    │
│  ├─ event_type_id: 123            │
│  ├─ start: 2025-11-15T10:00:00Z   │
│  └─ attendee: {...}               │
└─────┬─────────────────────────────┘
      │
      │ CalcomService::createBooking()
      ▼
┌───────────────────────────────────┐
│  Cal.com API                      │
│  POST /bookings                   │
│  { teamId: 34209,                 │
│    eventTypeId: 123,              │
│    start: "...", ... }            │
└─────┬─────────────────────────────┘
      │
      │ Booking created
      ▼
┌───────────────────────────────────┐
│  6. Cache Invalidation            │
│  Clear availability cache for:    │
│  ├─ team_id: 5                    │
│  └─ event_type_id: 123            │
└─────┬─────────────────────────────┘
      │
      ▼
    Booking confirmation sent to user
```

---

## 7. Security Verification Flow

```
┌───────────┐
│  User A   │ Company 1
└─────┬─────┘
      │
      │ GET /api/calcom/teams/99
      │ (Team 99 belongs to Company 2)
      ▼
┌───────────────────────────────────┐
│  CalcomTeamController             │
│  getEventTypes(CalcomTeam $team)  │
└─────┬─────────────────────────────┘
      │
      │ Step 1: Route Model Binding
      ▼
┌───────────────────────────────────┐
│  Laravel finds team by ID 99      │
│  (No company check yet)           │
└─────┬─────────────────────────────┘
      │
      │ Step 2: Explicit verification
      ▼
┌───────────────────────────────────┐
│  if ($team->company_id !== Auth::user()->company_id) │
│      abort(403, 'Unauthorized');  │
└─────┬─────────────────────────────┘
      │
      │ team->company_id = 2
      │ user->company_id = 1
      │ MISMATCH!
      ▼
┌───────────────────────────────────┐
│  HTTP 403 Forbidden               │
│  "Unauthorized access to team"    │
└───────────────────────────────────┘

✅ SECURITY: Cross-company access prevented
```

---

## 8. Performance Optimization Flow

```
Scenario: 10 concurrent users request teams

User 1 ─┐
User 2 ─┤
User 3 ─┤
...     ├─> GET /api/calcom/teams (simultaneous)
User 8 ─┤
User 9 ─┤
User 10 ┘
      │
      ▼
┌───────────────────────────────────┐
│  Cache Check                      │
│  Key: company:1:calcom_teams:all  │
└─────┬─────────────────────────────┘
      │
      ├─ Exists? ──Yes──> 10 users get cached data
      │                   (< 5ms each, no DB hit)
      │
      └─ Missing?
          │
          ▼
    ┌───────────────────────────────┐
    │  Request Coalescing           │
    │  (Redis Lock)                 │
    └─────┬─────────────────────────┘
          │
          ├─ User 1 wins lock
          │   ├─> Fetches from DB
          │   └─> Stores in cache
          │
          └─ Users 2-10 wait for User 1
              └─> All get cached result

✅ PERFORMANCE:
   - Without cache: 10 DB queries (500ms total)
   - With cache: 1 DB query, 9 cache hits (50ms total)
   - Improvement: 10x faster
```

---

## 9. Error Handling Flow

```
┌───────────┐
│  Admin    │ Sync Teams
└─────┬─────┘
      │
      │ POST /api/calcom/teams/sync
      ▼
┌───────────────────────────────────┐
│  CalcomTeamService::syncTeamsForCompany│
└─────┬─────────────────────────────┘
      │
      │ Call Cal.com API
      ▼
┌───────────────────────────────────┐
│  CalcomService::fetchTeams()      │
│  GET https://api.cal.com/v2/teams │
└─────┬─────────────────────────────┘
      │
      ├─ Success (200)
      │   └─> Process teams → Return success
      │
      ├─ API Error (4xx/5xx)
      │   │
      │   ▼
      │ ┌───────────────────────────┐
      │ │  CalcomApiException       │
      │ │  thrown with status code  │
      │ └─────┬─────────────────────┘
      │       │
      │       ▼
      │   Log error → Return failure
      │
      └─ Network Error (timeout, DNS)
          │
          ▼
        ┌───────────────────────────┐
        │  Circuit Breaker Check    │
        │  5 failures → OPEN        │
        └─────┬─────────────────────┘
              │
              ├─ Circuit CLOSED
              │   └─> Retry request
              │
              └─ Circuit OPEN
                  │
                  ▼
              ┌───────────────────────┐
              │  Return cached data   │
              │  (degraded mode)      │
              │  Error: "Cal.com      │
              │  temporarily          │
              │  unavailable"         │
              └───────────────────────┘

✅ RESILIENCE: System continues with cached data during Cal.com outage
```

---

## 10. Data Consistency Flow

```
┌─────────────────────────────────────────────────────┐
│  Scenario: Admin updates team in Cal.com directly   │
└─────────────────────┬───────────────────────────────┘
                      │
                      │ Team name changed in Cal.com
                      ▼
            ┌───────────────────┐
            │  Cal.com Platform │
            │  Team A → Team A' │
            └─────────┬─────────┘
                      │
                      │ No automatic webhook
                      │ (manual sync needed)
                      ▼
            ┌───────────────────┐
            │  AskPro Database  │
            │  Still shows:     │
            │  Team A (stale)   │
            └─────────┬─────────┘
                      │
                      │ Admin triggers sync
                      ▼
            ┌───────────────────────────┐
            │  POST /api/calcom/teams/sync │
            └─────────┬─────────────────┘
                      │
                      ▼
            ┌───────────────────────────┐
            │  Fetch from Cal.com       │
            │  GET /teams               │
            └─────────┬─────────────────┘
                      │
                      │ Response: Team A'
                      ▼
            ┌───────────────────────────┐
            │  Update database          │
            │  UPDATE calcom_teams      │
            │  SET name = 'Team A''     │
            │  WHERE calcom_team_id = X │
            └─────────┬─────────────────┘
                      │
                      │ Mark as synced
                      ▼
            ┌───────────────────────────┐
            │  sync_status = 'synced'   │
            │  last_synced_at = now()   │
            └─────────┬─────────────────┘
                      │
                      │ Invalidate cache
                      ▼
            ┌───────────────────────────┐
            │  Cache::forget(...)       │
            └─────────┬─────────────────┘
                      │
                      ▼
            ┌───────────────────────────┐
            │  Next request sees:       │
            │  Team A' (fresh)          │
            └───────────────────────────┘

✅ DATA CONSISTENCY: Manual sync ensures local data matches Cal.com
```

---

## Summary of Data Flow Patterns

### Read Operations (GET)
1. **Cache First**: Check Redis before database
2. **Multi-Tenant**: All queries scoped by company_id
3. **Fast Response**: <100ms target with caching

### Write Operations (POST/PUT/DELETE)
1. **Database First**: Write to PostgreSQL immediately
2. **Cache Invalidation**: Clear affected cache keys
3. **Event-Driven**: Observers handle side effects

### Sync Operations
1. **External API**: Fetch from Cal.com
2. **Upsert Pattern**: Create or update existing records
3. **Status Tracking**: Mark sync status and timestamp
4. **Error Resilience**: Circuit breaker for API failures

### Security Pattern
1. **Authentication**: Sanctum Bearer token
2. **Authorization**: Company_id verification
3. **Isolation**: Automatic query scoping
4. **Cache Safety**: Company-scoped cache keys

---

**Related Documents:**
- Architecture: `TEAM_SELECTOR_ARCHITECTURE.md`
- Summary: `TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md`
- Implementation: `TEAM_SELECTOR_IMPLEMENTATION_GUIDE.md`
- README: `TEAM_SELECTOR_README.md`
