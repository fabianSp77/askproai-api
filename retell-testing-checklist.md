# Retell.ai Testing Checklist for AskProAI

## Pre-Test Setup ✓

- [ ] Agent imported successfully
- [ ] Agent ID noted: ___________________
- [ ] Webhook configured in Retell dashboard
- [ ] Webhook secret added to .env
- [ ] Phone number assigned to agent
- [ ] Test phone ready for calling

## Basic Functionality Tests ✓

### 1. Initial Connection
- [ ] Call connects successfully
- [ ] Greeting plays in German
- [ ] Company name mentioned correctly
- [ ] No long pauses or delays

### 2. Language Recognition
- [ ] German speech recognized correctly
- [ ] Numbers understood (phone, dates)
- [ ] Umlauts handled (ä, ö, ü, ß)
- [ ] Common names recognized

### 3. Conversation Flow
- [ ] Natural conversation flow
- [ ] Appropriate response timing
- [ ] Backchannel responses work ("ja", "verstehe")
- [ ] Can be interrupted politely

## Appointment Booking Tests ✓

### 4. Service Selection
- [ ] Lists available services
- [ ] Understands service requests
- [ ] Handles unknown services gracefully
- [ ] Confirms service selection

### 5. Date and Time Handling
- [ ] Understands various date formats:
  - [ ] "nächsten Montag"
  - [ ] "15. Januar"
  - [ ] "morgen um 14 Uhr"
  - [ ] "übermorgen nachmittags"
- [ ] Suggests available times
- [ ] Handles unavailable times
- [ ] Confirms date/time clearly

### 6. Customer Information
- [ ] Captures first name
- [ ] Captures last name
- [ ] Confirms phone number
- [ ] Requests email (optional)
- [ ] Handles spelling requests

### 7. Booking Confirmation
- [ ] All details repeated back
- [ ] Booking created in system
- [ ] Confirmation number provided
- [ ] Email confirmation mentioned

## Advanced Function Tests ✓

### 8. Check Availability Function
```
Test: "Haben Sie morgen um 10 Uhr Zeit?"
```
- [ ] Function called correctly
- [ ] Response time < 3 seconds
- [ ] Available slots returned
- [ ] Handles no availability

### 9. Book Appointment Function
```
Test: Complete booking flow
```
- [ ] All required fields captured
- [ ] Booking saved to database
- [ ] Cal.com event created
- [ ] Confirmation returned

### 10. Business Info Function
```
Test: "Wie sind Ihre Öffnungszeiten?"
```
- [ ] Returns correct hours
- [ ] Provides location info
- [ ] Lists services accurately
- [ ] Contact info correct

## Error Handling Tests ✓

### 11. Missing Information
- [ ] Prompts for missing data
- [ ] Doesn't proceed without required fields
- [ ] Clear error messages
- [ ] Offers help

### 12. Invalid Inputs
- [ ] Handles invalid dates
- [ ] Rejects past dates appropriately
- [ ] Validates phone numbers
- [ ] Manages nonsense input

### 13. System Errors
- [ ] Graceful handling of API failures
- [ ] Fallback messages work
- [ ] Offers alternative (call back)
- [ ] Doesn't expose technical errors

## Edge Case Tests ✓

### 14. Long Conversations
- [ ] Handles 10+ minute calls
- [ ] Maintains context throughout
- [ ] No memory issues
- [ ] Proper call ending

### 15. Multiple Appointments
- [ ] Can book multiple appointments
- [ ] Keeps information separate
- [ ] No data mixing
- [ ] Clear confirmations

### 16. Cancellations/Changes
- [ ] Understands cancellation requests
- [ ] Can reschedule if enabled
- [ ] Provides clear next steps
- [ ] Records reason

## Voice Quality Tests ✓

### 17. Audio Quality
- [ ] Clear voice output
- [ ] Appropriate volume
- [ ] No distortion
- [ ] Natural pacing

### 18. Background Noise
- [ ] Handles moderate noise
- [ ] Asks for repetition politely
- [ ] Maintains conversation flow
- [ ] No feedback loops

## Integration Tests ✓

### 19. Webhook Processing
```bash
tail -f storage/logs/laravel.log | grep retell
```
- [ ] Webhooks received
- [ ] Signatures validated
- [ ] Events processed
- [ ] No duplicate processing

### 20. Database Integration
```sql
SELECT * FROM calls ORDER BY created_at DESC LIMIT 5;
SELECT * FROM appointments WHERE source = 'phone' ORDER BY created_at DESC;
```
- [ ] Calls recorded
- [ ] Transcripts saved
- [ ] Appointments created
- [ ] Customer records updated

### 21. Cal.com Sync
- [ ] Events created in Cal.com
- [ ] Correct event type used
- [ ] Staff member assigned
- [ ] Time slots blocked

## Performance Tests ✓

### 22. Response Times
- [ ] Initial greeting < 2 seconds
- [ ] Function responses < 3 seconds
- [ ] No long pauses
- [ ] Smooth conversation

### 23. Concurrent Calls
- [ ] System handles multiple calls
- [ ] No data mixing
- [ ] Performance maintained
- [ ] Queue processing works

## Compliance Tests ✓

### 24. GDPR Compliance
- [ ] No unnecessary data collection
- [ ] Sensitive data handling
- [ ] Consent mentioned if needed
- [ ] Data retention policies work

### 25. Security
- [ ] No sensitive data in logs
- [ ] Webhook signatures verified
- [ ] Rate limiting active
- [ ] Error messages sanitized

## Post-Test Validation ✓

### 26. Admin Dashboard
- [ ] Calls appear in dashboard
- [ ] Transcripts accessible
- [ ] Analytics updated
- [ ] No errors shown

### 27. Customer Experience
- [ ] Confirmation email sent
- [ ] Appointment in calendar
- [ ] Details correct
- [ ] Professional presentation

### 28. Monitoring
- [ ] Metrics collected
- [ ] Alerts configured
- [ ] Logs accessible
- [ ] Performance tracked

## Test Result Summary

**Date Tested**: ___________________

**Tester Name**: ___________________

**Overall Result**: 
- [ ] **PASS** - Ready for production
- [ ] **CONDITIONAL PASS** - Minor issues to fix
- [ ] **FAIL** - Major issues found

### Issues Found:
1. _________________________________
2. _________________________________
3. _________________________________

### Notes:
_____________________________________
_____________________________________
_____________________________________

## Sign-off

**Technical Lead**: ___________________ Date: _______

**Business Owner**: ___________________ Date: _______

**Quality Assurance**: _________________ Date: _______