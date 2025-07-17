# Business Portal Data Display Issues - Debug Report

## Date: 2025-07-05

### Summary of Issues

1. **No Customer Relationships**: Calls have `customer_id = NULL`, so the customer relationship doesn't exist
2. **No Summaries**: Both `summary` and `call_summary` fields are NULL in the database
3. **Customer Data Exists in Wrong Fields**: Customer information is stored in `custom_analysis_data` and `customer_data_backup` JSON fields but not displayed
4. **React Components Not Using Available Data**: The UI components are looking for fields that are empty while ignoring fields that contain data

### Database Analysis

#### Calls from 2025-07-04 with Data:
```sql
-- Example call with customer data
Call ID: call_76dd7e5b7c0ea87aa34f2938874
- extracted_name: Hans Schuster
- customer_id: NULL (no customer relationship)
- transcript: YES (1760 chars)
- summary: NULL
- call_summary: NULL
- customer_data_backup: YES (contains full customer info)
- custom_analysis_data: YES (contains extracted data)
```

#### Customer Data Location:
The customer data IS being captured but stored in JSON fields:

**custom_analysis_data contains:**
```json
{
  "caller_full_name": "Hans Schuster",
  "company_name": "Schuster GMBH",
  "customer_request": "Problem mit Tastatur",
  "customer_number": "12345",
  "urgency_level": "dringend",
  "callback_requested": 1
}
```

**customer_data_backup contains:**
```json
{
  "full_name": "Hans Schuster",
  "company": "Schuster GMBH",
  "customer_number": "12345",
  "phone_primary": "+491604366218",
  "request": "Problem mit Tastatur, Rückruf erwünscht, sehr dringend",
  "consent": 1
}
```

### Root Causes

1. **Missing Customer Creation**: The system is not creating customer records from the extracted data
2. **Missing Summary Generation**: No process is generating summaries for calls
3. **UI Not Adapted**: React components are not displaying the JSON data fields

### Verification Queries

```sql
-- Check calls with extracted data but no customer
SELECT 
    call_id,
    extracted_name,
    customer_id,
    custom_analysis_data IS NOT NULL as has_analysis,
    customer_data_backup IS NOT NULL as has_backup
FROM calls 
WHERE extracted_name IS NOT NULL 
AND customer_id IS NULL
LIMIT 10;

-- Check if any customers exist with matching names
SELECT * FROM customers WHERE name LIKE '%Hans Schuster%';

-- Get full data for a specific call
SELECT 
    call_id,
    transcript,
    summary,
    call_summary,
    extracted_name,
    customer_data_backup,
    custom_analysis_data
FROM calls 
WHERE call_id = 'call_76dd7e5b7c0ea87aa34f2938874';
```

### Recommended Solutions

1. **Short-term Fix**: Update React components to display data from `custom_analysis_data` and `customer_data_backup`
2. **Medium-term Fix**: Create a process to generate customer records from extracted data
3. **Long-term Fix**: Implement proper summary generation using the transcript data

### Files to Update

1. `/app/Http/Controllers/Portal/Api/CallApiController.php` - Already returns the fields, but React doesn't use them
2. `/resources/js/Pages/Portal/Calls/Show.jsx` - Needs to display custom_analysis_data
3. `/resources/js/components/CallDetailView.jsx` - Also needs to display the JSON fields

### Next Steps

1. Update React components to display available data from JSON fields
2. Create a job/command to process existing calls and create customer records
3. Implement summary generation from transcripts