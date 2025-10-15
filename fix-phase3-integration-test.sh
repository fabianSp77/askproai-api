#!/bin/bash
# Fix Phase 3: Integration Testing
# Priority: 🟡 Verification - Execute after Phase 1 & 2
# Time: 10 minutes
# Risk: None (read-only testing)

set -e

echo "======================================"
echo "Phase 3: Integration Testing"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}This script will run automated tests to verify both fixes.${NC}"
echo ""

echo -e "${YELLOW}Test 1: Cal.com API Connectivity${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

php artisan tinker --execute="
\$service = \App\Models\Service::find(46);
\$calcomService = app(\App\Services\CalcomService::class);

echo 'Service: ' . \$service->name . '\n';
echo 'Cal.com Event Type ID: ' . \$service->calcom_event_type_id . '\n';
echo 'Cal.com Team ID: ' . \$service->company->calcom_team_id . '\n';
echo '\n';

try {
    \$startDate = now()->format('Y-m-d');
    \$endDate = now()->addWeek()->format('Y-m-d');

    echo 'Calling Cal.com API...\n';
    echo 'GET /slots/available\n';
    echo '  eventTypeId: ' . \$service->calcom_event_type_id . '\n';
    echo '  teamId: ' . \$service->company->calcom_team_id . '\n';
    echo '  startDate: ' . \$startDate . '\n';
    echo '  endDate: ' . \$endDate . '\n';
    echo '\n';

    \$response = \$calcomService->getAvailableSlots(
        eventTypeId: (int) \$service->calcom_event_type_id,
        startDate: \$startDate,
        endDate: \$endDate,
        teamId: \$service->company->calcom_team_id
    );

    if (\$response->successful()) {
        \$data = \$response->json();
        \$slots = \$data['data']['slots'] ?? [];
        \$totalSlots = array_sum(array_map('count', \$slots));

        echo '✅ SUCCESS\n';
        echo 'HTTP Status: ' . \$response->status() . '\n';
        echo 'Dates with slots: ' . count(\$slots) . '\n';
        echo 'Total slots: ' . \$totalSlots . '\n';

        if (\$totalSlots > 0) {
            \$firstDate = array_key_first(\$slots);
            \$firstSlot = \$slots[\$firstDate][0] ?? null;
            if (\$firstSlot) {
                echo 'First available slot: ' . \$firstDate . ' at ' . \$firstSlot . '\n';
            }
        }

        exit(0);
    } else {
        echo '❌ FAILED\n';
        echo 'HTTP Status: ' . \$response->status() . '\n';
        echo 'Response: ' . \$response->body() . '\n';
        exit(1);
    }
} catch (\Exception \$e) {
    echo '❌ EXCEPTION\n';
    echo 'Error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

TEST1_RESULT=$?
echo ""

echo -e "${YELLOW}Test 2: Form Schema Structure${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

php artisan tinker --execute="
\$resource = new \App\Filament\Resources\AppointmentResource();
\$form = \$resource::form(\Filament\Forms\Form::make());
\$schema = \$form->getSchema();

echo 'Checking form schema structure...\n';
echo '\n';

// Find hidden fields at top level
\$hiddenFields = [];
foreach (\$schema as \$component) {
    if (\$component instanceof \Filament\Forms\Components\Hidden) {
        \$hiddenFields[] = \$component->getName();
    }
}

\$requiredFields = ['service_id', 'staff_id', 'branch_id', 'customer_id'];
\$missing = array_diff(\$requiredFields, \$hiddenFields);

echo 'Required hidden fields: ' . implode(', ', \$requiredFields) . '\n';
echo 'Found at top level: ' . implode(', ', \$hiddenFields) . '\n';
echo '\n';

if (empty(\$missing)) {
    echo '✅ SUCCESS: All required hidden fields at top level\n';
    exit(0);
} else {
    echo '❌ FAILED: Missing fields: ' . implode(', ', \$missing) . '\n';
    exit(1);
}
"

TEST2_RESULT=$?
echo ""

echo -e "${YELLOW}Test 3: WeeklyAvailabilityService Integration${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

php artisan tinker --execute="
\$service = \App\Models\Service::find(46);
\$availabilityService = app(\App\Services\Appointments\WeeklyAvailabilityService::class);

echo 'Service: ' . \$service->name . '\n';
echo 'Testing WeeklyAvailabilityService...\n';
echo '\n';

try {
    \$weekStart = now()->startOfWeek();
    \$weekData = \$availabilityService->getWeekAvailability(
        serviceId: \$service->id,
        weekStart: \$weekStart
    );

    \$totalSlots = 0;
    foreach (\$weekData['days'] as \$day) {
        \$totalSlots += count(\$day['slots'] ?? []);
    }

    echo '✅ SUCCESS\n';
    echo 'Week start: ' . \$weekStart->format('Y-m-d') . '\n';
    echo 'Days with slots: ' . count(\$weekData['days']) . '\n';
    echo 'Total slots: ' . \$totalSlots . '\n';

    if (\$totalSlots > 0) {
        foreach (\$weekData['days'] as \$day) {
            if (!empty(\$day['slots'])) {
                echo 'First available day: ' . \$day['date'] . ' (' . count(\$day['slots']) . ' slots)\n';
                break;
            }
        }
    }

    exit(0);
} catch (\Exception \$e) {
    echo '❌ EXCEPTION\n';
    echo 'Error: ' . \$e->getMessage() . '\n';
    echo 'Stack trace (first 3 lines):\n';
    \$lines = explode(\"\n\", \$e->getTraceAsString());
    echo implode(\"\n\", array_slice(\$lines, 0, 3)) . '\n';
    exit(1);
}
"

TEST3_RESULT=$?
echo ""

echo "======================================"
echo "Test Results Summary"
echo "======================================"
echo ""

if [ $TEST1_RESULT -eq 0 ]; then
    echo -e "${GREEN}✅ Test 1: Cal.com API Connectivity${NC}"
else
    echo -e "${RED}❌ Test 1: Cal.com API Connectivity${NC}"
fi

if [ $TEST2_RESULT -eq 0 ]; then
    echo -e "${GREEN}✅ Test 2: Form Schema Structure${NC}"
else
    echo -e "${RED}❌ Test 2: Form Schema Structure${NC}"
fi

if [ $TEST3_RESULT -eq 0 ]; then
    echo -e "${GREEN}✅ Test 3: WeeklyAvailabilityService Integration${NC}"
else
    echo -e "${RED}❌ Test 3: WeeklyAvailabilityService Integration${NC}"
fi

echo ""

if [ $TEST1_RESULT -eq 0 ] && [ $TEST2_RESULT -eq 0 ] && [ $TEST3_RESULT -eq 0 ]; then
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ ALL TESTS PASSED${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "🎉 Both fixes are working correctly!"
    echo ""
    echo -e "${BLUE}Next: Browser Testing Checklist${NC}"
    echo ""
    echo "Manual verification steps:"
    echo "  1. Navigate to: https://YOUR_DOMAIN/admin/appointments/create"
    echo "  2. Open browser DevTools (F12) → Console tab"
    echo "  3. Select a branch"
    echo "  4. Select a customer"
    echo "  5. Select a service '15 Minuten Schnellberatung'"
    echo "  6. Check console for:"
    echo "     ✅ '[BookingFlowWrapper] service_id updated: 46'"
    echo "     ✅ NO '[BookingFlowWrapper] service_id field not found'"
    echo "  7. Calendar should load with available time slots"
    echo "     ✅ NO 'Cal.com API-Fehler: GET /slots/available (HTTP 404)'"
    echo "  8. Select a time slot"
    echo "  9. Click 'Create' button"
    echo "  10. Appointment should be created successfully"
    echo ""
    exit 0
else
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${RED}❌ SOME TESTS FAILED${NC}"
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "Please review the failed tests above."
    echo ""
    echo "Common issues:"
    echo "  - Test 1 failed: Cal.com Event Type ID may still be incorrect"
    echo "  - Test 2 failed: Hidden fields not moved to top level (Phase 2 incomplete)"
    echo "  - Test 3 failed: Service or availability service configuration issue"
    echo ""
    echo "Recommendation:"
    echo "  1. Check logs: tail -100 storage/logs/laravel.log"
    echo "  2. Re-run failed phase fix script"
    echo "  3. Contact support if issue persists"
    echo ""
    exit 1
fi
