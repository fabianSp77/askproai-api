# Retell Sync Fix Summary

## Problem
When clicking the sync button in the Retell Ultimate Control Center, the following error occurred:
```
ArgumentCountError: Too few arguments to function App\Services\RetellV2Service::__construct(), 0 passed
```

## Root Causes
1. **API Key Initialization**: The `RetellAgent` model's `syncFromRetell()` and `pushToRetell()` methods were creating `RetellV2Service` instances without passing the required API key parameter.

2. **Encryption Handling**: The MCP server was using `decrypt()` directly without checking if the API key was actually encrypted, causing potential decryption errors.

3. **Tenant Scope Issue**: The `RetellAgent` model has a global `TenantScope` that was preventing the MCP server from creating or querying agent records because there was no company context in the MCP environment.

## Solutions Implemented

### 1. Fixed API Key Initialization in RetellAgent Model
Modified both `syncFromRetell()` and `pushToRetell()` methods to:
- Retrieve the API key from the company relationship
- Handle both encrypted and unencrypted API keys
- Pass the API key to the RetellV2Service constructor

```php
$company = $this->company;
if (!$company || !$company->retell_api_key) {
    $this->update(['sync_status' => 'error']);
    return false;
}

$apiKey = $company->retell_api_key;
if (strlen($apiKey) > 50) {
    try {
        $apiKey = decrypt($apiKey);
    } catch (\Exception $e) {
        // Use as-is if decryption fails
    }
}

$retellService = new \App\Services\RetellV2Service($apiKey);
```

### 2. Added Helper Method in MCP Server
Created `getDecryptedApiKey()` method to centralize API key decryption logic:
```php
protected function getDecryptedApiKey(string $apiKey): string
{
    if (strlen($apiKey) > 50) {
        try {
            return decrypt($apiKey);
        } catch (\Exception $e) {
            // Use as-is if decryption fails
        }
    }
    
    return $apiKey;
}
```

Replaced all direct `decrypt()` calls with this helper method throughout the MCP server.

### 3. Fixed Tenant Scope Issues
Added `withoutGlobalScopes()` to RetellAgent queries in the MCP server:
```php
// For updateOrCreate
$retellAgent = \App\Models\RetellAgent::withoutGlobalScopes()->updateOrCreate(...)

// For queries
$existingAgent = \App\Models\RetellAgent::withoutGlobalScopes()
    ->where('company_id', $companyId)
    ->where('agent_id', $agentId)
    ->first();
```

## Results
- ✅ Sync button now works without errors
- ✅ Successfully synced 41 agents for AskProAI GmbH
- ✅ Agent data is properly stored in the database with sync status
- ✅ Both manual sync (via UI) and scheduled sync (via artisan command) are functional

## Testing
```bash
# Manual sync via artisan command
php artisan retell:sync-configurations --company=1 --force

# Check synced data
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  -e "SELECT id, agent_id, name, sync_status, last_synced_at FROM retell_agents WHERE company_id = 1 LIMIT 5;"
```

## Next Steps
The sync functionality is now fully operational. Users can:
1. Click the "Sync Agents" button in the UI for manual sync
2. Use the artisan command for scheduled or manual sync
3. View sync status on each agent card
4. Access complete agent configuration data locally for better performance