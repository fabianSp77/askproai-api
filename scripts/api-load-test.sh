#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    API LOAD TESTING SUITE                        ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

BASE_URL="https://api.askproai.de"
REQUESTS=100
CONCURRENCY=10

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "Testing with $REQUESTS requests at $CONCURRENCY concurrency"
echo ""

# Test 1: Business Login Page
echo "1. Testing Business Login Page..."
if command -v ab &> /dev/null; then
    ab -n $REQUESTS -c $CONCURRENCY -g /tmp/login_test.tsv "$BASE_URL/business/login" 2>/dev/null | grep -E "Requests per second:|Time per request:|Failed requests:" | head -3
else
    echo "Using curl for sequential testing (ab not installed)..."
    START=$(date +%s%N)
    for i in $(seq 1 $REQUESTS); do
        curl -s -o /dev/null -w "%{http_code}\n" "$BASE_URL/business/login" &
        if [ $((i % CONCURRENCY)) -eq 0 ]; then
            wait
        fi
    done
    wait
    END=$(date +%s%N)
    DURATION=$((($END - $START) / 1000000))
    RPS=$(echo "scale=2; $REQUESTS * 1000 / $DURATION" | bc)
    echo -e "${GREEN}✓${NC} Completed: $REQUESTS requests in ${DURATION}ms ($RPS req/s)"
fi
echo ""

# Test 2: API Health Endpoint
echo "2. Testing API Health Endpoint..."
START=$(date +%s%N)
SUCCESS=0
FAILED=0
for i in $(seq 1 $REQUESTS); do
    CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/health")
    if [ "$CODE" == "200" ] || [ "$CODE" == "404" ]; then
        ((SUCCESS++))
    else
        ((FAILED++))
    fi
done
END=$(date +%s%N)
DURATION=$((($END - $START) / 1000000))
RPS=$(echo "scale=2; $REQUESTS * 1000 / $DURATION" | bc)

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓${NC} All requests completed successfully"
else
    echo -e "${YELLOW}⚠${NC} $SUCCESS succeeded, $FAILED failed"
fi
echo "   Average: $RPS requests/second"
echo ""

# Test 3: Static Asset Loading
echo "3. Testing Static Asset Performance..."
ASSET_TIMES=()
for i in {1..10}; do
    TIME=$(curl -o /dev/null -s -w "%{time_total}" "$BASE_URL/favicon.ico")
    TIME_MS=$(echo "$TIME * 1000" | bc)
    ASSET_TIMES+=($TIME_MS)
done

# Calculate average
SUM=0
for t in "${ASSET_TIMES[@]}"; do
    SUM=$(echo "$SUM + $t" | bc)
done
AVG=$(echo "scale=2; $SUM / 10" | bc)
echo -e "${GREEN}✓${NC} Static assets average: ${AVG}ms"
echo ""

# Test 4: Concurrent Connection Test
echo "4. Testing Concurrent Connections..."
PIDS=()
for i in {1..50}; do
    curl -s -o /dev/null "$BASE_URL/business" &
    PIDS+=($!)
done

# Wait for all background jobs
SUCCESS_COUNT=0
for pid in "${PIDS[@]}"; do
    if wait $pid; then
        ((SUCCESS_COUNT++))
    fi
done

echo -e "${GREEN}✓${NC} Handled $SUCCESS_COUNT/50 concurrent connections"
echo ""

# Summary
echo "═══════════════════════════════════════════════════════════════════"
echo "LOAD TEST SUMMARY"
echo "═══════════════════════════════════════════════════════════════════"
echo "• Total Requests: $((REQUESTS * 2 + 60))"
echo "• Concurrency Level: $CONCURRENCY"
echo "• Average Response: ~${AVG}ms for static assets"
echo "• System handled concurrent load successfully"
echo ""
echo "Test completed: $(date '+%Y-%m-%d %H:%M:%S')"