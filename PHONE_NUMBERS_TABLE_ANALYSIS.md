# Phone Numbers Table Structure and Analysis Report

## Summary

The phone number **+493083793369** is properly configured in the database with the following details:

- **Status**: ✅ Active and found in database
- **Company**: AskProAI Test Company (ID: 1)
- **Branch**: Hauptfiliale (ID: 35a66176-5376-11f0-b773-0ad77e7a9793)
- **Retell Agent ID**: agent_9a8202a740cd3120d96fcfda1e
- **Type**: direct (direct line, not a hotline)

## Database Table Structure

The `phone_numbers` table has the following columns:

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | char(36) | PRIMARY KEY | UUID primary key |
| company_id | bigint(20) unsigned | INDEX | Foreign key to companies table |
| branch_id | char(36) | INDEX | Foreign key to branches table |
| number | varchar(255) | NOT NULL, UNIQUE | The phone number in E.164 format |
| retell_phone_id | varchar(255) | INDEX | Retell.ai phone ID |
| retell_agent_id | varchar(255) | INDEX | Retell.ai agent ID |
| retell_agent_version | varchar(255) | | Version of the Retell agent |
| type | enum('direct','hotline') | DEFAULT 'direct' | Type of phone number |
| capabilities | longtext | | JSON array of capabilities |
| metadata | longtext | | JSON metadata |
| routing_config | longtext | | JSON routing configuration |
| agent_id | varchar(255) | | Local agent ID |
| description | varchar(255) | | Description of the phone number |
| is_active | tinyint(1) | DEFAULT 1 | Whether the number is active |
| is_primary | tinyint(1) | DEFAULT 0 | Whether this is the primary number |
| sms_enabled | tinyint(1) | DEFAULT 0 | SMS capability flag |
| whatsapp_enabled | tinyint(1) | DEFAULT 0 | WhatsApp capability flag |
| created_at | timestamp | | Creation timestamp |
| updated_at | timestamp | | Last update timestamp |

## Phone Number Resolution Flow

The `PhoneNumberResolver` service handles phone number resolution with the following priority:

1. **Metadata Resolution** - Check webhook metadata for `askproai_branch_id`
2. **Phone Number Lookup** - Search in `phone_numbers` table by exact match
3. **Agent ID Resolution** - Resolve by Retell agent ID
4. **Caller History** - Check previous interactions from the same caller
5. **Enhanced Fallback** - Multiple fallback strategies including partial matches
6. **Default Fallback** - Use first available company/branch

## Current System State

- **Total Phone Numbers**: 1 (only +493083793369)
- **Active Numbers**: 1
- **Orphaned Numbers**: 0 (all have proper company/branch associations)

## Key Relationships

```
PhoneNumber
├── belongsTo Company (via company_id)
├── belongsTo Branch (via branch_id)
└── belongsTo RetellAgent (via retell_agent_id)
```

## Important Notes

1. The system uses **tenant scoping** via `TenantScope` which requires company context for most operations
2. Phone numbers are unique across the entire system (UNIQUE constraint on `number` column)
3. The `type` field supports 'direct' and 'hotline' - hotlines can have special routing configurations
4. The number format should be E.164 (e.g., +493083793369)
5. The resolver normalizes phone numbers automatically for consistent matching

## Webhook Integration

When a call comes in via Retell webhook:
1. The phone number resolver attempts to identify the company/branch
2. It uses the "to" number (destination) to find the associated branch
3. The resolution confidence is tracked (1.0 = certain, 0.3 = fallback)
4. All resolution attempts are logged for debugging

## Recommendations

1. **Retell Phone ID**: Consider updating the `retell_phone_id` field which is currently NULL
2. **Capabilities**: Define capabilities array for the phone number (SMS, voice, etc.)
3. **Monitoring**: The system logs all resolution attempts - check logs if calls aren't routing correctly
4. **Test Mode**: Special phone numbers like +15551234567 trigger test mode handling