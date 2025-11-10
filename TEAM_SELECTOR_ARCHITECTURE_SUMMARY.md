# Team-Selector Architecture Summary

**Quick reference for the Team-Selector backend implementation**

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (Cal.com Widget)                 │
│                  Team Selection Dropdown UI                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      │ HTTP API
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                   API LAYER (Laravel)                        │
│                                                              │
│  CalcomTeamController                                        │
│  ├─ GET  /api/calcom/teams                                  │
│  ├─ GET  /api/calcom/teams/default                          │
│  ├─ GET  /api/calcom/teams/{team}/event-types               │
│  ├─ POST /api/calcom/teams/sync                   (admin)   │
│  └─ POST /api/calcom/teams/{team}/sync-event-types (admin)  │
│                                                              │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                   SERVICE LAYER                              │
│                                                              │
│  CalcomTeamService                                           │
│  ├─ getTeamsForCompany($companyId)                          │
│  ├─ getTeamsForBranch($branchId)                            │
│  ├─ getDefaultTeam($companyId)                              │
│  ├─ syncTeamsForCompany($companyId)                         │
│  ├─ syncEventTypesForTeam($team)                            │
│  └─ invalidateTeamCache($companyId)                         │
│                                                              │
│  CalcomService (Extended)                                    │
│  ├─ fetchTeams()                                             │
│  └─ fetchTeamEventTypes($teamId)                            │
│                                                              │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                   CACHE LAYER (Redis)                        │
│                                                              │
│  Cache Keys (TTL: 1 hour)                                    │
│  ├─ company:{id}:calcom_teams:all                           │
│  ├─ company:{id}:calcom_teams:default                       │
│  ├─ company:{id}:calcom_team:{team_id}:event_types (5min)   │
│  └─ branch:{id}:calcom_teams                                │
│                                                              │
│  Invalidation: Event-driven via CalcomTeamObserver           │
│                                                              │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                   DATA LAYER (PostgreSQL)                    │
│                                                              │
│  calcom_teams                                                │
│  ├─ id, company_id, branch_id                               │
│  ├─ calcom_team_id, calcom_team_slug                        │
│  ├─ name, timezone, is_active, is_default                   │
│  └─ sync_status, metadata                                   │
│                                                              │
│  calcom_team_event_types                                     │
│  ├─ calcom_team_id, calcom_event_type_id                    │
│  ├─ service_id (nullable)                                   │
│  ├─ event_type_title, event_type_slug                       │
│  └─ duration_minutes, price, is_active                      │
│                                                              │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│               EXTERNAL API (Cal.com v2)                      │
│                                                              │
│  GET  /teams                                                 │
│  GET  /teams/{id}/event-types                               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Primary Tables

**calcom_teams**
```sql
id                  BIGINT PK
company_id          BIGINT FK → companies.id (CASCADE DELETE)
branch_id           BIGINT FK → branches.id (NULL ON DELETE) [nullable]
calcom_team_id      BIGINT (Cal.com Team ID)
calcom_team_slug    VARCHAR(100)
name                VARCHAR(255)
description         TEXT [nullable]
timezone            VARCHAR(50) DEFAULT 'Europe/Berlin'
is_active           BOOLEAN DEFAULT true
is_default          BOOLEAN DEFAULT false
last_synced_at      TIMESTAMP [nullable]
sync_status         VARCHAR(20) DEFAULT 'pending'
sync_error          TEXT [nullable]
metadata            JSON [nullable]
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP [nullable] (soft delete)

UNIQUE: (company_id, calcom_team_id)
UNIQUE: (company_id, calcom_team_slug)
INDEX:  (company_id, is_active)
INDEX:  (company_id, is_default)
INDEX:  (branch_id)
```

**calcom_team_event_types**
```sql
id                      BIGINT PK
calcom_team_id          BIGINT FK → calcom_teams.id (CASCADE DELETE)
calcom_event_type_id    BIGINT (Cal.com Event Type ID)
service_id              BIGINT FK → services.id (NULL ON DELETE) [nullable]
event_type_title        VARCHAR(255)
event_type_slug         VARCHAR(255)
duration_minutes        INT [nullable]
price                   DECIMAL(10,2) [nullable]
is_active               BOOLEAN DEFAULT true
last_synced_at          TIMESTAMP [nullable]
created_at              TIMESTAMP
updated_at              TIMESTAMP

UNIQUE: (calcom_team_id, calcom_event_type_id)
INDEX:  (calcom_team_id, is_active)
INDEX:  (service_id)
```

---

## Data Relationships

```
companies (1) ──┬─> (N) calcom_teams
                │
branches (1) ───┴─> (N) calcom_teams [optional]
                     │
                     └─> (N) calcom_team_event_types
                              │
                              └─> (1) services [optional]
```

---

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/calcom/teams` | Sanctum | List all teams for user's company |
| GET | `/api/calcom/teams?branch_id=X` | Sanctum | List teams for specific branch |
| GET | `/api/calcom/teams/default` | Sanctum | Get default team for company |
| GET | `/api/calcom/teams/{team}/event-types` | Sanctum | Get event types for team |
| POST | `/api/calcom/teams/sync` | Sanctum + Admin | Sync teams from Cal.com |
| POST | `/api/calcom/teams/{team}/sync-event-types` | Sanctum + Admin | Sync event types for team |

**Query Parameters:**
- `branch_id` (optional): Filter teams by branch

**Response Format:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Friseur Team",
      "slug": "friseur",
      "calcom_team_id": 34209,
      "branch_id": 5,
      "branch_name": "Mitte",
      "is_active": true,
      "is_default": true,
      "timezone": "Europe/Berlin",
      "synced_at": "2025-11-10T10:00:00Z"
    }
  ]
}
```

---

## Multi-Tenant Security

### Security Layers

1. **Model-Level**: `BelongsToCompany` trait auto-scopes all queries
   ```php
   CalcomTeam::all(); // Automatically filtered by company_id
   ```

2. **Middleware**: `auth:sanctum` + `companyscope`
   ```php
   Route::middleware(['auth:sanctum', 'companyscope']);
   ```

3. **Controller**: Explicit company verification
   ```php
   if ($team->company_id !== Auth::user()->company_id) {
       abort(403);
   }
   ```

4. **Cache**: Company-scoped cache keys
   ```php
   company:{company_id}:calcom_teams:all
   ```

5. **Database**: Foreign key constraints with cascade deletes

---

## Caching Strategy

### Cache Keys

| Key Pattern | TTL | Invalidation |
|-------------|-----|--------------|
| `company:{id}:calcom_teams:all` | 1 hour | Team CRUD, sync |
| `company:{id}:calcom_teams:default` | 1 hour | Default team change |
| `company:{id}:calcom_team:{tid}:event_types` | 5 min | Event type sync |
| `branch:{id}:calcom_teams` | 1 hour | Branch-team assignment |

### Cache Invalidation

**Triggers:**
- Team created/updated/deleted → `CalcomTeamObserver`
- Team sync completed → `CalcomTeamService::syncTeamsForCompany()`
- Event type sync → `CalcomTeamService::syncEventTypesForTeam()`

**Automatic:**
```php
// Observer handles automatic invalidation
CalcomTeam::observe(CalcomTeamObserver::class);

// Manual invalidation
$teamService->invalidateTeamCache($companyId);
```

---

## Service Layer Methods

### CalcomTeamService

```php
// Fetch teams
$teams = $teamService->getTeamsForCompany($companyId);
$teams = $teamService->getTeamsForBranch($branchId);
$team = $teamService->getDefaultTeam($companyId);

// Sync operations
$result = $teamService->syncTeamsForCompany($companyId);
$result = $teamService->syncEventTypesForTeam($team);

// Cache management
$teamService->invalidateTeamCache($companyId);
```

### CalcomService (Extended)

```php
// Cal.com API calls
$response = $calcomService->fetchTeams();
$response = $calcomService->fetchTeamEventTypes($teamId);
```

---

## Migration Plan

### Phase 1: Database Setup
```bash
php artisan migrate --path=database/migrations/2025_11_10_000000_create_calcom_teams_table.php
php artisan migrate --path=database/migrations/2025_11_10_000001_create_calcom_team_event_types_table.php
```

### Phase 2: Data Backfill
```bash
php artisan migrate --path=database/migrations/2025_11_10_000002_backfill_calcom_teams_from_branches.php
```

### Phase 3: Verification
```sql
-- Check backfilled data
SELECT company_id, COUNT(*) FROM calcom_teams GROUP BY company_id;

-- Verify relationships
SELECT ct.name, b.name as branch_name, c.name as company_name
FROM calcom_teams ct
LEFT JOIN branches b ON ct.branch_id = b.id
JOIN companies c ON ct.company_id = c.id;
```

---

## Backward Compatibility

### Existing `branches.calcom_team_id` Column

**Status**: KEPT (not removed)

**Usage**: Fallback mechanism
```php
// Priority order:
// 1. Explicit calcom_teams mapping
// 2. Fallback to branches.calcom_team_id
// 3. Default team for company

public function getTeamForBranch(Branch $branch): ?CalcomTeam
{
    // 1. Explicit mapping
    $team = CalcomTeam::forBranch($branch->id)->active()->first();
    if ($team) return $team;

    // 2. Fallback to old column
    if ($branch->calcom_team_id) {
        return CalcomTeam::where('company_id', $branch->company_id)
            ->where('calcom_team_id', $branch->calcom_team_id)
            ->first();
    }

    // 3. Default team
    return $this->getDefaultTeam($branch->company_id);
}
```

---

## Testing Checklist

### Unit Tests
- [ ] Team retrieval with caching
- [ ] Multi-tenant isolation (company A can't see company B teams)
- [ ] Default team selection
- [ ] Cache invalidation on team updates
- [ ] Sync operations

### Integration Tests
- [ ] API authentication required
- [ ] User can only access their company's teams
- [ ] Admin-only sync operations
- [ ] Event type fetching with team verification

### Manual Tests
- [ ] Team dropdown UI displays correct teams
- [ ] Team selection updates booking flow
- [ ] Event types filter by selected team
- [ ] Cache performance (>95% hit rate)

---

## Performance Targets

| Metric | Target | Monitoring |
|--------|--------|------------|
| Cache Hit Rate | >95% | Redis INFO stats |
| API Response Time | <100ms | Laravel Telescope |
| Team Sync Duration | <5s per team | Application logs |
| Multi-tenant Query | <50ms | Database slow query log |

---

## Common Operations

### Admin: Sync Teams

```bash
# Via API
curl -X POST "https://api.askpro.ai/api/calcom/teams/sync" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Via Tinker
php artisan tinker
>>> $service = app(\App\Services\Calcom\CalcomTeamService::class);
>>> $service->syncTeamsForCompany(1);
```

### Developer: Clear Team Cache

```bash
php artisan tinker
>>> Cache::forget('company:1:calcom_teams:all');
>>> Cache::forget('company:1:calcom_teams:default');
```

### User: Get Available Teams

```javascript
// Frontend API call
fetch('/api/calcom/teams')
  .then(res => res.json())
  .then(data => {
    console.log('Available teams:', data.data);
  });
```

---

## Next Steps

1. **Code Implementation**
   - Create migrations
   - Implement models
   - Build service layer
   - Create API controller

2. **Testing**
   - Unit tests for service layer
   - Integration tests for API endpoints
   - Manual testing on staging

3. **Frontend Integration**
   - Add team selector dropdown to Cal.com widget
   - Update booking flow to use selected team
   - Implement team-specific event type filtering

4. **Deployment**
   - Run migrations on staging
   - Verify backfill data
   - Test end-to-end booking flow
   - Deploy to production

---

**Full Details**: See `TEAM_SELECTOR_ARCHITECTURE.md`
