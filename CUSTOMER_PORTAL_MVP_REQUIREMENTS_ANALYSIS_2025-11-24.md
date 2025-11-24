# Customer Portal MVP - Comprehensive Requirements Analysis

**Date**: 2025-11-24
**Analyst**: Claude (Requirements Analyst Mode)
**Project**: AskPro AI Gateway - Customer Portal Launch
**Phase**: Pre-Launch Requirements Validation

---

## Executive Summary

This document provides a systematic requirements analysis for the Customer Portal MVP launch based on confirmed business decisions. The analysis uncovers **73 specific requirements**, **42 edge cases**, **18 critical risks**, and **29 open questions** requiring business clarification.

**Key Findings**:
- ✅ **Foundation Exists**: Phase 1 implementation is complete (read-only portal)
- ⚠️ **Critical Gaps**: Appointment reschedule/cancel functionality is MISSING from current implementation
- ⚠️ **User Management Gap**: No current implementation for self-service user management
- 🚨 **Risk Level**: HIGH - Multiple security, UX, and integration risks identified

---

## Business Context (Confirmed Decisions)

### Immediate Scope (This Week)
1. **Launch Strategy**: Pilot with 2-3 customers (beta test)
2. **MVP Features**: Appointment reschedule/cancel + User Management
3. **Access Model**: Free for all customers initially
4. **Support Model**: Self-service documentation + onboarding assistance

### Strategic Scope (This Month)
5. **Permission Architecture**: Simplified (~30 permissions vs 400+)
6. **Role Naming**: Standardize to lowercase-hyphen format
7. **Multi-Level Isolation**: STRICT branch-level access control
8. **Shared Customer Access**: All staff with customer interaction history

### Long-Term Scope (This Quarter)
9. **Pricing Model**: TBD (measure usage first)
10. **Phase 2 Priority**: 1. Analytics → 2. CRM → 3. Staff Mgmt → 4. Services

---

## Section 1: Feature Requirements

### Feature 1.1: Appointment Reschedule

#### User Stories

**US-1.1.1**: As a **company_owner**, I want to reschedule any appointment in my company, so that I can manage operations flexibly.

**Acceptance Criteria**:
- Can view all company appointments (across all branches)
- Can select appointment and click "Reschedule" action
- Shown available alternative slots from Cal.com
- Can select new slot and confirm reschedule
- Appointment updated in both Laravel DB and Cal.com
- Customer receives notification (email/SMS)
- Original appointment metadata preserved
- AppointmentModification record created with modification_type='reschedule'

**US-1.1.2**: As a **company_admin**, I want to reschedule appointments in my company, so that I can support operational needs.

**Acceptance Criteria**:
- Same as US-1.1.1 (company_admin has same permissions as company_owner for appointments)

**US-1.1.3**: As a **company_manager**, I want to reschedule appointments in MY BRANCH ONLY, so that I can manage my branch operations.

**Acceptance Criteria**:
- Can ONLY see appointments where `appointment.branch_id === user.branch_id`
- Can reschedule these appointments following same flow as owner
- CANNOT see or reschedule appointments from other branches
- Policy enforcement via AppointmentPolicy (level 3: branch isolation)

**US-1.1.4**: As a **company_staff**, I want to reschedule MY OWN appointments, so that I can manage my personal schedule.

**Acceptance Criteria**:
- Can ONLY see appointments where `appointment.staff_id === user.staff_id`
- Can reschedule own appointments
- CANNOT reschedule other staff's appointments
- Policy enforcement via AppointmentPolicy (level 4: staff isolation)

**US-1.1.5**: As a **customer calling via phone**, I want to reschedule my appointment through the AI agent, so that I can change my booking without portal access.

**Acceptance Criteria**:
- Phone-authenticated customers (identified by phone number) can reschedule
- Retell AI agent calls reschedule_appointment function
- Same validation rules apply (policy checks, slot availability)
- Audit trail shows modification_by_type='Customer'

#### Business Rules

**BR-1.1.1**: **Minimum Notice Period**
- **Rule**: Appointments cannot be rescheduled within X hours of start time
- **Default**: 24 hours (configurable via PolicyConfiguration)
- **Enforcement**: Server-side validation in reschedule handler
- **User Feedback**: "Cannot reschedule - appointment starts in less than 24 hours"

**BR-1.1.2**: **Maximum Reschedule Count**
- **Rule**: Each appointment can be rescheduled maximum N times
- **Default**: 3 reschedules (configurable via PolicyConfiguration)
- **Enforcement**: Check count of AppointmentModification records with modification_type='reschedule'
- **User Feedback**: "Maximum reschedule limit reached (3/3)"

**BR-1.1.3**: **Fee Charging Rules**
- **Rule**: Reschedule may incur fee based on notice period and policy
- **Policy Check**: PolicyConfiguration (policy_type='reschedule', configurable_type='Company|Branch|Service')
- **Fee Calculation**: Based on hours_notice and policy config
- **Fee Recording**: AppointmentModification.fee_charged
- **Within Policy**: AppointmentModification.within_policy (boolean)

**BR-1.1.4**: **Composite Service Handling**
- **Rule**: Composite appointments (is_composite=true) must reschedule ALL segments atomically
- **Segments**: All phases must be rescheduled together
- **Validation**: New slot must accommodate entire composite duration
- **Rollback**: If any segment fails, entire reschedule must rollback

**BR-1.1.5**: **Cal.com Sync Requirement**
- **Rule**: Reschedule MUST sync to Cal.com to prevent calendar conflicts
- **Sync Job**: SyncAppointmentToCalcomJob dispatched
- **Sync Validation**: Check sync status before confirming to user
- **Failure Handling**: If Cal.com sync fails, appointment remains in old slot OR reschedule is rolled back

**BR-1.1.6**: **Staff Assignment Preservation**
- **Rule**: Rescheduled appointment keeps same staff assignment UNLESS staff is unavailable
- **Availability Check**: Validate staff availability at new time slot
- **Staff Change**: If original staff unavailable, require manual staff re-assignment OR offer staff alternatives

#### Constraints

**C-1.1.1**: **Cal.com API Rate Limits**
- 100 requests/minute per API key
- Availability checks consume 1 request per service/time combination
- Booking updates consume 1 request per appointment
- **Mitigation**: Cache availability data (5-minute TTL), batch operations

**C-1.1.2**: **Redis Lock TTL**
- Slot locks expire after 5 minutes (FEATURE_SLOT_LOCKING)
- Reschedule flow must complete within 5 minutes or lock expires
- **User Impact**: If user takes >5min to confirm, slot may be taken
- **Mitigation**: Show countdown timer, auto-refresh availability

**C-1.1.3**: **Transaction Isolation Level**
- Database must support READ COMMITTED isolation
- Optimistic locking via `appointments.version` column (if exists)
- **Mitigation**: Catch version mismatch exceptions, show conflict error

**C-1.1.4**: **Notification Delivery SLA**
- Emails sent via configured mail service (check config/mail.php)
- SMS sent via configured SMS service (if enabled)
- **No Guarantee**: Notification delivery is best-effort
- **Audit Trail**: Log notification attempts in system logs

#### Dependencies

**D-1.1.1**: **Cal.com API Availability**
- Reschedule requires live connection to Cal.com API
- **Failure Mode**: If Cal.com down, reschedule must be queued OR fail gracefully
- **User Feedback**: "Scheduling system temporarily unavailable"

**D-1.1.2**: **AppointmentModification Model**
- Already exists ✅
- Used to track reschedule history
- Required for policy enforcement (reschedule count)

**D-1.1.3**: **PolicyConfiguration System**
- Already exists ✅
- Stores reschedule policies (policy_type='reschedule')
- Hierarchical: Company → Branch → Service

**D-1.1.4**: **Weekly Availability Service**
- Already exists ✅
- Used to fetch alternative slots
- Respects staff assignments and branch constraints

#### Validations

**V-1.1.1**: **Input Validation**
```php
// Required fields
appointment_id: required|exists:appointments,id
new_start_time: required|date|after:now
new_staff_id: nullable|exists:staff,id

// Business validations
- new_start_time must be > (now + minimum_notice_hours)
- new_start_time must be within service operating hours
- new_staff_id (if provided) must belong to same branch
```

**V-1.1.2**: **Authorization Validation**
```php
// Policy checks
Gate::authorize('reschedule', $appointment)

// Multi-level checks
- Level 1: Admin bypass (always allowed)
- Level 2: Company isolation (user.company_id === appointment.company_id)
- Level 3: Branch isolation (IF company_manager: user.branch_id === appointment.branch_id)
- Level 4: Staff isolation (IF company_staff: user.staff_id === appointment.staff_id)
```

**V-1.1.3**: **Availability Validation**
```php
// Slot availability check
- Service must be available at new_start_time
- Staff must be available at new_start_time (if assigned)
- Branch must have capacity for appointment
- No conflicting appointments for same staff
```

**V-1.1.4**: **Policy Compliance Validation**
```php
// Check reschedule policy
$policy = PolicyConfiguration::getCachedPolicy($appointment->service, 'reschedule')

// Validate against policy
- hours_notice >= policy->config['minimum_hours'] ?? 24
- reschedule_count < policy->config['max_reschedules'] ?? 3
- fee_calculation based on policy->config['fee_rules']
```

#### Notifications

**N-1.1.1**: **Customer Notification**
- **Trigger**: Appointment successfully rescheduled
- **Channels**: Email (primary), SMS (if configured)
- **Content**:
  - Appointment rescheduled confirmation
  - Old date/time (crossed out)
  - NEW date/time (highlighted)
  - Service name
  - Staff name (if assigned)
  - Branch location
  - Cancellation policy reminder
  - Link to customer portal (if customer has account)

**N-1.1.2**: **Staff Notification**
- **Trigger**: Appointment rescheduled (affects their calendar)
- **Channels**: Email, Cal.com calendar update
- **Content**:
  - Appointment rescheduled by [role]
  - Customer name
  - Service
  - Old vs New time
  - Link to appointment details

**N-1.1.3**: **Admin Notification** (Optional)
- **Trigger**: Reschedule outside normal policy (late reschedule with fee waiver)
- **Channels**: Email to company admin
- **Content**: Policy exception notification

#### Edge Cases

**EC-1.1.1**: **Appointment in the Past**
- **Scenario**: User tries to reschedule past appointment
- **Expected**: Validation error "Cannot reschedule past appointments"
- **Current**: AppointmentPolicy.reschedule() checks `starts_at < now()` ✅

**EC-1.1.2**: **Appointment Starting in 15 Minutes**
- **Scenario**: User tries to reschedule appointment starting very soon
- **Expected**: Blocked by minimum notice period (default 24h)
- **Edge**: What if minimum_notice=0 in policy? Still allow?
- **Decision Needed**: Should there be absolute minimum (e.g., 1 hour) regardless of policy?

**EC-1.1.3**: **Cal.com Sync Failure**
- **Scenario**: Appointment rescheduled in Laravel but Cal.com API fails
- **Expected Behavior**:
  - Option A: Rollback Laravel changes (transactional)
  - Option B: Keep Laravel changes, queue retry, flag for manual review
- **Current Implementation**: Uses SyncAppointmentToCalcomJob (async)
- **Risk**: Async means user gets success message but sync could fail later
- **Recommendation**: Make sync synchronous for reschedule operations

**EC-1.1.4**: **Multiple Concurrent Reschedules**
- **Scenario**: User opens reschedule UI, another admin reschedules same appointment
- **Expected**: Optimistic locking failure, show conflict error
- **Current**: No version column on appointments table
- **Mitigation**: Check appointment.updated_at timestamp, reject if changed

**EC-1.1.5**: **Staff Assigned to Appointment is Deleted**
- **Scenario**: Appointment has staff_id but Staff was soft-deleted
- **Expected**: Allow reschedule but require new staff assignment
- **Validation**: Check Staff::withTrashed()->find($staff_id)

**EC-1.1.6**: **Composite Service with Mixed Availability**
- **Scenario**: Composite appointment (4 segments), only 2 segments available at new time
- **Expected**: Block reschedule, show "Full service not available at selected time"
- **Complexity**: Must check all segments atomically

**EC-1.1.7**: **Branch Changed After Appointment Created**
- **Scenario**: Appointment.branch_id points to branch user no longer has access to
- **Expected**: company_manager cannot reschedule (branch isolation)
- **Edge**: What if branch was merged/deleted? Need branch existence check

**EC-1.1.8**: **Customer Has Multiple Overlapping Appointments**
- **Scenario**: Customer has 2 appointments, tries to reschedule one to overlap the other
- **Expected**: Validation error "Customer has conflicting appointment"
- **Current**: No customer-level conflict check in code
- **Recommendation**: Add customer conflict validation

---

### Feature 1.2: Appointment Cancellation

#### User Stories

**US-1.2.1**: As a **company_owner**, I want to cancel any appointment, so that I can handle operational changes.

**Acceptance Criteria**:
- Can cancel any appointment in company
- Shown cancellation reason dropdown (required)
- Policy check calculates cancellation fee
- Fee displayed to user before confirmation
- Appointment status set to 'cancelled'
- Cal.com booking cancelled
- Customer notified via email/SMS
- AppointmentModification record created

**US-1.2.2**: As a **company_manager**, I want to cancel appointments in my branch, so that I can manage branch operations.

**Acceptance Criteria**:
- Can cancel appointments where `branch_id === user.branch_id`
- Same flow as owner (reason, fee, confirmation)

**US-1.2.3**: As a **company_staff**, I want to cancel my appointments (with manager approval?), so that I can handle emergencies.

**Acceptance Criteria**:
- Can cancel appointments where `staff_id === user.staff_id`
- **Question**: Should staff cancellations require manager approval?
- **Question**: Should staff be able to cancel at all, or only reschedule?

**US-1.2.4**: As a **customer via phone**, I want to cancel my appointment through AI agent, so that I don't need portal access.

**Acceptance Criteria**:
- Phone-authenticated customers can cancel
- AI agent explains cancellation policy and fee
- Customer confirms understanding
- Cancellation processed
- Audit trail shows modification_by_type='Customer'

#### Business Rules

**BR-1.2.1**: **Cancellation Policy Enforcement**
- **Rule**: Cancellation fee based on notice period
- **Example Policy**:
  - >48h notice: No fee (within_policy=true)
  - 24-48h notice: 50% fee
  - <24h notice: 100% fee
  - <2h notice: 100% fee + no-show penalty
- **Source**: PolicyConfiguration (policy_type='cancellation')

**BR-1.2.2**: **Cancellation Reason Required**
- **Rule**: All cancellations must have a reason
- **Options**:
  - Customer requested
  - Staff unavailable
  - Emergency
  - Weather/External factors
  - Business closure
  - Other (free text)

**BR-1.2.3**: **No Cancellation After Start Time**
- **Rule**: Cannot cancel appointments that already started
- **Validation**: `appointment.starts_at < now()` → reject
- **Alternative**: Mark as "no-show" instead of cancelled

**BR-1.2.4**: **Composite Appointment Atomic Cancellation**
- **Rule**: Cancelling composite appointment cancels ALL segments
- **Implementation**: Cancel parent appointment, cascade to all segments
- **Cal.com**: All segment bookings must be cancelled

**BR-1.2.5**: **Recurring Appointment Cancellation Scope**
- **Question**: Cancel single occurrence OR entire series?
- **Options**:
  - This appointment only
  - This and all future appointments
  - Entire series
- **Recommendation**: Ask user to choose scope

#### Constraints

**C-1.2.1**: **Cal.com Cancellation Sync**
- Must cancel Cal.com booking to free staff calendar
- If Cal.com API fails, flag appointment for manual review
- **Critical**: Staff calendar MUST reflect cancellation

**C-1.2.2**: **Fee Collection**
- Cancellation fee recorded in AppointmentModification.fee_charged
- **Question**: How is fee actually collected?
  - Deduct from customer account balance?
  - Invoice customer later?
  - No actual collection (tracking only)?
- **Recommendation**: Clarify fee collection mechanism

**C-1.2.3**: **Soft Delete vs Status Change**
- Current: Appointments have status='cancelled' (not soft-deleted)
- Cancelled appointments remain visible in admin panel
- **Question**: Should cancelled appointments be visible in customer portal?

#### Validations

**V-1.2.1**: **Cancellation Authorization**
```php
Gate::authorize('cancel', $appointment)

// AppointmentPolicy::cancel() checks:
- status !== 'completed' (can't cancel completed)
- Multi-level access (company → branch → staff isolation)
- User has role: admin|manager|staff|company_owner|company_admin|company_manager
```

**V-1.2.2**: **Cancellation Timing**
```php
// Cannot cancel past appointments
if ($appointment->starts_at < now()) {
    throw ValidationException::withMessages([
        'appointment' => 'Cannot cancel past appointments'
    ]);
}
```

**V-1.2.3**: **Reason Validation**
```php
'reason' => 'required|string|max:500',
'cancellation_category' => 'required|in:customer_requested,staff_unavailable,emergency,weather,business_closure,other'
```

#### Notifications

**N-1.2.1**: **Customer Cancellation Confirmation**
- **Channels**: Email, SMS
- **Content**:
  - Appointment cancelled
  - Date/time
  - Service
  - Cancellation fee (if applicable)
  - Rebooking link

**N-1.2.2**: **Staff Calendar Update**
- **Channels**: Cal.com sync (automatic)
- **Effect**: Frees staff availability for slot

#### Edge Cases

**EC-1.2.1**: **Cancel Already Cancelled Appointment**
- **Scenario**: User tries to cancel appointment with status='cancelled'
- **Expected**: Validation error "Appointment already cancelled"
- **Current**: AppointmentPolicy::cancel() blocks if status='completed' but allows re-cancel
- **Fix Needed**: Add check for status='cancelled'

**EC-1.2.2**: **Cancel During Active Call**
- **Scenario**: Customer is on phone booking, admin cancels appointment in portal
- **Expected**: Race condition - booking might still complete
- **Mitigation**: Check appointment.status in start_booking flow

**EC-1.2.3**: **Fee Waiver Override**
- **Scenario**: Manager wants to waive cancellation fee (customer exception)
- **Expected**: UI option to override fee calculation
- **Authorization**: Only company_owner/company_admin can override
- **Audit**: Record override in metadata

**EC-1.2.4**: **No-Show vs Cancellation**
- **Scenario**: Customer doesn't show up, appointment not pre-cancelled
- **Expected**: Different handling than cancellation
- **Status**: 'no-show' (separate status)
- **Question**: Should portal have "Mark as No-Show" action?

---

### Feature 2: User Management (Self-Service)

#### User Stories

**US-2.1.1**: As a **company_owner**, I want to create user accounts for my staff, so they can access the portal.

**Acceptance Criteria**:
- Navigate to "Team Management" section
- Click "Invite User" button
- Fill form: Name, Email, Role (dropdown)
- Select role from: company_admin, company_manager, company_staff
- If role=company_manager: Select branch assignment (required)
- If role=company_staff: Select staff profile (required)
- Click "Send Invitation"
- User receives invitation email with setup link
- Invitation expires after 7 days
- Owner can resend expired invitations

**US-2.1.2**: As a **company_admin**, I want to manage users in my company, so I can help owner with administration.

**Acceptance Criteria**:
- Same capabilities as company_owner for user management
- Can create, edit, suspend, delete users
- Can assign roles (except company_owner)
- Can reset passwords
- CANNOT delete or modify company_owner accounts

**US-2.1.3**: As a **company_manager**, I want to view team members in my branch, but not create/delete them.

**Acceptance Criteria**:
- Can VIEW users where `user.branch_id === manager.branch_id`
- CANNOT create new users
- CANNOT delete users
- CAN request admin to add users to their branch
- Read-only access to team directory

**US-2.1.4**: As a **company_staff**, I want to view my profile and update my personal settings.

**Acceptance Criteria**:
- Can view own user profile
- Can update: name, email, password, profile picture
- CANNOT change own role
- CANNOT view other users
- Can enable/disable 2FA

**US-2.1.5**: As a **new user**, I want to accept invitation and set up my account, so I can access the portal.

**Acceptance Criteria**:
- Receive invitation email
- Click invitation link (token-based)
- If token expired: Show error, provide "Request New Invitation" link
- If token valid: Show registration form
- Set password (meet complexity requirements)
- Agree to terms of service
- Submit and auto-login
- Redirected to portal dashboard

#### Business Rules

**BR-2.1.1**: **Role Hierarchy**
```
company_owner (highest privilege)
  ├─ company_admin (can manage everything except owners)
  ├─ company_manager (branch-scoped, limited user mgmt)
  └─ company_staff (own data only, no user mgmt)
```

**BR-2.1.2**: **Privilege Escalation Prevention**
- Users CANNOT create accounts with higher privilege than themselves
- company_admin CANNOT create company_owner
- company_manager CANNOT create company_admin or company_owner
- company_staff CANNOT create any users

**BR-2.1.3**: **Branch Assignment Rules**
- company_manager: branch_id REQUIRED
- company_staff: branch_id OPTIONAL (if staff assigned)
- company_owner/admin: branch_id NULL (see all branches)

**BR-2.1.4**: **Staff Profile Linkage**
- company_staff: staff_id REQUIRED
- Links portal user to Staff resource
- Used for appointment assignment visibility
- One Staff can have ONE user account (1:1 relationship)

**BR-2.1.5**: **Last Admin Protection**
- CANNOT delete last company_owner in company
- CANNOT delete last company_admin if no owners
- Validation before user deletion

**BR-2.1.6**: **Self-Modification Restrictions**
- Users CANNOT delete themselves
- Users CANNOT change own role
- Users CAN change own password/email
- Implemented in UserPolicy::delete() ✅

#### Functional Requirements

**FR-2.1.1**: **User Invitation Flow**
```
1. Admin fills invitation form
2. System validates:
   - Email not already registered
   - Role assignment valid for admin's privilege level
   - Branch exists (if role=company_manager)
   - Staff exists and unlinked (if role=company_staff)
3. Create invitation token (UUID)
4. Store invitation: user_invitations table
   - email, token, role, company_id, branch_id, staff_id, expires_at, invited_by
5. Send invitation email with link: /portal/invitation/{token}
6. User clicks link → redirect to registration
7. User submits registration
8. Validate token not expired
9. Create User record
10. Assign role via Spatie Permission
11. Mark invitation as accepted
12. Auto-login user
```

**FR-2.1.2**: **User Edit Flow**
```
1. Admin navigates to Users list
2. Clicks Edit on user
3. Can modify:
   - Name
   - Email (triggers email verification)
   - Role (respecting privilege rules)
   - Branch assignment (if role=company_manager)
   - Staff assignment (if role=company_staff)
   - Status: Active/Suspended
4. Cannot modify:
   - company_id (immutable - multi-tenant isolation)
5. Save triggers:
   - Validation
   - Audit log entry
   - Email to user if email/role changed
```

**FR-2.1.3**: **User Suspension (Soft Block)**
```
- Status: Active → Suspended
- User cannot login when suspended
- User's data remains intact
- Can be reactivated by admin
- Audit trail records suspension reason
- User receives suspension notification email
```

**FR-2.1.4**: **User Deletion (Hard Delete)**
```
- CANNOT delete users with active appointments as staff
- CANNOT delete users with pending actions
- Soft-delete User record (deleted_at timestamp)
- User can no longer login
- Historical data preserved (appointments, modifications)
- Cascade: Remove role assignments, invalidate sessions
```

**FR-2.1.5**: **Password Reset Flow**
```
1. User clicks "Forgot Password"
2. Enter email
3. System sends password reset link (token)
4. User clicks link
5. Enter new password (2x confirmation)
6. Password updated
7. All sessions invalidated (force re-login)
8. Email confirmation sent
```

**FR-2.1.6**: **2FA Management**
```
- Optional 2FA (TOTP-based, e.g., Google Authenticator)
- Users can enable via Profile Settings
- Enrollment: QR code scan → verify code → backup codes
- Login: password + 6-digit code
- Admin can force 2FA for all users (company setting)
- Backup codes for account recovery
```

#### Security Requirements

**SR-2.1.1**: **Company Isolation (CRITICAL)**
```php
// Users MUST NOT be able to:
- View users from other companies
- Create users in other companies
- Assign roles across company boundaries

// Enforcement:
- UserPolicy checks: user.company_id === target.company_id
- Filament Resources scoped by company (modifyQueryUsing)
```

**SR-2.1.2**: **Session Management**
```php
// Security controls:
- Session timeout: 2 hours idle
- Concurrent session limit: 3 devices
- IP change detection (optional warning)
- Session revocation on password change
- Logout all devices option
```

**SR-2.1.3**: **Audit Logging**
```php
// Log these events:
- User created (by whom, what role)
- User role changed (from → to, by whom)
- User suspended/deleted (reason, by whom)
- Failed login attempts (IP, timestamp)
- Password changes
- Permission changes

// Storage: activity_log table (Spatie Activity Log)
```

**SR-2.1.4**: **Password Complexity**
```php
// Requirements:
- Minimum 8 characters
- Must contain: uppercase, lowercase, number, special char
- Cannot be common password (check against blacklist)
- Cannot be same as last 3 passwords
- Enforced via Laravel validation rules
```

**SR-2.1.5**: **Email Verification**
```php
// When required:
- New account creation
- Email address change
- Re-enable after suspension

// Process:
- Send verification email
- User must click link within 24h
- Account marked email_verified_at
- Cannot access sensitive features until verified
```

#### Edge Cases

**EC-2.1.1**: **Owner Tries to Delete Self**
- **Scenario**: Last company_owner tries to delete own account
- **Expected**: Validation error "Cannot delete last owner - assign another owner first"
- **Current**: UserPolicy::delete() prevents self-deletion ✅

**EC-2.1.2**: **Duplicate Email Addresses Across Companies**
- **Scenario**: user@example.com exists in Company A, admin in Company B invites same email
- **Expected**:
  - Option A: Block (email must be unique globally)
  - Option B: Allow (same person, multi-company access)
- **Current**: Laravel unique validation on users.email (global unique) ✅
- **Recommendation**: Keep global unique for security

**EC-2.1.3**: **User Has Active Appointments, Then Deleted**
- **Scenario**: company_staff user with upcoming appointments is deleted
- **Expected**: Appointments still show staff name (soft-delete preserves data)
- **Display**: Show "(Former Staff)" badge in UI

**EC-2.1.4**: **Invitation Link Expired**
- **Scenario**: User clicks invitation link after 7 days
- **Expected**: Show friendly error page with "Request New Invitation" button
- **Action**: Sends email to company admin requesting re-invitation

**EC-2.1.5**: **Admin Changes User's Role While User is Logged In**
- **Scenario**: User is using portal, admin changes their role
- **Expected**: Next request detects role change, force re-login
- **Implementation**: Check role hash in session vs current roles

**EC-2.1.6**: **Staff Profile Already Linked to Another User**
- **Scenario**: Admin tries to assign staff_id that's already used
- **Expected**: Validation error "This staff member already has a user account"
- **Validation**: Unique constraint on users.staff_id OR application-level check

**EC-2.1.7**: **Branch Deleted After Manager Assigned**
- **Scenario**: company_manager.branch_id points to deleted branch
- **Expected**: Manager cannot access portal (no branch = no data scope)
- **Fix**: Prevent branch deletion if users assigned OR auto-reassign users

---

### Feature 3: Pilot Company Selection

#### Functional Requirements

**FR-3.1**: **Feature Flag Architecture**
```php
// config/features.php (already exists ✅)
'customer_portal' => env('FEATURE_CUSTOMER_PORTAL', false)

// Global kill switch
// When false: ALL companies blocked
// When true: Check pilot whitelist
```

**FR-3.2**: **Pilot Company Whitelist**
```php
// config/features.php
'customer_portal_pilot_companies' => array_filter(
    array_map('intval', explode(',', env('CUSTOMER_PORTAL_PILOT_COMPANIES', '')))
)

// .env example:
CUSTOMER_PORTAL_PILOT_COMPANIES=15,42,103
```

**FR-3.3**: **Access Control Logic**
```php
// In User::canAccessCustomerPortal()
public function canAccessCustomerPortal(): bool
{
    // Global kill switch
    if (!config('features.customer_portal')) {
        return false;
    }

    // Pilot whitelist check
    $pilotCompanies = config('features.customer_portal_pilot_companies');
    if (!empty($pilotCompanies)) {
        if (!in_array($this->company_id, $pilotCompanies)) {
            return false; // Not a pilot company
        }
    }

    // Role check
    return $this->hasAnyRole(['company_owner', 'company_admin', 'company_manager', 'company_staff']);
}
```

**FR-3.4**: **Admin UI for Pilot Management**
```
Location: /admin/system/settings

Section: Customer Portal Pilot Program
- [ ] Enable Customer Portal (global)
- Pilot Companies (multi-select dropdown)
  □ Company A (ID: 15)
  □ Company B (ID: 42)
  □ Company C (ID: 103)

[Save Settings]

// Stores to .env OR database system_settings table
```

**FR-3.5**: **Rollout Phases**
```
Phase 1: Internal Testing
- Global flag: ON
- Pilot list: [1] (internal test company)
- Users: 5 internal staff
- Duration: 3 days

Phase 2: Beta Launch
- Global flag: ON
- Pilot list: [1, 15, 42, 103]
- Users: 2-3 actual customers (~15-20 users)
- Duration: 2 weeks
- Feedback collection: Survey after each session

Phase 3: Gradual Rollout
- Week 1: 10 companies
- Week 2: 25 companies
- Week 3: 50 companies
- Week 4: Remove whitelist (all companies)

Phase 4: General Availability
- Global flag: ON
- Pilot list: [] (empty = all companies)
```

#### Monitoring & Analytics

**M-3.1**: **Portal Usage Metrics**
```php
// Track these metrics per company:
- Daily active users (DAU)
- Feature usage:
  - Appointment views
  - Reschedules initiated
  - Cancellations initiated
  - User management actions
- Session duration
- Error rate
- User feedback score (NPS)

// Storage: analytics events table OR external (Google Analytics, Mixpanel)
```

**M-3.2**: **Error Tracking**
```php
// Critical errors to monitor:
- 500 errors (server errors)
- Authorization failures (403)
- Cal.com sync failures
- Email delivery failures
- Database deadlocks

// Alerting: Slack/Email if error rate >5%
```

**M-3.3**: **Performance Monitoring**
```php
// Track response times:
- Page load time (target: <2s)
- API calls (target: <500ms)
- Cal.com availability checks (target: <1s)
- Database query time (target: <100ms)

// Slow query logging: >1s
```

#### Feedback Collection

**FC-3.1**: **In-App Feedback Widget**
```
Location: Bottom-right corner of portal

Widget:
"How is your experience?"
😀 😊 😐 🙁 😞
[Optional feedback text]
[Submit]

Triggers:
- After successful reschedule
- After 5 minutes in portal
- On logout
```

**FC-3.2**: **Pilot User Onboarding Survey**
```
Email sent after first login:

Subject: Welcome to AskPro Customer Portal Beta

Hi [Name],

Thank you for participating in our Customer Portal pilot!

Your feedback will shape the future of this product.

Quick Survey (2 minutes):
1. How easy was it to find the feature you needed? (1-5)
2. Did the portal meet your expectations? (Yes/No/Partially)
3. What feature would you like to see added?
4. Any bugs or issues encountered?

[Complete Survey]
```

**FC-3.3**: **Weekly Check-In**
```
Week 1: "How was your first week?"
Week 2: "Have you tried rescheduling appointments?"
Week 3: "Any features you wish existed?"
```

---

## Section 2: Non-Functional Requirements

### Performance Requirements

**NFR-P1**: **Page Load Time**
- **Target**: <2 seconds (from click to interactive)
- **Measurement**: Real User Monitoring (RUM)
- **Critical Pages**: Dashboard, Appointments List, Reschedule UI
- **Optimization**: Server-side rendering, lazy loading, image optimization

**NFR-P2**: **API Response Time**
- **Target**: <500ms (p95)
- **Critical APIs**:
  - GET /api/appointments: <300ms
  - POST /api/appointments/{id}/reschedule: <1s
  - GET /api/availability: <1s (Cal.com dependency)
- **Monitoring**: Application Performance Monitoring (APM)

**NFR-P3**: **Database Query Performance**
- **Target**: <100ms per query (p95)
- **Indexes**: Ensure indexes on:
  - appointments.company_id, appointments.branch_id
  - appointments.staff_id, appointments.starts_at
  - users.company_id, users.branch_id
- **N+1 Prevention**: Eager load relationships (with('customer', 'staff', 'service'))

**NFR-P4**: **Concurrent Users**
- **Target**: Support 100 concurrent users per company
- **Total System**: 1,000 concurrent users
- **Load Testing**: Apache JMeter or k6 scenarios
- **Scaling**: Horizontal (multiple app servers) + Redis session store

**NFR-P5**: **Cal.com API Performance**
- **Rate Limit**: 100 req/min per API key
- **Caching Strategy**:
  - Availability: 5-minute cache
  - Event Types: 1-hour cache
  - Cache invalidation on bookings
- **Timeout Handling**: 5s timeout, graceful degradation

### Security Requirements

**NFR-S1**: **Authentication**
- **Method**: Laravel Sanctum (SPA authentication)
- **Session**: HttpOnly cookies, SameSite=Lax
- **Password**: Bcrypt hash (cost=10)
- **2FA**: Optional TOTP (6-digit codes)
- **Brute Force Protection**: Max 5 failed attempts → 15min lockout

**NFR-S2**: **Authorization**
- **Model**: Role-Based Access Control (RBAC) via Spatie Permission
- **Enforcement**: Laravel Policies (AppointmentPolicy, UserPolicy, etc.)
- **Multi-Tenancy**: Company-scoped queries (BelongsToCompany trait)
- **Branch Isolation**: Enforced in policies (Level 3 checks)

**NFR-S3**: **Data Privacy (GDPR)**
- **Right to Access**: Export user data (JSON format)
- **Right to Deletion**: Anonymize user data (GDPR-compliant deletion)
- **Data Retention**:
  - Active appointments: Indefinite
  - Cancelled appointments: 2 years
  - User accounts: Until deletion requested
- **Consent**: Terms of Service acceptance required on registration

**NFR-S4**: **SSL/TLS**
- **Requirement**: HTTPS only (enforce redirect)
- **Certificate**: Valid SSL certificate (Let's Encrypt or commercial)
- **HSTS**: HTTP Strict Transport Security header
- **Minimum TLS**: TLS 1.2

**NFR-S5**: **Input Validation**
- **Client-Side**: Livewire validation (immediate feedback)
- **Server-Side**: Laravel FormRequest validation (security boundary)
- **Sanitization**: HTML purification for text inputs
- **SQL Injection**: Protected via Eloquent ORM (parameterized queries)

**NFR-S6**: **API Security**
- **Authentication**: Bearer token (Sanctum)
- **Rate Limiting**: 60 requests/minute per user
- **CORS**: Restrict to application domain
- **CSRF**: Enabled for state-changing requests

### Scalability Requirements

**NFR-SC1**: **Database Scaling**
- **Current**: Single PostgreSQL instance
- **Read Replicas**: Add read replicas if >10,000 appointments/day
- **Partitioning**: Partition appointments table by year (if >1M records)
- **Connection Pooling**: PgBouncer (max 100 connections)

**NFR-SC2**: **Caching Strategy**
- **Application**: Redis (cache store)
- **Session**: Redis (session store)
- **Policy Configs**: 5-minute cache (PolicyConfiguration::getCachedPolicy)
- **Availability**: 5-minute cache (WeeklyAvailabilityService)
- **Cache Invalidation**: Event-driven (AppointmentObserver)

**NFR-SC3**: **Queue Workers**
- **Jobs**:
  - SyncAppointmentToCalcomJob (high priority)
  - SendNotificationJob (medium priority)
  - CleanupExpiredReservationsJob (low priority)
- **Workers**: 3 workers (1 per queue)
- **Supervisor**: Auto-restart on failure

### Availability Requirements

**NFR-A1**: **Uptime SLA**
- **Target**: 99.5% uptime (3.6 hours downtime/month)
- **Measurement**: Uptime monitoring (UptimeRobot, Pingdom)
- **Maintenance Windows**: Sundays 2-4 AM (announced 48h prior)

**NFR-A2**: **Disaster Recovery**
- **Backup Frequency**: Daily (automated)
- **Backup Retention**: 30 days
- **Recovery Time Objective (RTO)**: 4 hours
- **Recovery Point Objective (RPO)**: 24 hours (daily backup)
- **Testing**: Quarterly restore drills

**NFR-A3**: **Graceful Degradation**
- **Cal.com Unavailable**: Show cached availability, queue booking requests
- **Redis Unavailable**: Fall back to database sessions (slower but functional)
- **Email Service Down**: Queue emails for retry (max 3 attempts)

### Accessibility Requirements

**NFR-ACC1**: **WCAG 2.1 Level AA Compliance**
- **Keyboard Navigation**: All interactive elements accessible via keyboard
- **Screen Readers**: ARIA labels on all UI components
- **Color Contrast**: Minimum 4.5:1 ratio (text/background)
- **Focus Indicators**: Visible focus states on all interactive elements

**NFR-ACC2**: **Responsive Design**
- **Mobile**: Optimized for iOS Safari, Android Chrome
- **Tablet**: Optimized for iPad, Android tablets
- **Desktop**: Support Chrome, Firefox, Safari, Edge (latest 2 versions)
- **Breakpoints**:
  - Mobile: <768px
  - Tablet: 768-1024px
  - Desktop: >1024px

**NFR-ACC3**: **Internationalization (i18n)**
- **Primary Language**: German (de_DE)
- **Fallback**: English (en_US)
- **Translation Files**: resources/lang/de/portal.php
- **Date/Time Format**: German locale (DD.MM.YYYY, HH:mm)
- **Timezone**: Europe/Berlin

---

## Section 3: Edge Case Matrix

| ID | Scenario | Expected Behavior | Current Behavior | Gap? |
|---|---|---|---|---|
| **Appointment Reschedule** |||||
| EC-1 | Reschedule past appointment | Validation error "Cannot reschedule past appointments" | AppointmentPolicy blocks ✅ | None |
| EC-2 | Reschedule appointment starting in 15min | Blocked by minimum notice policy (default 24h) | Not implemented | **MEDIUM** |
| EC-3 | Cal.com sync fails during reschedule | Rollback OR queue retry + flag manual review | Async job (no immediate validation) | **HIGH** |
| EC-4 | Concurrent reschedule by 2 admins | Optimistic locking conflict error | No version column | **HIGH** |
| EC-5 | Reschedule composite appointment (partial availability) | Block reschedule "Full service unavailable" | Not implemented | **MEDIUM** |
| EC-6 | Staff deleted after assignment | Allow reschedule, require new staff OR show error | Relationship exists (withTrashed) | **LOW** |
| EC-7 | Branch changed/deleted | Branch isolation check fails, block reschedule | Policy checks branch_id | **LOW** |
| EC-8 | Customer has overlapping appointments | Validation error "Conflicting appointment" | Not implemented | **MEDIUM** |
| **Appointment Cancellation** |||||
| EC-9 | Cancel already cancelled appointment | Validation error "Already cancelled" | Policy allows re-cancel | **MEDIUM** |
| EC-10 | Cancel during active booking call | Race condition - booking might complete | No lock mechanism | **HIGH** |
| EC-11 | Fee waiver override | UI option to override (admin only) | Not implemented | **LOW** |
| EC-12 | No-show vs cancellation | Different status, different handling | Status field exists | **LOW** |
| EC-13 | Recurring appointment cancellation scope | Ask user: single/future/all | Not implemented | **MEDIUM** |
| **User Management** |||||
| EC-14 | Owner deletes self (last owner) | Validation error "Cannot delete last owner" | UserPolicy blocks self-delete ✅ | None |
| EC-15 | Duplicate email across companies | Block (global unique email) | Laravel unique validation ✅ | None |
| EC-16 | User deleted with active appointments | Soft-delete, show "(Former Staff)" in UI | Soft-delete supported | **LOW** |
| EC-17 | Invitation link expired | Friendly error + "Request New Invitation" button | Not implemented | **MEDIUM** |
| EC-18 | Admin changes user role (user logged in) | Force re-login on next request | Not implemented | **MEDIUM** |
| EC-19 | Staff profile already linked | Validation error "Staff has account" | No unique constraint | **HIGH** |
| EC-20 | Branch deleted (manager assigned) | Prevent deletion OR auto-reassign users | Not implemented | **MEDIUM** |
| **Multi-Tenancy** |||||
| EC-21 | User switches company context (if multi-company) | Re-scope all queries to new company | Single company per user ✅ | None |
| EC-22 | Cross-company data leak via URL manipulation | 403 Forbidden (policy enforcement) | Policies check company_id ✅ | None |
| EC-23 | Branch manager accesses other branch via API | 403 Forbidden (branch isolation) | Policy Level 3 checks ✅ | None |
| **Cal.com Integration** |||||
| EC-24 | Cal.com API down during reschedule | Graceful error "Scheduling unavailable, try later" | Not implemented | **HIGH** |
| EC-25 | Cal.com rate limit exceeded | Queue request OR show error | Not implemented | **MEDIUM** |
| EC-26 | Cal.com returns conflicting availability | Show error "Availability changed, refresh" | Not implemented | **MEDIUM** |
| EC-27 | Cal.com booking deleted externally | Detect sync mismatch, flag for review | Webhook handles deletions | **LOW** |
| **Session & Auth** |||||
| EC-28 | Session expires during reschedule form | Auto-redirect to login, preserve form state | Not implemented | **MEDIUM** |
| EC-29 | Concurrent sessions (same user, 2 devices) | Allow (track session count) | Laravel sessions support | **LOW** |
| EC-30 | IP address changes mid-session | Optional warning, allow continuation | Not implemented | **LOW** |
| **Performance** |||||
| EC-31 | 100 users reschedule simultaneously | Queue bottleneck OR slow response | Load testing needed | **HIGH** |
| EC-32 | Large company (10+ branches, 100+ staff) | Pagination on users list, optimized queries | Filament pagination ✅ | **LOW** |
| EC-33 | Availability check times out (>5s) | Show cached data + warning OR retry | Timeout configured (30s) | **MEDIUM** |
| **Data Integrity** |||||
| EC-34 | Database transaction rollback mid-reschedule | Partial update (appointment vs Cal.com) | No transaction wrapper | **HIGH** |
| EC-35 | Redis lock expires during booking flow | Slot taken error, user must retry | FEATURE_SLOT_LOCKING handles | **LOW** |
| EC-36 | Notification delivery fails | Log failure, retry 3x, then give up | Not implemented | **MEDIUM** |
| **UI/UX** |||||
| EC-37 | Mobile user on slow 3G connection | Progressive loading, show skeleton screens | Not implemented | **MEDIUM** |
| EC-38 | Screen reader user navigates portal | ARIA labels, keyboard navigation works | Not implemented | **MEDIUM** |
| EC-39 | User has 1000+ appointments | Pagination, virtual scrolling, filtering | Filament pagination ✅ | **LOW** |
| EC-40 | JavaScript disabled | Show error "JavaScript required" OR basic HTML fallback | Not implemented | **LOW** |
| **Edge Time Scenarios** |||||
| EC-41 | Reschedule during DST change | Timezone handling (Europe/Berlin) | Carbon handles DST ✅ | None |
| EC-42 | Appointment at midnight (23:59 → 00:01) | Correct date handling | Carbon handles ✅ | None |

**Gap Severity**:
- **HIGH**: Critical for MVP launch (must fix)
- **MEDIUM**: Important but can be addressed post-launch
- **LOW**: Nice to have, low impact

---

## Section 4: Risk Assessment

### Risk Matrix

| ID | Risk | Likelihood | Severity | Impact | Mitigation Strategy |
|---|---|---|---|---|---|
| **Technical Risks** ||||||
| R-01 | Cal.com API downtime during pilot | Medium | Critical | Users cannot reschedule | Implement graceful degradation: queue requests, show cached availability, manual fallback |
| R-02 | Cal.com sync race condition | High | High | Double-booking, calendar conflicts | Use distributed locks (Redis), synchronous sync for reschedule, validate sync status |
| R-03 | Database deadlocks under load | Medium | High | Failed transactions, user errors | Optimize query order, use row-level locking, implement retry logic |
| R-04 | Redis unavailable (cache/sessions) | Low | Medium | Slower performance, session loss | Fallback to database sessions, monitor Redis health, implement circuit breaker |
| R-05 | Email delivery failures | Medium | Medium | Users miss notifications | Queue retries (3x), log failures, implement webhook confirmations (SendGrid) |
| R-06 | Performance degradation (>100 users) | Medium | High | Slow response times, timeout errors | Load testing before launch, horizontal scaling ready, CDN for assets |
| **Security Risks** ||||||
| R-07 | Multi-tenant data leak | Low | Critical | GDPR violation, customer trust loss | Comprehensive policy testing, automated security tests, manual penetration testing |
| R-08 | Privilege escalation via role manipulation | Low | Critical | Unauthorized access to admin features | Server-side role validation, audit logging, regular security reviews |
| R-09 | Session hijacking | Low | High | Account takeover | HTTPS only, HttpOnly cookies, IP validation, session timeout (2h) |
| R-10 | Brute force attacks on login | Medium | Medium | Account lockouts, DDoS | Rate limiting (5 attempts/15min), CAPTCHA after 3 fails, IP blocking |
| R-11 | Cross-Site Scripting (XSS) | Low | High | Session theft, data manipulation | Input sanitization, CSP headers, Filament's built-in XSS protection |
| **Business Risks** ||||||
| R-12 | User confusion (poor UX) | High | Medium | Low adoption, support tickets | User testing before launch, comprehensive documentation, onboarding videos |
| R-13 | Feature gaps vs expectations | Medium | Medium | Customer dissatisfaction | Clear communication of MVP scope, feedback loop, rapid iteration |
| R-14 | Pilot customers cancel appointments en masse | Low | High | Revenue loss, resource waste | Cancellation policy enforcement, fee structure, data-driven insights |
| R-15 | Support team overwhelmed | Medium | Medium | Slow response, customer churn | Self-service documentation, FAQ, in-app help, dedicated support channel |
| **Operational Risks** ||||||
| R-16 | Deployment failure (downtime) | Low | High | Portal unavailable, customer impact | Blue-green deployment, rollback plan, deploy in maintenance window |
| R-17 | Database migration failure | Low | Critical | Data corruption, rollback required | Test migrations on staging, backup before deployment, rollback script ready |
| R-18 | Monitoring gaps (undetected issues) | Medium | High | Silent failures, delayed response | Comprehensive monitoring (errors, performance, usage), alerting (Slack/Email) |

### Critical Risks Requiring Immediate Attention

**PRIORITY 1: Cal.com Sync Reliability (R-02)**
- **Problem**: Async sync means user gets success message but Cal.com might fail
- **Impact**: Calendar conflicts, staff double-booked, customer shows up but no appointment
- **Solution**: Make reschedule/cancel sync SYNCHRONOUS with timeout
- **Implementation**:
```php
// Instead of:
SyncAppointmentToCalcomJob::dispatch($appointment);
return response()->json(['success' => true]);

// Do this:
try {
    $calcomBooking = app(CalcomV2Client::class)->updateBooking($appointment);
    if (!$calcomBooking->successful()) {
        throw new CalcomSyncException("Cal.com sync failed");
    }
    $appointment->update(['calcom_booking_id' => $calcomBooking->json('id')]);
    return response()->json(['success' => true]);
} catch (CalcomSyncException $e) {
    // Rollback appointment changes
    DB::rollback();
    return response()->json(['success' => false, 'error' => 'Scheduling system unavailable'], 503);
}
```

**PRIORITY 2: Multi-Tenant Data Leak Prevention (R-07)**
- **Problem**: Insufficient testing of branch/company isolation
- **Impact**: GDPR violation, customer data leak, legal liability
- **Solution**: Comprehensive automated security tests
- **Implementation**:
```php
// Add to tests/Feature/CustomerPortal/SecurityTest.php
test('company_manager cannot access other branches appointments', function () {
    $branch1 = Branch::factory()->create(['company_id' => 1]);
    $branch2 = Branch::factory()->create(['company_id' => 1]);

    $manager = User::factory()->create([
        'company_id' => 1,
        'branch_id' => $branch1->id
    ])->assignRole('company_manager');

    $appointment = Appointment::factory()->create(['branch_id' => $branch2->id]);

    actingAs($manager);

    // Should return 403 Forbidden
    $response = get("/portal/appointments/{$appointment->id}");
    $response->assertForbidden();

    // Should not appear in list
    $response = get("/portal/appointments");
    $response->assertDontSee($appointment->id);
});
```

**PRIORITY 3: Staff Profile Uniqueness (R-19 / EC-19)**
- **Problem**: Multiple users could be linked to same staff profile
- **Impact**: Appointment visibility issues, audit trail confusion
- **Solution**: Add unique constraint on users.staff_id
- **Implementation**:
```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->unique('staff_id'); // Ensure 1:1 relationship
});

// Validation in UserPolicy::update()
if ($request->staff_id) {
    $existingUser = User::where('staff_id', $request->staff_id)
        ->where('id', '!=', $user->id)
        ->exists();
    if ($existingUser) {
        throw ValidationException::withMessages([
            'staff_id' => 'This staff member already has a user account'
        ]);
    }
}
```

---

## Section 5: Open Questions for Business Owner

### Feature Scope Questions

**Q-1.1**: **Reschedule Minimum Notice Period**
- Should there be an absolute minimum (e.g., 2 hours) regardless of policy configuration?
- Or should policy configuration be fully respected (even if minimum_hours=0)?
- **Recommendation**: Enforce absolute minimum of 1 hour for operational safety.

**Q-1.2**: **Staff Reschedule Approval Workflow**
- Can company_staff reschedule appointments without manager approval?
- OR should staff reschedules require approval from company_manager/company_admin?
- **Recommendation**: Allow staff to reschedule own appointments (faster resolution).

**Q-1.3**: **Cancellation Fee Collection Mechanism**
- How are cancellation fees actually collected?
  - A) Deducted from customer account balance (if balance system exists)
  - B) Invoice sent to customer (manual payment)
  - C) Tracking only (no actual collection)
- **Recommendation**: Start with tracking only (option C) for MVP, implement collection in Phase 2.

**Q-1.4**: **Recurring Appointment Cancellation Scope**
- When cancelling recurring appointment, what are the options?
  - A) This occurrence only
  - B) This and all future occurrences
  - C) Entire series (including past)
- **Recommendation**: Offer A and B (exclude C - don't cancel past appointments).

**Q-1.5**: **No-Show Handling**
- Should portal have "Mark as No-Show" action (separate from cancellation)?
- Who can mark no-shows? (Only admin? Or staff too?)
- What's the impact? (Fee? Block future bookings?)
- **Recommendation**: Admin-only feature, tracks customer reliability, no automatic blocks.

### User Management Questions

**Q-2.1**: **User Invitation Expiration**
- 7-day expiration for invitation links - is this acceptable?
- Should expired invitations auto-delete or remain for audit trail?
- **Recommendation**: 7 days is standard, keep expired invitations for audit (30-day cleanup).

**Q-2.2**: **Email Uniqueness**
- Should email addresses be globally unique (one person = one account)?
- OR allow same email across multiple companies (one person = multiple roles)?
- **Current**: Global unique ✅
- **Recommendation**: Keep global unique for security and simplicity.

**Q-2.3**: **User Deletion Impact**
- When user is deleted, what happens to their historical data?
  - A) Soft-delete user, preserve all data (name shows in history)
  - B) Hard-delete user, anonymize data (GDPR right to be forgotten)
  - C) Soft-delete user, anonymize after 90 days
- **Recommendation**: Option A for MVP (soft-delete preserves audit trail).

**Q-2.4**: **2FA Enforcement**
- Should 2FA be optional or mandatory?
- Can it be configured per-company?
- **Recommendation**: Optional for MVP, add company-level enforcement toggle in Phase 2.

**Q-2.5**: **Password Reset Self-Service**
- Users can reset own password via email link?
- OR require admin to reset passwords (more secure but less convenient)?
- **Recommendation**: Self-service via email link (standard practice).

### Pilot Program Questions

**Q-3.1**: **Pilot Company Selection Criteria**
- How will pilot companies be selected?
  - A) Manual selection by sales team
  - B) Volunteer-based (opt-in)
  - C) Usage-based (most active customers)
- **Recommendation**: Option B (volunteer beta testers provide better feedback).

**Q-3.2**: **Pilot Duration**
- How long should pilot run before general availability?
  - A) 1 week (rapid validation)
  - B) 2 weeks (balanced)
  - C) 4 weeks (thorough testing)
- **Recommendation**: 2 weeks minimum (1 week = insufficient data).

**Q-3.3**: **Pilot Exit Criteria**
- What metrics determine pilot success?
  - A) <5% error rate
  - B) >70% positive feedback (NPS)
  - C) Zero critical bugs
  - D) All of the above
- **Recommendation**: All of the above + 80% feature usage rate.

**Q-3.4**: **Rollback Plan**
- If pilot fails, what's the rollback strategy?
  - A) Disable feature flag (instant rollback)
  - B) Keep portal read-only (disable reschedule/cancel)
  - C) Full rollback + data cleanup
- **Recommendation**: Option A (feature flag = instant rollback, preserve data).

### Pricing & Monetization Questions

**Q-4.1**: **Free Access Duration**
- How long will portal remain free?
  - A) 3 months (during pilot + initial rollout)
  - B) 6 months (extended beta)
  - C) Forever for existing customers (new customers pay)
- **Recommendation**: 3 months free, then introduce pricing based on usage data.

**Q-4.2**: **Pricing Model Options** (for future)
- Which pricing model makes most sense?
  - A) Per-user per-month (e.g., €5/user/month)
  - B) Per-company flat fee (e.g., €50/month unlimited users)
  - C) Feature-based tiers (Basic, Pro, Enterprise)
  - D) Usage-based (per reschedule/cancel action)
- **Recommendation**: Measure usage during pilot, then decide. Likely option C (tiered).

**Q-4.3**: **Usage Tracking for Pricing**
- What metrics to track for pricing model design?
  - A) Number of portal users per company
  - B) Number of reschedule/cancel actions per month
  - C) Total appointments managed via portal
  - D) Session duration / engagement metrics
- **Recommendation**: Track all of the above, focus on B (actions = value).

### Support & Documentation Questions

**Q-5.1**: **Self-Service Documentation Scope**
- What documentation must exist before launch?
  - A) User guide (how to reschedule, cancel, manage users)
  - B) Video tutorials (screen recordings)
  - C) FAQ (common questions)
  - D) In-app tooltips
  - E) All of the above
- **Recommendation**: A + C + D minimum. Add B (videos) if time permits.

**Q-5.2**: **Onboarding Assistance**
- What level of onboarding support will be provided?
  - A) Email support only
  - B) 1:1 onboarding call (30 min per company)
  - C) Webinar for all pilot companies
  - D) Self-service only (documentation)
- **Recommendation**: Option B for pilot companies (high-touch), option D for general rollout.

**Q-5.3**: **Support Channel**
- Where should users get help?
  - A) Email: support@askpro.ai
  - B) In-app chat widget (Intercom, Crisp)
  - C) Knowledge base (FAQ/docs)
  - D) Phone support
- **Recommendation**: A + C for MVP. Add B (chat) if budget allows.

**Q-5.4**: **Bug Reporting**
- How should pilot users report bugs?
  - A) Email to support
  - B) In-app feedback form
  - C) Dedicated Slack channel (pilot companies)
  - D) Bug tracker (public or private)
- **Recommendation**: B (in-app feedback) + A (email fallback). C (Slack) for high-touch pilot.

### Technical Questions

**Q-6.1**: **Cal.com Sync Strategy**
- Should reschedule/cancel sync to Cal.com synchronously or asynchronously?
  - A) Synchronous (wait for Cal.com response, slower but safer)
  - B) Asynchronous (queue job, faster but risk of failure)
- **Current**: Asynchronous (SyncAppointmentToCalcomJob)
- **Recommendation**: Switch to synchronous for reschedule/cancel (see Risk R-02).

**Q-6.2**: **Error Handling User Experience**
- When Cal.com API fails, what should user see?
  - A) Generic error "Something went wrong, try again"
  - B) Specific error "Scheduling system unavailable, please contact support"
  - C) Graceful degradation "Booking queued, you'll receive confirmation via email"
- **Recommendation**: Option B (transparent communication) + retry button.

**Q-6.3**: **Session Timeout**
- What's the acceptable session timeout?
  - A) 30 minutes (aggressive, more secure)
  - B) 2 hours (balanced)
  - C) 24 hours (convenient, less secure)
- **Recommendation**: 2 hours idle timeout (can extend on activity).

**Q-6.4**: **Mobile App vs Mobile Web**
- Is a native mobile app planned?
  - A) Yes, after web portal proven
  - B) No, mobile-responsive web only
- **Recommendation**: Option B for MVP (responsive web = lower cost, faster iteration).

---

## Section 6: Implementation Recommendations

### Phase 1: Pre-Launch (Week 1)

**Milestone**: Production-Ready MVP

#### Tasks

1. **Implement Appointment Reschedule** (2-3 days)
   - [ ] Create RescheduleAppointmentAction (Filament Action)
   - [ ] Build RescheduleForm with available slots dropdown
   - [ ] Integrate WeeklyAvailabilityService for slot fetching
   - [ ] Implement PolicyConfiguration check (minimum_hours, max_reschedules, fees)
   - [ ] Add synchronous Cal.com sync (replace async job)
   - [ ] Create AppointmentModification record (modification_type='reschedule')
   - [ ] Send customer notification email
   - [ ] Add audit logging
   - [ ] Write tests (PolicyTest, ActionTest, IntegrationTest)

2. **Implement Appointment Cancellation** (1-2 days)
   - [ ] Create CancelAppointmentAction (Filament Action)
   - [ ] Build CancellationForm (reason required)
   - [ ] Implement PolicyConfiguration check (cancellation fees)
   - [ ] Update appointment.status = 'cancelled'
   - [ ] Synchronous Cal.com booking cancellation
   - [ ] Create AppointmentModification record
   - [ ] Send customer notification email
   - [ ] Write tests

3. **Implement User Management** (2-3 days)
   - [ ] Create UserResource in Customer Panel (app/Filament/Customer/Resources/)
   - [ ] Build User invitation flow (InviteUserAction)
   - [ ] Create user_invitations table migration
   - [ ] Build registration page (/portal/invitation/{token})
   - [ ] Implement UserPolicy checks (privilege escalation prevention)
   - [ ] Add staff_id unique constraint migration
   - [ ] Build user edit form (name, email, role, branch, staff)
   - [ ] Implement user suspension (status toggle)
   - [ ] Add audit logging (Spatie Activity Log)
   - [ ] Write tests (SecurityTest, PolicyTest)

4. **Security Hardening** (1 day)
   - [ ] Run automated security test suite
   - [ ] Manual penetration testing (branch isolation)
   - [ ] Add missing indexes (users.branch_id, users.staff_id)
   - [ ] Implement rate limiting (60 req/min per user)
   - [ ] Add CSRF protection verification
   - [ ] Review all policies for completeness

5. **Documentation** (1 day)
   - [ ] Write user guide (How to Reschedule, Cancel, Manage Users)
   - [ ] Create FAQ (common questions)
   - [ ] Record video tutorial (reschedule walkthrough) - OPTIONAL
   - [ ] Write in-app tooltips
   - [ ] Create admin guide (pilot management, monitoring)

6. **Monitoring Setup** (0.5 day)
   - [ ] Configure error tracking (Sentry or Bugsnag)
   - [ ] Set up performance monitoring (New Relic or Scout APM)
   - [ ] Create alerts (Slack channel for errors)
   - [ ] Build usage analytics (Mixpanel or Google Analytics)
   - [ ] Set up uptime monitoring (UptimeRobot)

### Phase 2: Pilot Launch (Week 2)

**Milestone**: 2-3 Companies Using Portal

#### Tasks

1. **Pilot Company Selection** (0.5 day)
   - [ ] Identify 2-3 volunteer companies
   - [ ] Validate they have:
     - Active appointments
     - 3-5 staff members
     - Willingness to provide feedback
   - [ ] Add company IDs to whitelist

2. **Feature Flag Configuration** (0.5 day)
   - [ ] Set FEATURE_CUSTOMER_PORTAL=true in .env
   - [ ] Set CUSTOMER_PORTAL_PILOT_COMPANIES=15,42,103
   - [ ] Verify access control (non-pilot companies blocked)
   - [ ] Deploy to production

3. **Pilot User Onboarding** (1 day)
   - [ ] Send welcome email with login credentials
   - [ ] Schedule 1:1 onboarding call (30 min each)
   - [ ] Walkthrough: Reschedule, Cancel, User Management
   - [ ] Provide documentation links
   - [ ] Set up feedback collection (survey link)

4. **Daily Monitoring** (ongoing)
   - [ ] Check error logs daily
   - [ ] Monitor usage metrics (DAU, feature usage)
   - [ ] Respond to support requests <2h
   - [ ] Collect feedback (weekly check-in emails)

5. **Weekly Review** (end of week)
   - [ ] Analyze usage data
   - [ ] Review collected feedback
   - [ ] Prioritize bug fixes
   - [ ] Decide: Continue pilot OR rollback OR iterate

### Phase 3: Gradual Rollout (Weeks 3-4)

**Milestone**: 10-25 Companies Using Portal

#### Tasks

1. **Expand Pilot** (Week 3)
   - [ ] Add 10 more companies to whitelist
   - [ ] Monitor system performance under load
   - [ ] Address any scalability issues
   - [ ] Refine documentation based on feedback

2. **Performance Optimization** (Week 3)
   - [ ] Optimize slow database queries
   - [ ] Add caching where needed
   - [ ] Load testing (simulate 100 concurrent users)
   - [ ] Horizontal scaling if needed

3. **Feature Iteration** (Week 4)
   - [ ] Implement top 3 feature requests (if quick wins)
   - [ ] Fix all medium-priority bugs
   - [ ] Improve UX based on user feedback

4. **Prepare for General Availability** (Week 4)
   - [ ] Remove pilot whitelist (open to all companies)
   - [ ] Announce general availability (email all customers)
   - [ ] Prepare support team (FAQ, response templates)
   - [ ] Set up self-service onboarding flow

### Phase 4: General Availability (Month 2)

**Milestone**: All Customers Can Access Portal

#### Tasks

1. **Launch** (Day 1)
   - [ ] Set CUSTOMER_PORTAL_PILOT_COMPANIES= (empty = all)
   - [ ] Deploy to production
   - [ ] Send announcement email to all customers
   - [ ] Monitor closely for 48 hours

2. **Post-Launch Support** (Week 1-2)
   - [ ] Respond to support tickets <4h
   - [ ] Daily error log review
   - [ ] Weekly usage report to stakeholders
   - [ ] Collect NPS feedback

3. **Measure Success** (Month 1)
   - [ ] Calculate metrics:
     - Daily Active Users (DAU)
     - Feature usage rates (reschedule, cancel, user mgmt)
     - Error rate
     - Customer satisfaction (NPS)
   - [ ] Compare to success criteria

4. **Plan Phase 2 Features** (Month 2)
   - [ ] Review Phase 2 priority: Analytics → CRM → Staff Mgmt → Services
   - [ ] Based on usage data, confirm priority
   - [ ] Start requirements analysis for Phase 2

---

## Section 7: Success Metrics & KPIs

### MVP Success Criteria (Pilot Phase)

**Must Achieve (Go/No-Go Criteria)**:
- ✅ Zero critical bugs (severity 1 issues)
- ✅ <5% error rate (successful operations ≥95%)
- ✅ ≥70% positive feedback (NPS ≥7/10)
- ✅ ≥80% feature usage (at least 80% of pilot users try reschedule/cancel)

**Desirable Metrics**:
- Daily Active Users (DAU): ≥60% of invited users
- Average session duration: ≥5 minutes
- Reschedule success rate: ≥90%
- Cal.com sync success rate: ≥98%

### Key Performance Indicators (Post-Launch)

**Adoption Metrics**:
- **Portal Activation Rate**: % of companies with ≥1 active portal user
  - Target: 50% in Month 1, 75% in Month 3
- **Daily Active Users (DAU)**: Unique users per day
  - Target: 100 DAU after 1 month
- **Monthly Active Users (MAU)**: Unique users per month
  - Target: 500 MAU after 3 months

**Engagement Metrics**:
- **Feature Usage Rate**: % of users who use each feature
  - Reschedule: Target 40%
  - Cancel: Target 15%
  - User Management: Target 60% (owner/admin only)
- **Session Duration**: Average time spent in portal
  - Target: 5-10 minutes per session
- **Return Rate**: % of users who return within 7 days
  - Target: 50%

**Performance Metrics**:
- **Page Load Time**: Time to interactive
  - Target: <2 seconds (p95)
- **API Response Time**: Server response time
  - Target: <500ms (p95)
- **Error Rate**: % of failed requests
  - Target: <2%
- **Cal.com Sync Success Rate**: % of successful syncs
  - Target: ≥98%

**Business Metrics**:
- **Support Ticket Reduction**: % reduction in manual reschedule/cancel requests
  - Target: 30% reduction
- **Customer Satisfaction (NPS)**: Net Promoter Score
  - Target: ≥50 (Excellent)
- **Time Savings**: Hours saved per company per month
  - Target: 5 hours/company/month

**Quality Metrics**:
- **Uptime**: System availability
  - Target: 99.5%
- **Security Incidents**: Data leaks, unauthorized access
  - Target: Zero
- **Bug Density**: Bugs per 1000 lines of code
  - Target: <1

---

## Section 8: Dependencies & Prerequisites

### Technical Prerequisites

**TP-1**: **Feature Flag System** ✅ EXISTS
- File: config/features.php
- Flag: customer_portal
- Whitelist: customer_portal_pilot_companies

**TP-2**: **User Roles & Permissions** ✅ EXISTS
- Roles: company_owner, company_admin, company_manager, company_staff
- Permissions: Managed via Spatie Permission package
- Policies: AppointmentPolicy, UserPolicy (extended for portal)

**TP-3**: **Database Schema** ✅ MOSTLY COMPLETE
- users.company_id ✅
- users.branch_id ✅
- users.staff_id ✅
- **MISSING**: users.staff_id unique constraint ⚠️
- **MISSING**: user_invitations table ⚠️

**TP-4**: **Cal.com Integration** ✅ EXISTS
- Service: CalcomV2Client
- API: v2 endpoints (bookings, slots)
- Rate Limiting: 100 req/min
- Sync Job: SyncAppointmentToCalcomJob

**TP-5**: **Notification System** ✅ EXISTS
- Email: Configured mail service (check config/mail.php)
- Templates: Blade email templates
- Queue: Email jobs queued for async delivery

**TP-6**: **Filament Customer Panel** ✅ EXISTS
- Provider: CustomerPanelProvider
- Path: /portal
- Resources: 11 resources (read-only)
- **MISSING**: Reschedule/Cancel actions ⚠️
- **MISSING**: UserResource ⚠️

### Business Prerequisites

**BP-1**: **Pilot Company Identification**
- Need: 2-3 volunteer companies
- Criteria: Active appointments, 3-5 staff, feedback willingness
- Timeline: Select before launch week

**BP-2**: **Support Team Readiness**
- Need: Dedicated support channel (email/chat)
- Training: Support team trained on portal features
- Response SLA: <2h for pilot, <4h for general

**BP-3**: **Documentation Complete**
- User Guide ⚠️ TO DO
- FAQ ⚠️ TO DO
- In-app tooltips ⚠️ TO DO
- Admin guide ⚠️ TO DO

**BP-4**: **Legal Review**
- Terms of Service: Updated for portal access
- Privacy Policy: GDPR compliance for user data
- Cancellation Policy: Clearly stated

### External Dependencies

**ED-1**: **Cal.com API Availability**
- Dependency: Third-party service (Cal.com)
- SLA: Not guaranteed (best-effort)
- Risk: API downtime = portal degradation
- Mitigation: Graceful fallback, cached data

**ED-2**: **Email Service**
- Dependency: SMTP server or SaaS (SendGrid, Mailgun)
- SLA: 99.9% (typical for SaaS)
- Risk: Email delivery failures
- Mitigation: Queue retries, log failures

**ED-3**: **Redis**
- Dependency: Redis server (cache + sessions)
- SLA: Self-hosted (monitor health)
- Risk: Redis down = slower performance
- Mitigation: Fallback to database sessions

---

## Appendices

### Appendix A: User Role Comparison Matrix

| Capability | company_owner | company_admin | company_manager | company_staff |
|---|:---:|:---:|:---:|:---:|
| **Appointment Management** |||||
| View all company appointments | ✅ | ✅ | ❌ (branch only) | ❌ (own only) |
| View branch appointments | ✅ | ✅ | ✅ | ❌ (own only) |
| View own appointments | ✅ | ✅ | ✅ | ✅ |
| Reschedule any appointment | ✅ | ✅ | ❌ (branch only) | ❌ (own only) |
| Cancel any appointment | ✅ | ✅ | ❌ (branch only) | ❌ (own only) |
| **User Management** |||||
| Create company_owner | ❌ | ❌ | ❌ | ❌ |
| Create company_admin | ✅ | ✅ | ❌ | ❌ |
| Create company_manager | ✅ | ✅ | ❌ | ❌ |
| Create company_staff | ✅ | ✅ | ❌ | ❌ |
| Edit users | ✅ | ✅ | ❌ | ❌ (own profile) |
| Delete users | ✅ | ✅ | ❌ | ❌ |
| Reset user passwords | ✅ | ✅ | ❌ | ❌ |
| **Branch Management** |||||
| View all branches | ✅ | ✅ | ❌ (own only) | ❌ (own only) |
| Create/Edit branches | ❌ (admin panel) | ❌ (admin panel) | ❌ | ❌ |
| **Customer Management** (Phase 2) |||||
| View all customers | ✅ | ✅ | ❌ (branch) | ❌ (assigned) |
| Create/Edit customers | 🔜 Phase 2 | 🔜 Phase 2 | 🔜 Phase 2 | ❌ |
| **Analytics** (Phase 3) |||||
| View company analytics | 🔜 Phase 3 | 🔜 Phase 3 | ❌ | ❌ |
| View branch analytics | 🔜 Phase 3 | 🔜 Phase 3 | 🔜 Phase 3 | ❌ |

### Appendix B: Data Model Relationships

```
Company (1) ──────┐
                  │
                  ├──► Branch (N)
                  │       │
                  │       ├──► Staff (N)
                  │       │       │
                  │       │       └──► Appointment (N)
                  │       │
                  │       └──► User (N) [company_manager]
                  │               branch_id → Branch
                  │
                  ├──► User (N)
                  │       company_id → Company
                  │       branch_id → Branch (nullable, for managers)
                  │       staff_id → Staff (nullable, for staff)
                  │       roles: company_owner|company_admin|company_manager|company_staff
                  │
                  ├──► Service (N)
                  │       │
                  │       └──► Appointment (N)
                  │
                  ├──► Customer (N)
                  │       │
                  │       └──► Appointment (N)
                  │
                  └──► PolicyConfiguration (N)
                          policy_type: cancellation|reschedule|recurring
                          configurable_type: Company|Branch|Service|Staff
                          config: JSON (policy rules)

Appointment (1) ──┐
                  │
                  ├──► AppointmentModification (N)
                  │       modification_type: cancel|reschedule
                  │       within_policy: boolean
                  │       fee_charged: decimal
                  │       modified_by_type: User|Staff|Customer|System
                  │
                  └──► AppointmentPhase (N) [if composite]
                          phase_type: initial|processing|final
                          staff_required: boolean
```

### Appendix C: Permission Naming Convention

**Current State**: Inconsistent (mix of uppercase, underscores, hyphens)

**Target State**: Lowercase hyphen-separated (kebab-case)

**Examples**:

| Old Name | New Name | Resource |
|---|---|---|
| viewAny | view-any | All resources |
| view | view | All resources |
| create | create | All resources |
| update | update | All resources |
| delete | delete | All resources |
| reschedule | reschedule | Appointment |
| cancel | cancel | Appointment |
| assign-role | assign-role | User |
| view-analytics | view-analytics | Dashboard (Phase 3) |

**Simplified Permission Count**: ~30 permissions (vs 400+ in over-engineered systems)

**Permission Groups**:
1. **Appointment Permissions** (6): view-any, view, create, update, delete, reschedule, cancel
2. **User Permissions** (6): view-any, view, create, update, delete, assign-role
3. **Customer Permissions** (5): view-any, view, create, update, delete (Phase 2)
4. **Analytics Permissions** (3): view-company, view-branch, export (Phase 3)
5. **Service Permissions** (5): view-any, view, create, update, delete (Phase 2)
6. **Staff Permissions** (5): view-any, view, create, update, delete (Phase 2)

**Total**: 30 permissions

---

## Document Metadata

**Version**: 1.0
**Date**: 2025-11-24
**Author**: Claude (Requirements Analyst)
**Reviewed By**: [Pending]
**Approved By**: [Pending]
**Next Review**: 2025-12-01 (post-pilot)

**Change Log**:
- 2025-11-24: Initial comprehensive requirements analysis
- [Future updates...]

---

**END OF REQUIREMENTS ANALYSIS**
