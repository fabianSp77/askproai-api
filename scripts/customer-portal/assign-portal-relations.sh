-- ============================================================================
-- Customer Portal Phase 1: Assign branch_id and staff_id to Users
-- ============================================================================
--
-- Purpose: Assign branch and staff relationships to existing users
--          to enable multi-level access control in Customer Portal
--
-- Prerequisites:
-- 1. Migration 2025_10_26_201516_add_branch_id_and_staff_id_to_users_table
--    must be executed first
-- 2. Users must already have roles assigned (via Spatie Laravel Permission)
-- 3. Branches and Staff records must exist
--
-- Safety:
-- - All updates use WHERE clauses to prevent accidental overwrites
-- - UUIDs must match existing records (foreign key constraints)
-- - Run in transaction for safety
--
-- Usage:
--   mysql -u root -p askproai_db < database/seeders/AssignCustomerPortalRelations.sql
--
-- ============================================================================

START TRANSACTION;

-- ============================================================================
-- STEP 1: Assign branch_id to company_manager users
-- ============================================================================
--
-- Logic: company_manager users should be assigned to a specific branch
-- They will only see data from their assigned branch
--
-- Example: Assign manager to main branch of their company
-- ============================================================================

-- EXAMPLE: Assign manager@friseur1.de to Friseur1 main branch
-- UPDATE users
-- SET branch_id = (
--     SELECT id FROM branches
--     WHERE company_id = (SELECT company_id FROM users WHERE email = 'manager@friseur1.de')
--     AND is_main = 1
--     LIMIT 1
-- )
-- WHERE email = 'manager@friseur1.de'
-- AND branch_id IS NULL;

-- TEMPLATE: Assign all company_manager users to their company's main branch
UPDATE users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id AND r.name = 'company_manager'
SET u.branch_id = (
    SELECT b.id FROM branches b
    WHERE b.company_id = u.company_id
    AND b.is_main = 1
    LIMIT 1
)
WHERE u.branch_id IS NULL
AND u.company_id IS NOT NULL;

SELECT
    'company_manager users assigned to main branch' AS status,
    COUNT(*) AS affected_rows
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id AND r.name = 'company_manager'
WHERE u.branch_id IS NOT NULL;

-- ============================================================================
-- STEP 2: Assign staff_id to company_staff users
-- ============================================================================
--
-- Logic: company_staff users should be linked to their staff record
-- They will only see their own appointments, calls, customers
--
-- Prerequisites:
-- - Staff record must exist with same email as user
-- - Or manual assignment if different emails
-- ============================================================================

-- TEMPLATE: Link users to staff records by matching email
UPDATE users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id AND r.name = 'company_staff'
INNER JOIN staff s ON u.email = s.email AND u.company_id = s.company_id
SET u.staff_id = s.id
WHERE u.staff_id IS NULL;

SELECT
    'company_staff users linked to staff records (by email)' AS status,
    COUNT(*) AS affected_rows
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id AND r.name = 'company_staff'
WHERE u.staff_id IS NOT NULL;

-- ============================================================================
-- STEP 3: Verification Queries
-- ============================================================================

-- Check users without branch_id who should have one
SELECT
    'company_manager users WITHOUT branch_id (should be 0)' AS check_type,
    COUNT(*) AS count,
    GROUP_CONCAT(u.email SEPARATOR ', ') AS emails
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id AND r.name = 'company_manager'
WHERE u.branch_id IS NULL;

-- Check users without staff_id who should have one
SELECT
    'company_staff users WITHOUT staff_id (manual assignment needed)' AS check_type,
    COUNT(*) AS count,
    GROUP_CONCAT(u.email SEPARATOR ', ') AS emails
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id AND r.name = 'company_staff'
WHERE u.staff_id IS NULL;

-- Show summary of all customer portal users with their assignments
SELECT
    r.name AS role,
    u.email,
    c.name AS company,
    b.name AS assigned_branch,
    s.name AS assigned_staff,
    u.branch_id IS NOT NULL AS has_branch,
    u.staff_id IS NOT NULL AS has_staff
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id
LEFT JOIN companies c ON u.company_id = c.id
LEFT JOIN branches b ON u.branch_id = b.id
LEFT JOIN staff s ON u.staff_id = s.id
WHERE r.name IN ('company_owner', 'company_admin', 'company_manager', 'company_staff')
ORDER BY r.name, u.email;

COMMIT;

-- ============================================================================
-- Manual Assignment Examples (if automatic assignment doesn't work)
-- ============================================================================

-- Example 1: Assign specific user to specific branch
-- UPDATE users
-- SET branch_id = 'uuid-of-branch'
-- WHERE email = 'manager@company.com';

-- Example 2: Assign specific user to specific staff record
-- UPDATE users
-- SET staff_id = 'uuid-of-staff'
-- WHERE email = 'staff@company.com';

-- Example 3: Assign all users of a company to their main branch
-- UPDATE users
-- SET branch_id = (
--     SELECT id FROM branches
--     WHERE company_id = 'uuid-of-company' AND is_main = 1
--     LIMIT 1
-- )
-- WHERE company_id = 'uuid-of-company'
-- AND branch_id IS NULL;

-- ============================================================================
-- Rollback (if needed)
-- ============================================================================

-- Rollback all branch_id assignments
-- UPDATE users SET branch_id = NULL WHERE branch_id IS NOT NULL;

-- Rollback all staff_id assignments
-- UPDATE users SET staff_id = NULL WHERE staff_id IS NOT NULL;

-- ============================================================================
-- Notes
-- ============================================================================
--
-- 1. This script is SAFE to re-run (uses WHERE branch_id/staff_id IS NULL)
-- 2. UUIDs must match existing records (foreign key constraints enforce this)
-- 3. If foreign key errors occur, check that branches/staff records exist
-- 4. For production: Test on staging first!
-- 5. Monitor logs after assignment: grep "Policy denied" storage/logs/laravel.log
--
-- ============================================================================
