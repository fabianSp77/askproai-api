# Customer Portal

## Overview

The Customer Portal provides self-service capabilities for customers to manage their appointments, update personal information, and access booking history.

## Current Status

⚠️ **Note**: The customer portal is currently in development. Phone-based booking via AI is the primary customer interface.

## Planned Features

### Self-Service Appointment Management
- View upcoming appointments
- Cancel or reschedule bookings
- Access appointment history
- Download appointment confirmations

### Personal Information
- Update contact details
- Manage communication preferences
- View and update medical history (for healthcare providers)

### Communication Hub
- Access email confirmations
- SMS notification history
- Chat with support (planned)

## Technical Architecture

### Authentication
```php
// Customer authentication via phone number
Route::post('/customer/auth', function (Request $request) {
    $customer = Customer::where('phone', $request->phone)
        ->where('company_id', $company->id)
        ->first();
    
    // Send OTP via SMS
    $otp = rand(100000, 999999);
    Cache::put("otp:{$customer->phone}", $otp, 300);
    
    // Send SMS
    app(SMSService::class)->send($customer->phone, "Your code: {$otp}");
});
```

### Portal Routes
```php
// Customer portal routes
Route::prefix('portal')->middleware(['customer.auth'])->group(function () {
    Route::get('/appointments', [CustomerPortalController::class, 'appointments']);
    Route::post('/appointments/{id}/cancel', [CustomerPortalController::class, 'cancel']);
    Route::get('/profile', [CustomerPortalController::class, 'profile']);
    Route::put('/profile', [CustomerPortalController::class, 'updateProfile']);
});
```

## Mobile App Integration

The customer portal will be accessible via:
- Responsive web interface
- Native iOS app (planned)
- Native Android app (planned)
- Progressive Web App (PWA)

## Security Considerations

### Data Protection
- Phone number verification required
- OTP-based authentication
- Session management with timeout
- GDPR-compliant data handling

### Multi-Tenancy
- Strict company isolation
- Branch-level access control
- Customer data segmentation

## Implementation Roadmap

### Phase 1: Basic Portal (Q2 2025)
- Authentication system
- Appointment viewing
- Basic profile management

### Phase 2: Advanced Features (Q3 2025)
- Appointment rescheduling
- Communication preferences
- Notification center

### Phase 3: Mobile Apps (Q4 2025)
- iOS app release
- Android app release
- Push notifications

## API Endpoints

```yaml
# Customer Portal API
GET    /api/customer/appointments      # List appointments
GET    /api/customer/appointments/{id} # Get appointment details
DELETE /api/customer/appointments/{id} # Cancel appointment
GET    /api/customer/profile          # Get profile
PUT    /api/customer/profile          # Update profile
```

## Related Documentation
- [API Reference](../api/reference.md)
- [Authentication](../api/authentication.md)
- [GDPR Compliance](gdpr.md)