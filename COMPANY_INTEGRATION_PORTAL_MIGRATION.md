# CompanyIntegrationPortal Migration Guide

## Features to migrate to QuickSetupWizardV2:

### Unique features that should be preserved:

1. **Integration Testing**
   - `testCalcomIntegration()` - Test Cal.com API connection
   - `testRetellIntegration()` - Test Retell.ai API connection
   - `testAllIntegrations()` - Test all integrations at once

2. **Sync Operations**
   - `syncCalcomEventTypes()` - Import event types from Cal.com
   - `syncRetellAgents()` - Sync Retell agents
   - `importRetellCalls()` - Import call history from Retell

3. **Integration Status Dashboard**
   - Shows connection status for all integrations
   - Shows recent webhook activity
   - Shows knowledge base document count

### Features already in QuickSetupWizardV2 (can be removed):
- Company selection/management
- Basic Cal.com/Retell configuration
- Branch creation and basic setup
- Phone number configuration

### Recommendation:
Instead of keeping CompanyIntegrationPortal, add a new "Integration Status" section to QuickSetupWizardV2 that includes:
- Integration health checks
- Test buttons for each integration
- Sync operations
- Recent activity overview

This would consolidate everything in one place and avoid confusion.