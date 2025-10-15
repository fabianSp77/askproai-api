#!/bin/bash
# Fix Phase 1: Cal.com Event Type ID Correction
# Priority: 🔴 CRITICAL - Execute this first
# Time: 5 minutes
# Risk: Low

set -e

echo "======================================"
echo "Phase 1: Cal.com Event Type ID Fix"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Checking current configuration...${NC}"
php artisan tinker --execute="
\$service = \App\Models\Service::find(46);
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
echo 'Service ID: ' . \$service->id . '\n';
echo 'Service Name: ' . \$service->name . '\n';
echo 'Current Cal.com Event Type ID: ' . \$service->calcom_event_type_id . '\n';
echo 'Company ID: ' . \$service->company_id . '\n';
echo 'Company Name: ' . \$service->company->name . '\n';
echo 'Company Cal.com Team ID: ' . \$service->company->calcom_team_id . '\n';
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
"

echo ""
echo -e "${RED}❌ Current Event Type ID: 1320965${NC}"
echo -e "${RED}❌ Cal.com returns: 404 Not Found${NC}"
echo ""

echo -e "${YELLOW}Step 2: Finding all Cal.com event types for company 15...${NC}"
php artisan tinker --execute="
\$services = \App\Models\Service::where('company_id', 15)
    ->whereNotNull('calcom_event_type_id')
    ->get();

echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
echo 'All Services with Cal.com Event Types:\n';
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
foreach (\$services as \$svc) {
    echo sprintf(
        'ID: %-4s | %-40s | Event Type: %s\n',
        \$svc->id,
        substr(\$svc->name, 0, 40),
        \$svc->calcom_event_type_id
    );
}
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
"

echo ""
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}ACTION REQUIRED: Manual Verification${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "Please follow these steps:"
echo ""
echo "1. Login to Cal.com: https://app.cal.com"
echo "2. Navigate to team 'AskProAI' (Team ID: 39203)"
echo "3. Go to Event Types"
echo "4. Find '15 Minuten Schnellberatung' (or create it if missing)"
echo "5. Check the URL: https://app.cal.com/event-types/{EVENT_TYPE_ID}"
echo "6. Copy the EVENT_TYPE_ID from the URL"
echo ""

read -p "Enter the CORRECT Cal.com Event Type ID: " CORRECT_EVENT_TYPE_ID

if [ -z "$CORRECT_EVENT_TYPE_ID" ]; then
    echo -e "${RED}ERROR: No event type ID provided. Aborting.${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 3: Updating database with correct event type ID...${NC}"
php artisan tinker --execute="
\$service = \App\Models\Service::find(46);
\$oldEventTypeId = \$service->calcom_event_type_id;
\$service->calcom_event_type_id = '$CORRECT_EVENT_TYPE_ID';
\$service->save();

echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
echo '✅ Service #46 Updated\n';
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
echo 'Old Event Type ID: ' . \$oldEventTypeId . '\n';
echo 'New Event Type ID: ' . \$service->calcom_event_type_id . '\n';
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
"

echo ""
echo -e "${YELLOW}Step 4: Clearing cache...${NC}"
php artisan cache:clear
echo -e "${GREEN}✅ Cache cleared${NC}"

echo ""
echo -e "${YELLOW}Step 5: Testing Cal.com API...${NC}"
php artisan tinker --execute="
\$service = \App\Models\Service::find(46);
\$calcomService = app(\App\Services\CalcomService::class);

try {
    \$response = \$calcomService->getAvailableSlots(
        eventTypeId: (int) \$service->calcom_event_type_id,
        startDate: now()->format('Y-m-d'),
        endDate: now()->addDay()->format('Y-m-d'),
        teamId: \$service->company->calcom_team_id
    );

    if (\$response->successful()) {
        echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
        echo '✅ Cal.com API Test: SUCCESS\n';
        echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
        echo 'HTTP Status: ' . \$response->status() . '\n';
        \$data = \$response->json();
        \$totalSlots = isset(\$data['data']['slots']) ? array_sum(array_map('count', \$data['data']['slots'])) : 0;
        echo 'Total Slots Found: ' . \$totalSlots . '\n';
        echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
        exit(0);
    } else {
        echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
        echo '❌ Cal.com API Test: FAILED\n';
        echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
        echo 'HTTP Status: ' . \$response->status() . '\n';
        echo 'Response: ' . \$response->body() . '\n';
        echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
        exit(1);
    }
} catch (\Exception \$e) {
    echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
    echo '❌ Cal.com API Test: EXCEPTION\n';
    echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
    echo 'Error: ' . \$e->getMessage() . '\n';
    echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ Phase 1 Complete: Cal.com Event Type Fixed${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "Next: Run ./fix-phase2-hidden-fields.sh"
else
    echo ""
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${RED}❌ Phase 1 Failed: Cal.com API Test Failed${NC}"
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "The event type ID may still be incorrect."
    echo "Please verify in Cal.com dashboard and re-run this script."
    exit 1
fi
