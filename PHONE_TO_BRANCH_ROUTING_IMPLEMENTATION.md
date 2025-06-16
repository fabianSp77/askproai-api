# Phone-to-Branch Routing Implementation

## Overview
This document describes the implementation of phone-to-branch routing for the AskProAI multi-tenant system. Each branch has its own Retell.ai agent with a dedicated phone number, and calls need to be properly routed to the correct branch and company.

## Problem Statement
- Each branch has its own Retell agent ID and phone number
- Incoming calls via Retell webhooks need to be assigned to the correct branch
- Historical calls needed retroactive branch assignment
- The system needed to handle multiple resolution strategies

## Solution Architecture

### 1. PhoneNumberResolver Service
Created `/app/Services/PhoneNumberResolver.php` to handle branch resolution with multiple strategies:

```php
public function resolveFromWebhook(array $webhookData): array
{
    // 1. Try metadata (if agent has branch_id stored)
    // 2. Try to resolve from to_number (Retell sends it as 'to')
    // 3. Try to resolve from agent_id
    // 4. Fallback to company from webhook
}
```

**Key Discovery**: Retell sends phone numbers as 'to' and 'from' fields, not 'to_number' as initially expected.

### 2. ProcessRetellWebhookJob Updates
Updated the webhook processing job to:
- Use PhoneNumberResolver for branch assignment
- Correctly map Retell fields ('to' → 'to_number', 'from' → 'from_number')
- Store branch_id, company_id, and agent_id with each call

### 3. Retroactive Branch Assignment
Created command `/app/Console/Commands/UpdateCallBranchAssignments.php`:
- Finds calls without branch_id but with to_number
- Uses PhoneNumberResolver to assign branches
- Successfully assigned 10 out of 14 historical calls

### 4. Agent Metadata Synchronization
Created command `/app/Console/Commands/SyncRetellAgentMetadata.php`:
- Syncs branch information to Retell agent metadata
- Stores askproai_branch_id, company_id, and other metadata
- Enables future calls to have branch info from the start

## Implementation Details

### Database Schema
- `calls` table has `branch_id`, `company_id`, `agent_id` columns
- `branches` table has `retell_agent_id` and `phone_number` columns
- `phone_numbers` table supports multiple numbers per branch (future use)

### Resolution Priority
1. **Metadata**: If Retell agent has askproai_branch_id in metadata
2. **Phone Number**: Match called number to branch phone
3. **Agent ID**: Match Retell agent ID to branch
4. **Fallback**: Use company ID from webhook if available

### Phone Number Normalization
- Removes non-numeric characters
- Handles German number format (0xx → +49xx)
- Ensures consistent format for matching

## Usage

### Update Historical Calls
```bash
# Dry run to see what would be updated
php artisan calls:update-branch-assignments --dry-run

# Actually update calls
php artisan calls:update-branch-assignments
```

### Sync Agent Metadata
```bash
# Dry run
php artisan retell:sync-metadata --dry-run

# Sync all agents
php artisan retell:sync-metadata

# Sync specific branch
php artisan retell:sync-metadata --branch=7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b
```

### Test Phone Resolution
```bash
php test_phone_resolution.php
```

## Results
- Successfully implemented multi-tenant call routing
- Retroactively assigned 10 historical calls to branches
- Set up metadata sync for future automatic assignment
- Created robust resolution system with multiple fallbacks

## Next Steps
1. Run `php artisan retell:sync-metadata` to sync all agent metadata
2. Monitor new incoming calls to ensure proper branch assignment
3. Consider implementing PhoneNumber resource in Filament for multi-number support
4. Add branch filtering to call logs in admin panel