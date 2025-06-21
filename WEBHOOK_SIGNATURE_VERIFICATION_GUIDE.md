# Webhook Signature Verification Guide

## Overview

This guide documents the webhook signature verification implementation for AskProAI, covering Retell.ai, Cal.com, and Stripe webhooks.

## Current Implementation Status

### Retell.ai Webhooks

**Status**: ⚠️ Currently in BYPASS mode (signature verification disabled)

**Middleware Options**:
- `VerifyRetellSignature` - Full signature verification (production)
- `VerifyRetellSignatureDebug` - Verification with detailed logging
- `VerifyRetellSignatureTemporary` - Bypass mode (logs only, no verification)

**Supported Signature Formats**:
1. Separate headers: `X-Retell-Signature` + `X-Retell-Timestamp`
2. Combined format: `X-Retell-Signature: v=timestamp,signature`
3. Plain signature: `X-Retell-Signature: signature` (no timestamp)
4. Base64 encoded: Base64-encoded HMAC signature

**Algorithm**: HMAC-SHA256

### Cal.com Webhooks

**Status**: ✅ Active and working

**Middleware**: `VerifyCalcomSignature`

**Supported Headers**:
- `X-Cal-Signature-256`
- `Cal-Signature-256`
- `X-Cal-Signature`
- `Cal-Signature`

**Algorithm**: HMAC-SHA256 (with or without `sha256=` prefix)

### Stripe Webhooks

**Status**: ✅ Active and working

**Middleware**: `VerifyStripeSignature`

**Uses**: Official Stripe SDK for signature verification

## Configuration

### Environment Variables

```env
# Retell.ai
RETELL_WEBHOOK_SECRET=your_webhook_secret_here
DEFAULT_RETELL_API_KEY=key_xxx  # Used as fallback if webhook secret not set
RETELL_VERIFY_IP=false  # Enable IP whitelist verification

# Cal.com
CALCOM_WEBHOOK_SECRET=your_calcom_webhook_secret

# Stripe
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

### Service Configuration

Located in `config/services.php`:

```php
'retell' => [
    'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
    'api_key' => env('DEFAULT_RETELL_API_KEY'),
    'verify_ip' => env('RETELL_VERIFY_IP', false),
],
```

## Testing Webhook Signatures

### 1. Test Script

Use the provided test script to verify different signature formats:

```bash
php test-retell-webhook-signatures.php
```

This will test all supported signature formats and show which ones work.

### 2. Capture Real Webhooks

To see exactly what Retell sends:

```bash
# 1. Copy the capture script to public directory
cp capture-retell-webhook.php public/

# 2. Update Retell agent webhook URL to:
# https://your-domain.com/capture-retell-webhook.php

# 3. Make a test call

# 4. Check the log
cat capture-retell-webhook.log
```

### 3. Switch Verification Modes

Use the artisan command to switch between modes:

```bash
# Enable strict verification (production)
php artisan webhook:signature-mode retell strict

# Enable debug mode (logs detailed info)
php artisan webhook:signature-mode retell debug

# Bypass verification (development only!)
php artisan webhook:signature-mode retell bypass

# Check current status
php artisan webhook:signature-mode retell strict
```

## Debugging Signature Failures

### Common Issues

1. **Wrong Secret Key**
   - Verify `RETELL_WEBHOOK_SECRET` in `.env`
   - Check if using API key vs dedicated webhook secret
   - Retell dashboard: Settings > Webhooks > Signing Secret

2. **Timestamp Issues**
   - Retell may send milliseconds instead of seconds
   - Our middleware handles: seconds, milliseconds, microseconds
   - Check server time sync (NTP)

3. **Signature Format Mismatch**
   - Enable debug mode to see all attempted formats
   - Check `storage/logs/laravel.log` for details
   - Look for `[Retell Debug]` entries

### Debug Checklist

1. **Enable Debug Mode**:
   ```bash
   php artisan webhook:signature-mode retell debug
   ```

2. **Make Test Call** and check logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "Retell"
   ```

3. **Check Debug Output** for:
   - Received signature format
   - Attempted verification methods
   - Which method succeeded/failed

4. **Common Log Patterns**:
   ```
   [Retell Debug] Webhook verification attempt
   - verified_method: "timestamp_dot_body"  ✅ Success
   - verified_method: null                  ❌ All methods failed
   ```

## Security Best Practices

### 1. **Production Settings**

Always use strict mode in production:
```bash
php artisan webhook:signature-mode retell strict
```

### 2. **Separate Secrets**

Use dedicated webhook secrets, not API keys:
```env
RETELL_WEBHOOK_SECRET=whsec_xxx  # Dedicated secret
# Not: RETELL_WEBHOOK_SECRET=${DEFAULT_RETELL_API_KEY}
```

### 3. **IP Whitelisting**

Enable IP verification for additional security:
```env
RETELL_VERIFY_IP=true
```

Known Retell IPs:
- 100.20.5.228
- 34.226.180.161
- 34.198.47.77
- 52.203.159.213
- 52.53.229.199
- 54.241.134.41
- 54.183.150.123
- 152.53.228.178

### 4. **Monitoring**

Monitor webhook failures:
```bash
# Check failed webhooks
grep "Signature verification failed" storage/logs/laravel.log

# Set up alerts for repeated failures
```

### 5. **Regular Security Audits**

```bash
# Check current webhook configuration
php artisan webhook:signature-mode retell strict

# Verify no bypass modes in production
grep -r "verify.retell.signature.bypass" routes/
```

## Testing

### Unit Tests

Run signature verification tests:
```bash
php artisan test --filter=VerifyRetellSignatureTest
```

### Integration Tests

Test full webhook flow:
```bash
php artisan test --filter=RetellWebhookTest
```

## Troubleshooting

### "Invalid signature" Errors

1. Check webhook secret configuration
2. Enable debug mode to see signature attempts
3. Verify timestamp handling (seconds vs milliseconds)
4. Contact Retell support for their exact format

### "Missing signature" Errors

1. Verify Retell is sending `X-Retell-Signature` header
2. Check nginx/Apache not stripping headers
3. Enable webhook events in Retell dashboard

### Performance Issues

1. Signature verification is CPU-intensive
2. Consider caching verified requests (with care!)
3. Use queue workers for webhook processing

## Migration from Bypass to Strict Mode

When ready to enable signature verification:

1. **Get Webhook Secret** from Retell dashboard
2. **Update .env**:
   ```env
   RETELL_WEBHOOK_SECRET=your_actual_secret
   ```
3. **Test with Debug Mode**:
   ```bash
   php artisan webhook:signature-mode retell debug
   # Make test calls and verify signatures work
   ```
4. **Enable Strict Mode**:
   ```bash
   php artisan webhook:signature-mode retell strict
   ```
5. **Monitor Logs** for any failures

## Contact Support

If signature verification continues to fail:

1. **Retell Support**: Ask for webhook signature format documentation
2. **Internal Team**: Check this guide and debug logs
3. **Emergency**: Use bypass mode temporarily (not recommended!)

Remember: Webhook signature verification is critical for security. Never disable it in production without a valid reason and compensating controls.