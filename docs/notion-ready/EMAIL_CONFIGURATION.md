# Email Configuration Guide

## Environment Variables

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.udag.de
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@askproai.de
MAIL_FROM_NAME="AskProAI"
```

## Queue Configuration

Emails are sent via queue for better performance:

```php
// config/queue.php
'mail' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => 'mail',
    'retry_after' => 90,
    'block_for' => null,
],
```

## Testing Email

```bash
# Test email configuration
php artisan tinker
>>> Mail::raw('Test email', function($message) {
>>>     $message->to('test@example.com')->subject('Test');
>>> });
```