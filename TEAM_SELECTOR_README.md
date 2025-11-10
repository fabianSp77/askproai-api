# Team-Selector Feature - Backend Architecture

**Author**: Backend Architect (Claude Code)
**Date**: 2025-11-10
**Status**: Design Complete - Ready for Implementation

---

## Overview

Complete backend architecture design for enabling multi-team Cal.com integration in the AskPro AI Gateway. Allows companies to manage multiple Cal.com teams (e.g., different branches) with proper multi-tenant isolation, caching, and security.

---

## What's Included

### 📋 Documentation Files

1. **TEAM_SELECTOR_ARCHITECTURE.md** (Primary Reference)
   - Complete architecture specification
   - Database schema with migrations
   - Eloquent models with relationships
   - Service layer design
   - API controller endpoints
   - Caching strategy
   - Multi-tenant security
   - Cal.com API integration plan
   - Testing strategy
   - Deployment checklist

2. **TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md** (Quick Reference)
   - Visual architecture diagram
   - Database schema quick reference
   - API endpoint summary
   - Cache key patterns
   - Common operations
   - Performance targets

3. **TEAM_SELECTOR_IMPLEMENTATION_GUIDE.md** (Developer Guide)
   - Step-by-step implementation
   - Testing procedures
   - Troubleshooting guide
   - Deployment checklist
   - Quick reference commands

4. **TEAM_SELECTOR_README.md** (This File)
   - Executive summary
   - File organization
   - Key decisions

---

## Key Features

### Multi-Team Support
- Company can have multiple Cal.com teams (1:N relationship)
- Optional branch-level team assignment
- Default team selection per company
- Team-specific event types (services)

### Security
- **Multi-tenant isolation** via `BelongsToCompany` trait
- **Row-Level Security (RLS)** enforced at database level
- **API route protection** with Sanctum authentication
- **Company-scoped caching** prevents cross-tenant data leaks
- **Explicit verification** in controllers

### Performance
- **Redis caching** with 1-hour TTL for team data
- **Event-driven cache invalidation** via observers
- **Request coalescing** for concurrent Cal.com API calls
- **Query optimization** with proper indexes
- **Target**: >95% cache hit rate, <100ms API response

### Backward Compatibility
- Existing `branches.calcom_team_id` column **preserved**
- Fallback mechanism for legacy data
- Data migration from old structure to new
- No breaking changes to existing APIs

---

## Database Design

### New Tables

**calcom_teams**
- Primary table for team management
- Multi-tenant isolated by `company_id`
- Optional `branch_id` for branch-specific teams
- Soft deletes for data safety
- Sync status tracking

**calcom_team_event_types**
- Junction table: Teams ↔ Event Types
- Caches Cal.com event type metadata
- Links to internal `services` table
- Enables cross-team service availability

### Key Relationships

```
companies (1) ──> (N) calcom_teams
branches (1)  ──> (N) calcom_teams [optional]
calcom_teams (1) ──> (N) calcom_team_event_types
calcom_team_event_types (N) ──> (1) services [optional]
```

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/calcom/teams` | List teams for user's company |
| GET | `/api/calcom/teams?branch_id=X` | Filter teams by branch |
| GET | `/api/calcom/teams/default` | Get default team |
| GET | `/api/calcom/teams/{team}/event-types` | Get team event types |
| POST | `/api/calcom/teams/sync` | Sync from Cal.com (admin) |
| POST | `/api/calcom/teams/{team}/sync-event-types` | Sync event types (admin) |

**Authentication**: All endpoints require Sanctum Bearer token
**Authorization**: Sync endpoints require admin role

---

## Service Layer

### CalcomTeamService

Main service for team operations:
- `getTeamsForCompany($companyId)` - Fetch teams with caching
- `getTeamsForBranch($branchId)` - Branch-specific teams
- `getDefaultTeam($companyId)` - Get default team
- `syncTeamsForCompany($companyId)` - Sync from Cal.com API
- `syncEventTypesForTeam($team)` - Sync team event types
- `invalidateTeamCache($companyId)` - Manual cache clear

### CalcomService Extensions

New methods added to existing `CalcomService`:
- `fetchTeams()` - GET /teams from Cal.com
- `fetchTeamEventTypes($teamId)` - GET /teams/{id}/event-types

---

## Caching Strategy

### Cache Keys

```
company:{company_id}:calcom_teams:all              # TTL: 1 hour
company:{company_id}:calcom_teams:default          # TTL: 1 hour
company:{company_id}:calcom_team:{team_id}:event_types  # TTL: 5 min
branch:{branch_id}:calcom_teams                    # TTL: 1 hour
```

### Invalidation Triggers

- Team CRUD operations → `CalcomTeamObserver`
- Team sync completion → `CalcomTeamService`
- Event type sync → `CalcomTeamService`

---

## Multi-Tenant Security

### Security Layers

1. **Model-Level**: `BelongsToCompany` trait (automatic query scoping)
2. **Middleware**: `auth:sanctum` + `companyscope`
3. **Controller**: Explicit company_id verification
4. **Cache**: Company-scoped cache keys
5. **Database**: Foreign key constraints with cascade

### Verification

All team access patterns verify:
```php
if ($team->company_id !== Auth::user()->company_id) {
    abort(403, 'Unauthorized access');
}
```

---

## Implementation Steps

### Phase 1: Database Setup (30 min)
1. Create migration files from architecture doc
2. Run migrations on staging
3. Verify tables and indexes created
4. Run data backfill migration

### Phase 2: Models & Services (1 hour)
1. Create `CalcomTeam` model with `BelongsToCompany` trait
2. Create `CalcomTeamEventType` model
3. Create `CalcomTeamService` service class
4. Extend `CalcomService` with new methods
5. Create `CalcomTeamObserver` for cache invalidation

### Phase 3: API Layer (45 min)
1. Create `CalcomTeamController`
2. Register routes in `routes/api.php`
3. Add authorization middleware
4. Test endpoints with Postman/curl

### Phase 4: Testing (1 hour)
1. Unit tests for service layer
2. Integration tests for API endpoints
3. Multi-tenant isolation tests
4. Cache behavior tests
5. Performance tests

### Phase 5: Deployment (30 min)
1. Deploy to staging
2. Run migrations
3. Smoke test all endpoints
4. Monitor logs
5. Deploy to production

**Total Estimated Time**: 4 hours

---

## Testing

### Unit Tests
- Team retrieval with caching
- Multi-tenant isolation
- Default team selection
- Cache invalidation on updates
- Sync operations

### Integration Tests
- API authentication required
- User can only access their company's teams
- Admin-only sync operations
- Event type fetching with verification

### Manual Tests
- Team dropdown UI displays correct teams
- Team selection updates booking flow
- Event types filter by selected team
- Cache performance verification

---

## Performance Targets

| Metric | Target | Current |
|--------|--------|---------|
| Cache Hit Rate | >95% | N/A (new) |
| API Response Time | <100ms | N/A (new) |
| Team Sync Duration | <5s/team | N/A (new) |
| Multi-tenant Query | <50ms | N/A (new) |

---

## Deployment

### Pre-Deployment Checklist
- [ ] Migrations tested on staging
- [ ] Backfill data verified
- [ ] API endpoints tested
- [ ] Multi-tenant isolation verified
- [ ] Cache behavior verified
- [ ] Load testing completed

### Deployment Steps
```bash
# 1. Backup database
pg_dump askpro_production > backup_$(date +%Y%m%d).sql

# 2. Deploy code
git pull origin main
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Post-Deployment
- [ ] Monitor logs for errors
- [ ] Verify API endpoints accessible
- [ ] Check cache hit rates
- [ ] Test end-to-end booking flow

---

## Backward Compatibility

### Existing Data
- `branches.calcom_team_id` column **preserved**
- Data migration creates `calcom_teams` records from existing branch assignments
- Fallback mechanism uses old column if no explicit mapping exists

### Existing APIs
- No breaking changes to current endpoints
- New endpoints are additive only
- Existing booking flow continues to work

---

## Future Enhancements

### Phase 2 Features (Not in Current Scope)
- Filament admin UI for team management
- Team-specific branding (logo, colors)
- Team member role assignments
- Team-level analytics dashboard
- Automated team sync scheduling
- Team availability calendar widget

---

## File Organization

```
/var/www/api-gateway/
├── TEAM_SELECTOR_ARCHITECTURE.md              # Full spec (13 sections)
├── TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md      # Quick reference
├── TEAM_SELECTOR_IMPLEMENTATION_GUIDE.md      # Developer guide
└── TEAM_SELECTOR_README.md                    # This file

database/migrations/
├── 2025_11_10_000000_create_calcom_teams_table.php
├── 2025_11_10_000001_create_calcom_team_event_types_table.php
└── 2025_11_10_000002_backfill_calcom_teams_from_branches.php

app/Models/
├── CalcomTeam.php
└── CalcomTeamEventType.php

app/Services/Calcom/
└── CalcomTeamService.php

app/Http/Controllers/Api/
└── CalcomTeamController.php

app/Observers/
└── CalcomTeamObserver.php

tests/Unit/Services/
└── CalcomTeamServiceTest.php

tests/Feature/Api/
└── CalcomTeamControllerTest.php
```

---

## Key Design Decisions

### 1. Separate Teams Table (vs. Extending Branches)
**Decision**: Create dedicated `calcom_teams` table
**Rationale**:
- Company can have teams not tied to branches
- Cleaner separation of concerns
- Easier to extend with team-specific features
- Better caching isolation

### 2. Keep `branches.calcom_team_id` Column
**Decision**: Preserve existing column as fallback
**Rationale**:
- Backward compatibility
- Zero downtime migration
- Gradual transition path
- Safer rollback option

### 3. Company-Scoped Caching (vs. Global)
**Decision**: Include `company_id` in all cache keys
**Rationale**:
- Multi-tenant security
- Prevents cross-company data leaks
- Easier to invalidate per company
- Aligns with existing architecture

### 4. Event-Driven Cache Invalidation (vs. TTL-Only)
**Decision**: Use observers for immediate invalidation
**Rationale**:
- Stale data prevention
- Better user experience
- Predictable cache behavior
- Aligns with existing patterns (CalcomService)

### 5. Junction Table for Event Types (vs. Direct Relationship)
**Decision**: Create `calcom_team_event_types` table
**Rationale**:
- Enables cross-team services
- Caches Cal.com metadata locally
- Supports future team-specific pricing
- Reduces Cal.com API calls

---

## Support & Questions

### Documentation References
- Laravel Multi-Tenancy: `/claudedocs/07_ARCHITECTURE/MULTI_TENANT_ARCHITECTURE.md`
- Cal.com Integration: `/claudedocs/02_BACKEND/Calcom/`
- Cache Strategy: `/app/Services/CalcomService.php` (lines 214-444)

### Common Questions

**Q: Why not use Laravel's built-in team features?**
A: Cal.com teams are external entities. We're mapping them, not creating our own team system.

**Q: Can a team belong to multiple branches?**
A: No. Each team has optional single branch assignment. Use default team for cross-branch services.

**Q: What happens if Cal.com API is down during sync?**
A: Circuit breaker opens after 5 failures. Cache continues serving stale data. Error logged for monitoring.

**Q: How do we handle team deletion in Cal.com?**
A: Sync operation marks team as inactive. Soft delete preserves historical data.

---

## Next Steps

### For Implementation
1. Review `TEAM_SELECTOR_ARCHITECTURE.md` - Full specification
2. Follow `TEAM_SELECTOR_IMPLEMENTATION_GUIDE.md` - Step-by-step
3. Use `TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md` - Quick reference during coding

### For Frontend Team
- Team selector dropdown component needed
- API endpoint: `GET /api/calcom/teams`
- Update booking flow to pass `team_id` parameter
- Filter event types by selected team

### For DevOps
- Monitor new cache keys in Redis
- Track Cal.com API rate limits
- Set up alerts for sync failures
- Monitor database query performance

---

## Architecture Review

**Strengths:**
- ✅ Multi-tenant security enforced at all layers
- ✅ Backward compatible with existing data
- ✅ High-performance caching with smart invalidation
- ✅ Clean separation of concerns (Model/Service/Controller)
- ✅ Comprehensive error handling
- ✅ Detailed testing strategy

**Considerations:**
- Cal.com API rate limits (300/min on Pro plan)
- Cache warming strategy for new companies
- Monitoring and observability setup
- Admin UI for team management (future phase)

---

**Status**: Ready for Implementation
**Estimated Implementation Time**: 4 hours
**Estimated Testing Time**: 2 hours
**Total Project Time**: 6 hours

---

**For Questions**: Review architecture documents or consult with:
- Backend Team: Database schema, API design
- Frontend Team: API integration, team selector UI
- DevOps: Cache monitoring, deployment strategy
