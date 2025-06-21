# 📊 AskProAI Codebase Analysis Report
**Date**: June 20, 2025  
**Analysis Scope**: Complete codebase review focusing on business model, database structure, and implementation consistency

## 🏢 1. Business Model Analysis

### Core Entity Hierarchy
```
Platform (AskProAI)
├── Company (Tenant/Mandant) - Multi-tenant root
│   ├── Branches (Filialen/Standorte) - Physical locations
│   ├── Staff (Mitarbeiter) - Employees across locations
│   ├── Customers (Kunden) - Shared customer base
│   └── Services/EventTypes - Service offerings
```

### Entity Relationships

#### Company (`companies` table)
- **Purpose**: Root tenant entity, holds all configuration
- **Key Fields**: 
  - API keys (retell, calcom, stripe)
  - Tax configuration
  - Subscription management
  - Revenue tracking
- **Status**: ✅ Well-implemented, central to multi-tenancy

#### Branch (`branches` table)
- **Purpose**: Physical locations/outlets
- **Key Fields**:
  - `phone_number` - Critical for call routing
  - `retell_agent_id` - AI agent assignment
  - `calcom_event_type_id` - Calendar integration
  - `calendar_mode` - inherit/override from company
- **Status**: ✅ Properly structured with UUID primary keys

#### Staff (`staff` table)
- **Purpose**: Employee management
- **Key Fields**:
  - `home_branch_id` - Primary branch assignment
  - `calendar_mode` - own/shared/inherit
  - `calcom_user_id` - Personal calendar
- **Relationships**:
  - Many-to-Many with branches via `staff_branches`
  - Many-to-Many with services via `staff_services`
  - Many-to-Many with event types via `staff_event_types`
- **Status**: ⚠️ Complex relationships, some duplication

#### Services vs EventTypes Confusion
**Major Issue Identified**: The system has parallel concepts that overlap:

1. **Services** (`services` table)
   - Internal service definitions
   - Company/branch specific
   - Fields: name, duration, price, calcom_event_type_id

2. **MasterServices** (`master_services` table)
   - Company-wide service templates
   - Can be overridden per branch
   - Linked via `branch_service_overrides`

3. **CalcomEventTypes** (`calcom_event_types` table)
   - External Cal.com event types
   - Synced from Cal.com API
   - The actual booking endpoints

4. **UnifiedEventTypes** (`unified_event_types` table)
   - Attempt to consolidate providers
   - Supports multiple calendar systems
   - Status: Partially implemented

### Current Implementation Status

✅ **Working Well**:
- Company/Branch/Staff core structure
- Multi-tenant scoping via `TenantScope`
- Phone number routing
- Basic appointment booking

⚠️ **Inconsistencies**:
- Services vs EventTypes duplication
- Multiple assignment tables (`staff_services`, `staff_service_assignments`, `staff_event_types`)
- Unclear which is authoritative

❌ **Missing/Broken**:
- Clear service-to-eventtype mapping strategy
- Unified approach to service management

## 🗄️ 2. Database Structure Analysis

### Migration Analysis Summary

**Total Migrations Found**: 200+
**Key Patterns Identified**:

#### Duplicate Table Creates
Multiple attempts to create the same tables:
- `services` table: 8 different migration files
- `staff_services` variations: 5 files
- `working_hours` vs `working_hour`: 3 files

#### Naming Inconsistencies
- Singular vs Plural: `working_hour` vs `working_hours`
- Different naming conventions: `staff_service` vs `service_staff`
- German vs English: `kunden` table exists alongside `customers`

#### Unused/Legacy Tables
- `tenants` - Replaced by `companies`
- `kalendar` - Misspelled, unused
- `integrations` - Partially implemented
- `dummy_companies` - Test data in production
- `additional_services` - Orphaned model

#### Relationship Tables Confusion
**Staff-to-Service Assignments** (3 different approaches):
1. `staff_services` - Direct many-to-many
2. `staff_service_assignments` - Via master services
3. `service_staff` - Another many-to-many variant

**Staff-to-EventType Assignments** (2 approaches):
1. `staff_event_types` - Current/new approach
2. Via service assignments - Legacy approach

### Database Consolidation Needed

**Tables to Remove**:
- `staff_service_assignments` (migrate to `staff_event_types`)
- `service_staff` (duplicate of `staff_services`)
- `master_services` + `branch_service_overrides` (overly complex)
- `unified_event_types` (incomplete implementation)
- `dummy_companies`, `zusatz_services`, `additional_services`

**Tables to Keep & Enhance**:
- `companies`, `branches`, `staff`, `customers`
- `calcom_event_types` (as single source of truth)
- `staff_event_types` (for assignments)
- `appointments`, `calls`

## 🧙 3. Wizard Implementation Analysis

### QuickSetupWizard (`app/Filament/Admin/Pages/QuickSetupWizard.php`)

**Current Implementation**:
- Multi-step wizard with 6 steps
- Supports both create and edit modes
- Industry templates for quick setup
- Complex state management

**Issues Found**:

1. **Service Creation Confusion**:
   ```php
   // Step 4: Creates both internal services AND tries to sync with Cal.com
   // This creates duplicate data and confusion
   ```

2. **Edit Mode Problems**:
   - Doesn't properly load existing services
   - Cal.com sync in edit mode can overwrite data
   - Industry template selection in edit mode is confusing

3. **Data Persistence**:
   - Some fields save to wrong tables
   - Relationships not properly established
   - Transaction handling missing in critical sections

**Recommendations**:
- Remove internal service creation
- Only manage Cal.com event type assignments
- Simplify to: Company → Branch → Import EventTypes → Assign Staff

## 🍴 4. Menu Structure Analysis

### Filament Resources Overview

**Company Management**:
- `CompanyResource` - Full CRUD with wizard
- Duplicate editing: Resource form + QuickSetupWizard
- Inconsistent field naming between interfaces

**Branch Management**:
- `BranchResource` - Standard resource
- Missing: Direct event type assignment UI
- Has complex calendar mode switching

**Staff Management**:
- `StaffResource` - Basic CRUD
- `StaffEventAssignment` page - Separate assignment UI
- `StaffEventAssignmentModern` - Another version!
- Confusion: Which UI to use for assignments?

**Service/EventType Management**:
- `ServiceResource` - Internal services (should be removed)
- `MasterServiceResource` - Master templates (overcomplicated)
- `CalcomEventTypeResource` - External event types (keep this)
- Too many ways to manage the same concept

### Menu Consolidation Needed

**Remove**:
- ServiceResource (internal services)
- MasterServiceResource (unnecessary complexity)
- One of the StaffEventAssignment pages

**Keep & Enhance**:
- CompanyResource (remove service creation)
- BranchResource (add event type assignment)
- StaffResource (integrate assignment inline)
- CalcomEventTypeResource (rename to EventTypeResource)

## 🔄 5. Event Types / Services Analysis

### Current Confusion Points

1. **Multiple Sources of Truth**:
   - Internal services with their own duration/price
   - Cal.com event types with external duration/price
   - Master services trying to bridge both
   - No clear synchronization strategy

2. **Assignment Complexity**:
   - Services assigned to branches
   - Services assigned to staff
   - Event types assigned to staff
   - Overrides at multiple levels

3. **Booking Flow Issues**:
   - Which duration to use? (service vs event type)
   - Which price to use? (internal vs Cal.com)
   - How to handle availability?

### Recommended Approach

**Single Source of Truth**: Cal.com Event Types
- Import event types from Cal.com
- Store as `calcom_event_types`
- Assign to branches (for organization)
- Assign staff to event types (for availability)
- Remove all internal service tables

**Simplified Assignment**:
```
Company has many EventTypes (via Cal.com)
  ↓
Branch is assigned EventTypes (which ones offered)
  ↓
Staff are assigned to EventTypes (who can perform)
```

## 📅 6. Cal.com Integration Analysis

### Current Implementation

**Services**:
- `CalcomService` - V1 API (legacy)
- `CalcomV2Service` - V2 API (current)
- `CalcomEventTypeSyncService` - Sync logic
- Multiple similar services with overlapping functionality

**Mapping Issues**:
1. Event types imported but not properly linked
2. Staff assignments to Cal.com users incomplete
3. Availability not synced properly
4. No clear branch-to-eventtype mapping

**Database**:
- `calcom_event_types` table exists but underutilized
- Missing proper foreign keys to branches
- Sync status tracking incomplete

### Recommendations

1. **Consolidate to Single Service**:
   - Use only `CalcomV2Service`
   - Remove all V1 code
   - Clear service boundaries

2. **Improve Sync Process**:
   - Regular sync job for event types
   - Track sync status per record
   - Handle conflicts gracefully

3. **Fix Mapping**:
   - Each branch selects its event types
   - Staff are assigned to event types with their Cal.com user ID
   - Clear availability rules

## 🎯 Consolidation Recommendations

### 1. Database Cleanup (Priority: HIGH)
```sql
-- Step 1: Backup everything
-- Step 2: Migrate data from old to new structure
-- Step 3: Drop redundant tables
DROP TABLE IF EXISTS staff_service_assignments;
DROP TABLE IF EXISTS master_services;
DROP TABLE IF EXISTS branch_service_overrides;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS unified_event_types;
```

### 2. Model Simplification (Priority: HIGH)
- Remove: `Service`, `MasterService`, `UnifiedEventType` models
- Keep: `CalcomEventType` as the only service representation
- Enhance: `StaffEventType` pivot with all necessary overrides

### 3. UI/UX Consolidation (Priority: MEDIUM)
- Single wizard for company setup
- Inline event type assignment in branch/staff resources
- Remove duplicate assignment interfaces
- Clear labeling: "Event Types" not "Services"

### 4. Migration Path (Priority: HIGH)
1. Create data migration to move service assignments to event type assignments
2. Update all references in code
3. Remove old tables and models
4. Update documentation

### 5. Clear Terminology (Priority: MEDIUM)
- Standardize on "Event Types" throughout the system
- Remove all references to "Services" (except in German UI where "Leistungen" is appropriate)
- Update all class names, database tables, and UI labels

## 📊 Summary Statistics

- **Duplicate Concepts**: 4 (Services, MasterServices, EventTypes, UnifiedEventTypes)
- **Redundant Tables**: 15+
- **Duplicate Assignment Methods**: 3
- **Migration Files**: 200+ (many duplicates)
- **Unused Models**: 8
- **Code Duplication**: ~30%

## ✅ Action Items

1. **Immediate** (This Week):
   - Create comprehensive data backup
   - Document current service-to-eventtype mappings
   - Fix SQLite test compatibility

2. **Short Term** (Next Sprint):
   - Implement data migration script
   - Remove redundant models and tables
   - Consolidate to single assignment method

3. **Medium Term** (Next Month):
   - Refactor wizard to use only event types
   - Update all UI labels and documentation
   - Complete Cal.com V2 migration

4. **Long Term** (Next Quarter):
   - Implement proper sync strategy
   - Add multi-provider support (Google, Outlook)
   - Performance optimization

## 🚨 Risk Assessment

**High Risk**:
- Data loss during migration (Mitigation: Comprehensive backups)
- Breaking existing bookings (Mitigation: Careful migration script)

**Medium Risk**:
- User confusion during transition (Mitigation: Clear communication)
- Integration disruption (Mitigation: Phased rollout)

**Low Risk**:
- Performance impact (Mitigation: Proper indexing)

---

*This analysis reveals significant architectural debt that needs addressing. The core business model is sound, but the implementation has evolved organically leading to confusion and duplication. A focused consolidation effort would greatly simplify the system and improve maintainability.*