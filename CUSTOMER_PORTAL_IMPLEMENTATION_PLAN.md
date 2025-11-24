# Customer Portal MVP - Comprehensive Implementation Plan
**Date:** 2025-11-24
**Status:** Planning Phase
**Target:** Complete Customer Portal (Phases 6-8)

---

## 🎯 Executive Summary

**Current State:** Backend infrastructure (Phases 4-5) is complete and tested (112/112 tests passed).

**Goal:** Build complete Customer Portal allowing customers to:
1. Accept invitations via email link
2. Register once with token
3. View their appointments
4. Reschedule appointments with alternatives
5. Cancel appointments with optional reason

**Architecture Decision:** Progressive implementation with parallel execution where possible.

---

## 📊 Current System Analysis

### ✅ What We Have (Completed)

#### Database Layer
- `user_invitations` table (token, expires_at, accepted_at)
- `appointment_audit_logs` table (immutable logs)
- `invitation_email_queue` table (retry mechanism)
- Extended columns on `appointments` (version, last_modified_at, last_modified_by)
- Extended columns on `companies` (is_pilot, pilot_enabled_at)

#### Service Layer
- `UserManagementService` - Full invitation flow
- `AppointmentRescheduleService` - Reschedule with alternatives
- `AppointmentCancellationService` - Cancel with audit
- `CalcomCircuitBreaker` - API protection

#### Background Jobs
- `ProcessInvitationEmailsJob` - Email queue processing (every 5 min)
- `CleanupExpiredInvitationsJob` - Housekeeping (daily 3am)
- `CleanupExpiredReservationsJob` - Slot lock cleanup (every 10 min)

#### Observers & Validation
- `AppointmentObserver` - Optimistic locking, audit logs
- `UserInvitationObserver` - Token generation, duplicate check
- `UserObserver` - Privilege escalation prevention

### ❌ What We Need (To Build)

#### Phase 6: API Layer
1. **Controllers**
   - `CustomerPortalAuthController` - Token validation, invitation acceptance
   - `CustomerPortalAppointmentController` - View, reschedule, cancel

2. **API Routes** (RESTful)
   ```
   GET    /api/customer-portal/invitations/{token}/validate
   POST   /api/customer-portal/invitations/{token}/accept
   GET    /api/customer-portal/appointments
   GET    /api/customer-portal/appointments/{id}
   PUT    /api/customer-portal/appointments/{id}/reschedule
   DELETE /api/customer-portal/appointments/{id}
   ```

3. **Request Validation**
   - `AcceptInvitationRequest` - Name, email, password, phone
   - `RescheduleAppointmentRequest` - Already exists ✅
   - `CancelAppointmentRequest` - Already exists ✅

4. **API Resources (Response Transformers)**
   - `AppointmentResource` - Public-safe appointment data
   - `UserResource` - Customer profile data
   - `InvitationResource` - Invitation status

#### Phase 7: Frontend
1. **Pages**
   - `/portal/einladung/{token}` - Landing + Registration
   - `/portal/meine-termine` - Appointment list
   - `/portal/termin/{id}` - Appointment detail
   - `/portal/termin/{id}/umbuchen` - Reschedule with alternatives
   - `/portal/termin/{id}/stornieren` - Cancel with reason

2. **Components**
   - Authentication (Token-based, no traditional login)
   - Appointment Card (Date, Time, Service, Staff, Location)
   - Alternative Slots Picker (Calendar-style)
   - Form Validation (Client-side + Server-side)
   - Loading States & Error Messages
   - Mobile-Responsive Layout

3. **Tech Stack Decision**
   - **Option A:** Blade + Alpine.js + Tailwind (Laravel native, fast)
   - **Option B:** Inertia.js + Vue 3 + Tailwind (SPA experience)
   - **Recommendation:** Option A (simpler, faster to ship MVP)

#### Phase 8: Integration & Testing
1. **Filament Admin Panel**
   - Invitation Management Resource
   - Send Invitation Button
   - Invitation Status Dashboard
   - Email Queue Monitoring

2. **Testing Strategy**
   - API Tests (PHPUnit) - All endpoints
   - E2E Tests (Playwright/Puppeteer) - Full user flows
   - Manual Testing (3-5 pilot salons)

3. **Deployment**
   - Gradual rollout (Alpha → Beta → Production)
   - Feature flags for pilot program
   - Monitoring & alerting

---

## 🏗️ Detailed Implementation Plan

### Phase 6: API Layer (Estimated: 4-6 hours)

#### Step 6.1: Create Controllers

**File:** `app/Http/Controllers/CustomerPortal/AuthController.php`
**Methods:**
- `validateToken(string $token)` - Check if invitation is valid
- `acceptInvitation(AcceptInvitationRequest $request, string $token)` - Register user
- `login(LoginRequest $request)` - Optional: Traditional login for returning users

**File:** `app/Http/Controllers/CustomerPortal/AppointmentController.php`
**Methods:**
- `index(Request $request)` - List user's appointments
- `show(Request $request, int $id)` - Show single appointment
- `reschedule(RescheduleAppointmentRequest $request, int $id)` - Reschedule
- `cancel(CancelAppointmentRequest $request, int $id)` - Cancel
- `alternatives(Request $request, int $id)` - Get alternative time slots

**Dependencies:**
- All controllers use existing Services (no new business logic)
- Authorization via Middleware (ensure user owns appointment)
- Multi-tenant scoping (automatic via CompanyScope)

#### Step 6.2: Create API Routes

**File:** `routes/api.php`
**Route Group:**
```php
Route::prefix('customer-portal')->group(function () {
    // Public routes (no auth)
    Route::get('/invitations/{token}/validate', [AuthController::class, 'validateToken']);
    Route::post('/invitations/{token}/accept', [AuthController::class, 'acceptInvitation']);

    // Protected routes (token-based auth via Sanctum)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
        Route::get('/appointments/{id}/alternatives', [AppointmentController::class, 'alternatives']);
        Route::put('/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);
        Route::delete('/appointments/{id}', [AppointmentController::class, 'cancel']);
    });
});
```

**Security:**
- Rate limiting: `throttle:60,1` (60 requests per minute)
- CORS: Allow only portal subdomain
- CSRF: Sanctum handles via token

#### Step 6.3: Create Request Validators

**File:** `app/Http/Requests/CustomerPortal/AcceptInvitationRequest.php`
**Rules:**
```php
return [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'max:255'],
    'password' => ['required', 'string', 'min:8', 'confirmed'],
    'phone' => ['nullable', 'string', 'max:20'],
    'terms_accepted' => ['required', 'accepted'],
];
```

**Note:** RescheduleAppointmentRequest and CancelAppointmentRequest already exist ✅

#### Step 6.4: Create API Resources

**File:** `app/Http/Resources/CustomerPortal/AppointmentResource.php`
**Output:**
```json
{
    "id": 762,
    "start_time": "2025-11-25 10:00:00",
    "end_time": "2025-11-25 11:00:00",
    "status": "confirmed",
    "service": {
        "name": "Herrenhaarschnitt",
        "duration": 60,
        "price": "25.00 EUR"
    },
    "staff": {
        "name": "Fabian Spitzer",
        "avatar_url": null
    },
    "location": {
        "branch_name": "Salon Mitte",
        "address": "Hauptstraße 1, 10115 Berlin"
    },
    "can_reschedule": true,
    "can_cancel": true,
    "reschedule_deadline": "2025-11-24 10:00:00",
    "cancel_deadline": "2025-11-24 10:00:00"
}
```

**File:** `app/Http/Resources/CustomerPortal/UserResource.php`
**File:** `app/Http/Resources/CustomerPortal/InvitationResource.php`

#### Step 6.5: Middleware & Authorization

**File:** `app/Http/Middleware/CustomerPortalAuth.php`
**Logic:**
- Verify user owns appointment (customer_id match)
- Check if appointment can be modified (not in past, not already cancelled)
- Multi-tenant isolation (company_id scope)

**Policy:** `app/Policies/CustomerPortal/AppointmentPolicy.php`
```php
public function view(User $user, Appointment $appointment): bool
{
    return $appointment->customer_id === $user->customer_id;
}

public function reschedule(User $user, Appointment $appointment): bool
{
    return $this->view($user, $appointment)
        && $appointment->canBeRescheduled();
}

public function cancel(User $user, Appointment $appointment): bool
{
    return $this->view($user, $appointment)
        && $appointment->canBeCancelled();
}
```

---

### Phase 7: Frontend (Estimated: 8-10 hours)

#### Step 7.1: Setup Frontend Structure

**Directory:** `resources/views/customer-portal/`
**Files:**
```
layouts/
  app.blade.php         (Base layout with Tailwind)
  navigation.blade.php  (Header with logout)
auth/
  invitation.blade.php  (Landing + Registration)
appointments/
  index.blade.php       (List all appointments)
  show.blade.php        (Single appointment detail)
  reschedule.blade.php  (Reschedule with alternatives)
  cancel.blade.php      (Cancel with reason)
components/
  appointment-card.blade.php
  time-slot-picker.blade.php
  loading-spinner.blade.php
  error-message.blade.php
```

**Tech Stack:**
- **Blade Templates** (Laravel native)
- **Alpine.js** (Lightweight reactivity, ~15KB)
- **Tailwind CSS** (Utility-first, responsive)
- **Axios** (AJAX requests to API)

#### Step 7.2: Authentication Flow

**URL:** `https://portal.askproai.de/einladung/{token}`

**Flow:**
1. User clicks email link → Lands on invitation page
2. Page validates token via API (`GET /api/customer-portal/invitations/{token}/validate`)
3. If valid → Show registration form
4. User fills form (name, email, password, phone, terms)
5. Submit → API (`POST /api/customer-portal/invitations/{token}/accept`)
6. Success → Create Sanctum token → Redirect to `/meine-termine`
7. Store token in localStorage (client-side)

**Security Considerations:**
- HTTPS only (enforce via middleware)
- Password strength indicator (client-side)
- Terms & Conditions checkbox required
- Email verification (auto-verified via invitation)

#### Step 7.3: Appointment List Page

**URL:** `https://portal.askproai.de/meine-termine`

**Features:**
- Tabs: "Anstehend" (upcoming) | "Vergangene" (past) | "Storniert" (cancelled)
- Sort by date (newest first)
- Filter by service type
- Search by date range
- Appointment cards with:
  - Date, Time, Duration
  - Service name + icon
  - Staff name + avatar
  - Location (branch name + address)
  - Actions: "Details", "Umbuchen", "Stornieren"

**API Call:**
```javascript
axios.get('/api/customer-portal/appointments', {
    headers: { 'Authorization': `Bearer ${token}` },
    params: { status: 'upcoming' }
})
```

#### Step 7.4: Reschedule Flow

**URL:** `https://portal.askproai.de/termin/{id}/umbuchen`

**Flow:**
1. User clicks "Umbuchen" → Redirect to reschedule page
2. Load appointment details
3. Load alternative slots (`GET /api/customer-portal/appointments/{id}/alternatives`)
4. Show calendar with:
   - Same day alternatives (highlighted)
   - Next 7 days slots
   - Color coding: Available (green), Almost full (yellow), Full (grey)
5. User selects new time slot → Confirm modal
6. Submit → API (`PUT /api/customer-portal/appointments/{id}/reschedule`)
7. Success → Show confirmation + sync to Cal.com
8. Send confirmation email

**UX Enhancements:**
- Show original appointment details (greyed out)
- Highlight recommended alternatives (same day)
- Show staff availability if multiple staff available
- Loading state during Cal.com sync
- Error handling: "Slot no longer available, please choose another"

#### Step 7.5: Cancel Flow

**URL:** `https://portal.askproai.de/termin/{id}/stornieren`

**Flow:**
1. User clicks "Stornieren" → Redirect to cancel page
2. Show appointment details
3. Show cancellation policy warning (if applicable)
4. Textarea for optional reason
5. Checkbox: "Ich möchte benachrichtigt werden, wenn dieser Termin wieder verfügbar ist"
6. Confirm button → Confirmation modal ("Sind Sie sicher?")
7. Submit → API (`DELETE /api/customer-portal/appointments/{id}`)
8. Success → Show confirmation + sync to Cal.com
9. Send cancellation email

**UX Enhancements:**
- Show cancellation deadline ("Kostenlos stornierbar bis 24h vorher")
- If past deadline → Show cancellation fee warning
- Reason field suggestions (dropdown):
  - "Terminkonflikt"
  - "Krankheit"
  - "Anderer Grund"
- Notify staff via email/SMS

#### Step 7.6: Mobile-Responsive Design

**Breakpoints:**
- Mobile: < 640px (single column, larger touch targets)
- Tablet: 640-1024px (2 columns)
- Desktop: > 1024px (3 columns, sidebar)

**Mobile-Specific:**
- Sticky header with back button
- Bottom sheet for actions (reschedule, cancel)
- Native date picker for iOS/Android
- Swipe gestures for tab switching

---

### Phase 8: Integration & Testing (Estimated: 6-8 hours)

#### Step 8.1: Filament Admin Panel Integration

**File:** `app/Filament/Resources/UserInvitationResource.php`

**Features:**
- List all invitations (pending, accepted, expired)
- Filter by status, date, email
- Search by email
- Bulk actions: "Resend Email", "Cancel Invitation"
- "Send Invitation" button → Modal with form
- View invitation details (token, expiry, accepted_at)
- Email queue status (pending, sent, failed)

**Table Columns:**
- Email
- Role
- Status (Badge: pending/accepted/expired/failed)
- Invited By (relation)
- Created At
- Expires At
- Accepted At (nullable)
- Actions (View, Resend, Cancel)

**Form Fields:**
```php
Forms\Components\TextInput::make('email')
    ->email()
    ->required(),
Forms\Components\Select::make('role_id')
    ->relationship('role', 'name')
    ->required(),
Forms\Components\Select::make('branch_id')
    ->relationship('branch', 'name')
    ->nullable(),
Forms\Components\Textarea::make('message')
    ->placeholder('Optional personal message...')
    ->maxLength(500),
```

**Actions:**
```php
Tables\Actions\Action::make('resend')
    ->icon('heroicon-o-envelope')
    ->action(function (UserInvitation $record) {
        // Re-queue email
        $record->emailQueue()->create([
            'status' => 'pending',
            'attempts' => 0,
        ]);

        Notification::make()
            ->title('Email re-queued')
            ->success()
            ->send();
    }),
```

#### Step 8.2: Email Templates

**File:** `resources/views/emails/customer-portal/invitation.blade.php`

**Content:**
```html
<h1>Willkommen bei {{ $company->name }}</h1>

<p>Hallo,</p>

<p>Sie wurden eingeladen, Ihre Termine selbst zu verwalten.</p>

<p>Klicken Sie auf den folgenden Link, um Ihr Konto zu erstellen:</p>

<a href="{{ $invitationUrl }}" style="...">
    Konto erstellen
</a>

<p>Dieser Link ist gültig bis {{ $expiresAt->format('d.m.Y H:i') }} Uhr.</p>

<p>Was Sie danach tun können:</p>
<ul>
    <li>Termine ansehen</li>
    <li>Termine umbuchen</li>
    <li>Termine stornieren</li>
</ul>

<p>Bei Fragen kontaktieren Sie uns unter {{ $company->email }}</p>
```

**File:** `resources/views/emails/customer-portal/reschedule-confirmation.blade.php`
**File:** `resources/views/emails/customer-portal/cancellation-confirmation.blade.php`

#### Step 8.3: API Testing (PHPUnit)

**File:** `tests/Feature/CustomerPortal/InvitationTest.php`
**Tests:**
- `test_can_validate_valid_token()`
- `test_cannot_validate_expired_token()`
- `test_can_accept_invitation()`
- `test_cannot_accept_with_wrong_email()`
- `test_cannot_reuse_accepted_invitation()`

**File:** `tests/Feature/CustomerPortal/AppointmentTest.php`
**Tests:**
- `test_can_list_own_appointments()`
- `test_cannot_list_other_users_appointments()` (multi-tenant)
- `test_can_reschedule_appointment()`
- `test_cannot_reschedule_past_appointment()`
- `test_can_cancel_appointment()`
- `test_optimistic_locking_prevents_conflicts()`

#### Step 8.4: E2E Testing (Playwright)

**File:** `tests/e2e/customer-portal.spec.js`

**Test Scenarios:**
1. **Happy Path: Accept Invitation**
   - Navigate to invitation URL
   - Fill registration form
   - Submit → Verify redirect to appointments
   - Verify Sanctum token stored

2. **Happy Path: Reschedule Appointment**
   - Login with token
   - Navigate to appointments list
   - Click "Umbuchen" on first appointment
   - Select alternative time slot
   - Confirm → Verify success message
   - Verify appointment updated in database
   - Verify Cal.com synced

3. **Happy Path: Cancel Appointment**
   - Login with token
   - Navigate to appointments list
   - Click "Stornieren" on first appointment
   - Enter reason
   - Confirm → Verify success message
   - Verify appointment cancelled in database
   - Verify Cal.com synced

4. **Edge Case: Expired Invitation**
   - Navigate to expired invitation URL
   - Verify error message
   - Verify "Request new invitation" button

5. **Edge Case: Concurrent Reschedule (Optimistic Lock)**
   - Open appointment in two browser windows
   - Reschedule in window 1 → Success
   - Try reschedule in window 2 → Conflict error
   - Verify clear error message + refresh prompt

#### Step 8.5: Deployment Strategy

**Phase A: Alpha Testing (Internal, 1 week)**
- Deploy to staging environment
- Internal team tests all features
- Fix critical bugs
- Performance optimization

**Phase B: Beta Testing (3-5 Pilot Salons, 2 weeks)**
- Mark companies with `is_pilot = true`
- Feature flag: `config('features.customer_portal_enabled')`
- Monitor logs daily
- Collect feedback via survey
- Iterate based on feedback

**Phase C: Gradual Rollout (4 weeks)**
- Week 1: Enable for 25% of salons (order by sign-up date)
- Week 2: Enable for 50% of salons
- Week 3: Enable for 75% of salons
- Week 4: Enable for 100% (full production)

**Rollback Plan:**
- Feature flag can disable instantly: `customer_portal_enabled = false`
- Database rollback: `php artisan migrate:rollback --step=1`
- Keep old invitation flow as fallback

---

## 🚀 Execution Strategy (Agent Delegation)

### Parallel Execution Phases

**Phase 6A: API Layer (Controller + Routes)** → `backend-architect` agent
**Phase 6B: Request Validation + Resources** → `backend-architect` agent
**Phase 7A: Frontend Structure + Auth Flow** → `frontend-architect` agent
**Phase 7B: Appointment Pages** → `frontend-architect` agent
**Phase 8A: Filament Admin** → `backend-architect` agent
**Phase 8B: Testing** → `quality-engineer` agent

### Agent Assignment

1. **requirements-analyst** (Quick, 30 min)
   - Analyze current codebase
   - Validate all requirements are clear
   - Identify missing pieces
   - Generate detailed specs

2. **backend-architect** (Parallel, 3-4 hours)
   - Phase 6A: Controllers + Routes
   - Phase 6B: Request Validators + Resources
   - Phase 8A: Filament Admin Integration

3. **frontend-architect** (Parallel, 4-5 hours)
   - Phase 7A: Frontend structure + Auth flow
   - Phase 7B: Appointment pages (List, Reschedule, Cancel)
   - Mobile-responsive design

4. **quality-engineer** (After 2+3, 2-3 hours)
   - Phase 8B: API Tests (PHPUnit)
   - E2E Tests (Playwright)
   - Manual testing checklist

5. **devops-architect** (After 4, 1-2 hours)
   - Phase 8C: Deployment scripts
   - Feature flags setup
   - Monitoring & alerting

---

## 📈 Success Metrics

### Technical Metrics
- ✅ 100% API test coverage
- ✅ 0 critical security vulnerabilities
- ✅ < 500ms API response time (p95)
- ✅ < 2s page load time (p95)
- ✅ Mobile-responsive (Lighthouse score > 90)

### Business Metrics
- 📊 Invitation acceptance rate > 70%
- 📊 Self-service reschedule rate > 50%
- 📊 Customer satisfaction score > 4.5/5
- 📊 Support call reduction > 40%

### Compliance Metrics
- 🛡️ GDPR compliance (audit trail, deletion)
- 🛡️ SOC2 compliance (access logs, security)
- 🛡️ Accessibility (WCAG 2.1 AA)

---

## 🎯 Timeline

### Optimistic (Best Case)
- Phase 6: 4 hours (API Layer)
- Phase 7: 8 hours (Frontend)
- Phase 8: 6 hours (Testing)
- **Total: 18 hours (2-3 days)**

### Realistic (Expected)
- Phase 6: 6 hours (includes debugging)
- Phase 7: 10 hours (includes UX polish)
- Phase 8: 8 hours (includes fixes from testing)
- **Total: 24 hours (3-4 days)**

### Pessimistic (Worst Case)
- Phase 6: 8 hours (API issues, Cal.com integration bugs)
- Phase 7: 12 hours (Browser compatibility, mobile bugs)
- Phase 8: 10 hours (E2E flakiness, manual testing)
- **Total: 30 hours (4-5 days)**

---

## 🔐 Security Checklist

- [ ] Token expiry enforced (7 days)
- [ ] Rate limiting on all endpoints (60 req/min)
- [ ] CSRF protection (Sanctum)
- [ ] SQL injection prevention (Eloquent ORM)
- [ ] XSS prevention (Blade escaping)
- [ ] Multi-tenant isolation (CompanyScope)
- [ ] Privilege escalation prevention (Observers)
- [ ] Audit trail for all actions (AppointmentAuditLog)
- [ ] HTTPS only (redirect HTTP)
- [ ] Secure password storage (bcrypt)
- [ ] Token encryption (Sanctum)
- [ ] CORS restricted to portal subdomain

---

## 📝 Documentation Deliverables

1. **API Documentation** (OpenAPI/Swagger)
2. **Frontend Component Library** (Storybook)
3. **Deployment Guide** (Step-by-step)
4. **User Manual** (Customer-facing, German)
5. **Admin Manual** (Salon staff, German)
6. **Troubleshooting Guide** (Common issues)
7. **RCA Template** (For production issues)

---

## ✅ Definition of Done

### Phase 6 (API Layer)
- [ ] All 6 API endpoints implemented
- [ ] Request validation in place
- [ ] API Resources transforming data
- [ ] Authorization middleware working
- [ ] Multi-tenant isolation verified
- [ ] API tests passing (100%)
- [ ] Postman collection created

### Phase 7 (Frontend)
- [ ] All 5 pages implemented
- [ ] Mobile-responsive (tested on iOS/Android)
- [ ] Browser compatibility (Chrome, Firefox, Safari)
- [ ] Loading states for all async operations
- [ ] Error handling for all failure scenarios
- [ ] Accessibility (keyboard nav, screen reader)
- [ ] German translations complete

### Phase 8 (Integration & Testing)
- [ ] Filament Admin Panel integrated
- [ ] Email templates designed
- [ ] E2E tests passing (100%)
- [ ] Manual testing checklist completed
- [ ] Staging environment deployed
- [ ] Performance benchmarks met
- [ ] Security audit passed
- [ ] Documentation complete

---

**Next Step:** Spawn agents for parallel implementation.
**Estimated Total Time:** 24-30 hours (3-4 days with 1 developer, 1-2 days with parallel agents)
