# Manual Testing Guide - AskProAI & Friseur 1
## Date: 2025-10-27

---

## 📋 OVERVIEW

This guide provides step-by-step instructions for manually testing both base companies (AskProAI and Friseur 1) to verify end-to-end functionality in the testing environment.

---

## 🎯 TESTING OBJECTIVES

1. **Voice AI Integration**: Verify Retell agents respond correctly
2. **Booking Flow**: Test appointment creation via voice
3. **Cal.com Sync**: Verify bookings appear in Cal.com
4. **Admin Panel**: Verify data visibility and editability
5. **Multi-Tenancy**: Verify data isolation between companies

---

## 🧪 TEST SUITE 1: ASKPROAI

### Company Information
```
Name:             AskProAI
Phone:            +493083793369
Retell Agent:     agent_616d645570ae613e421edb98e7
Cal.com Team:     39203
Admin Panel:      https://api.askproai.de/admin/companies/4
```

### Test 1.1: Voice AI Response Test
**Objective:** Verify Retell agent answers and identifies correctly

**Steps:**
1. Call: +493083793369
2. Wait for greeting

**Expected Result:**
- ✅ Agent answers within 2-3 seconds
- ✅ Greeting: "Guten Tag bei AskProAI, wie kann ich Ihnen helfen?"
- ✅ Agent responds naturally to questions
- ✅ Agent offers available consultation services

**Verification:**
```bash
# Check call was logged
php artisan tinker --execute="
DB::table('retell_call_sessions')
    ->where('phone_number', '+493083793369')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['call_id', 'call_status', 'started_at']);
"
```

**Pass Criteria:**
- [ ] Call connected successfully
- [ ] Correct greeting received
- [ ] Agent identified as AskProAI
- [ ] Natural conversation flow

---

### Test 1.2: Service Discovery Test
**Objective:** Verify agent knows available services

**Steps:**
1. Call: +493083793369
2. Ask: "Welche Dienstleistungen bieten Sie an?"

**Expected Result:**
- ✅ Agent lists 3 consultation services:
  - 15 Minuten Schnellberatung
  - 30 Minuten Beratungsgespräch
  - 60 Minuten Intensivberatung
- ✅ Agent explains each service duration
- ✅ Agent mentions all are free (€0.00)

**Pass Criteria:**
- [ ] All 3 services mentioned
- [ ] Correct durations stated
- [ ] Free pricing communicated

---

### Test 1.3: Booking Flow Test
**Objective:** Verify end-to-end appointment booking

**Steps:**
1. Call: +493083793369
2. Request: "Ich möchte einen Termin für eine 30 Minuten Beratung buchen"
3. Provide: Name, email, preferred date/time
4. Confirm booking

**Expected Result:**
- ✅ Agent collects name
- ✅ Agent collects email
- ✅ Agent collects preferred date/time
- ✅ Agent checks availability via Cal.com
- ✅ Agent confirms booking
- ✅ Confirmation details provided

**Verification:**
```bash
# Check appointment created in database
php artisan tinker --execute="
DB::table('appointments')
    ->where('company_id', 4)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get(['id', 'service_id', 'customer_id', 'start_time', 'status']);
"
```

**Pass Criteria:**
- [ ] Appointment created in database
- [ ] Correct service linked
- [ ] Customer record created
- [ ] Booking appears in Cal.com Team 39203

---

### Test 1.4: Admin Panel Verification
**Objective:** Verify company data visible and editable

**Steps:**
1. Login: https://api.askproai.de/admin
2. Navigate: Companies → AskProAI (ID: 4)
3. Check: Company details
4. Check: Branch (Hauptfiliale)
5. Check: Services (3 services)

**Expected Result:**
- ✅ Company listed in admin panel
- ✅ All fields visible and correct:
  - Name: AskProAI
  - Slug: askproai
  - Cal.com Team: 39203
  - Retell Agent: agent_616d645570ae613e421edb98e7
- ✅ 1 branch visible (Hauptfiliale)
- ✅ 3 services visible with correct details

**Pass Criteria:**
- [ ] Company accessible in admin
- [ ] All data fields correct
- [ ] Branch data visible
- [ ] Services editable

---

## 🧪 TEST SUITE 2: FRISEUR 1

### Company Information
```
Name:             Friseur 1
Phone:            +493033081738
Retell Agent:     agent_45daa54928c5768b52ba3db736
Cal.com Team:     34209
Admin Panel:      https://api.askproai.de/admin/companies/5
```

### Test 2.1: Voice AI Response Test
**Objective:** Verify Retell agent answers with salon personality

**Steps:**
1. Call: +493033081738
2. Wait for greeting

**Expected Result:**
- ✅ Agent answers within 2-3 seconds
- ✅ Greeting: "Guten Tag bei Friseur 1, mein Name ist Carola, wie kann ich Ihnen helfen?"
- ✅ Agent has friendly, professional salon personality
- ✅ Agent knows about hair services

**Verification:**
```bash
# Check call was logged
php artisan tinker --execute="
DB::table('retell_call_sessions')
    ->where('phone_number', '+493033081738')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['call_id', 'call_status', 'started_at']);
"
```

**Pass Criteria:**
- [ ] Call connected successfully
- [ ] Correct greeting with "Carola" name
- [ ] Salon personality evident
- [ ] Natural conversation flow

---

### Test 2.2: Service Discovery Test
**Objective:** Verify agent knows all 16 hair services

**Steps:**
1. Call: +493033081738
2. Ask: "Welche Dienstleistungen bieten Sie an?"
3. Ask: "Was kostet Waschen, schneiden, föhnen?"
4. Ask: "Wie lange dauert eine Balayage?"

**Expected Result:**
- ✅ Agent mentions various service categories:
  - Haarschnitte (Kinder, Trocken, etc.)
  - Waschen & Styling
  - Färben & Strähnentechniken
  - Pflegebehandlungen
- ✅ "Waschen, schneiden, föhnen" = 60 min, €45.00
- ✅ "Strähnentechnik Balayage" = 180 min, €255.00

**Pass Criteria:**
- [ ] Multiple service categories mentioned
- [ ] Correct pricing provided
- [ ] Correct durations stated
- [ ] Services match database (Event IDs 3719738-3719753)

---

### Test 2.3: Staff Availability Test
**Objective:** Verify agent knows staff members and their locations

**Steps:**
1. Call: +493033081738
2. Ask: "Wer arbeitet bei Ihnen?"
3. Ask: "Kann ich bei Emma Williams einen Termin buchen?"
4. Ask: "Arbeitet Emma in der Zentrale oder Zweigstelle?"

**Expected Result:**
- ✅ Agent mentions staff members:
  - Emma Williams
  - Fabian Spitzer
  - David Martinez
  - Michael Chen
  - Dr. Sarah Johnson
- ✅ Confirms Emma Williams available for booking
- ✅ Confirms Emma works at Zentrale (main branch)

**Verification:**
```bash
# Check staff assignments
php artisan tinker --execute="
DB::table('staff')
    ->where('company_id', 5)
    ->get(['name', 'email', 'branch_id']);
"
```

**Pass Criteria:**
- [ ] Agent aware of all 5 staff members
- [ ] Correct branch assignments
- [ ] Staff bookings possible

---

### Test 2.4: Multi-Branch Booking Test
**Objective:** Verify agent handles 2 branches correctly

**Steps:**
1. Call: +493033081738
2. Request: "Ich möchte einen Termin bei David Martinez"
3. Observe: Agent behavior regarding branch

**Expected Result:**
- ✅ Agent knows David Martinez works at Zweigstelle
- ✅ Agent offers Zweigstelle location/address
- ✅ Agent processes booking for Zweigstelle branch
- ✅ Correct branch_id in database

**Verification:**
```bash
# Check David Martinez branch assignment
php artisan tinker --execute="
\$david = DB::table('staff')->where('name', 'David Martinez')->first();
\$branch = DB::table('branches')->where('id', \$david->branch_id)->first();
echo 'David works at: ' . \$branch->name . ' (UUID: ' . \$branch->id . ')' . PHP_EOL;
"
```

**Pass Criteria:**
- [ ] Agent identifies correct branch
- [ ] Booking linked to Zweigstelle
- [ ] Branch isolation working

---

### Test 2.5: Complete Booking Flow Test
**Objective:** Verify end-to-end salon appointment booking

**Steps:**
1. Call: +493033081738
2. Request: "Ich möchte Waschen, schneiden, föhnen bei Emma Williams buchen"
3. Provide: Name, email, preferred date/time
4. Confirm booking

**Expected Result:**
- ✅ Agent confirms service: "Waschen, schneiden, föhnen" (60 min, €45.00)
- ✅ Agent confirms stylist: Emma Williams
- ✅ Agent collects customer details
- ✅ Agent checks Emma's availability via Cal.com
- ✅ Agent finds available slot
- ✅ Agent books appointment
- ✅ Agent confirms: date, time, stylist, service, branch

**Verification:**
```bash
# Check appointment created
php artisan tinker --execute="
\$appointment = DB::table('appointments')
    ->where('company_id', 5)
    ->orderBy('created_at', 'desc')
    ->first();

\$service = DB::table('services')->where('id', \$appointment->service_id)->first();
\$staff = DB::table('staff')->where('id', \$appointment->staff_id)->first();
\$customer = DB::table('customers')->where('id', \$appointment->customer_id)->first();

echo 'Appointment:' . PHP_EOL;
echo '  Service: ' . \$service->name . PHP_EOL;
echo '  Staff: ' . \$staff->name . PHP_EOL;
echo '  Customer: ' . \$customer->name . PHP_EOL;
echo '  Start: ' . \$appointment->start_time . PHP_EOL;
echo '  Cal.com Booking ID: ' . (\$appointment->calcom_booking_id ?? 'NULL') . PHP_EOL;
"
```

**Pass Criteria:**
- [ ] Appointment created in database
- [ ] Correct service linked (Waschen, schneiden, föhnen)
- [ ] Correct staff linked (Emma Williams)
- [ ] Customer record created
- [ ] Start/end times correct (60 min duration)
- [ ] Booking appears in Cal.com Team 34209
- [ ] Cal.com booking ID stored

---

### Test 2.6: Admin Panel Verification
**Objective:** Verify full company structure visible

**Steps:**
1. Login: https://api.askproai.de/admin
2. Navigate: Companies → Friseur 1 (ID: 5)
3. Check: Company details
4. Check: Branches (Zentrale + Zweigstelle)
5. Check: Staff (5 members)
6. Check: Services (16 services)

**Expected Result:**
- ✅ Company listed in admin panel
- ✅ All fields visible and correct:
  - Name: Friseur 1
  - Slug: friseur-1
  - Cal.com Team: 34209
  - Retell Agent: agent_45daa54928c5768b52ba3db736
- ✅ 2 branches visible (Zentrale, Zweigstelle)
- ✅ 5 staff members visible:
  - 3 at Zentrale
  - 2 at Zweigstelle
- ✅ 16 services visible with Event Type IDs

**Verification:**
```bash
# Check branch-service links
php artisan tinker --execute="
\$zentrale = DB::table('branches')->where('name', 'Friseur 1 Zentrale')->first();
\$zweigstelle = DB::table('branches')->where('name', 'Friseur 1 Zweigstelle')->first();

\$zentraleServices = DB::table('branch_service')->where('branch_id', \$zentrale->id)->count();
\$zweigstelleServices = DB::table('branch_service')->where('branch_id', \$zweigstelle->id)->count();

echo 'Zentrale Services: ' . \$zentraleServices . '/16' . PHP_EOL;
echo 'Zweigstelle Services: ' . \$zweigstelleServices . '/16' . PHP_EOL;
"
```

**Pass Criteria:**
- [ ] Company accessible in admin
- [ ] All data fields correct
- [ ] Both branches visible and editable
- [ ] All 5 staff members listed
- [ ] All 16 services visible
- [ ] Branch-service links correct (16 each)
- [ ] Staff-service links correct (80 total)

---

## 🧪 TEST SUITE 3: MULTI-TENANCY

### Test 3.1: Data Isolation Test
**Objective:** Verify no cross-company data leakage

**Steps:**
1. Query: AskProAI services
2. Query: Friseur 1 services
3. Verify: No overlap

**Verification:**
```bash
php artisan tinker --execute="
\$askproaiServices = DB::table('services')->where('company_id', 4)->pluck('name');
\$friseur1Services = DB::table('services')->where('company_id', 5)->pluck('name');

echo 'AskProAI Services (' . \$askproaiServices->count() . '):' . PHP_EOL;
foreach (\$askproaiServices as \$svc) {
    echo '  - ' . \$svc . PHP_EOL;
}

echo PHP_EOL . 'Friseur 1 Services (' . \$friseur1Services->count() . '):' . PHP_EOL;
foreach (\$friseur1Services as \$svc) {
    echo '  - ' . \$svc . PHP_EOL;
}

echo PHP_EOL . 'Overlap: ' . \$askproaiServices->intersect(\$friseur1Services)->count() . ' services' . PHP_EOL;
"
```

**Expected Result:**
- ✅ AskProAI: 3 consultation services
- ✅ Friseur 1: 16 hair services
- ✅ Zero overlap
- ✅ No cross-company references

**Pass Criteria:**
- [ ] Services correctly isolated
- [ ] No shared service IDs
- [ ] Company IDs correct on all records

---

### Test 3.2: Admin Panel Access Control Test
**Objective:** Verify company managers see only their data

**Steps:**
1. Create test users for each company
2. Login as AskProAI manager
3. Verify: Only sees AskProAI data
4. Login as Friseur 1 manager
5. Verify: Only sees Friseur 1 data

**Expected Result:**
- ✅ AskProAI manager sees:
  - AskProAI company only
  - 1 branch
  - 3 services
  - No staff (consulting company)
- ✅ Friseur 1 manager sees:
  - Friseur 1 company only
  - 2 branches
  - 16 services
  - 5 staff members

**Pass Criteria:**
- [ ] Data isolation enforced
- [ ] No cross-company visibility
- [ ] Policies working correctly

---

## 📊 TEST SUMMARY TEMPLATE

Use this template to record test results:

```
DATE: 2025-10-27
TESTER: [Your Name]

╔══════════════════════════════════════════════════════════════╗
║                     TEST RESULTS SUMMARY                     ║
╚══════════════════════════════════════════════════════════════╝

ASKPROAI TESTS:
  [✓/✗] Test 1.1: Voice AI Response
  [✓/✗] Test 1.2: Service Discovery
  [✓/✗] Test 1.3: Booking Flow
  [✓/✗] Test 1.4: Admin Panel

FRISEUR 1 TESTS:
  [✓/✗] Test 2.1: Voice AI Response
  [✓/✗] Test 2.2: Service Discovery
  [✓/✗] Test 2.3: Staff Availability
  [✓/✗] Test 2.4: Multi-Branch Booking
  [✓/✗] Test 2.5: Complete Booking Flow
  [✓/✗] Test 2.6: Admin Panel

MULTI-TENANCY TESTS:
  [✓/✗] Test 3.1: Data Isolation
  [✓/✗] Test 3.2: Admin Access Control

TOTAL: ___/12 PASSED

ISSUES FOUND:
  1. [If any]
  2. [If any]

NOTES:
  - [Any observations]
  - [Any suggestions]
```

---

## 🔍 DEBUGGING TIPS

### If Call Doesn't Connect
```bash
# Check Retell agent status
curl -X GET "https://api.retellai.com/v2/agent/agent_616d645570ae613e421edb98e7" \
  -H "Authorization: Bearer YOUR_API_KEY"

# Check phone number mapping
php artisan tinker --execute="
DB::table('phone_numbers')
    ->whereIn('phone_number', ['+493083793369', '+493033081738'])
    ->get();
"
```

### If Booking Fails
```bash
# Check Cal.com API connectivity
php artisan tinker --execute="
\$response = Http::get('https://api.cal.com/v1/event-types?apiKey=YOUR_KEY&teamId=34209');
echo \$response->status();
"

# Check service Event Type IDs
php artisan tinker --execute="
DB::table('services')
    ->where('company_id', 5)
    ->whereNull('calcom_event_type_id')
    ->get(['id', 'name']);
"
```

### If Admin Panel Shows Wrong Data
```bash
# Verify company_id on all tables
php artisan tinker --execute="
echo 'Services company_id:' . PHP_EOL;
DB::table('services')->select('company_id', DB::raw('COUNT(*) as count'))
    ->groupBy('company_id')
    ->get()
    ->each(function(\$row) {
        echo '  Company ' . \$row->company_id . ': ' . \$row->count . ' services' . PHP_EOL;
    });
"
```

---

## ✅ COMPLETION CRITERIA

All tests considered complete when:
- [ ] All 12 tests passed
- [ ] Zero critical issues found
- [ ] Both companies fully functional
- [ ] Cal.com sync working
- [ ] Admin panel accessible
- [ ] Multi-tenancy verified

---

**Guide Version**: 1.0
**Last Updated**: 2025-10-27
**Status**: Ready for execution
