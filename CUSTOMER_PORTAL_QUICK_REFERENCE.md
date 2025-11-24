# Customer Portal API - Quick Reference

**Last Updated:** 2025-11-24

---

## API Endpoints

### Authentication

```http
GET  /api/customer-portal/invitations/{token}/validate
POST /api/customer-portal/invitations/{token}/accept
```

### Appointments (Authenticated)

```http
GET    /api/customer-portal/appointments
GET    /api/customer-portal/appointments/{id}
GET    /api/customer-portal/appointments/{id}/alternatives
PUT    /api/customer-portal/appointments/{id}/reschedule
DELETE /api/customer-portal/appointments/{id}
```

---

## Quick Test (cURL)

### 1. Validate Token
```bash
curl https://api.askproai.de/api/customer-portal/invitations/{TOKEN}/validate
```

### 2. Accept Invitation
```bash
curl -X POST https://api.askproai.de/api/customer-portal/invitations/{TOKEN}/accept \
  -H "Content-Type: application/json" \
  -d '{"name":"Max Mustermann","email":"max@example.com","password":"SecurePass123","password_confirmation":"SecurePass123","terms_accepted":true}'
```

### 3. List Appointments
```bash
curl https://api.askproai.de/api/customer-portal/appointments \
  -H "Authorization: Bearer {ACCESS_TOKEN}"
```

### 4. Reschedule
```bash
curl -X PUT https://api.askproai.de/api/customer-portal/appointments/762/reschedule \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"new_start_time":"2025-11-26T10:00:00+01:00","reason":"Terminkonflikt"}'
```

### 5. Cancel
```bash
curl -X DELETE https://api.askproai.de/api/customer-portal/appointments/762 \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"reason":"Krankheit - kann leider nicht kommen"}'
```

---

## Files Created

### Controllers
- `app/Http/Controllers/CustomerPortal/AuthController.php`
- `app/Http/Controllers/CustomerPortal/AppointmentController.php`

### Request Validators
- `app/Http/Requests/CustomerPortal/AcceptInvitationRequest.php`
- `app/Http/Requests/CustomerPortal/RescheduleAppointmentRequest.php` (Phase 4)
- `app/Http/Requests/CustomerPortal/CancelAppointmentRequest.php` (Phase 4)

### API Resources
- `app/Http/Resources/CustomerPortal/AppointmentResource.php`
- `app/Http/Resources/CustomerPortal/UserResource.php`
- `app/Http/Resources/CustomerPortal/InvitationResource.php`

### Policies
- `app/Policies/CustomerPortal/AppointmentPolicy.php`

### Tests
- `tests/Feature/CustomerPortal/InvitationTest.php` (9 tests)
- `tests/Feature/CustomerPortal/AppointmentTest.php` (10 tests)

### Routes
- `routes/api.php` (Customer Portal group added)

---

## Run Tests

```bash
# All Customer Portal tests
php artisan test --filter=CustomerPortal

# Invitation tests only
php artisan test tests/Feature/CustomerPortal/InvitationTest.php

# Appointment tests only
php artisan test tests/Feature/CustomerPortal/AppointmentTest.php
```

---

## Documentation

📄 **Full API Documentation:** `/var/www/api-gateway/CUSTOMER_PORTAL_API_DOCUMENTATION.md`
📄 **Implementation Summary:** `/var/www/api-gateway/CUSTOMER_PORTAL_PHASE6_SUMMARY.md`
📄 **Quick Reference:** This file

---

## Status

✅ **Phase 6 Complete** - API Layer Implemented
⏳ **Phase 7 Next** - Frontend Implementation
⏳ **Phase 8 Next** - Integration & Testing

---

## Key Features

✅ 7 RESTful API endpoints
✅ Token-based authentication (Sanctum)
✅ Policy-based authorization
✅ Multi-tenant isolation
✅ Optimistic locking
✅ Comprehensive error handling
✅ Rate limiting
✅ Audit logging
✅ 19 feature tests
✅ Complete documentation
