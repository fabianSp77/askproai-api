#!/bin/bash

# Email Confirmation Setup Verification Script
# Checks all prerequisites for email confirmation feature

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  Email Confirmation Setup Verification                       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check counter
ERRORS=0
WARNINGS=0

# Function to check and report
check_pass() {
    echo -e "${GREEN}✅${NC} $1"
}

check_fail() {
    echo -e "${RED}❌${NC} $1"
    ((ERRORS++))
}

check_warn() {
    echo -e "${YELLOW}⚠️${NC}  $1"
    ((WARNINGS++))
}

echo "1. Checking modified files..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check AppointmentCreationService
if grep -q "📧 FEATURE: Send confirmation email" app/Services/Retell/AppointmentCreationService.php 2>/dev/null; then
    check_pass "AppointmentCreationService.php - Email integration added"
else
    check_fail "AppointmentCreationService.php - Email integration NOT found"
fi

# Check RetellFunctionCallHandler
if grep -q "confirmation_email_sent" app/Http/Controllers/RetellFunctionCallHandler.php 2>/dev/null; then
    check_pass "RetellFunctionCallHandler.php - Response message updated"
else
    check_fail "RetellFunctionCallHandler.php - Response message NOT updated"
fi

echo ""
echo "2. Checking required infrastructure..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check Mailable exists
if [ -f "app/Mail/AppointmentConfirmation.php" ]; then
    check_pass "AppointmentConfirmation Mailable exists"
else
    check_fail "AppointmentConfirmation Mailable NOT found"
fi

# Check NotificationService exists
if [ -f "app/Services/Communication/NotificationService.php" ]; then
    check_pass "NotificationService exists"
else
    check_fail "NotificationService NOT found"
fi

# Check IcsGeneratorService exists
if [ -f "app/Services/Communication/IcsGeneratorService.php" ]; then
    check_pass "IcsGeneratorService exists"
else
    check_fail "IcsGeneratorService NOT found"
fi

# Check email template exists
if [ -f "resources/views/emails/appointments/confirmation.blade.php" ]; then
    check_pass "Email template exists"
else
    check_warn "Email template NOT found (may not be created yet)"
fi

echo ""
echo "3. Checking configuration..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check .env for mail settings
if [ -f ".env" ]; then
    if grep -q "^MAIL_MAILER=" .env; then
        MAIL_DRIVER=$(grep "^MAIL_MAILER=" .env | cut -d '=' -f2)
        check_pass "MAIL_MAILER configured: $MAIL_DRIVER"
    else
        check_fail "MAIL_MAILER not configured in .env"
    fi

    if grep -q "^MAIL_FROM_ADDRESS=" .env; then
        check_pass "MAIL_FROM_ADDRESS configured"
    else
        check_warn "MAIL_FROM_ADDRESS not configured in .env"
    fi

    if grep -q "^QUEUE_CONNECTION=" .env; then
        QUEUE_DRIVER=$(grep "^QUEUE_CONNECTION=" .env | cut -d '=' -f2)
        if [ "$QUEUE_DRIVER" = "sync" ]; then
            check_warn "QUEUE_CONNECTION is 'sync' - emails will be sent synchronously"
        else
            check_pass "QUEUE_CONNECTION configured: $QUEUE_DRIVER"
        fi
    else
        check_warn "QUEUE_CONNECTION not configured (using default)"
    fi
else
    check_fail ".env file not found"
fi

echo ""
echo "4. Checking database tables..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if jobs table exists (for queue)
php artisan tinker --execute="echo Schema::hasTable('jobs') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"
if [ $? -eq 0 ]; then
    check_pass "Jobs table exists (queue configured)"
else
    check_warn "Jobs table not found - run: php artisan queue:table && php artisan migrate"
fi

# Check if customers table has email column
php artisan tinker --execute="echo Schema::hasColumn('customers', 'email') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"
if [ $? -eq 0 ]; then
    check_pass "Customers table has email column"
else
    check_fail "Customers table missing email column"
fi

echo ""
echo "5. Checking test scripts..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "scripts/testing/test_email_confirmation.php" ]; then
    check_pass "Test script exists"
    chmod +x scripts/testing/test_email_confirmation.php 2>/dev/null
else
    check_fail "Test script NOT found"
fi

echo ""
echo "6. Checking documentation..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "EMAIL_CONFIRMATION_IMPLEMENTATION.md" ]; then
    check_pass "Implementation documentation exists"
else
    check_warn "Implementation documentation NOT found"
fi

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  Summary                                                     ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed! Email confirmation is ready.${NC}"
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  Setup complete with $WARNINGS warnings${NC}"
    echo "   Review warnings above for optimal configuration"
else
    echo -e "${RED}❌ Setup incomplete: $ERRORS errors, $WARNINGS warnings${NC}"
    echo "   Fix errors before testing email confirmation"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 NEXT STEPS:"
echo ""

if [ $ERRORS -eq 0 ]; then
    echo "1. Start queue worker:"
    echo "   → php artisan queue:work"
    echo ""
    echo "2. Test email confirmation:"
    echo "   → php scripts/testing/test_email_confirmation.php"
    echo ""
    echo "3. Monitor logs:"
    echo "   → tail -f storage/logs/laravel.log | grep -i email"
    echo ""
    echo "4. Create test appointment via Retell agent"
    echo ""
else
    echo "1. Fix errors listed above"
    echo "2. Re-run this verification script"
    echo "3. Ensure .env is configured correctly"
    echo "4. Run migrations if needed"
    echo ""
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

exit $ERRORS
