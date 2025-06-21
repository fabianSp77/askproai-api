# Webhook MCP Migration Plan

## Current State Analysis

### Problem
The user correctly identified that webhooks are NOT consistently using MCP. We have **7 different Retell webhook endpoints** and multiple implementations, which violates the MCP architecture principle.

### Current Webhook Endpoints

#### Retell Webhooks (7 different endpoints!)
1. `/api/retell/webhook` → `RetellWebhookController`
2. `/api/retell/optimized-webhook` → `OptimizedRetellWebhookController`
3. `/api/retell/debug-webhook` → `RetellDebugController`
4. `/api/retell/enhanced-webhook` → `RetellEnhancedWebhookController`
5. `/api/retell/mcp-webhook` → `MCPWebhookController` ✓ (Uses MCP)
6. `/api/webhooks/retell` → `UnifiedWebhookController` ✓ (Uses WebhookProcessor)
7. `/api/mcp/retell/webhook` → `RetellWebhookMCPController` ✓ (Uses MCP)

#### Cal.com Webhooks
1. `/api/calcom/webhook` → `CalcomWebhookController`
2. `/api/webhooks/calcom` → `UnifiedWebhookController` ✓ (Uses WebhookProcessor)

#### Stripe Webhooks
1. `/api/webhooks/stripe` → `UnifiedWebhookController` ✓ (Uses WebhookProcessor)

## Target Architecture

All webhooks should route through ONE of these MCP-based systems:
1. **Primary**: `/api/webhooks/*` → `UnifiedWebhookController` → `WebhookProcessor`
2. **Alternative**: `/api/mcp/*/webhook` → MCP Controllers → MCP Servers

## Migration Steps

### Phase 1: Update Route Definitions (Immediate)
1. Redirect all webhook routes to use UnifiedWebhookController
2. Keep original URLs for backward compatibility
3. Add logging to track which endpoints are still being used

### Phase 2: Update WebhookProcessor (Immediate)
1. Ensure signature verification works for all providers
2. Fix Retell signature verification (currently bypassed)
3. Add proper error handling and logging

### Phase 3: Clean Up (After Testing)
1. Remove redundant controllers
2. Update documentation
3. Notify Retell.ai to use new endpoint

## Implementation Plan

### 1. Update routes/api.php
```php
// Replace all individual webhook routes with redirects to unified endpoint
Route::post('/retell/webhook', function (Request $request) {
    return app(UnifiedWebhookController::class)->handle($request);
})->middleware(['throttle:webhook']);

// Repeat for all other webhook endpoints
```

### 2. Ensure WebhookProcessor Handles All Events
- Already supports Retell, Cal.com, and Stripe
- Uses proper handlers for each provider
- Has deduplication and retry logic

### 3. Fix Signature Verification
- Retell signature verification is currently bypassed
- Need to work with Retell support to fix this

### 4. Update External Services
- Update Retell.ai webhook URL to: `https://api.askproai.de/api/webhooks/retell`
- Update Cal.com webhook URL to: `https://api.askproai.de/api/webhooks/calcom`
- Update Stripe webhook URL to: `https://api.askproai.de/api/webhooks/stripe`

## Benefits
1. **Single point of entry** for all webhooks
2. **Consistent error handling** and logging
3. **Built-in deduplication** using Redis
4. **Automatic retry logic** for failures
5. **Unified monitoring** through webhook_logs table
6. **MCP architecture compliance**

## Testing Plan
1. Test each webhook endpoint with sample payloads
2. Verify signature verification works
3. Check deduplication prevents double processing
4. Confirm all features still work (appointment booking, call logging, etc.)
5. Monitor webhook_logs table for issues