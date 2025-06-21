# Database Analysis Report - AskProAI
**Date**: 2025-06-17
**Status**: CRITICAL ISSUES FOUND

## Executive Summary
The database cleanup migration `2025_06_17_cleanup_redundant_tables` dropped 119 tables, including some that are still referenced by foreign keys and models. This has created broken relationships and potential application errors.

## Current Database State

### Total Tables: 58
- Active tables with data: 10
- Empty tables: 48
- Total foreign key relationships: 42

### Tables with Data
1. `cache` - 84 records
2. `companies` - 1 record
3. `migrations` - 203 records
4. `model_has_roles` - 2 records
5. `onboarding_progress` - 1 record
6. `permissions` - 1 record
7. `roles` - 1 record
8. `sessions` - 10 records
9. `users` - 2 records

## Critical Issues Found

### 1. Broken Foreign Key Constraints
The following foreign keys reference tables that no longer exist:

| Table | Column | Referenced Table | Status |
|-------|--------|------------------|--------|
| `calls` | `kunde_id` | `kunden` | **TABLE MISSING** |
| `users` | `kunde_id` | `kunden` | **TABLE MISSING** |

### 2. Dropped Tables Still Referenced by Models
The following models exist but their tables were dropped:

1. **Kunde** (Model: `/app/Models/Kunde.php`)
   - Table: `kunden` - **DROPPED**
   - Referenced by: `calls.kunde_id`, `users.kunde_id`
   - Impact: Customer relationship broken

2. **Telefonnummer** (Model: `/app/Models/Telefonnummer.php`)
   - Table: `telefonnummern` - **DROPPED**
   - Impact: Phone number mapping broken

3. **Dienstleistung** (Model: `/app/Models/Dienstleistung.php`)
   - Table: `dienstleistungen` - **DROPPED**
   - Impact: Service definitions lost

4. **Termin** (Model: `/app/Models/Termin.php`)
   - Table: `termine` - **DROPPED**
   - Impact: Appointment data lost

### 3. Tables Dropped by Cleanup Migration
The `2025_06_17_cleanup_redundant_tables` migration dropped 119 tables including:

**Critical Tables Dropped**:
- `kunden` - Customer data (referenced by FKs)
- `master_services` - Service definitions
- `branch_service_overrides` - Branch-specific service settings
- `retell_agents` - AI agent configurations
- `retell_webhooks` - Webhook logs (had 1383 records!)
- `staff_event_type_assignments` - Staff-event mappings
- `event_type_import_logs` - Import history
- `activity_log` - Audit trail
- `sessions` - Laravel sessions
- `password_reset_tokens` - Auth functionality

### 4. Restoration Attempts
After the cleanup, the following restoration migrations were created:
1. `2025_06_17_restore_critical_tables` - Restored 8 tables
2. `2025_06_17_restore_critical_pivot_tables` - Restored pivot tables
3. `2025_06_17_fix_missing_master_services_tables` - Restored service tables

**However, the following were NOT restored**:
- `kunden` table (still has broken FKs)
- `telefonnummern` table
- `dienstleistungen` table
- `termine` table

## Impact Analysis

### Application Functionality Affected
1. **Customer Management**: The `kunden` table is missing but still referenced
2. **Call Processing**: `calls.kunde_id` foreign key is broken
3. **User Authentication**: `users.kunde_id` foreign key is broken
4. **Phone Number Resolution**: `telefonnummern` table missing
5. **Service Management**: `dienstleistungen` table missing
6. **Appointment Booking**: `termine` table missing

### Data Loss
- Potential loss of customer data from `kunden` table
- Loss of 1383 webhook records from `retell_webhooks` (later restored)
- Loss of audit trail from `activity_log` (later restored)

## Recommendations

### Immediate Actions Required
1. **Drop Broken Foreign Keys**:
   ```sql
   ALTER TABLE calls DROP FOREIGN KEY calls_kunde_id_foreign;
   ALTER TABLE users DROP FOREIGN KEY users_kunde_id_foreign;
   ```

2. **Decide on Customer Model Strategy**:
   - Option A: Restore `kunden` table and migrate data from `customers`
   - Option B: Update models to use `customers` table instead
   - Option C: Remove `kunde_id` columns and update relationships

3. **Clean Up Orphaned Models**:
   - Remove or update models for non-existent tables
   - Update model relationships to match actual database structure

4. **Create Missing Tables Migration**:
   - For models that should exist but don't have tables
   - Ensure foreign key constraints are properly defined

### Long-term Improvements
1. **Pre-Migration Validation**: Check for dependencies before dropping tables
2. **Model-Database Sync Check**: Regular validation of model-table consistency
3. **Foreign Key Documentation**: Maintain a map of all relationships
4. **Staged Cleanup**: Drop tables in phases with validation between each

## Migration History (Today's Changes)
- Batch 9: Initial fixes and additions
- Batch 10: Performance indexes
- Batch 11: Cleanup (119 tables dropped) and restoration attempts

## Conclusion
The aggressive cleanup migration created several broken relationships in the database. While some critical tables were restored, the `kunden` table and its relationships remain broken, requiring immediate attention to restore application functionality.