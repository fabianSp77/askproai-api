# 🎯 DUAL BASE SETUP GUIDE
## AskProAI + Friseur 1 Template System

**Status**: Phase 1 Complete - Ready for Execution
**Created**: 2025-10-27
**Purpose**: Clean database setup with 2 base companies for template system

---

## 📋 WHAT WE'VE CREATED

### Scripts Created:
1. **scripts/setup/create_askproai_base.php**
   - Creates AskProAI company (1 branch)
   - Phone: +493083793369
   - Agent: agent_616d645570ae613e421edb98e7
   - Cal.com Team: 39203

2. **scripts/setup/create_friseur1_base.php**
   - Creates Friseur 1 company (2 branches)
   - Phone: +493033081738
   - Agent: agent_45daa54928c5768b52ba3db736
   - Cal.com Team: 34209
   - 5 Staff Members
   - 16 Services (Event Types 3719738-3719753)

3. **scripts/setup/verify_dual_setup.php**
   - Comprehensive validation (13 tests)
   - Multi-tenant isolation checks
   - Pivot table verification

---

## 🚀 EXECUTION STEPS

### Step 1: Database Reset (DESTRUCTIVE!)
```bash
cd /var/www/api-gateway

# BACKUP FIRST (if needed)
# php artisan backup:run

# Reset database (DELETES ALL DATA!)
php artisan migrate:fresh

# Expected Output:
# Dropped all tables successfully.
# Migration table created successfully.
# Migrating: 2014_10_12_000000_create_users_table
# Migrated:  2014_10_12_000000_create_users_table (XX.XXms)
# ...
```

**⚠️ WARNING**: This will DELETE ALL current database data!

### Step 2: Create AskProAI Base
```bash
php scripts/setup/create_askproai_base.php

# Expected Output:
# ╔══════════════════════════════════════════════════════════════╗
# ║           CREATE ASKPROAI BASE COMPANY SETUP                ║
# ╚══════════════════════════════════════════════════════════════╝
#
# 📋 STEP 1: Creating AskProAI Company...
#    ✅ Company created: AskProAI (ID: 1)
# ...
# ✅ AskProAI Base Setup Complete!
```

### Step 3: Create Friseur 1 Base
```bash
php scripts/setup/create_friseur1_base.php

# Expected Output:
# ╔══════════════════════════════════════════════════════════════╗
# ║         CREATE FRISEUR 1 BASE COMPANY (TEMPLATE)            ║
# ╚══════════════════════════════════════════════════════════════╝
#
# 📋 STEP 1: Creating Friseur 1 Company...
#    ✅ Company created: Friseur 1 (ID: 2)
# ...
# 📋 STEP 7: Linking Staff to Services...
#    ✅ All staff linked to all services
#
# ✅ Friseur 1 Base Setup Complete!
```

### Step 4: Verify Setup
```bash
php scripts/setup/verify_dual_setup.php

# Expected Output:
# ╔══════════════════════════════════════════════════════════════╗
# ║           VERIFY DUAL BASE SETUP (COMPREHENSIVE)            ║
# ╚══════════════════════════════════════════════════════════════╝
#
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
#   ASKPROAI VERIFICATION
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
#
# 🔍 Test 1: AskProAI Company...
#    ✅ Company: AskProAI (ID: 1)
# ...
# 🎉 ALL TESTS PASSED! Dual base setup is perfect!
```

---

## 🧪 MANUAL TESTING

### Test 1: AskProAI Phone Call
```
1. Call: +493083793369
2. Expected Response: "Guten Tag bei AskProAI..."
3. Verify Agent: agent_616d645570ae613e421edb98e7 in dashboard
4. Test Booking: "15 Minuten Schnellberatung"
5. Check Cal.com Team 39203: Appointment should appear
```

### Test 2: Friseur 1 Phone Call
```
1. Call: +493033081738
2. Expected Response: "Guten Tag bei Friseur 1, mein Name ist Carola..."
3. Verify Agent: agent_45daa54928c5768b52ba3db736 in dashboard
4. Test Booking: "Waschen, schneiden, föhnen bei Emma"
5. Check Cal.com Team 34209: Appointment should appear
```

### Test 3: Multi-Branch Test (Friseur 1)
```
1. Call: +493033081738
2. Request: "Termin in der Zentrale"
3. Expected Staff Options: Emma, Fabian, Sarah
4. Request: "Termin in der Zweigstelle"
5. Expected Staff Options: David, Michael
```

### Test 4: Admin Panel Verification
```
1. Visit: https://api.askproai.de/admin/companies
2. Verify: 2 companies visible (AskProAI, Friseur 1)
3. Click AskProAI: Verify 1 branch, 3+ services
4. Click Friseur 1: Verify 2 branches, 5 staff, 16 services
```

---

## ✅ SUCCESS CRITERIA

### Database Structure:
- ✅ 2 Companies (AskProAI, Friseur 1)
- ✅ 3 Branches (1 AskProAI, 2 Friseur 1)
- ✅ 5 Staff Members (all in Friseur 1)
- ✅ 19+ Services (3+ AskProAI, 16 Friseur 1)
- ✅ 2 Phone Numbers (correctly mapped)

### External Integrations:
- ✅ Cal.com Team 39203 accessible
- ✅ Cal.com Team 34209 accessible with Event Types 3719738-3719753
- ✅ Retell Agent agent_616d645570ae613e421edb98e7 responds
- ✅ Retell Agent agent_45daa54928c5768b52ba3db736 responds

### Multi-Tenancy:
- ✅ AskProAI cannot see Friseur 1 data
- ✅ Friseur 1 cannot see AskProAI data
- ✅ No data leaks between companies

### Pivot Tables:
- ✅ branch_service: 32 links (16 per Friseur 1 branch)
- ✅ service_staff: 80 links (5 staff × 16 services)

---

## 🔄 NEXT PHASE: TEMPLATE SYSTEM

Once verification passes, proceed to:

### Phase 2 Tasks:
1. Create `FriseurTemplateSeeder.php`
2. Export Friseur 1 as JSON template
3. Build `CalcomTemplateService.php`
4. Build `RetellTemplateService.php`
5. Create `FriseurCloneSeeder.php`
6. Add Artisan commands (`friseur:export-template`, `friseur:clone`)

### Time Estimate:
- Phase 1 (Current): 60 min ✅
- Phase 2 (Template): 2-3h
- Phase 3 (Testing): 1h
- **Total System**: ~4h

---

## 🆘 TROUBLESHOOTING

### Issue: Migration fails
```bash
# Check database connection
php artisan config:clear
php artisan cache:clear

# Verify .env DB settings
cat .env | grep DB_

# Retry
php artisan migrate:fresh
```

### Issue: Script fails with "Class not found"
```bash
# Regenerate autoload
composer dump-autoload

# Retry script
php scripts/setup/create_askproai_base.php
```

### Issue: Phone number not working
```bash
# Verify Retell mapping
php scripts/setup/verify_dual_setup.php

# Check Retell dashboard manually
# https://dashboard.retellai.com/phone-numbers
```

### Issue: Services missing Cal.com Event Types
```bash
# Check Friseur 1 services
mysql -u root -p askproai_db -e "SELECT id, name, settings FROM services WHERE company_id = 2 LIMIT 5;"

# Verify settings JSON contains calcom_event_type_id
```

---

## 📊 CURRENT STATUS

**Phase 1**: ✅ COMPLETE
**Scripts Created**: 3/3
**Ready for Execution**: YES
**Next Step**: Database Reset + Script Execution

---

## 🎯 STRATEGIC VALUE

**Problem Solved**:
- Data loss → Clean slate opportunity
- Manual setup inefficiency → Automated template system
- Inconsistent configurations → Perfect, reproducible setup

**Business Impact**:
- 20 clients × 7h manual = 140h
- Template system = 4h setup + 20 × 10min = ~7.3h
- **Savings: 133h (95% reduction)**

---

**Ready to execute?** Run commands in order, verify after each step. 🚀
