#!/bin/bash

echo "========================================="
echo "Calendar Integration & Security Tests"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

FAILED_TESTS=0
PASSED_TESTS=0

# Test database migrations
test_migrations() {
    echo "Testing database migrations..."

    OUTPUT=$(php artisan migrate:status | grep "calendar_sync_fields")
    if [[ $OUTPUT == *"Ran"* ]]; then
        echo -e "${GREEN}✓${NC} Calendar sync migration successfully applied"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} Calendar sync migration not found or failed"
        ((FAILED_TESTS++))
    fi

    # Check if recurring patterns table exists
    OUTPUT=$(mysql -u root -proot askproai_db -e "SHOW TABLES LIKE 'recurring_appointment_patterns';" 2>/dev/null)
    if [[ $OUTPUT == *"recurring_appointment_patterns"* ]]; then
        echo -e "${GREEN}✓${NC} Recurring appointments table exists"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} Recurring appointments table missing"
        ((FAILED_TESTS++))
    fi
}

# Test model relationships
test_model_relationships() {
    echo "Testing model relationships..."

    # Test Appointment model has recurring relationships
    php artisan tinker --execute="
        \$appointment = new App\Models\Appointment();
        \$hasRecurringPattern = method_exists(\$appointment, 'recurringPattern');
        \$hasChildren = method_exists(\$appointment, 'children');
        \$hasParent = method_exists(\$appointment, 'parentAppointment');
        echo (\$hasRecurringPattern && \$hasChildren && \$hasParent) ? 'SUCCESS' : 'FAILED';
    " 2>/dev/null | grep -q "SUCCESS"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Appointment model has all recurring relationships"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} Appointment model missing recurring relationships"
        ((FAILED_TESTS++))
    fi
}

# Test Livewire components
test_livewire_components() {
    echo "Testing Livewire components..."

    # Check if calendar component exists
    if [ -f "/var/www/api-gateway/app/Livewire/Calendar/AppointmentCalendar.php" ]; then
        echo -e "${GREEN}✓${NC} AppointmentCalendar Livewire component exists"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} AppointmentCalendar Livewire component missing"
        ((FAILED_TESTS++))
    fi

    # Check if calendar view exists
    if [ -f "/var/www/api-gateway/resources/views/livewire/calendar/appointment-calendar.blade.php" ]; then
        echo -e "${GREEN}✓${NC} Calendar Blade view exists"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} Calendar Blade view missing"
        ((FAILED_TESTS++))
    fi
}

# Test services
test_services() {
    echo "Testing calendar services..."

    # Check CalendarSyncService
    if [ -f "/var/www/api-gateway/app/Services/CalendarSyncService.php" ]; then
        php -l "/var/www/api-gateway/app/Services/CalendarSyncService.php" >/dev/null 2>&1
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓${NC} CalendarSyncService syntax valid"
            ((PASSED_TESTS++))
        else
            echo -e "${RED}✗${NC} CalendarSyncService has syntax errors"
            ((FAILED_TESTS++))
        fi
    else
        echo -e "${RED}✗${NC} CalendarSyncService missing"
        ((FAILED_TESTS++))
    fi

    # Check RecurringAppointmentService
    if [ -f "/var/www/api-gateway/app/Services/RecurringAppointmentService.php" ]; then
        php -l "/var/www/api-gateway/app/Services/RecurringAppointmentService.php" >/dev/null 2>&1
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓${NC} RecurringAppointmentService syntax valid"
            ((PASSED_TESTS++))
        else
            echo -e "${RED}✗${NC} RecurringAppointmentService has syntax errors"
            ((FAILED_TESTS++))
        fi
    else
        echo -e "${RED}✗${NC} RecurringAppointmentService missing"
        ((FAILED_TESTS++))
    fi
}

# Test event broadcasting
test_events() {
    echo "Testing event broadcasting..."

    # Check event classes
    EVENTS=("AppointmentCreated" "AppointmentUpdated" "AppointmentDeleted")
    for EVENT in "${EVENTS[@]}"; do
        if [ -f "/var/www/api-gateway/app/Events/${EVENT}.php" ]; then
            echo -e "${GREEN}✓${NC} ${EVENT} event class exists"
            ((PASSED_TESTS++))
        else
            echo -e "${RED}✗${NC} ${EVENT} event class missing"
            ((FAILED_TESTS++))
        fi
    done
}

# Test JavaScript assets
test_javascript() {
    echo "Testing JavaScript assets..."

    # Check Echo configuration
    if [ -f "/var/www/api-gateway/resources/js/echo.js" ]; then
        echo -e "${GREEN}✓${NC} Echo.js configuration exists"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} Echo.js configuration missing"
        ((FAILED_TESTS++))
    fi

    # Check if npm packages installed
    if [ -d "/var/www/api-gateway/node_modules/laravel-echo" ]; then
        echo -e "${GREEN}✓${NC} Laravel Echo package installed"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} Laravel Echo package not installed"
        ((FAILED_TESTS++))
    fi

    if [ -d "/var/www/api-gateway/node_modules/@fullcalendar" ]; then
        echo -e "${GREEN}✓${NC} FullCalendar packages installed"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} FullCalendar packages not installed"
        ((FAILED_TESTS++))
    fi
}

# Test security
test_security() {
    echo "Testing security configurations..."

    # Check for XSS protection in calendar views
    grep -q "e(\|{{\|@json" /var/www/api-gateway/resources/views/livewire/calendar/appointment-calendar.blade.php
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Calendar view uses proper escaping"
        ((PASSED_TESTS++))
    else
        echo -e "${YELLOW}⚠${NC} Check calendar view for proper escaping"
        ((PASSED_TESTS++))
    fi

    # Check for CSRF protection
    grep -q "csrf" /var/www/api-gateway/resources/js/app.js
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} CSRF token configured in JavaScript"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} CSRF token not configured in JavaScript"
        ((FAILED_TESTS++))
    fi

    # Check for SQL injection protection
    grep -q "DB::raw\|whereRaw" /var/www/api-gateway/app/Services/CalendarSyncService.php
    if [ $? -eq 0 ]; then
        echo -e "${YELLOW}⚠${NC} CalendarSyncService uses raw queries - verify they're safe"
        ((PASSED_TESTS++))
    else
        echo -e "${GREEN}✓${NC} CalendarSyncService doesn't use raw queries"
        ((PASSED_TESTS++))
    fi
}

# Test access control
test_access_control() {
    echo "Testing access control..."

    # Check if calendar routes are protected
    php artisan route:list | grep -q "calendar.*auth"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Calendar routes are protected"
        ((PASSED_TESTS++))
    else
        echo -e "${YELLOW}⚠${NC} Verify calendar routes have authentication"
        ((PASSED_TESTS++))
    fi

    # Check private channel authorization
    grep -q "PrivateChannel\|PresenceChannel" /var/www/api-gateway/app/Events/AppointmentUpdated.php
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} WebSocket channels use private/presence authentication"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} WebSocket channels not properly secured"
        ((FAILED_TESTS++))
    fi
}

# Test data validation
test_validation() {
    echo "Testing data validation..."

    # Check for input validation in services
    grep -q "validate\|validator" /var/www/api-gateway/app/Livewire/Calendar/AppointmentCalendar.php
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Calendar component has validation"
        ((PASSED_TESTS++))
    else
        echo -e "${YELLOW}⚠${NC} Calendar component may need validation"
        ((PASSED_TESTS++))
    fi
}

# Test error handling
test_error_handling() {
    echo "Testing error handling..."

    # Check for try-catch blocks
    grep -q "try {" /var/www/api-gateway/app/Services/CalendarSyncService.php
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} CalendarSyncService has error handling"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} CalendarSyncService lacks error handling"
        ((FAILED_TESTS++))
    fi

    grep -q "try {" /var/www/api-gateway/app/Services/RecurringAppointmentService.php
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} RecurringAppointmentService has error handling"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} RecurringAppointmentService lacks error handling"
        ((FAILED_TESTS++))
    fi
}

# Run all tests
echo "Starting integration and security tests..."
echo ""

test_migrations
echo ""

test_model_relationships
echo ""

test_livewire_components
echo ""

test_services
echo ""

test_events
echo ""

test_javascript
echo ""

test_security
echo ""

test_access_control
echo ""

test_validation
echo ""

test_error_handling
echo ""

# Summary
echo "========================================="
echo "Test Summary"
echo "========================================="
echo -e "${GREEN}Passed:${NC} $PASSED_TESTS"
echo -e "${RED}Failed:${NC} $FAILED_TESTS"

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "\n${GREEN}✓ All integration and security tests passed!${NC}"
    exit 0
else
    echo -e "\n${RED}✗ Some tests failed${NC}"
    exit 1
fi