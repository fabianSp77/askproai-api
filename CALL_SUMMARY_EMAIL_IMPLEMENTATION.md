# Call Summary Email Feature Implementation

## Overview
Implemented an automated system to send call summary emails after each completed call, with CSV export functionality and branch/user-specific notification settings.

## Components Implemented

### 1. Database Schema Updates
- **Migration**: `2025_07_06_115925_add_call_notification_preferences_to_companies_table.php`
- Added fields to `companies` table:
  - `send_call_summaries` (boolean)
  - `call_summary_recipients` (JSON array)
  - `include_transcript_in_summary` (boolean)
  - `include_csv_export` (boolean)
  - `summary_email_frequency` (enum: immediate/hourly/daily)
  - `call_notification_settings` (JSON)
- Added `call_notification_overrides` to `branches` table
- Added `call_notification_preferences` to `portal_users` table

### 2. Models Updated
- **Company**: Added new fields and casts for call notification preferences
- **Branch**: Added support for branch-specific overrides
- **PortalUser**: Added user-specific call notification preferences

### 3. Services
- **CallExportService** (`app/Services/CallExportService.php`)
  - Export single or multiple calls to CSV
  - Configurable columns
  - UTF-8 BOM for Excel compatibility
  - Support for filters (date range, status, branch, etc.)

### 4. Email Components
- **CallSummaryEmail** (`app/Mail/CallSummaryEmail.php`)
  - Extends Mailable with queue support
  - Configurable transcript and CSV inclusion
  - Custom messages
  - Action items extraction
  
- **Email Template** (`resources/views/emails/call-summary.blade.php`)
  - Professional responsive design
  - Call details summary
  - Action items highlighting
  - Optional transcript section
  - Direct links to admin portal

### 5. Background Jobs
- **SendCallSummaryJob** (`app/Jobs/SendCallSummaryJob.php`)
  - Processes email sending asynchronously
  - Handles multiple recipients
  - Respects company/branch/user preferences
  - Implements retry logic

### 6. API Endpoints

#### Call Management
- `POST /business/api/calls/{id}/send-summary` - Send summary for specific call
- `POST /business/api/calls/export-batch` - Export multiple calls

#### Settings
- `GET /business/api/settings/call-notifications` - Get notification settings
- `PUT /business/api/settings/call-notifications` - Update company settings
- `PUT /business/api/settings/call-notifications/user` - Update user preferences

### 7. UI Components
- **call-email-actions.blade.php** - Updated with working "Send via System" functionality
  - Email recipient dialog
  - Multiple recipients support
  - Optional custom message
  - Real-time sending feedback

### 8. Integration Points
- **ProcessRetellCallEndedJob**: Automatically dispatches summary emails after call processing
- Respects frequency settings (immediate/hourly/daily)
- Checks company enablement before sending

## Usage

### Enabling Call Summaries

#### Via API:
```json
PUT /business/api/settings/call-notifications
{
  "send_call_summaries": true,
  "call_summary_recipients": ["manager@company.com", "admin@company.com"],
  "include_transcript_in_summary": true,
  "include_csv_export": false,
  "summary_email_frequency": "immediate"
}
```

#### User Preferences:
```json
PUT /business/api/settings/call-notifications/user
{
  "receive_summaries": true
}
```

### Manual Send
1. Navigate to call details in admin portal
2. Click email actions button
3. Select "Über System versenden"
4. Add recipients and optional message
5. Click "Senden"

### CSV Export
```bash
# Single call
GET /business/api/calls/{id}/export-csv

# Batch with filters
POST /business/api/calls/export-batch
{
  "filters": {
    "date_from": "2025-01-01",
    "date_to": "2025-01-31",
    "branch_id": "uuid-here"
  },
  "columns": ["created_at", "phone_number", "customer_name", "summary"]
}
```

## Email Recipients Priority
1. Custom recipients (if manually sending)
2. Branch notification email
3. Company configured recipients
4. Users with receive_summaries preference enabled

## Configuration

### Environment Variables
No new environment variables required. Uses existing mail configuration.

### Queue Configuration
Emails are sent via the `emails` queue. Ensure queue workers are running:
```bash
php artisan queue:work --queue=emails
```

## Security Considerations
- Email addresses are validated
- Company isolation enforced
- Permission checks for manual sending
- Rate limiting should be considered for production

## Future Enhancements
1. Batch email sending for hourly/daily frequencies
2. Email templates customization per company
3. SMS/WhatsApp notification options
4. Webhook notifications
5. Email delivery tracking and analytics

## Testing
```bash
# Test email sending
php artisan tinker
$call = App\Models\Call::latest()->first();
App\Jobs\SendCallSummaryJob::dispatch($call, ['test@example.com']);

# Test CSV export
$service = new App\Services\CallExportService();
$csv = $service->exportSingleCall($call);
file_put_contents('test.csv', $csv);
```

## Troubleshooting

### Emails not sending
1. Check queue workers are running
2. Verify mail configuration in .env
3. Check Laravel logs for errors
4. Ensure company has `send_call_summaries` enabled

### CSV encoding issues
- Files include UTF-8 BOM for Excel compatibility
- Use semicolon (;) as delimiter for German Excel

### Permission errors
- Ensure user has `calls.edit_all` permission for manual sending
- Company settings require `settings.manage` permission