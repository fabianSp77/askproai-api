# Call Notification System Implementation Summary

## Overview
Implemented a comprehensive call notification and communication system that allows companies to automatically receive call summaries via email with customizable settings and CSV export functionality.

## Features Implemented

### 1. Database Structure
- **Migration**: `2025_07_06_120949_add_call_notification_preferences_to_companies_and_branches_table.php`
- Added notification preferences to both `companies` and `branches` tables:
  - `send_call_summaries` - Enable/disable call summaries
  - `call_summary_recipients` - JSON array of email addresses
  - `include_transcript_in_summary` - Include full transcript
  - `include_csv_export` - Attach CSV file
  - `summary_email_frequency` - immediate/hourly/daily
  - `call_notification_settings` - Additional settings (JSON)

### 2. Email System

#### CallSummaryEmail
- **File**: `app/Mail/CallSummaryEmail.php`
- Beautiful HTML email template with:
  - Call information grid (caller, phone, date, duration)
  - Urgency indicators
  - Summary section
  - Action items (appointments needed, callbacks, etc.)
  - Dynamic variables display
  - Optional transcript
  - Customer information
  - Link to admin dashboard

#### CallSummaryBatchEmail
- **File**: `app/Mail/CallSummaryBatchEmail.php`
- Batch summary email for hourly/daily digests
- Statistics overview (total calls, duration, appointments booked)
- Urgent calls section
  - Detailed call table
- CSV attachment support

### 3. Email Templates

#### Individual Call Summary
- **File**: `resources/views/emails/call-summary.blade.php`
- Modern, responsive design
- Color-coded urgency levels
- Action items with icons
- Mobile-optimized

#### Batch Summary
- **File**: `resources/views/emails/call-summary-batch.blade.php`
- Statistics dashboard
- Time period display
- Urgent calls highlight
- All calls table
- CSV attachment notice

### 4. Services

#### CallExportService
- **File**: `app/Services/CallExportService.php`
- Export single or multiple calls to CSV
- UTF-8 BOM for Excel compatibility
- Custom column selection
- German localization
- Filters support (date range, status, etc.)

### 5. Jobs

#### SendCallSummaryJob
- **File**: `app/Jobs/SendCallSummaryJob.php`
- Processes individual call summaries
- Handles recipient resolution (company, branch, user preferences)
- Respects notification settings hierarchy
- Tracks sent emails in metadata

#### Integration with ProcessRetellCallEndedJob
- Added `dispatchCallSummaryIfNeeded()` method
- Automatic dispatch based on company settings
- 30-second delay for data processing completion

### 6. Scheduled Tasks

#### SendBatchCallSummariesCommand
- **File**: `app/Console/Commands/SendBatchCallSummariesCommand.php`
- Processes hourly and daily batch summaries
- Aggregates calls by time period
- Generates statistics
- Sends to all configured recipients

#### Scheduler Configuration
Added to `app/Console/Kernel.php`:
- Hourly summaries: Every hour at :05
- Daily summaries: Daily at 08:00

### 7. API Endpoints

#### Settings API
- **File**: `app/Http/Controllers/Portal/Api/SettingsApiController.php`
- `GET /business/api/settings/notifications/calls` - Get notification settings
- `PUT /business/api/settings/notifications/calls` - Update company settings
- `PUT /business/api/settings/notifications/calls/user` - Update user preferences

#### Call API
- **File**: `app/Http/Controllers/Portal/CallController.php`
- `POST /portal/api/calls/{call}/send-summary` - Send summary for specific call
- `POST /portal/api/calls/export-batch` - Export multiple calls with filters

### 8. Model Updates
- **Company**: Added notification preference fields and casts
- **Branch**: Added override fields for branch-specific settings

## Configuration Options

### Company Level
- Enable/disable call summaries globally
- Set default recipients list
- Configure what to include (transcript, CSV)
- Set frequency (immediate, hourly, daily)

### Branch Level
- Override company settings per branch
- Branch-specific recipient email

### User Level
- Individual opt-in/opt-out for summaries
- Stored in `call_notification_preferences`

## Email Flow

### Immediate Summaries
1. Call ends → Webhook received
2. `ProcessRetellCallEndedJob` processes call data
3. Checks if summaries enabled
4. Dispatches `SendCallSummaryJob` with 30s delay
5. Email sent to all configured recipients

### Batch Summaries
1. Scheduler runs hourly/daily
2. `SendBatchCallSummariesCommand` executes
3. Aggregates calls for time period
4. Generates statistics and CSV
5. Sends batch email to recipients

## CSV Export Features
- Complete call data export
- German localization
- Excel-compatible (UTF-8 BOM)
- Custom column selection
- Formatted costs and durations
- Filtered dynamic variables

## Security & Performance
- Queue-based email sending
- Permission checks for API endpoints
- Duplicate recipient removal
- Error handling with logging
- Retry logic for failed emails

## Usage

### Enable for a Company
```php
$company->update([
    'send_call_summaries' => true,
    'call_summary_recipients' => ['admin@company.com', 'manager@company.com'],
    'include_transcript_in_summary' => false,
    'include_csv_export' => true,
    'summary_email_frequency' => 'immediate'
]);
```

### Manual Summary Send
```bash
# Via API
POST /portal/api/calls/{call-id}/send-summary
{
    "recipients": ["email@example.com"],
    "message": "Hier ist die Anrufzusammenfassung",
    "include_transcript": true,
    "include_csv": true
}
```

### Test Batch Summaries
```bash
# Run hourly summaries manually
php artisan calls:send-batch-summaries --frequency=hourly

# Run daily summaries manually
php artisan calls:send-batch-summaries --frequency=daily
```

## Future Enhancements
1. SMS/WhatsApp notifications
2. Webhook notifications
3. Custom email templates per company
4. Advanced filtering for batch summaries
5. Multi-language support
6. Integration with CRM systems