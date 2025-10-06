#!/bin/bash
#
# Test Data Seeding Script - PHASE B Migration Testing
#
# Purpose: Seed comprehensive test data for migration validation
# Usage: ./scripts/seed_test_data.sh [database_name]
# Exit codes: 0=success, 1=failure
#

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Database connection
DB_USER="askproai_user"
DB_PASS="askproai_secure_pass_2024"
DB_HOST="127.0.0.1"
TEST_DB="${1:-askproai_test}"

echo -e "${BLUE}Seeding test data for migration validation...${NC}"
echo -e "${BLUE}Target database: ${TEST_DB}${NC}"
echo ""

# Function: Run query
run_query() {
    mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$TEST_DB" -e "$1" 2>&1
}

# Get existing IDs for relationships
echo -e "${BLUE}Fetching existing entity IDs...${NC}"

COMPANY_ID=$(run_query "SELECT id FROM companies LIMIT 1;" | tail -1)
CUSTOMER_ID=$(run_query "SELECT id FROM customers LIMIT 1;" | tail -1)
BRANCH_ID=$(run_query "SELECT id FROM branches LIMIT 1;" | tail -1)
SERVICE_ID=$(run_query "SELECT id FROM services LIMIT 1;" | tail -1)
STAFF_ID=$(run_query "SELECT id FROM staff LIMIT 1;" | tail -1)
APPOINTMENT_ID=$(run_query "SELECT id FROM appointments LIMIT 1;" | tail -1)

if [ -z "$COMPANY_ID" ] || [ -z "$CUSTOMER_ID" ] || [ -z "$BRANCH_ID" ]; then
    echo -e "${RED}Required base data missing (companies, customers, branches)${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Base entities found${NC}"
echo "  Company ID: $COMPANY_ID"
echo "  Customer ID: $CUSTOMER_ID"
echo "  Branch ID: $BRANCH_ID"
echo "  Service ID: ${SERVICE_ID:-N/A}"
echo "  Staff ID: ${STAFF_ID:-N/A}"
echo "  Appointment ID: ${APPOINTMENT_ID:-N/A}"
echo ""

# ================================================================
# Seed notification_configurations
# ================================================================
echo -e "${BLUE}Seeding notification_configurations...${NC}"

run_query "
INSERT INTO notification_configurations
  (company_id, configurable_type, configurable_id, event_type, channel, fallback_channel, is_enabled, retry_count, retry_delay_minutes, created_at, updated_at)
VALUES
  -- Company level configurations
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', ${COMPANY_ID}, 'booking_confirmed', 'email', 'sms', 1, 3, 5, NOW(), NOW()),
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', ${COMPANY_ID}, 'booking_confirmed', 'sms', 'none', 1, 3, 5, NOW(), NOW()),
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', ${COMPANY_ID}, 'reminder_24h', 'email', 'sms', 1, 3, 5, NOW(), NOW()),
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', ${COMPANY_ID}, 'cancellation', 'email', 'none', 1, 2, 10, NOW(), NOW()),
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', ${COMPANY_ID}, 'reschedule_confirmed', 'email', 'sms', 1, 3, 5, NOW(), NOW()),
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', ${COMPANY_ID}, 'callback_request_received', 'email', 'none', 1, 3, 5, NOW(), NOW())
" > /dev/null || echo -e "${YELLOW}⚠ Some notification_configurations may already exist${NC}"

# Branch level override (if branch exists)
if [ ! -z "$BRANCH_ID" ]; then
    run_query "
    INSERT IGNORE INTO notification_configurations
      (company_id, configurable_type, configurable_id, event_type, channel, is_enabled, retry_count, retry_delay_minutes, created_at, updated_at)
    VALUES
      (${COMPANY_ID}, 'App\\\\Models\\\\Branch', '${BRANCH_ID}', 'booking_confirmed', 'whatsapp', 1, 3, 5, NOW(), NOW())
    " > /dev/null || true
fi

# Service level override (if service exists)
if [ ! -z "$SERVICE_ID" ]; then
    run_query "
    INSERT IGNORE INTO notification_configurations
      (company_id, configurable_type, configurable_id, event_type, channel, is_enabled, retry_count, retry_delay_minutes, created_at, updated_at)
    VALUES
      (${COMPANY_ID}, 'App\\\\Models\\\\Service', ${SERVICE_ID}, 'booking_confirmed', 'push', 1, 2, 3, NOW(), NOW())
    " > /dev/null || true
fi

echo -e "${GREEN}✓ notification_configurations seeded${NC}"

# ================================================================
# Seed policy_configurations
# ================================================================
echo -e "${BLUE}Seeding policy_configurations...${NC}"

run_query "
INSERT INTO policy_configurations
  (company_id, configurable_type, configurable_id, policy_type, config, is_override, created_at, updated_at)
VALUES
  -- Company level policies
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', '${COMPANY_ID}', 'cancellation',
   '{\"hours_before\": 24, \"fee_percentage\": 50, \"max_cancellations_per_month\": 3}', 0, NOW(), NOW()),
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', '${COMPANY_ID}', 'reschedule',
   '{\"hours_before\": 12, \"max_reschedules_per_appointment\": 2, \"fee_percentage\": 25}', 0, NOW(), NOW()),
  (${COMPANY_ID}, 'App\\\\Models\\\\Company', '${COMPANY_ID}', 'recurring',
   '{\"allow_partial_cancel\": true, \"require_full_series_notice\": false}', 0, NOW(), NOW())
" > /dev/null || echo -e "${YELLOW}⚠ Some policy_configurations may already exist${NC}"

# Branch level policy override
if [ ! -z "$BRANCH_ID" ]; then
    run_query "
    INSERT IGNORE INTO policy_configurations
      (company_id, configurable_type, configurable_id, policy_type, config, is_override, created_at, updated_at)
    VALUES
      (${COMPANY_ID}, 'App\\\\Models\\\\Branch', '${BRANCH_ID}', 'cancellation',
       '{\"hours_before\": 48, \"fee_percentage\": 75, \"max_cancellations_per_month\": 2}', 1, NOW(), NOW())
    " > /dev/null || true
fi

# Service level policy override
if [ ! -z "$SERVICE_ID" ]; then
    run_query "
    INSERT IGNORE INTO policy_configurations
      (company_id, configurable_type, configurable_id, policy_type, config, is_override, created_at, updated_at)
    VALUES
      (${COMPANY_ID}, 'App\\\\Models\\\\Service', '${SERVICE_ID}', 'cancellation',
       '{\"hours_before\": 72, \"fee_percentage\": 100, \"max_cancellations_per_month\": 1}', 1, NOW(), NOW())
    " > /dev/null || true
fi

echo -e "${GREEN}✓ policy_configurations seeded${NC}"

# ================================================================
# Seed callback_requests
# ================================================================
echo -e "${BLUE}Seeding callback_requests...${NC}"

run_query "
INSERT INTO callback_requests
  (company_id, customer_id, branch_id, service_id, phone_number, customer_name, priority, status, expires_at, created_at, updated_at)
VALUES
  (${COMPANY_ID}, ${CUSTOMER_ID}, '${BRANCH_ID}', ${SERVICE_ID:-NULL}, '+14155551234', 'John Doe', 'normal', 'pending', DATE_ADD(NOW(), INTERVAL 4 HOUR), NOW(), NOW()),
  (${COMPANY_ID}, ${CUSTOMER_ID}, '${BRANCH_ID}', ${SERVICE_ID:-NULL}, '+14155555678', 'Jane Smith', 'high', 'pending', DATE_ADD(NOW(), INTERVAL 2 HOUR), NOW(), NOW()),
  (${COMPANY_ID}, ${CUSTOMER_ID}, '${BRANCH_ID}', ${SERVICE_ID:-NULL}, '+14155559012', 'Bob Johnson', 'urgent', 'assigned', DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW(), NOW()),
  (${COMPANY_ID}, ${CUSTOMER_ID}, '${BRANCH_ID}', NULL, '+14155553456', 'Alice Williams', 'normal', 'completed', DATE_ADD(NOW(), INTERVAL -1 HOUR), DATE_ADD(NOW(), INTERVAL -2 HOUR), NOW())
" > /dev/null

CALLBACK_ID_1=$(run_query "SELECT id FROM callback_requests WHERE phone_number = '+14155551234' LIMIT 1;" | tail -1)
CALLBACK_ID_2=$(run_query "SELECT id FROM callback_requests WHERE phone_number = '+14155555678' LIMIT 1;" | tail -1)

echo -e "${GREEN}✓ callback_requests seeded${NC}"

# ================================================================
# Seed callback_escalations
# ================================================================
echo -e "${BLUE}Seeding callback_escalations...${NC}"

if [ ! -z "$CALLBACK_ID_2" ]; then
    run_query "
    INSERT INTO callback_escalations
      (company_id, callback_request_id, escalation_reason, escalated_at, created_at, updated_at)
    VALUES
      (${COMPANY_ID}, ${CALLBACK_ID_2}, 'sla_breach', NOW(), NOW(), NOW())
    " > /dev/null

    echo -e "${GREEN}✓ callback_escalations seeded${NC}"
else
    echo -e "${YELLOW}⚠ Skipped callback_escalations (no callback_request)${NC}"
fi

# ================================================================
# Seed appointment_modifications
# ================================================================
echo -e "${BLUE}Seeding appointment_modifications...${NC}"

if [ ! -z "$APPOINTMENT_ID" ]; then
    run_query "
    INSERT INTO appointment_modifications
      (company_id, appointment_id, customer_id, modification_type, within_policy, fee_charged, reason, modified_by_type, created_at, updated_at)
    VALUES
      (${COMPANY_ID}, ${APPOINTMENT_ID}, ${CUSTOMER_ID}, 'cancel', 1, 0.00, 'Customer emergency', 'Customer', DATE_ADD(NOW(), INTERVAL -5 DAY), DATE_ADD(NOW(), INTERVAL -5 DAY)),
      (${COMPANY_ID}, ${APPOINTMENT_ID}, ${CUSTOMER_ID}, 'reschedule', 1, 0.00, 'Time conflict', 'Customer', DATE_ADD(NOW(), INTERVAL -10 DAY), DATE_ADD(NOW(), INTERVAL -10 DAY)),
      (${COMPANY_ID}, ${APPOINTMENT_ID}, ${CUSTOMER_ID}, 'cancel', 0, 25.00, 'Late cancellation', 'Customer', DATE_ADD(NOW(), INTERVAL -15 DAY), DATE_ADD(NOW(), INTERVAL -15 DAY)),
      (${COMPANY_ID}, ${APPOINTMENT_ID}, ${CUSTOMER_ID}, 'reschedule', 0, 12.50, 'Late reschedule', 'Staff', DATE_ADD(NOW(), INTERVAL -20 DAY), DATE_ADD(NOW(), INTERVAL -20 DAY)),
      (${COMPANY_ID}, ${APPOINTMENT_ID}, ${CUSTOMER_ID}, 'cancel', 1, 0.00, 'Provider unavailable', 'System', DATE_ADD(NOW(), INTERVAL -25 DAY), DATE_ADD(NOW(), INTERVAL -25 DAY))
    " > /dev/null

    echo -e "${GREEN}✓ appointment_modifications seeded${NC}"
else
    echo -e "${YELLOW}⚠ Skipped appointment_modifications (no appointment)${NC}"
fi

# ================================================================
# Seed appointment_modification_stats
# ================================================================
echo -e "${BLUE}Seeding appointment_modification_stats...${NC}"

run_query "
INSERT INTO appointment_modification_stats
  (company_id, customer_id, stat_type, period_start, period_end, count, calculated_at, created_at, updated_at)
VALUES
  (${COMPANY_ID}, ${CUSTOMER_ID}, 'cancellation_count', DATE_ADD(CURDATE(), INTERVAL -30 DAY), CURDATE(), 2, NOW(), NOW(), NOW()),
  (${COMPANY_ID}, ${CUSTOMER_ID}, 'reschedule_count', DATE_ADD(CURDATE(), INTERVAL -30 DAY), CURDATE(), 1, NOW(), NOW(), NOW()),
  (${COMPANY_ID}, ${CUSTOMER_ID}, 'cancellation_count', DATE_ADD(CURDATE(), INTERVAL -60 DAY), DATE_ADD(CURDATE(), INTERVAL -30 DAY), 1, NOW(), DATE_ADD(NOW(), INTERVAL -30 DAY), DATE_ADD(NOW(), INTERVAL -30 DAY))
" > /dev/null

echo -e "${GREEN}✓ appointment_modification_stats seeded${NC}"

# ================================================================
# Verify seeded data
# ================================================================
echo ""
echo -e "${BLUE}Verifying seeded data counts...${NC}"

NOTIF_COUNT=$(run_query "SELECT COUNT(*) FROM notification_configurations WHERE company_id = ${COMPANY_ID};" | tail -1)
POLICY_COUNT=$(run_query "SELECT COUNT(*) FROM policy_configurations WHERE company_id = ${COMPANY_ID};" | tail -1)
CALLBACK_COUNT=$(run_query "SELECT COUNT(*) FROM callback_requests WHERE company_id = ${COMPANY_ID};" | tail -1)
ESCALATION_COUNT=$(run_query "SELECT COUNT(*) FROM callback_escalations WHERE company_id = ${COMPANY_ID};" | tail -1)
MOD_COUNT=$(run_query "SELECT COUNT(*) FROM appointment_modifications WHERE company_id = ${COMPANY_ID};" | tail -1)
STATS_COUNT=$(run_query "SELECT COUNT(*) FROM appointment_modification_stats WHERE company_id = ${COMPANY_ID};" | tail -1)

echo -e "${GREEN}Data seeding complete:${NC}"
echo "  notification_configurations: $NOTIF_COUNT records"
echo "  policy_configurations: $POLICY_COUNT records"
echo "  callback_requests: $CALLBACK_COUNT records"
echo "  callback_escalations: $ESCALATION_COUNT records"
echo "  appointment_modifications: $MOD_COUNT records"
echo "  appointment_modification_stats: $STATS_COUNT records"
echo ""

echo -e "${GREEN}✓ Test data seeding completed successfully${NC}"
exit 0
