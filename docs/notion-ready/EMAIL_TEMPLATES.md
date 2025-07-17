# Email Templates

## Available Templates

### 1. Call Summary Email
- Location: `resources/views/emails/call-summary.blade.php`
- Used for: Sending call summaries after AI phone calls
- Variables: `$call`, `$company`, `$customer`

### 2. Appointment Confirmation
- Location: `resources/views/emails/appointment-confirmation.blade.php`
- Used for: Confirming new appointments
- Variables: `$appointment`, `$customer`, `$service`

### 3. Appointment Reminder
- Location: `resources/views/emails/appointment-reminder.blade.php`
- Used for: Reminding customers of upcoming appointments
- Variables: `$appointment`, `$customer`

## Template Customization

```php
// Example template structure
@component('mail::message')
# Hello {{ $customer->name }}

Your appointment details:
- Date: {{ $appointment->formatted_date }}
- Time: {{ $appointment->formatted_time }}
- Service: {{ $appointment->service->name }}

@component('mail::button', ['url' => $url])
View Appointment
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```