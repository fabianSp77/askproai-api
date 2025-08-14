#!/bin/bash

echo "====================================================="
echo "NAVIGATION FIX VERIFICATION - CURL BASED"
echo "====================================================="

TIMESTAMP=$(date +"%Y-%m-%d-%H-%M-%S")
REPORT_FILE="/var/www/api-gateway/public/screenshots/navigation-curl-report-${TIMESTAMP}.json"

echo "Timestamp: $TIMESTAMP"
echo ""

# Step 1: Test login page
echo "Step 1: Testing login page..."
LOGIN_RESPONSE=$(curl -s -c /tmp/cookies.txt "https://api.askproai.de/admin/login")

if [[ $LOGIN_RESPONSE == *"login"* ]] && [[ $LOGIN_RESPONSE == *"email"* ]]; then
    echo "✅ Login page accessible"
    LOGIN_PAGE_STATUS="accessible"
else
    echo "❌ Login page not accessible"
    LOGIN_PAGE_STATUS="not accessible"
fi

# Step 2: Get CSRF token and attempt login
echo ""
echo "Step 2: Extracting CSRF token and attempting login..."
CSRF_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -oP 'name="_token" value="\K[^"]*')

if [[ -n "$CSRF_TOKEN" ]]; then
    echo "✅ CSRF token extracted: ${CSRF_TOKEN:0:10}..."
    
    # Attempt login
    LOGIN_POST=$(curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt \
        -X POST \
        -d "_token=$CSRF_TOKEN" \
        -d "email=admin@askproai.de" \
        -d "password=password" \
        "https://api.askproai.de/admin/login")
    
    # Check if redirected to admin dashboard
    DASHBOARD_RESPONSE=$(curl -s -b /tmp/cookies.txt "https://api.askproai.de/admin" -L)
    
    if [[ $DASHBOARD_RESPONSE == *"fi-sidebar"* ]] || [[ $DASHBOARD_RESPONSE == *"Dashboard"* ]]; then
        echo "✅ Login successful - Dashboard accessible"
        LOGIN_STATUS="successful"
    else
        echo "❌ Login failed or dashboard not accessible"
        LOGIN_STATUS="failed"
        echo "Response preview: ${DASHBOARD_RESPONSE:0:200}..."
    fi
else
    echo "❌ Could not extract CSRF token"
    LOGIN_STATUS="csrf_failed"
fi

# Step 3: Analyze dashboard HTML for navigation structure
echo ""
echo "Step 3: Analyzing dashboard HTML structure..."

if [[ "$LOGIN_STATUS" == "successful" ]]; then
    # Look for CSS Grid layout indicators
    if [[ $DASHBOARD_RESPONSE == *"display: grid"* ]] || [[ $DASHBOARD_RESPONSE == *"grid-template-columns"* ]]; then
        echo "✅ CSS Grid layout detected"
        GRID_LAYOUT="detected"
    else
        echo "❓ CSS Grid layout not explicitly found in HTML"
        GRID_LAYOUT="not_found"
    fi
    
    # Check for sidebar visibility
    if [[ $DASHBOARD_RESPONSE == *"fi-sidebar"* ]]; then
        echo "✅ Sidebar element present"
        SIDEBAR_PRESENT="yes"
        
        # Check for navigation links
        NAV_LINKS=$(echo "$DASHBOARD_RESPONSE" | grep -o 'href="[^"]*admin[^"]*"' | wc -l)
        echo "✅ Found $NAV_LINKS navigation links"
        NAVIGATION_LINKS="$NAV_LINKS"
    else
        echo "❌ Sidebar element not found"
        SIDEBAR_PRESENT="no"
        NAVIGATION_LINKS="0"
    fi
    
    # Check for main content area
    if [[ $DASHBOARD_RESPONSE == *"fi-main"* ]]; then
        echo "✅ Main content area present"
        MAIN_CONTENT_PRESENT="yes"
    else
        echo "❌ Main content area not found"
        MAIN_CONTENT_PRESENT="no"
    fi
    
    # Look for specific CSS classes that indicate the fix
    if [[ $DASHBOARD_RESPONSE == *"16rem"* ]]; then
        echo "✅ 16rem sidebar width found"
        SIDEBAR_WIDTH_FIX="detected"
    else
        echo "❓ 16rem sidebar width not explicitly found"
        SIDEBAR_WIDTH_FIX="not_found"
    fi
    
else
    echo "❌ Cannot analyze dashboard - login failed"
    GRID_LAYOUT="unknown"
    SIDEBAR_PRESENT="unknown"
    MAIN_CONTENT_PRESENT="unknown"
    NAVIGATION_LINKS="unknown"
    SIDEBAR_WIDTH_FIX="unknown"
fi

# Step 4: Test specific navigation endpoints
echo ""
echo "Step 4: Testing navigation endpoints..."

ENDPOINTS=(
    "/admin/dashboard"
    "/admin/calls"
    "/admin/appointments" 
    "/admin/customers"
)

ENDPOINT_RESULTS=""

for endpoint in "${ENDPOINTS[@]}"; do
    echo "Testing $endpoint..."
    ENDPOINT_RESPONSE=$(curl -s -b /tmp/cookies.txt -w "%{http_code}" "https://api.askproai.de$endpoint")
    HTTP_CODE="${ENDPOINT_RESPONSE: -3}"
    
    if [[ "$HTTP_CODE" == "200" ]]; then
        echo "  ✅ $endpoint - HTTP $HTTP_CODE"
        ENDPOINT_STATUS="accessible"
    else
        echo "  ❌ $endpoint - HTTP $HTTP_CODE"
        ENDPOINT_STATUS="error_$HTTP_CODE"
    fi
    
    ENDPOINT_RESULTS="${ENDPOINT_RESULTS}\"$endpoint\": \"$ENDPOINT_STATUS\", "
done

# Remove trailing comma and space
ENDPOINT_RESULTS="${ENDPOINT_RESULTS%, }"

# Step 5: Generate verdict
echo ""
echo "Step 5: Generating verdict..."

PASSED_TESTS=0
TOTAL_TESTS=6

if [[ "$LOGIN_PAGE_STATUS" == "accessible" ]]; then ((PASSED_TESTS++)); fi
if [[ "$LOGIN_STATUS" == "successful" ]]; then ((PASSED_TESTS++)); fi
if [[ "$SIDEBAR_PRESENT" == "yes" ]]; then ((PASSED_TESTS++)); fi
if [[ "$MAIN_CONTENT_PRESENT" == "yes" ]]; then ((PASSED_TESTS++)); fi
if [[ "$NAVIGATION_LINKS" -gt 0 ]]; then ((PASSED_TESTS++)); fi
if [[ "$GRID_LAYOUT" == "detected" ]] || [[ "$SIDEBAR_WIDTH_FIX" == "detected" ]]; then ((PASSED_TESTS++)); fi

if [[ $PASSED_TESTS -ge 4 ]]; then
    VERDICT="LIKELY FIXED"
    CONFIDENCE="medium"
else
    VERDICT="LIKELY STILL BROKEN"
    CONFIDENCE="medium"
fi

echo ""
echo "====================================================="
echo "FINAL VERDICT: $VERDICT"
echo "Confidence: $CONFIDENCE (based on HTML analysis)"
echo "Tests passed: $PASSED_TESTS/$TOTAL_TESTS"
echo "====================================================="

# Generate JSON report
cat > "$REPORT_FILE" << JSON_EOF
{
  "timestamp": "$TIMESTAMP",
  "test_method": "curl_based_html_analysis",
  "verdict": "$VERDICT",
  "confidence": "$CONFIDENCE",
  "tests_passed": $PASSED_TESTS,
  "tests_total": $TOTAL_TESTS,
  "results": {
    "login_page": "$LOGIN_PAGE_STATUS",
    "login_authentication": "$LOGIN_STATUS",
    "sidebar_present": "$SIDEBAR_PRESENT",
    "main_content_present": "$MAIN_CONTENT_PRESENT",
    "navigation_links_count": "$NAVIGATION_LINKS",
    "css_grid_layout": "$GRID_LAYOUT",
    "sidebar_width_fix": "$SIDEBAR_WIDTH_FIX",
    "navigation_endpoints": {
      $ENDPOINT_RESULTS
    }
  },
  "limitations": [
    "Cannot test visual layout positioning",
    "Cannot test actual clickability",
    "Cannot verify CSS Grid implementation visually",
    "Cannot test JavaScript interactions"
  ],
  "recommendations": [
    "Manual browser testing recommended for full verification",
    "Visual inspection needed to confirm sidebar positioning",
    "JavaScript console testing for click events"
  ]
}
JSON_EOF

echo ""
echo "📁 Detailed report saved: $(basename "$REPORT_FILE")"
echo ""

# Cleanup
rm -f /tmp/cookies.txt

echo "Note: This is a limited test using curl. For complete verification,"
echo "manual browser testing is recommended."
echo ""
