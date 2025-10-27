/**
 * k6 Load Test - Customer Portal
 *
 * Purpose: Verify customer portal can handle 100+ concurrent users
 * Target: 95th percentile response time < 2s, error rate < 5%
 *
 * Usage:
 *   # Install k6: sudo apt install k6 (or from https://k6.io/docs/getting-started/installation/)
 *
 *   # Run smoke test (10 VUs, 1 minute)
 *   k6 run --vus 10 --duration 1m tests/load/customer_portal_load_test.js
 *
 *   # Run load test (100 VUs, 5 minutes)
 *   k6 run --vus 100 --duration 5m tests/load/customer_portal_load_test.js
 *
 *   # Run stress test (ramp up to 200 VUs)
 *   k6 run --stage 1m:50 --stage 2m:100 --stage 2m:150 --stage 1m:200 tests/load/customer_portal_load_test.js
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom Metrics
const loginFailures = new Rate('login_failures');
const portalAccessFailures = new Rate('portal_access_failures');
const apiErrors = new Rate('api_errors');
const responseTime = new Trend('response_time');
const sessionCount = new Counter('sessions_created');

// Configuration
const BASE_URL = __ENV.BASE_URL || 'https://staging.askproai.de';
const TEST_EMAIL = __ENV.TEST_EMAIL || 'customer@staging.local';
const TEST_PASSWORD = __ENV.TEST_PASSWORD || 'TestPass123!';

// Load Test Stages (can be overridden with CLI)
export const options = {
    stages: [
        { duration: '1m', target: 20 },   // Ramp up to 20 users
        { duration: '3m', target: 50 },   // Ramp up to 50 users
        { duration: '5m', target: 100 },  // Ramp up to 100 users
        { duration: '3m', target: 100 },  // Stay at 100 users
        { duration: '2m', target: 50 },   // Ramp down to 50
        { duration: '1m', target: 0 },    // Ramp down to 0
    ],
    thresholds: {
        // 95% of requests must complete within 2s
        'http_req_duration': ['p(95)<2000'],

        // Error rate must be below 5%
        'http_req_failed': ['rate<0.05'],

        // Login success rate must be above 95%
        'login_failures': ['rate<0.05'],

        // Portal access success rate must be above 95%
        'portal_access_failures': ['rate<0.05'],

        // API error rate must be below 5%
        'api_errors': ['rate<0.05'],
    },
};

// Test Data
const testUsers = [
    { email: 'customer@staging.local', password: 'TestPass123!' },
    { email: 'staff-a@test.local', password: 'TestPass123!' },
    { email: 'owner-a@test.local', password: 'TestPass123!' },
];

/**
 * Main Test Scenario
 */
export default function () {
    // Select random test user
    const user = testUsers[Math.floor(Math.random() * testUsers.length)];

    // 1. Login Flow
    const loginSuccess = performLogin(user.email, user.password);

    if (!loginSuccess) {
        loginFailures.add(1);
        sleep(1);
        return;
    }

    loginFailures.add(0);
    sessionCount.add(1);

    // 2. Access Customer Portal Dashboard
    accessPortalDashboard();

    // 3. Load Call Sessions (75% of users)
    if (Math.random() < 0.75) {
        loadCallSessions();
    }

    // 4. Load Appointments (50% of users)
    if (Math.random() < 0.50) {
        loadAppointments();
    }

    // 5. View Call Details (25% of users)
    if (Math.random() < 0.25) {
        viewCallDetails();
    }

    // 6. Logout
    performLogout();

    // Think time between scenarios
    sleep(Math.random() * 3 + 2); // 2-5 seconds
}

/**
 * Login Flow
 */
function performLogin(email, password) {
    const startTime = Date.now();

    // Get CSRF token
    let res = http.get(`${BASE_URL}/portal`);

    const csrfToken = res.body.match(/name="_token" value="([^"]+)"/);
    if (!csrfToken) {
        console.error('CSRF token not found');
        return false;
    }

    // Perform login
    res = http.post(`${BASE_URL}/login`, {
        email: email,
        password: password,
        _token: csrfToken[1],
    }, {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirects: 0, // Don't follow redirects automatically
    });

    const duration = Date.now() - startTime;
    responseTime.add(duration);

    const success = check(res, {
        'login successful': (r) => r.status === 302 && r.headers['Location']?.includes('/portal'),
        'login response time < 1s': (r) => duration < 1000,
    });

    return success;
}

/**
 * Access Portal Dashboard
 */
function accessPortalDashboard() {
    const startTime = Date.now();

    const res = http.get(`${BASE_URL}/portal`, {
        headers: {
            'Accept': 'text/html',
        },
    });

    const duration = Date.now() - startTime;
    responseTime.add(duration);

    const success = check(res, {
        'portal dashboard loads': (r) => r.status === 200,
        'portal has content': (r) => r.body.includes('dashboard') || r.body.includes('Portal'),
        'dashboard response time < 2s': (r) => duration < 2000,
    });

    if (!success) {
        portalAccessFailures.add(1);
    } else {
        portalAccessFailures.add(0);
    }

    sleep(1);
}

/**
 * Load Call Sessions (API)
 */
function loadCallSessions() {
    const startTime = Date.now();

    const res = http.get(`${BASE_URL}/api/customer-portal/call-sessions`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const duration = Date.now() - startTime;
    responseTime.add(duration);

    const success = check(res, {
        'call sessions API responds': (r) => r.status === 200,
        'call sessions returns JSON': (r) => {
            try {
                JSON.parse(r.body);
                return true;
            } catch {
                return false;
            }
        },
        'call sessions response time < 1s': (r) => duration < 1000,
    });

    if (!success) {
        apiErrors.add(1);
    } else {
        apiErrors.add(0);
    }

    sleep(0.5);
}

/**
 * Load Appointments (API)
 */
function loadAppointments() {
    const startTime = Date.now();

    const res = http.get(`${BASE_URL}/api/customer-portal/appointments`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const duration = Date.now() - startTime;
    responseTime.add(duration);

    const success = check(res, {
        'appointments API responds': (r) => r.status === 200,
        'appointments returns JSON': (r) => {
            try {
                JSON.parse(r.body);
                return true;
            } catch {
                return false;
            }
        },
        'appointments response time < 1s': (r) => duration < 1000,
    });

    if (!success) {
        apiErrors.add(1);
    } else {
        apiErrors.add(0);
    }

    sleep(0.5);
}

/**
 * View Call Details
 */
function viewCallDetails() {
    // First, get a call session ID
    const sessionsRes = http.get(`${BASE_URL}/api/customer-portal/call-sessions?limit=1`, {
        headers: {
            'Accept': 'application/json',
        },
    });

    if (sessionsRes.status !== 200) {
        return;
    }

    let sessions;
    try {
        sessions = JSON.parse(sessionsRes.body);
    } catch {
        return;
    }

    if (!sessions.data || sessions.data.length === 0) {
        return;
    }

    const callId = sessions.data[0].id;

    // Load call details
    const startTime = Date.now();

    const res = http.get(`${BASE_URL}/api/customer-portal/call-sessions/${callId}`, {
        headers: {
            'Accept': 'application/json',
        },
    });

    const duration = Date.now() - startTime;
    responseTime.add(duration);

    check(res, {
        'call details loads': (r) => r.status === 200,
        'call details response time < 1.5s': (r) => duration < 1500,
    });

    sleep(1);
}

/**
 * Logout
 */
function performLogout() {
    http.post(`${BASE_URL}/logout`, {}, {
        redirects: 0,
    });

    sleep(0.5);
}

/**
 * Setup (runs once per VU)
 */
export function setup() {
    console.log('🚀 Starting Customer Portal Load Test');
    console.log(`   Base URL: ${BASE_URL}`);
    console.log(`   Test Users: ${testUsers.length}`);
    console.log('');

    // Verify staging is accessible
    const healthCheck = http.get(BASE_URL);

    if (healthCheck.status !== 200 && healthCheck.status !== 302) {
        console.error('❌ Staging environment not accessible');
        throw new Error('Cannot reach staging environment');
    }

    console.log('✅ Staging environment is accessible');
    console.log('');

    return {
        startTime: new Date().toISOString(),
    };
}

/**
 * Teardown (runs once after all VUs)
 */
export function teardown(data) {
    console.log('');
    console.log('🏁 Load Test Complete');
    console.log(`   Started: ${data.startTime}`);
    console.log(`   Ended: ${new Date().toISOString()}`);
}

/**
 * Custom Summary Handler
 */
export function handleSummary(data) {
    const summary = {
        'stdout': textSummary(data, { indent: '  ', enableColors: true }),
        'summary.json': JSON.stringify(data, null, 2),
    };

    return summary;
}

// Text Summary Helper
function textSummary(data, options = {}) {
    const indent = options.indent || '';
    const enableColors = options.enableColors !== false;

    const GREEN = enableColors ? '\x1b[32m' : '';
    const RED = enableColors ? '\x1b[31m' : '';
    const YELLOW = enableColors ? '\x1b[33m' : '';
    const RESET = enableColors ? '\x1b[0m' : '';

    let output = '\n';
    output += `${indent}═══════════════════════════════════════════════════════\n`;
    output += `${indent}   LOAD TEST SUMMARY\n`;
    output += `${indent}═══════════════════════════════════════════════════════\n\n`;

    // HTTP Metrics
    const httpReqs = data.metrics.http_reqs?.values?.count || 0;
    const httpFailed = data.metrics.http_req_failed?.values?.rate || 0;
    const httpDuration = data.metrics.http_req_duration?.values;

    output += `${indent}HTTP Requests:\n`;
    output += `${indent}  Total: ${httpReqs}\n`;
    output += `${indent}  Failed: ${(httpFailed * 100).toFixed(2)}% ${httpFailed < 0.05 ? GREEN + '✓' + RESET : RED + '✗' + RESET}\n`;
    output += `${indent}  Avg Duration: ${httpDuration?.avg?.toFixed(2)}ms\n`;
    output += `${indent}  P95 Duration: ${httpDuration?.['p(95)']?.toFixed(2)}ms ${httpDuration?.['p(95)'] < 2000 ? GREEN + '✓' + RESET : RED + '✗' + RESET}\n`;
    output += `${indent}  P99 Duration: ${httpDuration?.['p(99)']?.toFixed(2)}ms\n`;
    output += '\n';

    // Custom Metrics
    const loginFailRate = data.metrics.login_failures?.values?.rate || 0;
    const portalFailRate = data.metrics.portal_access_failures?.values?.rate || 0;
    const apiErrorRate = data.metrics.api_errors?.values?.rate || 0;
    const sessions = data.metrics.sessions_created?.values?.count || 0;

    output += `${indent}Custom Metrics:\n`;
    output += `${indent}  Sessions Created: ${sessions}\n`;
    output += `${indent}  Login Failures: ${(loginFailRate * 100).toFixed(2)}% ${loginFailRate < 0.05 ? GREEN + '✓' + RESET : RED + '✗' + RESET}\n`;
    output += `${indent}  Portal Access Failures: ${(portalFailRate * 100).toFixed(2)}% ${portalFailRate < 0.05 ? GREEN + '✓' + RESET : RED + '✗' + RESET}\n`;
    output += `${indent}  API Errors: ${(apiErrorRate * 100).toFixed(2)}% ${apiErrorRate < 0.05 ? GREEN + '✓' + RESET : RED + '✗' + RESET}\n`;
    output += '\n';

    // Verdict
    const allPassed = httpFailed < 0.05 &&
                      loginFailRate < 0.05 &&
                      portalFailRate < 0.05 &&
                      apiErrorRate < 0.05 &&
                      httpDuration?.['p(95)'] < 2000;

    if (allPassed) {
        output += `${indent}${GREEN}✅ All thresholds passed!${RESET}\n`;
    } else {
        output += `${indent}${RED}❌ Some thresholds failed${RESET}\n`;
    }

    output += '\n';

    return output;
}
