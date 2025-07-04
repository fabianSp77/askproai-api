# Retell.ai Webhook Signature Verification Issue - Technical Report

## Executive Summary
We are experiencing webhook signature verification failures despite correct API configuration and successful API calls. All webhooks from Retell.ai are being rejected with HTTP 401 "Invalid webhook signature".

## Environment Details
- **Webhook Endpoint**: `https://api.askproai.de/api/retell/webhook`
- **Framework**: Laravel 11.45.1 (PHP 8.3)
- **API Key**: `key_e973c8962e09d6a34b3b1cf386...` (truncated)
- **Agent ID**: `agent_9a8202a740cd3120d96fcfda1e`
- **Verified IPs**: Webhooks are coming from known Retell IPs (100.20.5.228, 152.53.228.178)

## The Problem

### What's Working ✅
1. API calls to Retell.ai work perfectly with the same API key
2. Webhooks ARE being sent by Retell.ai and received by our server
3. Headers `X-Retell-Signature` and `X-Retell-Timestamp` are present
4. Call data is complete and well-formed

### What's NOT Working ❌
1. Webhook signature verification fails every time
2. All webhook requests return HTTP 401 "Invalid webhook signature"
3. No webhooks are processed due to signature mismatch

## Technical Details

### Our Verification Implementation
We've tried multiple signature verification methods based on common webhook patterns:

```php
// Method 1: timestamp.body (most common pattern)
$payload = "{$timestamp}.{$body}";
$expected = hash_hmac('sha256', $payload, $apiKey);

// Method 2: body only
$expected = hash_hmac('sha256', $body, $apiKey);

// Method 3: base64 encoded
$expected = base64_encode(hash_hmac('sha256', $payload, $apiKey, true));
```

### Example Request (from our logs)
```
Headers Received:
- X-Retell-Signature: [64-character hex string]
- X-Retell-Timestamp: 1751453976

Body Sample:
{"event":"call_ended","call":{"call_id":"call_a35bde73a77ba58f6a3ea97f75a"...}}

Our Calculations:
- Method 1 produces: 8e0ae658497c670b2f264ef9c47258936fc0c8469a0b849cb944f44baa3d185e
- Method 2 produces: f7c4ef274b29e085a676482eab908b825c71a492fe1d4418101a331b8b1d3354
- Method 3 produces: 4bfwdzQ8EoYXzTTFJNhQvN3B9hdBfSgsFIIz820mWVY=
- None match the received signature
```

### Configuration Verification
```bash
# All 8 phone numbers configured correctly:
+493083793369 → agent_9a8202a740cd3120d96fcfda1e → https://api.askproai.de/api/retell/webhook
+493041735870 → agent_9a8202a740cd3120d96fcfda1e → https://api.askproai.de/api/retell/webhook
... (all 8 numbers have identical correct configuration)
```

## Questions for Retell Support

1. **What is the exact signature algorithm?**
   - Is it HMAC-SHA256?
   - What's the exact format of the payload to sign?

2. **Is the API key used directly as the secret?**
   - Or is there a separate webhook secret?
   - Does the API key need special formatting?

3. **What's the correct payload format?**
   - Is it `timestamp.body`?
   - Is it `body.timestamp`?
   - Or something else?

4. **Is there a difference between v1 and v2 API webhooks?**
   - We're using v2 endpoints for API calls
   - Are webhook signatures different?

5. **Node.js SDK Reference**
   - Your docs mention `Retell.verify()` function
   - Can you provide the exact algorithm this function uses?

## Current Workaround
We've implemented a manual import script that runs every 15 minutes via cron to fetch calls directly from the API. This works but adds up to 15 minutes delay for call processing.

## Request
Please provide either:
1. The exact signature verification algorithm/code
2. A PHP example for webhook verification
3. Or confirm if there's an issue with our API key's webhook capabilities

## Test Call for Reference
- **Call ID**: `call_a35bde73a77ba58f6a3ea97f75a`
- **Date**: 2025-07-02 12:55:58 UTC
- **Status**: Successfully completed, transcript available
- **Issue**: No webhook received (or rejected due to signature)

Thank you for your assistance in resolving this issue.