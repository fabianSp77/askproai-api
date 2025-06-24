# Security Audit Report

Generated on: 2025-06-23 16:14:17

!!! danger "Critical Issues Found"
    This audit found several security concerns that need immediate attention.

## Debug Routes in Production

Found **45 debug routes** that should not be in production:

- `GET|HEAD` admin/calcom-api-test
- `GET|HEAD` admin/calcom-complete-test
- `GET|HEAD` admin/calcom-live-test
- `GET|HEAD` admin/event-type-setup-wizard-debug
- `GET|HEAD` admin/mcp-test
- `GET|HEAD` admin/system-health-monitor-debug
- `GET|HEAD` admin/table-debug
- `GET|HEAD` admin/test-livewire-dropdown
- `GET|HEAD` admin/test-minimal-page
- `GET|POST|HEAD` api/test/webhook
- `POST` api/test/webhook/simulate-retell
- `GET|HEAD` api/retell/collect-appointment/test
- `POST` api/retell/webhook-debug
- `GET|HEAD` api/mcp/webhook/test
- `GET|HEAD` api/mcp/calcom/test/{companyId}
- `GET|HEAD` api/mcp/retell/test/{companyId}
- `GET|HEAD` api/metrics-test
- `POST` api/retell/debug-webhook
- `POST` api/test/webhook
- `POST` api/test/mcp-webhook
- `POST` api/calcom/book-test
- `GET|HEAD` api/test/calcom-v2/event-types
- `GET|HEAD` api/test/calcom-v2/slots
- `POST` api/test/calcom-v2/book
- `GET|HEAD` api/validation/last-test/{entityId}
- `POST` api/validation/run-test/{entityId}
- `GET|HEAD` api/mobile/test
- `GET|HEAD` api/mcp/sentry/issues/{issueId}/latest-event
- `GET|HEAD` test
- `GET|HEAD` auth-debug
- `GET|HEAD` test-dashboard
- `GET|HEAD` csrf-test
- `POST` test-csrf
- `POST` test-post
- `GET|HEAD` test-debug
- `GET|HEAD` livewire-debug
- `GET|HEAD` test-livewire-check
- `GET|HEAD` debug/session
- `POST` debug/login
- `GET|HEAD` debug/check-auth
- `POST` debug/clear-logs
- `GET|HEAD` debug-login
- `POST` debug-login/attempt
- `GET|HEAD` test-error-logging
- `GET|HEAD` test-500-error

## Potential Security Issues

### Configuration Security

- Check that all API keys are loaded from environment variables
- Ensure no credentials are hardcoded in config files
- Verify encryption keys are properly set

