# Missing Conversation Flows - Gap Analysis

**Date:** 2025-10-22
**Current Flow Chart:** conversation-flow-chart.html (5 intents, 23 nodes)
**Analysis:** Codebase vs Flow Chart comparison

---

## Executive Summary

Your codebase has **12 implemented function calls** but only **5 intents** in the flow chart.

**Missing from Flow Chart:**
- ✅ Rückruf-Bitten (Callbacks) - FULLY IMPLEMENTED
- ✅ Termin verschieben (Reschedule) - FULLY IMPLEMENTED
- ✅ Termin stornieren (Cancel) - FULLY IMPLEMENTED
- ✅ Nächster verfügbarer Termin (Find Next) - FULLY IMPLEMENTED
- ✅ Alternative Zeiten (Get Alternatives) - FULLY IMPLEMENTED
- ✅ Services auflisten (List Services) - FULLY IMPLEMENTED
- ✅ Anonyme Anrufer (Query by Name) - FULLY IMPLEMENTED
- ⚠️ Warteliste (Waitlist) - PARTIALLY IMPLEMENTED
- ⚠️ Wiederkehrende Termine (Recurring) - PARTIALLY IMPLEMENTED
- ❓ Voicemail/Nachricht - UNCLEAR

---

## 🔴 CRITICAL: Available Function Calls vs Flow Chart

### Currently in Flow Chart (5 intents):
1. ✅ Termin buchen → `book_appointment`
2. ✅ Termin abfragen → `query_appointment`
3. ⚠️ Termin verschieben → NOT IN FLOW (but `reschedule_appointment` exists!)
4. ⚠️ Termin stornieren → NOT IN FLOW (but `cancel_appointment` exists!)
5. ✅ Informationen → General info

### Available in Code but NOT in Flow Chart:

#### 1️⃣ **request_callback** 🔴 CRITICAL MISSING
**Location:** `RetellFunctionCallHandler.php:193`
**Implementation:** `handleCallbackRequest()` at line 3459

**Full Feature Set:**
```php
CallbackRequest Model:
- Priority: normal | high | urgent
- Status: pending → assigned → contacted → completed | expired | cancelled
- Auto-assignment to staff (round-robin or load-based)
- Escalation workflows
- Expiration tracking
- Preferred time windows
- Metadata tracking
```

**Workflow:**
1. Customer: "Ich möchte einen Rückruf bitte"
2. Agent sammelt: Name, Telefonnummer, bevorzugte Zeit, Thema
3. API Call: `request_callback`
4. System:
   - Creates CallbackRequest
   - Auto-assigns to staff
   - Sends notification to staff
   - Customer receives confirmation
5. Staff ruft zurück innerhalb der Zeit

**Auto-Assignment Service:**
- Round-robin: Fair distribution
- Load-based: Assigns to staff with fewest active callbacks
- Considers: branch, service, staff availability

**Why Missing This is Critical:**
User specifically mentioned: "Es gibt zum Beispiel noch Rückruf bitten"

---

#### 2️⃣ **reschedule_appointment** 🟡 IMPORTANT
**Location:** `RetellFunctionCallHandler.php:192`
**Implementation:** `handleRescheduleAttempt()` at line 2873

**Security:**
- Anonymous callers → create CallbackRequest instead
- Authenticated customers → direct reschedule

**Workflow:**
1. Customer: "Ich möchte meinen Termin verschieben"
2. Agent: query_appointment to find existing
3. Agent: get_alternatives or find_next_available
4. Customer wählt neue Zeit
5. API Call: reschedule_appointment
6. System: Updates appointment, syncs Cal.com, sends notification

---

#### 3️⃣ **cancel_appointment** 🟡 IMPORTANT
**Location:** `RetellFunctionCallHandler.php:191`
**Implementation:** `handleCancellationAttempt()` at line 2702

**Security:**
- Anonymous callers → create CallbackRequest instead
- Authenticated customers → direct cancellation

**Workflow:**
1. Customer: "Ich möchte meinen Termin stornieren"
2. Agent: query_appointment to verify
3. Agent asks for confirmation
4. API Call: cancel_appointment
5. System: Cancels appointment, syncs Cal.com, notifies

**Automatic Waitlist Trigger:**
When appointment cancelled → system automatically offers slot to waitlist

---

#### 4️⃣ **find_next_available** 🟢 USEFUL
**Location:** `RetellFunctionCallHandler.php:194`
**Implementation:** `handleFindNextAvailable()` at line 3638

**Use Case:**
Customer: "Wann ist der nächste freie Termin?"

**Workflow:**
1. Agent sammelt: service, preferred staff (optional)
2. API Call: find_next_available
3. System: Searches next 14 days for first available slot
4. Agent: "Der nächste freie Termin ist Donnerstag um 14 Uhr"

---

#### 5️⃣ **get_alternatives** 🟢 USEFUL
**Location:** `RetellFunctionCallHandler.php:189`
**Implementation:** `getAlternatives()`

**Use Case:**
Desired time not available, customer wants options

**Workflow:**
1. Customer: "Donnerstag 13 Uhr" → NOT available
2. API Call: get_alternatives with preferred date/time
3. System: Uses AppointmentAlternativeFinder service
4. Returns: 5 similar time slots (±2 days, ±2 hours)
5. Agent: "Donnerstag 14 Uhr oder Freitag 13 Uhr wären frei"

---

#### 6️⃣ **list_services** 🟢 USEFUL
**Location:** `RetellFunctionCallHandler.php:190`
**Implementation:** `listServices()`

**Use Case:**
Customer doesn't know what services are available

**Workflow:**
1. Customer: "Was für Services bieten Sie an?"
2. API Call: list_services
3. System: Returns all active services for company/branch
4. Agent: Lists services with descriptions

---

#### 7️⃣ **query_appointment_by_name** 🟢 USEFUL
**Location:** `RetellFunctionCallHandler.php:188`

**Use Case:**
Anonymous caller or hidden number

**Workflow:**
1. Call comes in with 'anonymous' number
2. Agent: "Darf ich Ihren Namen haben?"
3. Customer: "Hans Schubert"
4. API Call: query_appointment_by_name
5. System: Searches by customer name
6. Agent: Can now reschedule/cancel/query

**Security:**
Additional verification questions for sensitive operations

---

#### 8️⃣ **check_customer** 🟢 RECOGNITION
**Location:** `RetellFunctionCallHandler.php:181`
**Implementation:** `checkCustomer()` at line 204

**Use Case:**
First action in every call - recognize returning customer

**Workflow:**
1. Call starts
2. Automatic: check_customer (uses phone number)
3. If customer found: "Guten Tag Herr Schubert!"
4. If new: "Guten Tag bei Ask Pro AI"

---

#### 9️⃣ **parse_date** 🔧 UTILITY
**Location:** `RetellFunctionCallHandler.php:183`
**Implementation:** `handleParseDate()` at line 3976

**Purpose:**
Prevent agent from calculating dates incorrectly

**Use Case:**
Customer says: "nächsten Donnerstag"
System calculates: exact Y-m-d date

---

## 🟡 Partially Implemented Features

### Waitlist Management
**Status:** Referenced in `AutomatedProcessService.php:266` but models incomplete

**Concept:**
```php
function processWaitlist(): int
{
    // When appointment cancelled
    // → Offer slot to waitlist customers
    // → Sorted by created_at
    // → Automatic notification
}
```

**Missing:**
- WaitlistEntry model
- Database migration
- Customer-facing API
- Flow integration

**Recommendation:** Finish implementation, then add to flow

---

### Recurring Appointments
**Status:** Referenced in `AutomatedProcessService.php:78` but models incomplete

**Concept:**
```php
RecurringAppointment:
- Pattern: daily | weekly | monthly | custom
- Interval: number
- End date
- Auto-create instances
```

**Use Case:**
"Ich brauche jeden Donnerstag um 14 Uhr einen Termin"

**Missing:**
- RecurringAppointment model
- Database migration
- Customer-facing API
- Flow integration

**Recommendation:** Finish implementation, then add to flow

---

### Business Hours Handling
**Status:** ✅ Implemented (WorkingHour model, Branch model)

**Missing from Flow:**
Agent should inform when calling outside business hours

**Example:**
Call at 22:00 Uhr:
- "Es ist derzeit außerhalb unserer Geschäftszeiten"
- "Möchten Sie einen Rückruf während unserer Öffnungszeiten buchen?"
- → Transitions to request_callback

---

### Voicemail / Message
**Status:** ❓ Unclear

**Found:** 46 files mention "voicemail|nachricht|message"
**Unclear:** Is there a leave_voicemail function or is it handled via request_callback?

**Recommendation:** Clarify requirement
- Option A: request_callback handles this use case
- Option B: Implement dedicated voicemail function

---

## 📊 Complete Function Call Inventory

| Function Name | Status | In Flow? | Priority |
|--------------|--------|----------|----------|
| `check_customer` | ✅ Implemented | ❌ No | 🟢 Auto-executed |
| `parse_date` | ✅ Implemented | ❌ No | 🟢 Utility |
| `check_availability` | ✅ Implemented | ✅ Yes | ✅ Core |
| `book_appointment` | ✅ Implemented | ✅ Yes | ✅ Core |
| `query_appointment` | ✅ Implemented | ✅ Yes | ✅ Core |
| `query_appointment_by_name` | ✅ Implemented | ❌ No | 🟡 Important |
| `get_alternatives` | ✅ Implemented | ❌ No | 🟡 Important |
| `list_services` | ✅ Implemented | ❌ No | 🟢 Useful |
| `cancel_appointment` | ✅ Implemented | ❌ No | 🔴 Critical |
| `reschedule_appointment` | ✅ Implemented | ❌ No | 🔴 Critical |
| `request_callback` | ✅ Implemented | ❌ No | 🔴 Critical |
| `find_next_available` | ✅ Implemented | ❌ No | 🟢 Useful |

**Total:** 12 functions
**In Flow Chart:** 3 functions (25%)
**Missing:** 9 functions (75%)

---

## 🎯 Recommended Flow Chart Updates

### Priority 1: Add Critical Intents

#### Intent: Rückruf-Bitten (Callback Request)
```
User: "Ich möchte einen Rückruf" | "Rufen Sie mich zurück"

Flow:
1. node_callback_intent [Intent erkannt]
   ↓
2. node_callback_collect [Sammle: Name, Telefon, Zeit, Thema]
   ↓
3. func_request_callback [API Call]
   ↓ success
4. node_callback_confirm [Bestätigung]
   ↓
5. end_callback_success

Variations:
- Priority: urgent → escalation
- Keine Zeit genannt → "Wann passt es Ihnen?"
- Invalid phone → Verification
```

#### Intent: Termin verschieben (Reschedule)
```
User: "Ich möchte verschieben" | "Neuer Termin"

Flow:
1. node_reschedule_intent [Intent erkannt]
   ↓
2. func_query_appointment [Find existing]
   ↓ found
3. node_reschedule_verify [Bestätigung: Welcher Termin?]
   ↓
4. node_reschedule_new_time [Neue Zeit sammeln]
   ↓
5. func_check_availability [Check new time]
   ↓ available
6. func_reschedule_appointment [API Call]
   ↓ success
7. node_reschedule_success [Bestätigung]

Variations:
- No appointment found → query_appointment_by_name
- New time not available → get_alternatives
- Anonymous caller → request_callback instead
```

#### Intent: Termin stornieren (Cancel)
```
User: "Ich möchte stornieren" | "Termin absagen"

Flow:
1. node_cancel_intent [Intent erkannt]
   ↓
2. func_query_appointment [Find existing]
   ↓ found
3. node_cancel_verify [Doppelte Bestätigung!]
   ↓
4. func_cancel_appointment [API Call]
   ↓ success
5. node_cancel_success [Bestätigung + Waitlist triggered]

Security:
- Anonymous → request_callback instead
- Requires explicit confirmation
```

---

### Priority 2: Add Helpful Intents

#### Intent: Nächster freier Termin
```
User: "Wann ist der nächste freie Termin?"

Flow:
1. node_next_available_intent
   ↓
2. node_collect_service [Optional: Which service?]
   ↓
3. func_find_next_available [API Call]
   ↓ found
4. node_offer_next [Agent bietet Termin an]
   ↓
5. Decision: Book? → book_appointment flow
   OR: Different time? → check_availability flow
```

#### Intent: Alternative Zeiten
```
Context: check_availability returned unavailable

Auto-trigger:
1. func_get_alternatives [API Call with preferred time]
   ↓
2. node_offer_alternatives [Agent listet Optionen]
   ↓
3. Decision: Customer chooses → book_appointment
```

#### Intent: Services auflisten
```
User: "Was bieten Sie an?" | "Welche Services?"

Flow:
1. node_services_intent
   ↓
2. func_list_services [API Call]
   ↓
3. node_present_services [Agent listet]
   ↓
4. Decision: Book service? → book_appointment flow
```

---

### Priority 3: Special Cases

#### Intent: Geschäftszeiten
```
User: "Wann haben Sie geöffnet?"

Flow:
1. node_business_hours_intent
   ↓
2. node_present_hours [Agent nennt Öffnungszeiten from Branch]
   ↓
3. Decision: Book? → book_appointment flow
```

#### Intent: Anonyme Anrufer
```
Trigger: phone_number === 'anonymous'

Flow:
1. node_anonymous_greeting [Special greeting]
   ↓
2. node_ask_name [Agent fragt nach Name]
   ↓
3. func_query_appointment_by_name [API Call]
   ↓ found
4. node_verify_customer [Sicherheitsfragen]
   ↓
5. Continue with main intent
```

---

## 📋 Implementation Checklist

### Phase 1: Critical Missing Flows (Week 1)
- [ ] Add request_callback intent to flow chart
- [ ] Add reschedule_appointment intent to flow chart
- [ ] Add cancel_appointment intent to flow chart
- [ ] Update askproai_conversation_flow_correct.json
- [ ] Deploy updated flow to Retell.ai
- [ ] Test all 3 new intents

### Phase 2: Helper Functions (Week 2)
- [ ] Add find_next_available intent
- [ ] Add get_alternatives (auto-trigger on unavailable)
- [ ] Add list_services intent
- [ ] Update flow JSON
- [ ] Deploy and test

### Phase 3: Special Cases (Week 3)
- [ ] Add business_hours handling
- [ ] Add query_appointment_by_name for anonymous
- [ ] Add voicemail/message intent (if needed)
- [ ] Update flow JSON
- [ ] Deploy and test

### Phase 4: Advanced Features (Future)
- [ ] Finish WaitlistEntry implementation
- [ ] Add waitlist to flow
- [ ] Finish RecurringAppointment implementation
- [ ] Add recurring to flow
- [ ] Emergency/urgent appointment handling

---

## 🔍 Code References

### Function Handler
`/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
- Lines 179-196: Function routing
- Line 181: check_customer
- Line 183: parse_date
- Line 184: check_availability
- Line 185: book_appointment
- Line 186: query_appointment
- Line 188: query_appointment_by_name
- Line 189: get_alternatives
- Line 190: list_services
- Line 191: cancel_appointment
- Line 192: reschedule_appointment
- Line 193: request_callback
- Line 194: find_next_available

### Callback System
- Model: `/var/www/api-gateway/app/Models/CallbackRequest.php`
- Service: `/var/www/api-gateway/app/Services/Callbacks/CallbackAssignmentService.php`
- Event: `/var/www/api-gateway/app/Events/Appointments/CallbackRequested.php`

### Automation
- Service: `/var/www/api-gateway/app/Services/AutomatedProcessService.php`
- Line 266: processWaitlist()
- Line 78: processRecurringAppointments()

---

## 💡 Recommendations

### 1. Update Flow Chart HTML
Current: 5 intents, 23 nodes
Recommended: 12 intents, ~60 nodes

**New Intents:**
1. ✅ Termin buchen (existing)
2. ✅ Termin abfragen (existing)
3. 🆕 Termin verschieben (reschedule)
4. 🆕 Termin stornieren (cancel)
5. 🆕 Rückruf bitten (callback)
6. 🆕 Nächster Termin (find next)
7. 🆕 Services auflisten (list services)
8. 🆕 Geschäftszeiten (business hours)
9. ✅ Unklar (existing)
10. 🆕 Anonymer Anrufer (query by name)
11. ⏳ Warteliste (waitlist - when implemented)
12. ⏳ Wiederkehrend (recurring - when implemented)

### 2. Update Retell Flow JSON
Add nodes for:
- request_callback flow (7 nodes)
- reschedule_appointment flow (8 nodes)
- cancel_appointment flow (6 nodes)
- find_next_available flow (5 nodes)
- get_alternatives (auto-trigger, 3 nodes)
- list_services flow (4 nodes)

**Estimated:** +33 nodes = ~40 nodes total (up from 7)

### 3. Global Prompt Updates
Add intent recognition for:
```markdown
## Intent Recognition Rules

**Rückruf:**
- "rückruf", "zurückrufen", "callback"
→ request_callback flow

**Verschieben:**
- "verschieben", "neuer termin", "ander zeit"
→ reschedule_appointment flow

**Stornieren:**
- "stornieren", "absagen", "canceln"
→ cancel_appointment flow

**Nächster freier:**
- "nächste", "wann frei", "schnellster termin"
→ find_next_available flow
```

---

## 🎯 Success Metrics

After implementing all flows:

**Coverage:**
- Function calls in flow: 12/12 (100%)
- Customer intents covered: 12
- Edge cases handled: Anonymous, urgent, outside hours

**User Experience:**
- Customer can reschedule ✅
- Customer can cancel ✅
- Customer can request callback ✅
- Agent offers alternatives when unavailable ✅
- Returning customers recognized ✅

**Next Level:**
- Waitlist when fully booked
- Recurring appointments for regular customers
- Voicemail/message handling
- Emergency appointment handling

---

**Analysis Complete:** 2025-10-22
**Next Step:** Update flow chart and Retell conversation flow JSON
**Priority:** Phase 1 (request_callback, reschedule, cancel)
