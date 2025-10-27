# Email Confirmation - Quick Start Guide

**Status**: ✅ Implemented
**Date**: 2025-10-25

---

## What Was Implemented

✅ Automatic email confirmation after appointment booking via Retell V4 agent
✅ ICS calendar attachment for easy calendar import
✅ Enhanced voice agent response with email confirmation status
✅ Graceful error handling (email failure doesn't break booking)

---

## Quick Test (3 minutes)

### 1. Verify Setup
```bash
./scripts/testing/verify_email_setup.sh
```

Expected: All ✅ green checks (1 warning is OK for testing)

### 2. Test Email Manually
```bash
php scripts/testing/test_email_confirmation.php
```

This will:
- Find the latest appointment
- Send a test confirmation email
- Show verification steps

### 3. Monitor Queue
```bash
# For testing (emails in logs)
MAIL_MAILER=log

# Process one queued email
php artisan queue:work --once

# Check logs
tail -f storage/logs/laravel.log | grep -i email
```

---

## How It Works

```
Voice Call → Book Appointment → Create in DB → Send Email (queued) → Customer Inbox
```

**Integration Point**: `AppointmentCreationService::createLocalRecord()` (line 577)

**Key Components**:
- ✅ `NotificationService` - Handles email sending
- ✅ `AppointmentConfirmation` Mailable - Email template
- ✅ `IcsGeneratorService` - Creates calendar attachment

---

## Production Checklist

Before deploying to production:

### 1. Configure Mail Settings (.env)
```env
# Change from 'log' to real SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME="Your Company"
```

### 2. Configure Queue (.env)
```env
# Change from 'sync' to 'database' or 'redis'
QUEUE_CONNECTION=database
```

### 3. Start Queue Worker
```bash
# For testing
php artisan queue:work

# For production (use supervisor or systemd)
# See: https://laravel.com/docs/11.x/queues#supervisor-configuration
```

### 4. Create Queue Tables (if using database queue)
```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

---

## Monitoring

### Check Email Logs
```bash
# Real-time monitoring
tail -f storage/logs/laravel.log | grep -i "email\|confirmation"

# Search for specific appointment
grep "appointment_id.*123" storage/logs/laravel.log | grep email
```

### Check Queue Status
```bash
# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all
```

### Success Indicators
```
✅ Log: "📧 Sending appointment confirmation email"
✅ Log: "✅ Confirmation email queued successfully"
✅ Customer receives email with ICS attachment
```

### Warning Indicators
```
⚠️  Log: "⚠️ No valid email address for customer"
⚠️  Log: "⚠️ Failed to queue confirmation email"
→ Appointment still created, email just skipped
```

---

## Testing via Retell Agent

### 1. Call the Retell Number
Dial: `[Your Retell phone number]`

### 2. Book Appointment
- "Ich möchte einen Termin buchen"
- Provide name: "Max Mustermann"
- **IMPORTANT**: Provide email: "max@example.com"
- Select service
- Choose date/time
- Confirm booking

### 3. Verify Response
Agent should say:
> "Perfekt! Ihr Termin am [datum] um [uhrzeit] wurde erfolgreich gebucht. Sie erhalten eine Bestätigungs-E-Mail an max@example.com."

### 4. Check Email
- Check inbox: max@example.com
- Subject: "Terminbestätigung - [Company]"
- Attachment: `termin_[id].ics`
- Import to calendar

---

## Troubleshooting

### Email not received?

**Check 1: Customer has email?**
```sql
SELECT id, name, email FROM customers WHERE id = [customer_id];
```

**Check 2: Email was queued?**
```bash
grep "Confirmation email queued" storage/logs/laravel.log | tail -n 5
```

**Check 3: Queue worker running?**
```bash
ps aux | grep "queue:work"
# If not running: php artisan queue:work
```

**Check 4: Failed jobs?**
```bash
php artisan queue:failed
# If found: php artisan queue:retry all
```

**Check 5: SMTP configured?**
```bash
php artisan config:show mail
```

---

## Files Modified

### Code Changes
- ✅ `app/Services/Retell/AppointmentCreationService.php` (lines 577-631)
- ✅ `app/Http/Controllers/RetellFunctionCallHandler.php` (lines 2560-2576)

### Documentation
- ✅ `EMAIL_CONFIRMATION_IMPLEMENTATION.md` - Full documentation
- ✅ `EMAIL_CONFIRMATION_QUICK_START.md` - This guide

### Test Scripts
- ✅ `scripts/testing/test_email_confirmation.php` - Manual test
- ✅ `scripts/testing/verify_email_setup.sh` - Setup verification

---

## Important Notes

### Email Failure Strategy
**Email failure MUST NOT break appointment booking**

✅ Appointment is created FIRST
✅ Email is sent SECOND (queued, async)
✅ If email fails, appointment still exists
✅ Customer can be contacted manually if needed

### No Email Address?
If customer doesn't provide email:
- ✅ Appointment still created
- ⚠️  Warning logged
- 📱 Agent says: "Bitte beachten Sie, dass keine E-Mail-Bestätigung gesendet werden konnte"

### Queue Recommendation
- ❌ `QUEUE_CONNECTION=sync` (testing only)
- ✅ `QUEUE_CONNECTION=database` (production)
- ✅ `QUEUE_CONNECTION=redis` (high-performance production)

---

## Support

### Full Documentation
```bash
cat EMAIL_CONFIRMATION_IMPLEMENTATION.md
```

### Run Tests
```bash
# Verify setup
./scripts/testing/verify_email_setup.sh

# Test email sending
php scripts/testing/test_email_confirmation.php [appointment_id]
```

### Logs Location
```
storage/logs/laravel.log
```

### Related Services
- `NotificationService`: `/app/Services/Communication/NotificationService.php`
- `IcsGeneratorService`: `/app/Services/Communication/IcsGeneratorService.php`
- `AppointmentConfirmation`: `/app/Mail/AppointmentConfirmation.php`

---

## Success Criteria

✅ Customer receives email within 60 seconds of booking
✅ Email contains ICS attachment
✅ ICS can be imported to calendar (Google/Outlook/Apple)
✅ Voice agent confirms email address in response
✅ Email failure doesn't prevent booking
✅ Logs show email status for monitoring

---

**Quick Start**: Run `./scripts/testing/verify_email_setup.sh` then test!
