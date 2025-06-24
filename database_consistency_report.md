# Database Consistency Check Report - AskProAI

## Executive Summary
Date: 2025-06-22
Database: askproai_db
Total Tables: 87

### Overall Status: ⚠️ NEEDS ATTENTION

While no orphaned records or data integrity violations were found, several critical foreign key constraints are missing, which could lead to future data integrity issues.

## 1. Foreign Key Relationship Check

### ✅ Existing Foreign Keys
- ✓ appointments.customer_id → customers.id
- ✓ appointments.calcom_event_type_id → calcom_event_types.id
- ✓ phone_numbers.company_id → companies.id
- ✓ service_event_type_mappings (all FKs properly configured)
- ✓ branch_event_types (all FKs properly configured)
- ✓ calcom_event_types.branch_id → branches.id
- ✓ calcom_event_types.staff_id → staff.id

### ❌ Missing Foreign Keys (CRITICAL)
The following foreign key constraints are missing and should be added:

1. **appointments table**:
   - company_id → companies.id
   - branch_id → branches.id
   - staff_id → staff.id
   - service_id → services.id

2. **phone_numbers table**:
   - branch_id → branches.id

## 2. Required Columns Check

### ✅ All Required Columns Present
All tables have the expected columns based on the application requirements.

## 3. Orphaned Records Check

### ✅ No Orphaned Records Found
- Appointments with non-existent customers: 0
- Appointments with non-existent branches: 0
- Appointments with non-existent staff: 0
- Appointments with non-existent services: 0
- Appointments with non-existent event types: 0
- Phone numbers with non-existent companies: 0
- Phone numbers with non-existent branches: 0
- Service mappings with non-existent services: 0
- Service mappings with non-existent event types: 0

## 4. Multi-Tenant Isolation Check

### ✅ No Cross-Tenant Violations Found
- Appointments without company_id: 0
- Cross-tenant appointments (company mismatch): 0
- Phone numbers without company_id: 0
- Services without company_id: 0
- Branches without company_id: 0

## 5. Index Analysis

### ✅ Existing Indexes
**appointments table**:
- PRIMARY (id)
- appointments_customer_id_foreign
- appointments_external_id_index
- appointments_call_id_index
- appointments_staff_id_index
- appointments_calcom_event_type_id_index
- appointments_version_index
- appointments_lock_expires_at_lock_token_index (composite)

**phone_numbers table**:
- PRIMARY (id)
- phone_numbers_number_unique
- phone_numbers_branch_id_index
- phone_numbers_retell_phone_id_index
- phone_numbers_retell_agent_id_index
- phone_numbers_type_index
- phone_numbers_company_id_is_active_index (composite)

### 🔍 Recommended Additional Indexes
Based on the multi-tenant architecture and common query patterns:

1. **appointments table**:
   ```sql
   CREATE INDEX idx_appointments_company_id ON appointments(company_id);
   CREATE INDEX idx_appointments_branch_id ON appointments(branch_id);
   CREATE INDEX idx_appointments_starts_at ON appointments(starts_at);
   CREATE INDEX idx_appointments_status ON appointments(status);
   CREATE INDEX idx_appointments_company_branch_starts ON appointments(company_id, branch_id, starts_at);
   ```

2. **service_event_type_mappings table**:
   ```sql
   CREATE INDEX idx_service_mappings_company_active ON service_event_type_mappings(company_id, is_active);
   ```

## 6. Migration Status

### ✅ Latest Migrations Applied
Latest batch: 20
Most recent migration: 2025_06_22_131755_add_calcom_event_type_id_to_appointments

## 7. Data Integrity Check

### ✅ No Duplicate Data Found
- No duplicate phone numbers
- No duplicate service mappings

### ✅ No Constraint Violations
- All services have company_id
- All branches have company_id
- All staff have branch_id

## SQL Queries to Fix Issues

### 1. Add Missing Foreign Key Constraints

```sql
-- Add foreign keys to appointments table
ALTER TABLE appointments 
ADD CONSTRAINT appointments_company_id_foreign 
FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE;

ALTER TABLE appointments 
ADD CONSTRAINT appointments_branch_id_foreign 
FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE;

ALTER TABLE appointments 
ADD CONSTRAINT appointments_staff_id_foreign 
FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL;

ALTER TABLE appointments 
ADD CONSTRAINT appointments_service_id_foreign 
FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL;

-- Add foreign key to phone_numbers table
ALTER TABLE phone_numbers 
ADD CONSTRAINT phone_numbers_branch_id_foreign 
FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE;
```

### 2. Add Performance Indexes

```sql
-- Appointments performance indexes
CREATE INDEX idx_appointments_company_id ON appointments(company_id);
CREATE INDEX idx_appointments_branch_id ON appointments(branch_id);
CREATE INDEX idx_appointments_starts_at ON appointments(starts_at);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_appointments_company_branch_starts ON appointments(company_id, branch_id, starts_at);

-- Service mappings performance index
CREATE INDEX idx_service_mappings_company_active ON service_event_type_mappings(company_id, is_active);
```

### 3. Create Migration File

Create a new migration file to apply these changes:

```bash
php artisan make:migration add_missing_foreign_keys_and_indexes
```

Then add the above SQL commands to the migration file.

## Recommendations

1. **URGENT**: Add the missing foreign key constraints to prevent future data integrity issues
2. **PERFORMANCE**: Add the recommended indexes to improve query performance, especially for multi-tenant queries
3. **MONITORING**: Set up regular database consistency checks (weekly)
4. **BACKUP**: Ensure database backups are taken before applying these changes
5. **TESTING**: Test foreign key constraints in development environment first

## Next Steps

1. Review this report with the development team
2. Create and test the migration in development
3. Schedule maintenance window for production deployment
4. Apply the migration with proper rollback plan
5. Verify all constraints are working correctly post-deployment