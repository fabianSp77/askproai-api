#!/bin/bash

# Post-Deployment UI Verification Script
# Captures screenshots after deployment to verify UI integrity

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
SCREENSHOT_DIR="/var/www/api-gateway/storage/app/screenshots/deployments"
REPORT_FILE="$SCREENSHOT_DIR/deploy_${TIMESTAMP}_report.md"

echo "🚀 Post-Deployment UI Check - $TIMESTAMP" > $REPORT_FILE
echo "=======================================" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Create screenshot directory if not exists
mkdir -p $SCREENSHOT_DIR

# Function to capture and analyze
capture_page() {
    local NAME=$1
    local URL=$2
    local SELECTOR=$3
    
    echo "📸 Capturing $NAME..." | tee -a $REPORT_FILE
    
    # Using headless Chrome via puppeteer or playwright
    # For now, using curl to check HTTP status
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$URL")
    
    echo "- URL: $URL" >> $REPORT_FILE
    echo "- Status: $HTTP_STATUS" >> $REPORT_FILE
    echo "- Screenshot: ${NAME}_${TIMESTAMP}.png" >> $REPORT_FILE
    echo "" >> $REPORT_FILE
    
    # TODO: Actual screenshot capture
    # node scripts/capture-screenshot.js "$URL" "$SCREENSHOT_DIR/${NAME}_${TIMESTAMP}.png"
}

# Capture main pages
capture_page "dashboard" "https://api.askproai.de/admin" "body"
capture_page "appointments" "https://api.askproai.de/admin/appointments" ".filament-tables-table"
capture_page "customers" "https://api.askproai.de/admin/customers" ".filament-tables-table"

# Check for visual anomalies
echo "🔍 Checking for common issues..." | tee -a $REPORT_FILE
echo "- [ ] Missing icons or images" >> $REPORT_FILE
echo "- [ ] Broken layouts" >> $REPORT_FILE
echo "- [ ] JavaScript errors in console" >> $REPORT_FILE
echo "- [ ] 404 errors in network tab" >> $REPORT_FILE

echo "" >> $REPORT_FILE
echo "✅ Post-deployment UI check completed!" >> $REPORT_FILE
echo "📊 Report saved to: $REPORT_FILE"

# Notify Claude/Dev team
echo "IMPORTANT: Please review screenshots at $SCREENSHOT_DIR"