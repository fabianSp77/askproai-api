# Staff Duplicates Fix - 2025-07-09

## Problem
- Duplicate staff members with same email address in same company
- UI display issues in staff list (misaligned columns, unclear layout)

## Solution Implemented

### 1. Database Cleanup
- **Script**: `cleanup-staff-duplicates.php`
  - Found 1 duplicate: 2 staff with email fabian@askproai.de in company ID 1
  - Deleted duplicate ID 133, kept ID 101
  - Transferred appointments and service assignments before deletion

### 2. Database Constraint
- **Migration**: `2025_07_09_085211_add_unique_constraint_to_staff_table.php`
  - Added unique constraint on company_id + email
  - Includes automatic cleanup of duplicates before applying constraint
  - Transfers appointments and services to the kept record

### 3. Form Validation
- **Updated**: `app/Filament/Admin/Resources/StaffResource.php`
  - Added unique validation rule for email within same company
  - German error message: "Diese E-Mail-Adresse wird bereits von einem anderen Mitarbeiter in diesem Unternehmen verwendet."
  - Validation considers only non-empty email addresses

### 4. UI Improvements
- **Created**: `resources/css/filament/admin/staff-table-fix.css`
  - Removed table-layout: fixed for flexible column widths
  - Proper vertical alignment for all cells
  - Optimized column widths:
    - Avatar: 40px fixed
    - Name: 200-250px
    - Phone: 150px
    - Company/Branch: 140-200px
    - Count badges: 90px centered
    - Actions: 120px
  - Responsive breakpoints hide less important columns on smaller screens
  - Improved badge styling and text overflow handling

- **Updated**: `StaffResource::table()`
  - Simplified layout removing Split/Stack components
  - Added description (email) under name
  - Better column organization
  - Added phone column with icon

## Files Modified
1. `/var/www/api-gateway/cleanup-staff-duplicates.php` (created)
2. `/var/www/api-gateway/database/migrations/2025_07_09_085211_add_unique_constraint_to_staff_table.php` (created)
3. `/var/www/api-gateway/app/Filament/Admin/Resources/StaffResource.php`
4. `/var/www/api-gateway/resources/css/filament/admin/staff-table-fix.css` (created)
5. `/var/www/api-gateway/resources/css/filament/admin/theme.css`
6. `/var/www/api-gateway/app/Filament/Admin/Resources/StaffResource/Pages/ListStaff.php`

## Result
- No more duplicate staff with same email in same company
- Database constraint prevents future duplicates
- Form validation provides user-friendly error message
- Improved table layout with better spacing and alignment
- Responsive design hides less important columns on smaller screens
- Clear visual hierarchy with optimized column widths