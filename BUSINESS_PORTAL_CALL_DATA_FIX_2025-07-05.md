# Business Portal Call Data Fix - 2025-07-05

## Problem
The user reported that call ID 257 shows data in the Admin Portal but not in the Business Portal. Upon investigation, we found that:

1. The call has data stored in `custom_analysis_data` field as a JSON object
2. The `summary` and `call_summary` fields are NULL
3. The Business Portal API was only checking `call_summary` and `summary` fields, ignoring `custom_analysis_data`

## Root Cause
The API endpoint `/business/api/calls/{id}` in `CallApiController` was not properly extracting summaries from the `custom_analysis_data` field when the traditional summary fields were empty.

## Solution Implemented

### 1. Updated CallApiController show() method
Added logic to check multiple sources for summary data:
- First check `call_summary`
- Then check `summary`
- Finally, generate a summary from `custom_analysis_data` if available

### 2. Added generateSummaryFromAnalysisData() method
This method creates a human-readable summary from the structured data in `custom_analysis_data`:
```php
private function generateSummaryFromAnalysisData($analysisData)
{
    $parts = [];
    
    if (isset($analysisData['caller_full_name'])) {
        $parts[] = "Anrufer: " . $analysisData['caller_full_name'];
    }
    
    if (isset($analysisData['company_name'])) {
        $parts[] = "Firma: " . $analysisData['company_name'];
    }
    
    if (isset($analysisData['customer_request'])) {
        $parts[] = "Anliegen: " . $analysisData['customer_request'];
    }
    
    // ... additional fields
    
    return implode('. ', $parts);
}
```

### 3. Enhanced extracted field mapping
The API now also checks `custom_analysis_data` for extracted fields:
- `extracted_name` falls back to `custom_analysis_data['caller_full_name']`
- `extracted_email` falls back to `custom_analysis_data['caller_email']`
- `extracted_phone` falls back to `custom_analysis_data['caller_phone']`

### 4. Updated index() method
Applied the same logic to the calls list endpoint to ensure summaries are displayed in the calls table.

## Data Structure Example (Call ID 257)
```json
{
    "custom_analysis_data": {
        "call_successful": true,
        "caller_full_name": "Hans Schuster",
        "caller_phone": "{{caller_phone_number}}",
        "urgency_level": "Routine",
        "additional_notes": null,
        "customer_request": "Rückruf wegen Tastatur",
        "gdpr_consent_given": true,
        "callback_requested": true,
        "preferred_callback_time": null,
        "customer_number": "123456",
        "caller_email": null,
        "company_name": "Schuster GmbH"
    }
}
```

## Files Modified
- `/app/Http/Controllers/Portal/Api/CallApiController.php`
  - Updated `show()` method to extract summaries from multiple sources
  - Updated `index()` method for consistent summary handling
  - Added `generateSummaryFromAnalysisData()` helper method

## Testing
After these changes, call ID 257 should now display:
- Summary: "Anrufer: Hans Schuster. Firma: Schuster GmbH. Anliegen: Rückruf wegen Tastatur. Rückruf angefordert"
- Extracted Name: Hans Schuster
- Customer Request: Rückruf wegen Tastatur
- Company: Schuster GmbH

## Future Considerations
1. Consider migrating data from `custom_analysis_data` to dedicated fields during a data migration
2. Standardize where call analysis data is stored across the system
3. Update the Retell webhook processor to populate both `call_summary` and `custom_analysis_data` for consistency