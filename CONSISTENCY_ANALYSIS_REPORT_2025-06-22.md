# AskProAI Consistency Analysis Report
**Date:** June 22, 2025  
**Analysis Type:** Comprehensive Consistency Review

## Executive Summary

After thorough analysis, the AskProAI codebase shows significant architectural inconsistencies and redundancies that create confusion and maintenance challenges. The system has evolved organically, resulting in multiple overlapping solutions for the same problems.

### Critical Findings:
- **119 database tables** with many duplicates and unclear purposes
- **Multiple service versions** (V1 vs V2) running simultaneously
- **3+ ways to configure the same settings**
- **Conflicting data flows** for core features
- **UI/UX inconsistencies** with multiple admin panels

## 1. Database Schema Inconsistencies

### 1.1 Duplicate/Redundant Tables

#### Event Type Tables (9 variations!)
```
1. calcom_event_types          - Main event types from Cal.com
2. calendar_event_types        - Duplicate? Purpose unclear
3. unified_event_types         - Attempted consolidation
4. branch_event_types          - Branch-specific event types
5. event_type_mappings         - Service to event type mapping
6. service_event_type_mappings - Another mapping table
7. staff_event_types           - Staff assignments
8. event_type_import_logs      - Import tracking
9. staff_service_assignments_backup - Legacy backup
```

**Impact:** Developers don't know which table to use. Data can be inconsistent across tables.

#### Staff Assignment Tables (8 variations!)
```
1. staff                       - Main staff table
2. branch_staff               - Branch assignments
3. staff_branches             - Duplicate of above?
4. staff_services             - Service assignments
5. service_staff              - Reverse mapping?
6. staff_branches_and_staff_services_tables - Migration artifact?
7. staff_event_types          - New structure
8. staff_service_assignments_backup - Legacy backup
```

**Impact:** Staff assignments are scattered, making it impossible to get a single source of truth.

### 1.2 Conflicting Phone/Agent Configuration

**Phone Number Storage (3 locations):**
1. `phone_numbers` table - Dedicated table with full features
2. `branches.phone_number` - Branch main number
3. `branches.phone` - Duplicate column?

**Agent ID Storage (4 locations):**
1. `phone_numbers.retell_agent_id` - Per phone number
2. `branches.retell_agent_id` - Per branch
3. `companies.retell_agent_id` - Per company
4. `phone_numbers.agent_id` - Generic agent reference

**Impact:** When a call comes in, the system checks multiple places, leading to race conditions and inconsistent routing.

### 1.3 Unused/Legacy Columns

Many tables have columns that appear unused:
- `branches.retell_agent_data`
- `branches.retell_synced_at`
- `branches.retell_agent_status`
- `branches.retell_agent_created_at`
- `branches.calcom_user_id`
- `companies.calcom_user_id`

## 2. Service Layer Inconsistencies

### 2.1 Multiple Service Versions

**Cal.com Services (7 implementations!):**
```php
1. CalcomService              - Original V1 service
2. CalcomService_v1_only      - Backup of V1?
3. CalcomV2Service            - New V2 implementation
4. CalcomMCPServer            - MCP wrapper
5. CalcomMigrationService     - Migration helper
6. CalcomV2MigrationService   - Another migration service
7. CalcomSyncService          - Sync operations
```

**Retell Services (5 implementations!):**
```php
1. RetellService              - Original service
2. RetellV2Service            - New version
3. RetellMCPServer            - MCP wrapper
4. RetellAgentService         - Agent management
5. RetellAgentProvisioner     - Agent provisioning
```

**Impact:** 
- Code duplication everywhere
- Bug fixes need to be applied in multiple places
- Developers use different services inconsistently

### 2.2 MCP Servers vs Direct Services

The system has both:
- Direct service calls (`CalcomV2Service->getEventTypes()`)
- MCP server calls (`CalcomMCPServer->getEventTypes()`)

**Confusion:** When should developers use MCP vs direct services? Both exist and do similar things.

## 3. Configuration Chaos

### 3.1 API Keys Storage (4 locations!)

**Cal.com API Keys:**
1. `.env` file: `DEFAULT_CALCOM_API_KEY`
2. `companies.calcom_api_key`
3. `branches.calcom_api_key`
4. `api_credentials` table

**Retell API Keys:**
1. `.env` file: `DEFAULT_RETELL_API_KEY`, `RETELL_TOKEN`
2. `companies.retell_api_key`
3. Not in branches (inconsistent with Cal.com)

**Impact:** Which API key is used? Precedence is unclear.

### 3.2 Event Type Configuration (3 ways!)

1. **Branch Level:** `branches.calcom_event_type_id`
2. **Service Mapping:** `service_event_type_mappings` table
3. **Staff Assignment:** `staff_event_types` table

**Confusion:** If a customer books "Haircut" service, which event type is used?

## 4. UI/UX Inconsistencies

### 4.1 Multiple Setup Wizards

```
1. OnboardingWizard           - Initial setup
2. CompanySetupWizard         - Company configuration
3. QuickSetupWizard           - Simplified setup
4. QuickSetupWizardV2         - Another version
5. EventTypeSetupWizard       - Event type specific
6. EventTypeImportWizard      - Import functionality
7. RetellAgentImportWizard    - Agent import
```

**Impact:** Users don't know which wizard to use. Features are duplicated across wizards.

### 4.2 Integration Configuration Locations

Users can configure integrations in:
1. Company Integration Portal
2. Individual resource pages (branches, staff)
3. Various setup wizards
4. Direct database manipulation

**Impact:** Users configure settings in one place but don't see them reflected elsewhere.

## 5. Data Flow Inconsistencies

### 5.1 Phone Call → Branch Resolution

The system has **5 different methods** to resolve which branch a call belongs to:

```php
1. metadata['askproai_branch_id']  - From Retell metadata
2. PhoneNumber table lookup        - By destination number
3. Branch.retell_agent_id lookup   - By agent ID
4. Customer history               - Previous interactions
5. Fallback to first branch       - Default
```

**Problem:** Different code paths use different methods, leading to inconsistent results.

### 5.2 Booking Flow Paths

Multiple booking implementations:
1. `AppointmentBookingService`
2. `UniversalBookingOrchestrator`
3. `MCPBookingOrchestrator`
4. Direct `CalcomV2Service` calls

**Impact:** Different features use different booking paths with slightly different behavior.

## 6. Migration Artifacts

### 6.1 Incomplete Migrations

Evidence of multiple incomplete migration attempts:
- V1 to V2 API migration (both still active)
- `staff_service_assignments` to `staff_event_types` (both exist)
- Multiple backup tables left in production

### 6.2 Feature Flags Confusion

```php
// Multiple ways to check API version
config('calcom.use_v2_api')
config('calcom.api_version') == 'v2'
config('calcom-v2.enabled')
in_array($method, config('calcom.v2_enabled_methods'))
```

## 7. Critical Issues

### 7.1 Race Conditions

Multiple services can update the same data:
- Phone number assignments
- Agent configurations
- Event type mappings

No clear locking or transaction boundaries.

### 7.2 Silent Failures

Many configuration checks fail silently:
- Missing branch → uses first branch
- Missing event type → uses default
- Invalid phone number → ignored

### 7.3 Circular Dependencies

- Branch needs Company
- PhoneNumber needs Branch
- But PhoneNumberResolver tries to work without either

## Recommendations

### 1. Database Consolidation

**Immediate Actions:**
1. Choose ONE event type table: `calcom_event_types`
2. Choose ONE staff assignment method: `staff_event_types`
3. Remove all backup and duplicate tables
4. Create clear foreign key relationships

### 2. Service Layer Cleanup

**Consolidate to:**
- `CalcomService` - Single implementation using V2 API
- `RetellService` - Single implementation
- Remove all V2 variants and MCP servers (or make MCP primary)

### 3. Configuration Hierarchy

**Establish clear precedence:**
```
1. Branch-specific settings (highest priority)
2. Company settings
3. Environment variables (fallback)
```

### 4. Single Phone Resolution Strategy

```php
// Use ONLY phone_numbers table
$phoneNumber = PhoneNumber::where('number', $number)
    ->with(['branch', 'company'])
    ->first();
    
if (!$phoneNumber) {
    throw new PhoneNumberNotFoundException();
}
```

### 5. UI/UX Simplification

**Keep only:**
1. `CompanyIntegrationPortal` - For all settings
2. `OnboardingWizard` - For initial setup
3. Remove all other wizards and consolidate features

### 6. Data Model Simplification

**Target Architecture:**
```
Company
  ├── Branches
  │     ├── PhoneNumbers (with agents)
  │     ├── Staff
  │     └── Services
  ├── Customers
  └── Appointments
```

### 7. Code Cleanup Priority

1. **Week 1:** Remove duplicate tables via migration
2. **Week 2:** Consolidate service classes
3. **Week 3:** Unify configuration management
4. **Week 4:** Clean up UI/UX redundancies

## Impact Assessment

**Current State:**
- 119 tables → Target: 25 tables
- 7 Cal.com services → Target: 1 service
- 5 Retell services → Target: 1 service
- 7 setup wizards → Target: 2 wizards

**Benefits:**
- 70% reduction in code complexity
- Clear data flow paths
- Predictable system behavior
- Easier onboarding for new developers
- Reduced bug surface area

## Conclusion

The AskProAI codebase suffers from organic growth without architectural governance. Multiple developers have solved the same problems in different ways, leading to a system that works but is extremely difficult to understand and maintain.

The recommended consolidation would transform this into a clean, predictable system while maintaining all current functionality. The key is to choose ONE way to do each thing and remove all alternatives.