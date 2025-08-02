#!/bin/bash

# Business Portal Performance Benchmark using curl
# Tests login, dashboard, and API performance with detailed metrics

set -e

# Configuration
BASE_URL="${BASE_URL:-https://api.askproai.de}"
ITERATIONS="${ITERATIONS:-10}"
TEST_EMAIL="${TEST_EMAIL:-demo@askproai.de}"
TEST_PASSWORD="${TEST_PASSWORD:-password}"
COOKIE_JAR="/tmp/benchmark_cookies_$(date +%s).txt"
OUTPUT_DIR="/tmp/benchmark_results_$(date +%s)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create output directory
mkdir -p "$OUTPUT_DIR"

echo -e "${BLUE}🚀 Business Portal Performance Benchmark${NC}"
echo "========================================"
echo "Base URL: $BASE_URL"
echo "Iterations: $ITERATIONS"
echo "Test User: $TEST_EMAIL"
echo "Cookie Jar: $COOKIE_JAR"
echo "Results: $OUTPUT_DIR"
echo ""

# Arrays to store results
declare -a login_page_times=()
declare -a login_post_times=()
declare -a dashboard_times=()
declare -a api_stats_times=()
declare -a api_calls_times=()
declare -a api_appointments_times=()

# Function to measure HTTP request
measure_request() {
    local url="$1"
    local method="${2:-GET}"
    local data="$3"
    local description="$4"
    local cookie_jar="$5"
    
    local curl_args=()
    curl_args+=(-s)
    curl_args+=(-w "%{time_total},%{time_namelookup},%{time_connect},%{time_appconnect},%{time_pretransfer},%{time_redirect},%{time_starttransfer},%{size_download},%{speed_download},%{http_code}\n")
    curl_args+=(-o /dev/null)
    curl_args+=(--max-time 30)
    
    if [ -n "$cookie_jar" ]; then
        curl_args+=(-b "$cookie_jar")
        curl_args+=(-c "$cookie_jar")
    fi
    
    if [ "$method" = "POST" ]; then
        curl_args+=(-X POST)
        if [ -n "$data" ]; then
            curl_args+=(-d "$data")
        fi
        curl_args+=(-H "Content-Type: application/x-www-form-urlencoded")
    fi
    
    curl_args+=(-H "User-Agent: Performance-Benchmark/1.0")
    curl_args+=(-H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8")
    curl_args+=(-L) # Follow redirects
    
    local result
    result=$(curl "${curl_args[@]}" "$url" 2>/dev/null)
    
    if [ $? -eq 0 ] && [ -n "$result" ]; then
        echo "$result"
    else
        echo "0,0,0,0,0,0,0,0,0,000"
    fi
}

# Function to get CSRF token
get_csrf_token() {
    local cookie_jar="$1"
    local login_page_response
    login_page_response=$(curl -s -b "$cookie_jar" -c "$cookie_jar" "$BASE_URL/business/login" 2>/dev/null)
    
    if [ $? -eq 0 ]; then
        echo "$login_page_response" | grep -o '_token[^>]*value="[^"]*"' | sed 's/.*value="//;s/".*//' | head -1
    fi
}

echo -e "${YELLOW}🔐 Testing Login Performance...${NC}"

for ((i=1; i<=ITERATIONS; i++)); do
    echo -n "  Iteration $i/$ITERATIONS: "
    
    # Clean cookie jar for each iteration
    rm -f "$COOKIE_JAR"
    
    # 1. Measure login page load
    login_page_result=$(measure_request "$BASE_URL/business/login" "GET" "" "Login Page" "$COOKIE_JAR")
    login_page_time=$(echo "$login_page_result" | cut -d',' -f1)
    login_page_code=$(echo "$login_page_result" | cut -d',' -f10)
    
    if [ "$login_page_code" = "200" ]; then
        login_page_times+=("$login_page_time")
        echo -n "page: ${login_page_time}s "
        
        # Get CSRF token
        csrf_token=$(get_csrf_token "$COOKIE_JAR")
        
        if [ -n "$csrf_token" ]; then
            # 2. Measure login form submission
            login_data="_token=${csrf_token}&email=${TEST_EMAIL}&password=${TEST_PASSWORD}"
            login_post_result=$(measure_request "$BASE_URL/business/login" "POST" "$login_data" "Login Submit" "$COOKIE_JAR")
            login_post_time=$(echo "$login_post_result" | cut -d',' -f1)
            login_post_code=$(echo "$login_post_result" | cut -d',' -f10)
            
            if [ "$login_post_code" = "302" ] || [ "$login_post_code" = "200" ]; then
                login_post_times+=("$login_post_time")
                echo -n "submit: ${login_post_time}s "
                echo -e "${GREEN}✓${NC}"
            else
                echo -e "${RED}✗ Login failed (HTTP $login_post_code)${NC}"
            fi
        else
            echo -e "${RED}✗ CSRF token not found${NC}"
        fi
    else
        echo -e "${RED}✗ Page load failed (HTTP $login_page_code)${NC}"
    fi
done

echo ""
echo -e "${YELLOW}📊 Testing Dashboard Performance...${NC}"

for ((i=1; i<=ITERATIONS; i++)); do
    echo -n "  Iteration $i/$ITERATIONS: "
    
    # Clean cookie jar and login
    rm -f "$COOKIE_JAR"
    
    # Quick login
    measure_request "$BASE_URL/business/login" "GET" "" "Login Page" "$COOKIE_JAR" > /dev/null
    csrf_token=$(get_csrf_token "$COOKIE_JAR")
    
    if [ -n "$csrf_token" ]; then
        login_data="_token=${csrf_token}&email=${TEST_EMAIL}&password=${TEST_PASSWORD}"
        measure_request "$BASE_URL/business/login" "POST" "$login_data" "Login" "$COOKIE_JAR" > /dev/null
        
        # Measure dashboard load
        dashboard_result=$(measure_request "$BASE_URL/business/dashboard" "GET" "" "Dashboard" "$COOKIE_JAR")
        dashboard_time=$(echo "$dashboard_result" | cut -d',' -f1)
        dashboard_code=$(echo "$dashboard_result" | cut -d',' -f10)
        dashboard_size=$(echo "$dashboard_result" | cut -d',' -f8)
        
        if [ "$dashboard_code" = "200" ]; then
            dashboard_times+=("$dashboard_time")
            echo -e "dashboard: ${dashboard_time}s (${dashboard_size} bytes) ${GREEN}✓${NC}"
        else
            echo -e "${RED}✗ Dashboard failed (HTTP $dashboard_code)${NC}"
        fi
    else
        echo -e "${RED}✗ Login failed${NC}"
    fi
done

echo ""
echo -e "${YELLOW}🌐 Testing API Performance...${NC}"

# Test APIs with a single logged-in session
rm -f "$COOKIE_JAR"
measure_request "$BASE_URL/business/login" "GET" "" "Login Page" "$COOKIE_JAR" > /dev/null
csrf_token=$(get_csrf_token "$COOKIE_JAR")

if [ -n "$csrf_token" ]; then
    login_data="_token=${csrf_token}&email=${TEST_EMAIL}&password=${TEST_PASSWORD}"
    measure_request "$BASE_URL/business/login" "POST" "$login_data" "Login" "$COOKIE_JAR" > /dev/null
    
    # Test each API endpoint
    api_endpoints=(
        "/business/api/dashboard/stats,Stats API"
        "/business/api/dashboard/recent-calls,Recent Calls API"
        "/business/api/dashboard/upcoming-appointments,Appointments API"
    )
    
    for endpoint_info in "${api_endpoints[@]}"; do
        IFS=',' read -r endpoint name <<< "$endpoint_info"
        echo "  Testing $name..."
        
        declare -a times=()
        
        for ((i=1; i<=ITERATIONS; i++)); do
            result=$(measure_request "$BASE_URL$endpoint" "GET" "" "$name" "$COOKIE_JAB")
            time=$(echo "$result" | cut -d',' -f1)
            code=$(echo "$result" | cut -d',' -f10)
            size=$(echo "$result" | cut -d',' -f8)
            
            if [ "$code" = "200" ]; then
                times+=("$time")
                echo -n "."
            else
                echo -n "x"
            fi
        done
        echo ""
        
        # Store results based on endpoint
        case "$endpoint" in
            *stats*)
                api_stats_times=("${times[@]}")
                ;;
            *calls*)
                api_calls_times=("${times[@]}")
                ;;
            *appointments*)
                api_appointments_times=("${times[@]}")
                ;;
        esac
    done
fi

# Function to calculate statistics
calculate_stats() {
    local -n arr=$1
    local count=${#arr[@]}
    
    if [ $count -eq 0 ]; then
        echo "0,0,0,0,0"
        return
    fi
    
    # Convert to milliseconds and sort
    local times_ms=()
    for time in "${arr[@]}"; do
        times_ms+=($(echo "$time * 1000" | bc -l | cut -d'.' -f1))
    done
    
    IFS=$'\n' sorted=($(sort -n <<<"${times_ms[*]}"))
    unset IFS
    
    local sum=0
    for time in "${sorted[@]}"; do
        sum=$((sum + time))
    done
    
    local avg=$((sum / count))
    local min=${sorted[0]}
    local max=${sorted[-1]}
    local p95_index=$(((count * 95) / 100))
    local p95=${sorted[$p95_index]}
    
    echo "$avg,$min,$max,$p95,$count"
}

echo ""
echo -e "${BLUE}📈 PERFORMANCE BENCHMARK RESULTS${NC}"
echo "=================================="

# Login Performance
if [ ${#login_page_times[@]} -gt 0 ]; then
    echo ""
    echo -e "${GREEN}🔐 LOGIN PERFORMANCE:${NC}"
    
    page_stats=($(calculate_stats login_page_times))
    IFS=',' read -r avg min max p95 count <<< "${page_stats[0]}"
    
    echo "  Login Page Load:"
    echo "    Average: ${avg}ms"
    echo "    Min: ${min}ms, Max: ${max}ms"
    echo "    95th Percentile: ${p95}ms"
    echo "    Success Rate: $count/$ITERATIONS ($(((count * 100) / ITERATIONS))%)"
    
    # Performance rating
    if [ $avg -lt 1000 ]; then
        echo -e "    Rating: ${GREEN}🟢 EXCELLENT${NC} (< 1s)"
    elif [ $avg -lt 2000 ]; then
        echo -e "    Rating: ${YELLOW}🟡 GOOD${NC} (< 2s)"
    else
        echo -e "    Rating: ${RED}🔴 NEEDS IMPROVEMENT${NC} (> 2s)"
    fi
fi

if [ ${#login_post_times[@]} -gt 0 ]; then
    post_stats=($(calculate_stats login_post_times))
    IFS=',' read -r avg min max p95 count <<< "${post_stats[0]}"
    
    echo "  Login Form Submission:"
    echo "    Average: ${avg}ms"
    echo "    Min: ${min}ms, Max: ${max}ms"
    echo "    95th Percentile: ${p95}ms"
    echo "    Success Rate: $count/$ITERATIONS ($(((count * 100) / ITERATIONS))%)"
fi

# Dashboard Performance
if [ ${#dashboard_times[@]} -gt 0 ]; then
    echo ""
    echo -e "${GREEN}📊 DASHBOARD PERFORMANCE:${NC}"
    
    dash_stats=($(calculate_stats dashboard_times))
    IFS=',' read -r avg min max p95 count <<< "${dash_stats[0]}"
    
    echo "  Dashboard Load Time:"
    echo "    Average: ${avg}ms"
    echo "    Min: ${min}ms, Max: ${max}ms"
    echo "    95th Percentile: ${p95}ms"
    echo "    Success Rate: $count/$ITERATIONS ($(((count * 100) / ITERATIONS))%)"
    
    # Performance rating
    if [ $avg -lt 1500 ]; then
        echo -e "    Rating: ${GREEN}🟢 EXCELLENT${NC} (< 1.5s)"
    elif [ $avg -lt 3000 ]; then
        echo -e "    Rating: ${YELLOW}🟡 GOOD${NC} (< 3s)"
    else
        echo -e "    Rating: ${RED}🔴 NEEDS IMPROVEMENT${NC} (> 3s)"
    fi
fi

# API Performance
echo ""
echo -e "${GREEN}🌐 API PERFORMANCE:${NC}"

api_names=("Stats API" "Recent Calls API" "Appointments API")
api_arrays=(api_stats_times api_calls_times api_appointments_times)

for i in "${!api_names[@]}"; do
    local -n current_array=${api_arrays[$i]}
    
    if [ ${#current_array[@]} -gt 0 ]; then
        api_stats=($(calculate_stats ${api_arrays[$i]}))
        IFS=',' read -r avg min max p95 count <<< "${api_stats[0]}"
        
        echo "  ${api_names[$i]}:"
        echo "    Average: ${avg}ms"
        echo "    Min: ${min}ms, Max: ${max}ms"
        echo "    95th Percentile: ${p95}ms"
        
        # Performance rating
        if [ $avg -lt 200 ]; then
            echo -e "    Rating: ${GREEN}🟢 EXCELLENT${NC} (< 200ms)"
        elif [ $avg -lt 500 ]; then
            echo -e "    Rating: ${YELLOW}🟡 GOOD${NC} (< 500ms)"
        else
            echo -e "    Rating: ${RED}🔴 NEEDS IMPROVEMENT${NC} (> 500ms)"
        fi
    fi
done

echo ""
echo -e "${BLUE}🏭 INDUSTRY STANDARDS COMPARISON:${NC}"
echo "  Login < 1s: EXCELLENT | < 2s: GOOD | > 2s: POOR"
echo "  Dashboard < 1.5s: EXCELLENT | < 3s: GOOD | > 3s: POOR"
echo "  API < 200ms: EXCELLENT | < 500ms: GOOD | > 500ms: POOR"

echo ""
echo -e "${BLUE}💡 PERFORMANCE RECOMMENDATIONS:${NC}"

recommendations=()

# Check login performance
if [ ${#login_page_times[@]} -gt 0 ]; then
    page_stats=($(calculate_stats login_page_times))
    IFS=',' read -r avg min max p95 count <<< "${page_stats[0]}"
    
    if [ $avg -gt 2000 ]; then
        recommendations+=("🔴 LOGIN: Optimize login page loading - consider CDN, compression, and asset optimization")
    fi
fi

if [ ${#login_post_times[@]} -gt 0 ]; then
    post_stats=($(calculate_stats login_post_times))
    IFS=',' read -r avg min max p95 count <<< "${post_stats[0]}"
    
    if [ $avg -gt 1000 ]; then
        recommendations+=("🔴 LOGIN: Optimize authentication processing - check database queries and session handling")
    fi
fi

# Check dashboard performance
if [ ${#dashboard_times[@]} -gt 0 ]; then
    dash_stats=($(calculate_stats dashboard_times))
    IFS=',' read -r avg min max p95 count <<< "${dash_stats[0]}"
    
    if [ $avg -gt 3000 ]; then
        recommendations+=("🔴 DASHBOARD: Implement lazy loading and code splitting for React components")
    elif [ $avg -gt 1500 ]; then
        recommendations+=("🟡 DASHBOARD: Consider optimizing initial bundle size and resource loading")
    fi
fi

# Check API performance
for i in "${!api_names[@]}"; do
    local -n current_array=${api_arrays[$i]}
    
    if [ ${#current_array[@]} -gt 0 ]; then
        api_stats=($(calculate_stats ${api_arrays[$i]}))
        IFS=',' read -r avg min max p95 count <<< "${api_stats[0]}"
        
        if [ $avg -gt 500 ]; then
            recommendations+=("🔴 API: Optimize ${api_names[$i]} - implement caching or database query optimization")
        fi
    fi
done

if [ ${#recommendations[@]} -eq 0 ]; then
    echo "  🎉 No critical performance issues detected!"
else
    for rec in "${recommendations[@]}"; do
        echo "  $rec"
    done
fi

# Save detailed results
echo ""
echo "📄 Saving detailed results..."

{
    echo "Business Portal Performance Benchmark Results"
    echo "============================================"
    echo "Timestamp: $(date -u +"%Y-%m-%d %H:%M:%S UTC")"
    echo "Base URL: $BASE_URL"
    echo "Iterations: $ITERATIONS"
    echo "Test User: $TEST_EMAIL"
    echo ""
    
    echo "Raw Results:"
    echo "============"
    echo "Login Page Times (seconds): ${login_page_times[*]}"
    echo "Login Post Times (seconds): ${login_post_times[*]}"
    echo "Dashboard Times (seconds): ${dashboard_times[*]}"
    echo "API Stats Times (seconds): ${api_stats_times[*]}"
    echo "API Calls Times (seconds): ${api_calls_times[*]}"
    echo "API Appointments Times (seconds): ${api_appointments_times[*]}"
    
} > "$OUTPUT_DIR/detailed_results.txt"

echo "Results saved to: $OUTPUT_DIR/detailed_results.txt"

# Cleanup
rm -f "$COOKIE_JAR"

echo ""
echo -e "${GREEN}✅ Performance benchmark completed!${NC}"