# Debugging Checklist Package - Complete Guide

**Date Created**: 2025-10-25
**Purpose**: Comprehensive information gathering and debugging workflow for 4 critical bugs
**Status**: Ready for use - Plan Mode (review before execution)

---

## What's Included

This package contains **4 comprehensive debugging documents** and **1 automated verification script** to help systematically investigate and fix 4 critical bugs in the Voice AI appointment system.

### Documents Created

1. **DEBUGGING_CHECKLIST_COMPREHENSIVE.md** (Primary Document)
   - Detailed information gathering checklist for each bug
   - Specific questions to answer
   - Commands to verify each finding
   - Expected outputs
   - Knowledge base: What we already know vs. what needs investigation

2. **DEBUG_QUICK_REFERENCE.md** (Quick Lookup)
   - 2-3 minute per-bug check commands
   - Essential diagnostic commands
   - Step-by-step debugging workflow
   - When-stuck troubleshooting guide

3. **BUG_INVESTIGATION_FLOWCHART.txt** (Visual Guide)
   - ASCII flowchart showing decision tree for each bug
   - Priority order for fixes
   - Key files organized by bug
   - Investigation decision tree

4. **run_debug_verification.sh** (Automated Script)
   - Bash script that runs all verification commands
   - Generates timestamped report
   - Organizes findings by bug
   - Ready to share or archive

---

## Quick Start

### Option 1: Run Everything (10 minutes)
```bash
bash /var/www/api-gateway/run_debug_verification.sh
cat DEBUG_VERIFICATION_RESULTS_*.txt
```

This single command:
- Gathers all available information for 4 bugs
- Runs all verification checks
- Generates timestamped report
- Shows what information is missing

### Option 2: Quick 2-Minute Check per Bug
```bash
# BUG #1: Agent Hallucination
ls -la /var/www/api-gateway/public/*.json | grep -i flow
ls -la /var/www/api-gateway/scripts/deployment/

# BUG #2: Date Parsing
wc -l /var/www/api-gateway/app/Services/Retell/DateTimeParser.php
cd /var/www/api-gateway && vendor/bin/pest tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php -v

# BUG #3: Email Crash
grep "spatie/icalendar" /var/www/api-gateway/composer.json
find /var/www/api-gateway/app -name "*Notification.php" | head -5

# BUG #4: V9 Not Deployed
cd /var/www/api-gateway && git log --oneline -1
git log --all --oneline | grep -i "v9\|version 9" | head -5
```

### Option 3: Deep Investigation (Follow Flowchart)
```bash
# 1. Review the visual decision tree
cat /var/www/api-gateway/BUG_INVESTIGATION_FLOWCHART.txt

# 2. For each bug, follow the decision tree
# 3. Execute the recommended commands
# 4. Document findings
```

---

## The 4 Bugs: Overview

### BUG #1: Agent Hallucination (Service Selection Mismatch)
- **Symptom**: AI suggests services that don't exist
- **Impact**: Booking failures, user confusion
- **Status**: Needs investigation - unclear where flow is stored and updated
- **Priority**: #4 (after V9 deployment)
- **Estimated Fix Time**: 45 minutes

**Critical Questions**:
1. Where is Retell conversation flow stored? (Dashboard/JSON/DB)
2. How to update it? (API/Artisan/Script)
3. Can we test locally? (Simulator/Mock/Test calls)
4. How are function results passed to flow? (JSON format?)
5. What's the condition syntax for transitions?

**Key File**: `/var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php`

---

### BUG #2: Date Parsing Errors
- **Symptom**: Incorrect date interpretation from user input
- **Impact**: Wrong appointments created, parsing failures
- **Status**: Well-documented service (945 lines) - high confidence in diagnosis
- **Priority**: #2 (after V9)
- **Estimated Fix Time**: 15 minutes (run tests, identify failure, fix)

**What We Know**:
- DateTimeParser exists with 10+ public methods
- German relative dates fully supported ('heute', 'morgen', 'dieser Donnerstag')
- Multiple input formats supported (ISO, German, German short)
- Comprehensive test file available
- Timezone handling: Europe/Berlin with smart caching

**Key File**: `/var/www/api-gateway/app/Services/Retell/DateTimeParser.php` (945 lines)

**Next Step**: Run tests to identify specific failure case

---

### BUG #3: Email Crash (ICS Attachment)
- **Symptom**: Email notifications fail when attaching calendar
- **Impact**: Users don't receive booking confirmations
- **Status**: Likely quick fix - library version or attachment code issue
- **Priority**: #3 (after V9 and date parsing)
- **Estimated Fix Time**: 30 minutes

**What We Know**:
- ICS library: spatie/icalendar-generator v3.0
- Library is installed in vendor/
- Config files exist for mail (config/mail.php)
- Notification/Mailable classes need to be located

**Key Files**:
- Notification class location: TBD (grep search needed)
- `/var/www/api-gateway/config/mail.php`
- `/var/www/api-gateway/vendor/spatie/icalendar-generator/`

**Next Step**: Find notification class sending ICS, check error logs

---

### BUG #4: V9 Not Deployed (Cache/Version Issue)
- **Symptom**: Code changes not reflected in running system
- **Impact**: Fixes from V9 not active despite being committed
- **Status**: Deployment verification needed - HIGH PRIORITY
- **Priority**: #1 (FIX FIRST - blocks other fixes)
- **Estimated Fix Time**: 5 minutes (clear caches + restart)

**Root Cause Possibilities**:
1. V9 commit not in current branch (deploy missing code)
2. OPcache caching old bytecode (clear opcache)
3. Code modified but not committed (stash or commit)
4. PHP-FPM not restarted (reload services)

**Key Checks**:
```bash
git log --oneline -1                    # Should show V9 commit
php -r "var_dump(opcache_get_status());"  # Check if cache is full
systemctl status php-fpm                # Verify service running
```

**Quick Fix**:
```bash
git pull                                # Ensure V9 is deployed
php artisan optimize:clear              # Clear all caches
sudo systemctl restart php-fpm          # Restart PHP
```

---

## How to Use These Documents

### For Systematic Investigation:
1. Start with **BUG_INVESTIGATION_FLOWCHART.txt**
   - Get visual overview of decision tree
   - Understand priority order
   - See which questions to ask first

2. Run **run_debug_verification.sh**
   - Automatically gathers all findings
   - Saves timestamped report

3. Review **DEBUGGING_CHECKLIST_COMPREHENSIVE.md**
   - For each bug, check what information we have
   - Identify missing pieces
   - Execute recommended commands

4. Follow **DEBUG_QUICK_REFERENCE.md**
   - Quick 2-3 minute checks
   - Essential diagnostic commands
   - Troubleshooting guide

### For Quick Lookups:
- **"Which file does bug X involve?"** → BUG_INVESTIGATION_FLOWCHART.txt (Key Files section)
- **"What commands do I run?"** → DEBUG_QUICK_REFERENCE.md (Essential Commands section)
- **"What's the exact error?"** → DEBUGGING_CHECKLIST_COMPREHENSIVE.md (Root Cause Analysis sections)
- **"I'm stuck, what do I do?"** → DEBUG_QUICK_REFERENCE.md (When Stuck section)

### For Bug Reproduction:
- Each bug has a "Testing & Validation" section
- Use local test files, staging environment if available
- Run test suite after changes

---

## Information Status Summary

### BUG #1: Agent Hallucination
| Question | Status | Location |
|----------|--------|----------|
| Where is flow stored? | UNKNOWN | Section 1.1-1.2 |
| How to update it? | UNKNOWN | Section 1.3-1.5 |
| Can test locally? | UNKNOWN | Section 1.7 |
| Function response format? | UNKNOWN | Section 1.8 |
| Transition syntax? | UNKNOWN | Review flow JSON |

**Action**: Run sections 1.1-1.8 commands

### BUG #2: Date Parsing
| Question | Status | Location |
|----------|--------|----------|
| Methods available? | KNOWN (10 methods) | Section 2.1 |
| German dates? | KNOWN (Full support) | Section 2.2 |
| Input/output format? | KNOWN (Multiple formats) | Section 2.3 |
| Tests exist? | KNOWN (Test file exists) | Section 2.5 |
| Timezone handling? | KNOWN (Berlin TZ, cached) | Section 2.4, 2.6 |

**Action**: Run tests to identify specific failure case (Section 2.5)

### BUG #3: Email Crash
| Question | Status | Location |
|----------|--------|----------|
| Which library? | KNOWN (spatie/icalendar v3.0) | Section 3.1 |
| Version installed? | PARTIALLY (v3.0 known, exact build TBD) | Section 3.2 |
| Recently updated? | UNKNOWN | Section 3.8 |
| Other emails work? | UNKNOWN | Section 3.5 |
| Disable ICS? | LIKELY (approach in section 3.5) | Section 3.5 |

**Action**: Run sections 3.3-3.8 commands, find notification class

### BUG #4: V9 Not Deployed
| Question | Status | Location |
|----------|--------|----------|
| Verify code version? | NEED TO RUN | Section 4.1 |
| When V9 committed? | NEED TO RUN | Section 4.2-4.3 |
| Version constants? | NEED TO RUN | Section 4.6 |
| OPcache cleared? | NEED TO RUN | Section 4.9 |
| PHP-FPM restarted? | NEED TO RUN | Section 4.12 |

**Action**: Run ALL section 4 commands immediately

---

## Workflow Recommendation

### Optimal Debugging Sequence:

```
1. BUG #4 FIRST (5 min)
   ├─ Check V9 deployment status
   ├─ Clear caches
   └─ Restart PHP-FPM

2. BUG #2 (15 min)
   ├─ Run DateTimeParser tests
   ├─ Identify failure case
   └─ Fix and re-test

3. BUG #3 (30 min)
   ├─ Find notification class
   ├─ Check ICS generation
   └─ Fix library issue or disable attachment

4. BUG #1 (45 min)
   ├─ Understand flow structure
   ├─ Fix service validation
   └─ Test in Retell Simulator
```

**Total Time**: ~95 minutes for complete investigation and fixes

---

## Testing After Fixes

### For BUG #2 (Date Parsing):
```bash
cd /var/www/api-gateway
vendor/bin/pest tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php -v
```

### For BUG #3 (Email):
```bash
# Test sending notification without attachment
php artisan tinker
> app(\YourNotificationClass::class)->send($user)
```

### For BUG #1 & #4:
```bash
# Make test call to Retell
# Check logs for successful execution
tail -f storage/logs/laravel.log
```

---

## Important Notes

1. **Plan Mode Active**: Review this checklist and confirm plan before executing any fixes
2. **Destructive Operations**: Cache clearing is safe, but backup database before major changes
3. **Testing Required**: Run test suite after each fix: `vendor/bin/pest`
4. **Documentation**: Update DEBUGGING_CHECKLIST_COMPREHENSIVE.md with findings
5. **Git Workflow**: Work on feature branch, commit with meaningful messages

---

## Related Documentation

These debugging documents complement the existing project documentation:
- `/var/www/api-gateway/claudedocs/03_API/Retell_AI/` - Retell AI implementation
- `/var/www/api-gateway/claudedocs/02_BACKEND/Calcom/` - Cal.com integration
- `/var/www/api-gateway/tests/Unit/` - Test suite

---

## File Locations Summary

```
/var/www/api-gateway/
├─ DEBUGGING_CHECKLIST_COMPREHENSIVE.md (⭐ START HERE)
├─ DEBUG_QUICK_REFERENCE.md
├─ BUG_INVESTIGATION_FLOWCHART.txt
├─ run_debug_verification.sh
├─ DEBUG_CHECKLIST_README.md (THIS FILE)
├─ DEBUG_VERIFICATION_RESULTS_*.txt (Generated after running script)
│
├─ app/Services/Retell/
│  ├─ ServiceSelectionService.php (BUG #1)
│  ├─ DateTimeParser.php (BUG #2)
│  └─ WebhookResponseService.php (BUG #1, #2)
│
├─ config/
│  └─ mail.php (BUG #3)
│
└─ tests/Unit/Services/Retell/
   └─ DateTimeParserShortFormatTest.php (BUG #2)
```

---

## Next Steps

1. **Review**: Read BUG_INVESTIGATION_FLOWCHART.txt for visual overview
2. **Run**: Execute `bash run_debug_verification.sh`
3. **Analyze**: Compare results against DEBUGGING_CHECKLIST_COMPREHENSIVE.md
4. **Plan**: Confirm implementation plan before executing fixes
5. **Execute**: Follow priority order (BUG #4 → #2 → #3 → #1)
6. **Test**: Run test suite after each fix
7. **Document**: Update findings in this package for future reference

---

**Status**: Ready for execution pending plan confirmation
**Last Updated**: 2025-10-25
**Prepared by**: Claude Code - Root Cause Analysis Specialist
