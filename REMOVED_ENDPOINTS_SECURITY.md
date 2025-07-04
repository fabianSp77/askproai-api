# Removed Endpoints for Security

The following endpoints have been removed or secured:

## Test Endpoints (Removed)
- `/api/test/webhook` - Use proper webhook testing tools
- `/api/test/mcp-webhook` - Use authenticated MCP routes
- `/api/calcom/book-test` - Use proper booking API
- `/api/metrics-test` - Use authenticated metrics endpoint

## Debug Endpoints (Removed)
- `/api/retell/webhook-debug` - Use proper logging
- `/api/retell/webhook-nosig` - All webhooks require signatures
- `/api/retell/debug-webhook` - Use proper logging

## Secured Endpoints (Now Require Authentication)
- `/api/mcp/*` - Requires Sanctum authentication
- `/api/retell/realtime/*` - Requires Sanctum authentication
- `/api/billing/webhook` - Now requires Stripe signature

## Migration Guide
1. All webhooks now require proper signature verification
2. Use Sanctum tokens for API authentication
3. Test webhooks using proper tools (Postman, curl with signatures)
4. Monitor logs for debugging instead of debug endpoints