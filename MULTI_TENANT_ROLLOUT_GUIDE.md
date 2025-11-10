# Multi-Tenant Rollout Guide - Optimaler Telefon-Flow V110

**Ziel:** Neue Unternehmen/Filialen mit dem optimalen V110 Flow in <1 Stunde onboarden

**Zielgruppe:** DevOps, Customer Success, Implementation Engineers

---

## Quick Start (< 10 min Setup)

```bash
# 1. Neues Unternehmen anlegen
php artisan company:create \
  --name="Salon Beispiel GmbH" \
  --email="info@salon-beispiel.de" \
  --phone="+49301234567"

# Output: Company ID: 123

# 2. Branch anlegen
php artisan branch:create \
  --company-id=123 \
  --name="Hauptfiliale" \
  --address="Musterstraße 1, 10115 Berlin" \
  --phone="+49301234568"

# Output: Branch ID: 456

# 3. Retell Agent klonen von Template
php artisan retell:clone-agent \
  --from="optimal_v110_template" \
  --company-id=123 \
  --branch-id=456

# Output: Agent ID: agent_xyz789

# 4. Telefonnummer zuweisen
php artisan retell:assign-phone \
  --agent-id="agent_xyz789" \
  --phone="+49301234568"

# Output: ✅ Phone assigned successfully
```

**Fertig! System ist live.**

---

## Detailed Setup Guide

### Step 1: Company Onboarding (15 min)

#### 1.1 Company Creation

**Via Filament Admin:**
1. Navigate to Companies → Create
2. Fill in:
   - Name: "Salon Beispiel GmbH"
   - Email: info@salon-beispiel.de
   - Phone: +49301234567
   - Cal.com Team ID: (from Cal.com setup)
   - Cal.com API Key: (from Cal.com)

**Via Artisan:**
```bash
php artisan company:create \
  --name="Salon Beispiel GmbH" \
  --email="info@salon-beispiel.de" \
  --phone="+49301234567" \
  --calcom-team-id="team_abc123" \
  --calcom-api-key="cal_live_xyz..."
```

#### 1.2 Company Settings Configuration

**Callback Notifications:**
```bash
php artisan company:config \
  --company-id=123 \
  --callback-channels="email,sms,portal"
```

**Smart Service Prediction:**
```bash
php artisan company:config \
  --company-id=123 \
  --enable-smart-service=true \
  --service-confidence-threshold=0.8
```

**Staff Preferences:**
```bash
php artisan company:config \
  --company-id=123 \
  --enable-staff-preferences=true \
  --staff-confidence-threshold=0.8
```

**Custom Greeting Templates:**
```bash
php artisan company:greeting \
  --company-id=123 \
  --type="personalized_with_service" \
  --template="Guten Tag! Schön dass Sie wieder da sind. Möchten Sie wieder einen {{service}} buchen?"
```

---

### Step 2: Branch Setup (10 min)

#### 2.1 Branch Creation

**Required Fields:**
- Name
- Address
- Phone Number (will be agent's number)
- Business Hours

**Via Filament:**
Companies → Select Company → Branches → Create

**Via Artisan:**
```bash
php artisan branch:create \
  --company-id=123 \
  --name="Hauptfiliale" \
  --address="Musterstraße 1, 10115 Berlin" \
  --phone="+49301234568" \
  --email="hauptfiliale@salon-beispiel.de" \
  --business-hours='{
    "monday": {"open": "09:00", "close": "18:00"},
    "tuesday": {"open": "09:00", "close": "18:00"},
    "wednesday": {"open": "09:00", "close": "18:00"},
    "thursday": {"open": "09:00", "close": "18:00"},
    "friday": {"open": "09:00", "close": "20:00"},
    "saturday": {"open": "09:00", "close": "16:00"},
    "sunday": {"open": null, "close": null}
  }'
```

#### 2.2 Branch Notification Settings

**Email Notifications:**
```bash
php artisan branch:notifications \
  --branch-id=456 \
  --email="hauptfiliale@salon-beispiel.de"
```

**SMS Notifications:**
```bash
php artisan branch:notifications \
  --branch-id=456 \
  --sms-phone="+49151XXXXXXX"
```

**WhatsApp Notifications:**
```bash
php artisan branch:notifications \
  --branch-id=456 \
  --whatsapp="+49151XXXXXXX"
```

---

### Step 3: Cal.com Integration (15 min)

#### 3.1 Cal.com Team Setup

**In Cal.com Dashboard:**
1. Create Team: "Salon Beispiel - Hauptfiliale"
2. Add Team Members (Staff):
   - Maria (Stylistin)
   - Julia (Friseurin)
   - Thomas (Barber)
3. Copy Team ID: `team_abc123`

#### 3.2 Event Types (Services)

**Create Event Types in Cal.com:**
1. Herrenhaarschnitt (30 min, €25)
2. Damenhaarschnitt (45 min, €45)
3. Färben (90 min, €65)
4. Dauerwelle (120 min, €85)

**Note Event Type IDs:**
- Herrenhaarschnitt: `evt_123`
- Damenhaarschnitt: `evt_456`
- ...

#### 3.3 Import Services to AskPro

**Automatic Import:**
```bash
php artisan calcom:import-services \
  --company-id=123 \
  --branch-id=456

# Scans Cal.com team, creates Services automatically
```

**Manual Creation (if needed):**
```bash
php artisan service:create \
  --company-id=123 \
  --name="Herrenhaarschnitt" \
  --duration=30 \
  --price=25.00 \
  --calcom-event-type-id="evt_123"
```

#### 3.4 Service Synonyms

**Add common variations:**
```bash
php artisan service:add-synonym \
  --service-id=789 \
  --synonym="Herren Haarschnitt"

php artisan service:add-synonym \
  --service-id=789 \
  --synonym="Männer Haarschnitt"

php artisan service:add-synonym \
  --service-id=789 \
  --synonym="Herrenschnitt"
```

**Bulk Import from CSV:**
```bash
php artisan service:import-synonyms \
  --file="storage/imports/synonyms_salon_beispiel.csv"
```

CSV Format:
```csv
service_name,synonym
Herrenhaarschnitt,Herren Haarschnitt
Herrenhaarschnitt,Männer Haarschnitt
Damenhaarschnitt,Damen Haarschnitt
```

---

### Step 4: Retell Agent Setup (10 min)

#### 4.1 Clone Template Agent

**Using Artisan Command:**
```bash
php artisan retell:clone-agent \
  --from="optimal_v110_template" \
  --company-id=123 \
  --branch-id=456 \
  --name="Salon Beispiel - Hauptfiliale"

# Output:
# ✅ Agent cloned successfully
# Agent ID: agent_xyz789
# Flow ID: flow_abc456
```

**What this does:**
1. Clones V110 Conversation Flow
2. Clones V110 Agent Configuration
3. Updates company/branch-specific settings
4. Registers all function endpoints
5. Configures voice settings

#### 4.2 Customize Agent (Optional)

**Update Greeting:**
```bash
php artisan retell:update-greeting \
  --agent-id="agent_xyz789" \
  --greeting="Willkommen bei Salon Beispiel!"
```

**Update Voice:**
```bash
php artisan retell:update-voice \
  --agent-id="agent_xyz789" \
  --voice-id="rachel" \
  --language="de-DE"
```

**Update Business Name in Prompts:**
```bash
php artisan retell:update-prompts \
  --agent-id="agent_xyz789" \
  --replace="Friseur 1:Salon Beispiel"
```

#### 4.3 Assign Phone Number

**Option A: Use Branch Phone (recommended)**
```bash
php artisan retell:assign-phone \
  --agent-id="agent_xyz789" \
  --phone="+49301234568"
```

**Option B: Buy new Retell number**
```bash
php artisan retell:buy-phone \
  --agent-id="agent_xyz789" \
  --country="DE" \
  --area-code="030"
```

---

### Step 5: Staff Configuration (10 min)

#### 5.1 Import Staff from Cal.com

**Automatic:**
```bash
php artisan calcom:import-staff \
  --branch-id=456

# Syncs all team members from Cal.com
```

#### 5.2 Manual Staff Creation

```bash
php artisan staff:create \
  --branch-id=456 \
  --name="Maria Schmidt" \
  --email="maria@salon-beispiel.de" \
  --phone="+49151XXXXXXX" \
  --calcom-user-id="user_123"
```

#### 5.3 Staff-Service Mapping

```bash
# Maria can do all services
php artisan staff:assign-services \
  --staff-id=101 \
  --services="all"

# Thomas only does men's services
php artisan staff:assign-services \
  --staff-id=102 \
  --services="Herrenhaarschnitt,Bart trimmen"
```

---

### Step 6: Testing (5 min)

#### 6.1 Test Call

```bash
# Make test call via Retell API
php artisan retell:test-call \
  --agent-id="agent_xyz789" \
  --from="+491511234567" \
  --scenario="booking"
```

**Test Scenarios:**
1. **New Customer Booking:** Unknown number, full data collection
2. **Returning Customer:** Known number, smart service prediction
3. **Availability Check:** Termin verfügbar vs. nicht verfügbar
4. **Reschedule:** Existing appointment, move to new time
5. **Cancellation:** Cancel an appointment
6. **Error Handling:** Simulate Cal.com timeout

#### 6.2 Verify in Logs

```bash
tail -f storage/logs/retell/retell.log

# Should see:
# [2025-11-10 14:30:00] Call started: agent_xyz789
# [2025-11-10 14:30:01] check_customer: found=false (new customer)
# [2025-11-10 14:30:15] check_availability: available=true
# [2025-11-10 14:30:20] confirm_booking: success=true
```

#### 6.3 Check Booking in Cal.com

1. Login to Cal.com
2. Go to Bookings
3. Verify appointment created

---

## Configuration Templates

### Friseur / Barber

```bash
# Company Settings
--callback-channels="email,sms,portal"
--enable-smart-service=true
--service-confidence-threshold=0.8
--enable-staff-preferences=true
--staff-confidence-threshold=0.8

# Business Hours (typical)
monday-friday: 09:00-18:00
saturday: 09:00-16:00
sunday: closed

# Services (typical)
- Herrenhaarschnitt (30min, €25)
- Damenhaarschnitt (45min, €45)
- Bart trimmen (15min, €15)
- Färben (90min, €65)
```

### Physiotherapie

```bash
# Company Settings
--callback-channels="email,portal" # NO SMS (GDPR)
--enable-smart-service=true
--service-confidence-threshold=0.9 # Higher threshold
--enable-staff-preferences=true
--staff-confidence-threshold=0.9

# Business Hours
monday-friday: 08:00-20:00
saturday: 08:00-14:00
sunday: closed

# Services (typical)
- Manuelle Therapie (30min, €45)
- Krankengymnastik (30min, €35)
- Massagetherapie (45min, €55)
- Lymphdrainage (60min, €65)

# Special: Cancellation Policy
--cancellation-hours=24
```

### Kosmetikstudio

```bash
# Company Settings
--callback-channels="email,whatsapp,portal"
--enable-smart-service=true
--service-confidence-threshold=0.7 # Lower (more variety)
--enable-staff-preferences=false # Less important

# Business Hours
monday: closed
tuesday-friday: 10:00-19:00
saturday: 10:00-17:00
sunday: closed

# Services (typical)
- Gesichtsbehandlung (60min, €75)
- Maniküre (45min, €35)
- Pediküre (60min, €45)
- Waxing (30min, €25)
```

---

## Troubleshooting

### Problem: Agent doesn't recognize customers

**Check:**
```bash
# 1. Verify check_customer function is working
php artisan retell:test-function \
  --function="check_customer" \
  --params='{"call_id":"test_123"}'

# 2. Check if customers have phone numbers
php artisan db:query \
  "SELECT id, name, phone FROM customers WHERE company_id=123"

# 3. Check Redis cache
php artisan cache:get "customer_lookup:123:+491512345678"
```

**Solution:**
- Ensure customers have `phone` field populated
- Check phone number format (+49 prefix)
- Verify company_id in call context

---

### Problem: Services not found

**Check:**
```bash
# 1. List all services for company
php artisan service:list --company-id=123

# 2. Check Cal.com sync
php artisan calcom:verify-sync --company-id=123

# 3. Check synonyms
php artisan service:show-synonyms --service-id=789
```

**Solution:**
```bash
# Re-import from Cal.com
php artisan calcom:import-services --company-id=123 --force
```

---

### Problem: Bookings failing

**Check:**
```bash
# 1. Test Cal.com API
php artisan calcom:test-connection --company-id=123

# 2. Check pending bookings cache
php artisan cache:get "pending_booking:{{call_id}}"

# 3. Check logs
tail -f storage/logs/retell/retell.log | grep "confirm_booking"
```

**Solution:**
- Verify Cal.com API key is valid
- Check event_type_ids are correct
- Ensure `call_id` parameter mapping is `{{call_id}}` not "1"

---

## Multi-Branch Scenarios

### Scenario 1: Chain with centralized management

**Setup:**
- 1 Company
- 5 Branches (different locations)
- Shared Services
- Shared Staff Pool
- Centralized callback handling

**Configuration:**
```bash
# Create company once
php artisan company:create --name="Friseur Kette GmbH"

# Create 5 branches
for i in {1..5}; do
  php artisan branch:create \
    --company-id=123 \
    --name="Filiale $i" \
    --phone="+4930123456$i"
done

# Clone agent for each branch
for branch_id in {456..460}; do
  php artisan retell:clone-agent \
    --from="optimal_v110_template" \
    --company-id=123 \
    --branch-id=$branch_id
done

# Centralized callback email
php artisan company:config \
  --company-id=123 \
  --callback-email="zentrale@friseur-kette.de"
```

---

### Scenario 2: Franchise with independent branches

**Setup:**
- 1 Company per Franchise
- 1 Branch per Company
- Independent Services
- Independent Staff
- Branch-specific callback handling

**Configuration:**
```bash
# Create separate companies
php artisan company:create --name="Franchise Berlin"
php artisan company:create --name="Franchise Hamburg"

# Each gets own branch, agent, services
# Fully isolated
```

---

## Rollout Checklist

**Pre-Rollout (1 day before):**
- [ ] Company created in Filament
- [ ] Cal.com team setup complete
- [ ] Services imported and verified
- [ ] Staff imported and mapped
- [ ] Agent cloned and customized
- [ ] Phone number assigned
- [ ] Test calls successful
- [ ] Monitoring dashboards configured

**Go-Live:**
- [ ] Announce to customer
- [ ] Switch phone routing to Retell
- [ ] Monitor first 10 calls closely
- [ ] Verify bookings appear in Cal.com
- [ ] Check callback notifications work

**Post-Rollout (first week):**
- [ ] Daily metrics review
- [ ] Customer feedback collection
- [ ] Adjust confidence thresholds if needed
- [ ] Add missing service synonyms
- [ ] Optimize greeting templates

---

## Metrics & KPIs

**Track per Company/Branch:**
```sql
-- Booking Success Rate
SELECT
  COUNT(*) FILTER (WHERE status = 'confirmed') * 100.0 / COUNT(*) AS success_rate
FROM calls
WHERE company_id = 123 AND created_at > NOW() - INTERVAL '7 days';

-- Average Call Duration
SELECT AVG(duration_seconds) AS avg_duration
FROM calls
WHERE company_id = 123 AND created_at > NOW() - INTERVAL '7 days';

-- Customer Recognition Rate
SELECT
  COUNT(*) FILTER (WHERE customer_id IS NOT NULL) * 100.0 / COUNT(*) AS recognition_rate
FROM calls
WHERE company_id = 123 AND created_at > NOW() - INTERVAL '7 days';

-- Smart Service Prediction Usage
SELECT
  COUNT(*) FILTER (WHERE service_auto_predicted = true) * 100.0 / COUNT(*) AS prediction_rate
FROM calls
WHERE company_id = 123 AND customer_id IS NOT NULL
  AND created_at > NOW() - INTERVAL '7 days';
```

---

## Support & Escalation

**Tier 1: Automatic Monitoring**
- Alerts on Slack for error rates >5%
- Daily summary emails

**Tier 2: Customer Success Team**
- Email: success@askproai.de
- Response SLA: 4 hours

**Tier 3: Engineering**
- Slack: #askpro-urgent
- On-call: +49151XXXXXXX
- For P0/P1 incidents only

---

**Document Version:** 1.0
**Last Updated:** 2025-11-10
**Estimated Onboarding Time:** <1 hour per company
**Success Rate:** 95%+ first-attempt deployments
