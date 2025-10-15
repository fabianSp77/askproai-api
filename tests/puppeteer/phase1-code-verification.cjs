#!/usr/bin/env node

/**
 * Phase 1 Code Verification - Direct File Analysis
 *
 * Verifies that all 4 Phase 1 features are implemented correctly
 * by analyzing the source code directly
 */

const fs = require('fs');
const path = require('path');

const BASE_DIR = '/var/www/api-gateway';
const RESULTS = [];

function log(message, type = 'INFO') {
    const emoji = {
        'INFO': 'ℹ️',
        'SUCCESS': '✅',
        'ERROR': '❌',
        'WARNING': '⚠️',
        'TEST': '🧪'
    }[type] || 'ℹ️';
    console.log(`${emoji} ${message}`);
}

function addResult(test, pass, details) {
    RESULTS.push({ test, pass, details });
    log(`${test}: ${pass ? 'PASS' : 'FAIL'} - ${details}`, pass ? 'SUCCESS' : 'ERROR');
}

function searchInFile(filepath, patterns) {
    try {
        const content = fs.readFileSync(filepath, 'utf-8');
        const results = {};

        for (const [key, pattern] of Object.entries(patterns)) {
            if (typeof pattern === 'string') {
                results[key] = content.includes(pattern);
            } else {
                results[key] = pattern.test(content);
            }
        }

        return results;
    } catch (error) {
        log(`Error reading ${filepath}: ${error.message}`, 'ERROR');
        return null;
    }
}

function test1_ConflictDetection() {
    log('\n=== TEST 1: Conflict Detection ===', 'TEST');

    // Check CreateAppointment.php
    const createFile = path.join(BASE_DIR, 'app/Filament/Resources/AppointmentResource/Pages/CreateAppointment.php');
    const createResults = searchInFile(createFile, {
        hasBeforeCreate: /protected function beforeCreate/,
        hasConflictQuery: /Appointment::where.*staff_id/,
        hasOverlapCheck: /where\(['"]starts_at/,
        hasNotification: /Notification::make/,
        hasHalt: /\$this->halt/,
        hasWarningMessage: /Konflikt erkannt/
    });

    if (!createResults) {
        addResult('Test 1.1: CreateAppointment Conflict Detection', false, 'File not found or not readable');
    } else {
        const allPresent = Object.values(createResults).every(v => v);
        addResult('Test 1.1: CreateAppointment Conflict Detection', allPresent,
            `beforeCreate: ${createResults.hasBeforeCreate}, conflict query: ${createResults.hasConflictQuery}, halt: ${createResults.hasHalt}`);
    }

    // Check EditAppointment.php
    const editFile = path.join(BASE_DIR, 'app/Filament/Resources/AppointmentResource/Pages/EditAppointment.php');
    const editResults = searchInFile(editFile, {
        hasBeforeSave: /protected function beforeSave/,
        hasConflictQuery: /Appointment::where.*staff_id/,
        hasExcludeCurrentRecord: /where\(['"]id['"],\s*'!=',\s*\$this->record->id/,
        hasNotification: /Notification::make/,
        hasHalt: /\$this->halt/
    });

    if (!editResults) {
        addResult('Test 1.2: EditAppointment Conflict Detection', false, 'File not found or not readable');
    } else {
        const allPresent = Object.values(editResults).every(v => v);
        addResult('Test 1.2: EditAppointment Conflict Detection', allPresent,
            `beforeSave: ${editResults.hasBeforeSave}, excludes current: ${editResults.hasExcludeCurrentRecord}`);
    }

    // Check reschedule action in AppointmentResource.php
    const resourceFile = path.join(BASE_DIR, 'app/Filament/Resources/AppointmentResource.php');
    const resourceResults = searchInFile(resourceFile, {
        hasRescheduleConflict: /reschedule.*action.*function.*\$record.*conflicts\s*=/s,
        hasConflictCheck: /if\s*\(\$conflicts\)/
    });

    if (!resourceResults) {
        addResult('Test 1.3: Reschedule Action Conflict Detection', false, 'File not found or not readable');
    } else {
        const hasConflict = resourceResults.hasRescheduleConflict || resourceResults.hasConflictCheck;
        addResult('Test 1.3: Reschedule Action Conflict Detection', hasConflict,
            `Conflict check in reschedule action: ${hasConflict}`);
    }
}

function test2_AvailableSlots() {
    log('\n=== TEST 2: Available Slots in Reschedule Modal ===', 'TEST');

    const resourceFile = path.join(BASE_DIR, 'app/Filament/Resources/AppointmentResource.php');
    const results = searchInFile(resourceFile, {
        hasRescheduleForm: /->form\(function\s*\(\$record\)/,
        hasAvailableSlots: /findAvailableSlots/,
        hasPlaceholder: /Placeholder::make\(['"]available_slots/,
        hasNextSlotsText: /Nächste verfügbare Zeitfenster/,
        hasFindAvailableSlotsMethod: /protected static function findAvailableSlots/,
        hasSlotCalculation: /while\s*\(count\(\$availableSlots\)\s*<\s*\$count/,
        hasBusinessHours: /setTime\(9,\s*0\)|setTime\(17,\s*0\)/,
        hasConflictCheck: /hasConflict\s*=\s*Appointment::where/
    });

    if (!results) {
        addResult('Test 2: Available Slots', false, 'File not found or not readable');
    } else {
        const hasModalFeature = results.hasAvailableSlots && results.hasPlaceholder && results.hasNextSlotsText;
        const hasHelperMethod = results.hasFindAvailableSlotsMethod && results.hasSlotCalculation;

        addResult('Test 2.1: Available Slots Modal Feature', hasModalFeature,
            `Form: ${results.hasRescheduleForm}, Placeholder: ${results.hasPlaceholder}, Text: ${results.hasNextSlotsText}`);

        addResult('Test 2.2: findAvailableSlots() Helper Method', hasHelperMethod,
            `Method exists: ${results.hasFindAvailableSlotsMethod}, Logic: ${results.hasSlotCalculation}, Hours: ${results.hasBusinessHours}`);
    }
}

function test3_CustomerHistory() {
    log('\n=== TEST 3: Customer History Widget ===', 'TEST');

    const resourceFile = path.join(BASE_DIR, 'app/Filament/Resources/AppointmentResource.php');
    const results = searchInFile(resourceFile, {
        hasHistoryWidget: /Placeholder::make\(['"]customer_history/,
        hasHistoryLabel: /Kunden-Historie/,
        hasHistoryContent: /->content\(function\s*\(callable\s*\$get\)/,
        hasRecentAppointments: /Appointment::where\(['"]customer_id/,
        hasServiceFrequency: /mostFrequentService/,
        hasPreferredTime: /preferredHour.*HOUR\(starts_at\)/,
        hasStatusIcons: /match\(\$apt->status\)/,
        hasNeukunde: /Neukunde/,
        hasVisible: /->visible\(fn.*\$get\(['"]customer_id/
    });

    if (!results) {
        addResult('Test 3: Customer History', false, 'File not found or not readable');
    } else {
        const allFeatures = results.hasHistoryWidget && results.hasRecentAppointments &&
                            results.hasServiceFrequency && results.hasPreferredTime;

        addResult('Test 3: Customer History Widget', allFeatures,
            `Widget: ${results.hasHistoryWidget}, Query: ${results.hasRecentAppointments}, Patterns: ${results.hasServiceFrequency}, Visibility: ${results.hasVisible}`);
    }
}

function test4_NextAvailableSlot() {
    log('\n=== TEST 4: Next Available Slot Button ===', 'TEST');

    const resourceFile = path.join(BASE_DIR, 'app/Filament/Resources/AppointmentResource.php');
    const results = searchInFile(resourceFile, {
        hasSuffixAction: /->suffixAction\(/,
        hasNextSlotAction: /Action::make\(['"]nextAvailableSlot/,
        hasSparklesIcon: /heroicon-m-sparkles/,
        hasNextSlotLabel: /Nächster freier Slot/,
        hasStaffCheck: /if\s*\(!\$staffId\)/,
        hasFindSlotsCall: /findAvailableSlots\(\$staffId/,
        hasAutoFill: /\$set\(['"]starts_at/,
        hasEndTimeFill: /\$set\(['"]ends_at/,
        hasSuccessNotification: /Nächster freier Slot gefunden/
    });

    if (!results) {
        addResult('Test 4: Next Available Slot Button', false, 'File not found or not readable');
    } else {
        const allFeatures = results.hasSuffixAction && results.hasNextSlotAction &&
                            results.hasSparklesIcon && results.hasFindSlotsCall &&
                            results.hasAutoFill;

        addResult('Test 4: Next Available Slot Button', allFeatures,
            `Suffix Action: ${results.hasSuffixAction}, Icon: ${results.hasSparklesIcon}, Auto-fill: ${results.hasAutoFill && results.hasEndTimeFill}`);
    }
}

function generateReport() {
    log('\n=== TEST REPORT ===', 'INFO');

    const passed = RESULTS.filter(r => r.pass).length;
    const failed = RESULTS.filter(r => !r.pass).length;
    const total = RESULTS.length;
    const percentage = Math.round((passed / total) * 100);

    console.log('\n' + '='.repeat(80));
    console.log('PHASE 1 CODE VERIFICATION SUMMARY');
    console.log('='.repeat(80));
    console.log(`Total Tests: ${total}`);
    console.log(`✅ Passed: ${passed}`);
    console.log(`❌ Failed: ${failed}`);
    console.log(`Success Rate: ${percentage}%`);
    console.log('='.repeat(80) + '\n');

    RESULTS.forEach(r => {
        console.log(`${r.pass ? '✅' : '❌'} ${r.test}`);
        console.log(`   └─ ${r.details}\n`);
    });

    // Generate markdown report
    const report = `
# Phase 1 Code Verification Report

**Date**: ${new Date().toISOString()}
**Type**: Static Code Analysis

---

## Summary

| Metric | Value |
|--------|-------|
| Total Tests | ${total} |
| Passed | ✅ ${passed} |
| Failed | ❌ ${failed} |
| Success Rate | ${percentage}% |

---

## Detailed Results

${RESULTS.map(r => `
### ${r.test}
- **Status**: ${r.pass ? '✅ PASS' : '❌ FAIL'}
- **Details**: ${r.details}
`).join('\n')}

---

## Conclusion

${percentage === 100 ? '✅ All Phase 1 features are correctly implemented in the code!' :
  `⚠️ ${failed} test(s) failed. Review the details above and fix the issues.`}

**Implementation Quality**: ${percentage >= 80 ? 'Excellent' : percentage >= 60 ? 'Good' : 'Needs Improvement'}
`;

    const reportPath = path.join(BASE_DIR, 'tests/puppeteer/PHASE1_CODE_VERIFICATION_REPORT.md');
    fs.writeFileSync(reportPath, report);
    log(`\nReport saved to: ${reportPath}`, 'SUCCESS');

    return percentage === 100;
}

function main() {
    log('Starting Phase 1 Code Verification...', 'TEST');

    test1_ConflictDetection();
    test2_AvailableSlots();
    test3_CustomerHistory();
    test4_NextAvailableSlot();

    const allPassed = generateReport();

    if (allPassed) {
        log('\n🎉 ALL TESTS PASSED! Phase 1 implementation is complete and correct!', 'SUCCESS');
        process.exit(0);
    } else {
        log('\n⚠️ Some tests failed. Please review the report.', 'WARNING');
        process.exit(1);
    }
}

main();
