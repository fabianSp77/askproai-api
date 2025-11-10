# Team-Selector Feature Documentation Index

**Complete backend architecture design for Cal.com multi-team integration**

---

## Documentation Structure

```
TEAM_SELECTOR_INDEX.md (This File)
├── Overview & Navigation
├── Quick Links by Role
└── Document Summaries

TEAM_SELECTOR_README.md
├── Executive Summary
├── Feature Overview
├── Key Decisions
└── Implementation Status

TEAM_SELECTOR_ARCHITECTURE.md
├── Database Schema (Migrations)
├── Eloquent Models
├── Service Layer Design
├── API Controller & Routes
├── Caching Strategy
├── Multi-Tenant Security
├── Cal.com API Integration
├── Testing Strategy
└── Deployment Checklist

TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md
├── Visual Architecture Diagram
├── Quick Reference Tables
├── API Endpoint Summary
├── Cache Key Patterns
├── Common Operations
└── Performance Targets

TEAM_SELECTOR_IMPLEMENTATION_GUIDE.md
├── Step-by-Step Setup
├── Testing Procedures
├── Troubleshooting Guide
├── Deployment Checklist
└── Quick Commands

TEAM_SELECTOR_DATAFLOW.md
├── Team Retrieval Flow
├── Team Sync Flow
├── Multi-Tenant Isolation
├── Cache Invalidation
├── Booking Flow
├── Security Verification
├── Performance Optimization
├── Error Handling
└── Data Consistency
```

---

## Quick Start by Role

### 👨‍💻 Backend Developer

**Start Here**: `TEAM_SELECTOR_IMPLEMENTATION_GUIDE.md`

**Your Path**:
1. Read Implementation Guide (30 min)
2. Review Architecture document sections 1-2 (Database & Models) (20 min)
3. Copy migration files and models from Architecture doc
4. Follow testing procedures in Implementation Guide
5. Reference Summary doc during coding

**Key Files**:
- Migrations: Section 1 of Architecture doc
- Models: Section 2 of Architecture doc
- Services: Section 3 of Architecture doc
- Controllers: Section 4 of Architecture doc
- Tests: Implementation Guide

---

### 🎨 Frontend Developer

**Start Here**: `TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md`

**Your Path**:
1. Read API Endpoint Summary (5 min)
2. Review Data Flow diagram (Booking with Team Selection) (10 min)
3. Integrate team selector dropdown with `/api/calcom/teams`
4. Filter event types by selected team

**Key Endpoints**:
```javascript
// Get available teams
GET /api/calcom/teams
Response: [{ id, name, slug, calcom_team_id, ... }]

// Get team event types
GET /api/calcom/teams/{team}/event-types
Response: [{ id, title, slug, duration, price, ... }]

// Booking with team
POST /api/retell/book-appointment
Body: { team_id, event_type_id, start, attendee }
```

---

### 🔧 DevOps Engineer

**Start Here**: `TEAM_SELECTOR_README.md` → Deployment Section

**Your Path**:
1. Review deployment checklist (10 min)
2. Prepare staging environment
3. Monitor cache performance in Redis
4. Set up alerts for Cal.com API failures

**Key Monitoring**:
- Redis cache hit rate (target: >95%)
- Cal.com API response time
- Database query performance
- Multi-tenant query isolation

**Commands**:
```bash
# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:clear

# Monitor logs
tail -f storage/logs/laravel.log | grep -i "calcom"

# Check Redis cache
redis-cli
> INFO stats
> KEYS company:*:calcom_teams:*
```

---

### 📊 Product Manager

**Start Here**: `TEAM_SELECTOR_README.md`

**Your Path**:
1. Read Overview and Key Features (10 min)
2. Review Implementation Timeline (5 min)
3. Check Performance Targets (5 min)

**Key Deliverables**:
- Multi-team support for companies
- Branch-specific team assignment
- Team-specific service filtering
- Admin sync functionality
- Backward compatibility maintained

**Timeline**: 6 hours total
- Implementation: 4 hours
- Testing: 2 hours

---

### 🏗️ Architect / Tech Lead

**Start Here**: `TEAM_SELECTOR_ARCHITECTURE.md`

**Your Path**:
1. Review complete architecture (60 min)
2. Evaluate security model (Section 6)
3. Review performance strategy (Section 5 + 12)
4. Assess testing strategy (Section 9)
5. Approve for implementation

**Key Decisions to Review**:
- Separate teams table vs. extending branches
- Company-scoped caching strategy
- Event-driven cache invalidation
- Junction table for event types
- Backward compatibility approach

---

## Document Summaries

### TEAM_SELECTOR_README.md
**Length**: ~300 lines | **Read Time**: 15 minutes

**Contents**:
- Executive summary
- Feature overview (Multi-team, Security, Performance, Compatibility)
- Database design summary
- API endpoints table
- Service layer overview
- Implementation steps
- Testing strategy
- Deployment guide
- Key design decisions

**Best For**: Project overview, stakeholder communication

---

### TEAM_SELECTOR_ARCHITECTURE.md
**Length**: ~1,350 lines | **Read Time**: 90 minutes

**Contents**:
- Complete database migrations (3 files)
- Eloquent models (CalcomTeam, CalcomTeamEventType)
- Service layer (CalcomTeamService + CalcomService extensions)
- API controller (CalcomTeamController)
- Route definitions
- Caching strategy (keys, TTL, invalidation)
- Multi-tenant security (5 layers)
- Cal.com API integration (endpoints, examples, error handling)
- Backward compatibility
- Testing strategy (unit + integration)
- Deployment checklist

**Best For**: Implementation reference, code copying, architecture review

---

### TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md
**Length**: ~450 lines | **Read Time**: 20 minutes

**Contents**:
- Visual architecture diagram
- Database schema quick reference
- API endpoint table
- Cache key patterns
- Service layer method signatures
- Migration plan
- Backward compatibility notes
- Testing checklist
- Performance targets
- Common operations

**Best For**: Quick reference during coding, team onboarding

---

### TEAM_SELECTOR_IMPLEMENTATION_GUIDE.md
**Length**: ~750 lines | **Read Time**: 30 minutes

**Contents**:
- Step-by-step setup instructions
- Testing procedures (Database, Service, API, Security)
- Cal.com API integration testing
- Cache verification tests
- Performance testing scripts
- Troubleshooting guide (common issues + solutions)
- Deployment checklist (Pre/During/Post)
- Quick reference commands

**Best For**: Hands-on implementation, troubleshooting

---

### TEAM_SELECTOR_DATAFLOW.md
**Length**: ~550 lines | **Read Time**: 25 minutes

**Contents**:
- 10 visual data flow diagrams:
  1. Team Retrieval Flow
  2. Team Sync Flow
  3. Event Type Retrieval
  4. Multi-Tenant Isolation
  5. Cache Invalidation
  6. Booking with Team Selection
  7. Security Verification
  8. Performance Optimization
  9. Error Handling
  10. Data Consistency
- Flow pattern summary

**Best For**: Understanding system behavior, debugging, onboarding

---

## Key Concepts

### Multi-Tenant Isolation

**Layers**:
1. Model: `BelongsToCompany` trait (automatic query scoping)
2. Middleware: `auth:sanctum` + `companyscope`
3. Controller: Explicit `company_id` verification
4. Cache: Company-scoped cache keys
5. Database: Foreign key constraints

**Security**: Users can ONLY access their company's teams

---

### Caching Strategy

**Pattern**: Redis with event-driven invalidation

**Keys**:
- `company:{id}:calcom_teams:all` (TTL: 1h)
- `company:{id}:calcom_teams:default` (TTL: 1h)
- `company:{id}:calcom_team:{tid}:event_types` (TTL: 5min)

**Invalidation**: Automatic via `CalcomTeamObserver`

---

### Cal.com Integration

**Endpoints Used**:
- `GET /teams` - Fetch all teams
- `GET /teams/{id}/event-types` - Fetch team event types

**Rate Limits**:
- Free: 30 req/min
- Pro: 300 req/min

**Mitigation**: Caching + request coalescing

---

## Implementation Checklist

### Phase 1: Database (30 min)
- [ ] Create migration: `create_calcom_teams_table.php`
- [ ] Create migration: `create_calcom_team_event_types_table.php`
- [ ] Create migration: `backfill_calcom_teams_from_branches.php`
- [ ] Run migrations on staging
- [ ] Verify tables created

### Phase 2: Models & Services (1 hour)
- [ ] Create `CalcomTeam` model
- [ ] Create `CalcomTeamEventType` model
- [ ] Create `CalcomTeamService` service
- [ ] Extend `CalcomService` with new methods
- [ ] Create `CalcomTeamObserver` observer
- [ ] Register observer in `AppServiceProvider`

### Phase 3: API Layer (45 min)
- [ ] Create `CalcomTeamController`
- [ ] Register routes in `routes/api.php`
- [ ] Add middleware (auth + admin)
- [ ] Test endpoints with Postman

### Phase 4: Testing (1 hour)
- [ ] Unit tests: Service layer
- [ ] Integration tests: API endpoints
- [ ] Security tests: Multi-tenant isolation
- [ ] Cache tests: Hit rate, invalidation
- [ ] Performance tests: Response time

### Phase 5: Deployment (30 min)
- [ ] Deploy to staging
- [ ] Run migrations
- [ ] Smoke test all endpoints
- [ ] Monitor logs
- [ ] Deploy to production

---

## Performance Benchmarks

| Metric | Target | Measurement |
|--------|--------|-------------|
| Cache Hit Rate | >95% | Redis INFO stats |
| API Response | <100ms | Laravel Telescope |
| Team Sync | <5s/team | Application logs |
| DB Query | <50ms | Slow query log |

---

## Common Operations

### Get Teams for Company
```php
$service = app(\App\Services\Calcom\CalcomTeamService::class);
$teams = $service->getTeamsForCompany($companyId);
```

### Sync Teams from Cal.com
```php
$result = $service->syncTeamsForCompany($companyId);
// Returns: ['success' => true, 'synced' => 3, 'errors' => []]
```

### Get Default Team
```php
$team = $service->getDefaultTeam($companyId);
```

### Clear Team Cache
```php
$service->invalidateTeamCache($companyId);
```

### API: List Teams
```bash
curl -X GET "http://localhost:8000/api/calcom/teams" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

---

## Troubleshooting Quick Links

| Issue | Solution Location |
|-------|-------------------|
| Teams not visible | Implementation Guide → Troubleshooting → "Teams not visible" |
| Cache not working | Implementation Guide → Troubleshooting → "Cache not working" |
| Cal.com API errors | Implementation Guide → Troubleshooting → "Cal.com API errors" |
| Migration fails | Implementation Guide → Troubleshooting → "Migration fails" |
| Multi-tenant leak | Architecture → Section 6 (Security) |
| Performance issues | Architecture → Section 12 (Performance) |

---

## Related Documentation

### Project Documentation
- Multi-Tenancy: `/claudedocs/07_ARCHITECTURE/MULTI_TENANT_ARCHITECTURE.md`
- Cal.com Integration: `/claudedocs/02_BACKEND/Calcom/`
- Cache Strategy: `/app/Services/CalcomService.php`

### Laravel Documentation
- Route Model Binding: https://laravel.com/docs/11.x/routing#route-model-binding
- Eloquent Observers: https://laravel.com/docs/11.x/eloquent#observers
- Cache: https://laravel.com/docs/11.x/cache

### Cal.com API
- API Documentation: https://cal.com/docs/enterprise-features/api
- Team Endpoints: https://cal.com/docs/api-reference/v2/teams

---

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-11-10 | Backend Architect | Initial design complete |

---

## Support

### For Implementation Questions
- Check Implementation Guide troubleshooting section
- Review Architecture document for design decisions
- Reference Data Flow diagrams for behavior understanding

### For Architecture Questions
- Review Key Decisions in README
- Check Security section in Architecture doc
- Consult Performance section for optimization

### For API Integration
- Review API Endpoint Summary
- Check Data Flow: "Booking with Team Selection"
- Reference Cal.com API integration section

---

## Next Steps

1. **Review**: Read README for overview (15 min)
2. **Plan**: Review Architecture for design (90 min)
3. **Implement**: Follow Implementation Guide (4 hours)
4. **Test**: Execute testing procedures (2 hours)
5. **Deploy**: Follow deployment checklist (30 min)

**Total Estimated Time**: ~8 hours (design already complete)

---

## Quick Navigation

- **[Overview]** → `TEAM_SELECTOR_README.md`
- **[Full Spec]** → `TEAM_SELECTOR_ARCHITECTURE.md`
- **[Quick Ref]** → `TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md`
- **[How To]** → `TEAM_SELECTOR_IMPLEMENTATION_GUIDE.md`
- **[Diagrams]** → `TEAM_SELECTOR_DATAFLOW.md`
- **[Index]** → `TEAM_SELECTOR_INDEX.md` (This File)

---

**Status**: ✅ Design Complete | 🟡 Implementation Pending | ⏳ Testing Pending | ⏳ Deployment Pending

**Last Updated**: 2025-11-10
