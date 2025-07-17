# Email Troubleshooting Guide

## Common Issues

### 1. Emails Not Sending

**Check Queue Worker**
```bash
# Check if Horizon is running
php artisan horizon:status

# Start Horizon
php artisan horizon

# Check failed jobs
php artisan queue:failed
```

**Check Mail Configuration**
```bash
# Test mail config
php artisan tinker
>>> config('mail')
```

### 2. Email Delays

**Monitor Queue**
```bash
# Check queue size
php artisan queue:monitor mail

# Process mail queue specifically
php artisan queue:work --queue=mail
```

### 3. Template Errors

**Debug Templates**
```php
// Add to controller
return view('emails.template', $data);
```

**Clear View Cache**
```bash
php artisan view:clear
```