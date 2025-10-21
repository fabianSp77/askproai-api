import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';
import { htmlReport } from "https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js";

// Custom metrics
const errorRate = new Rate('errors');
const bookingSuccessRate = new Rate('booking_success');
const apiLatency = new Trend('api_latency');
const concurrentBookingConflicts = new Counter('concurrent_booking_conflicts');

export const options = {
    scenarios: {
        // Scenario 1: Gradual load increase
        gradual_load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '2m', target: 10 },   // Warm up
                { duration: '5m', target: 50 },   // Moderate load
                { duration: '10m', target: 100 }, // Peak load
                { duration: '5m', target: 50 },   // Ramp down
                { duration: '2m', target: 0 },    // Cool down
            ],
            gracefulRampDown: '30s',
        },

        // Scenario 2: Spike test
        spike_test: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '30s', target: 10 },  // Normal load
                { duration: '30s', target: 200 }, // Sudden spike
                { duration: '1m', target: 200 },  // Hold spike
                { duration: '30s', target: 10 },  // Return to normal
            ],
            startTime: '30m', // Run after gradual load
        },

        // Scenario 3: Stress test - find breaking point
        stress_test: {
            executor: 'ramping-arrival-rate',
            startRate: 10,
            timeUnit: '1m',
            preAllocatedVUs: 50,
            maxVUs: 500,
            stages: [
                { duration: '5m', target: 50 },   // 50 requests/min
                { duration: '5m', target: 100 },  // 100 requests/min
                { duration: '5m', target: 200 },  // 200 requests/min
                { duration: '5m', target: 300 },  // 300 requests/min
                { duration: '5m', target: 400 },  // 400 requests/min - find limit
            ],
            startTime: '45m', // Run after spike test
        }
    },

    thresholds: {
        // Availability
        'http_req_failed': ['rate<0.05'],  // <5% error rate

        // Performance
        'http_req_duration': ['p(95)<5000'], // P95 < 5s
        'api_latency': ['p(99)<10000'],     // P99 < 10s

        // Success rates
        'booking_success': ['rate>0.90'],   // 90% success rate under load

        // Error handling
        'errors': ['rate<0.10'],  // <10% errors
    },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

/**
 * Load test main scenario
 */
export default function () {
    group('Booking Flow Under Load', function () {
        const userId = `load_test_${__VU}_${__ITER}`;

        // Check availability
        const availabilityResponse = http.post(
            `${BASE_URL}/api/retell/check-availability`,
            JSON.stringify({
                args: {
                    datum: getNextBusinessDay(),
                    uhrzeit: '14:00'
                }
            }),
            {
                headers: { 'Content-Type': 'application/json' },
                tags: { name: 'availability_under_load' }
            }
        );

        apiLatency.add(availabilityResponse.timings.duration);

        const availSuccess = check(availabilityResponse, {
            'availability check successful': (r) => r.status === 200,
        });

        if (!availSuccess) {
            errorRate.add(1);
            return;
        }

        sleep(1); // Small delay

        // Attempt booking
        const bookingResponse = http.post(
            `${BASE_URL}/api/retell/collect-appointment`,
            JSON.stringify({
                args: {
                    datum: getNextBusinessDay(),
                    uhrzeit: '14:00',
                    name: `Load Test User ${__VU}`,
                    telefon: `+49151${String(__VU).padStart(8, '0')}`,
                    dienstleistung: 'Beratung',
                    bestaetigung: true,
                    call_id: `call_${userId}`
                }
            }),
            {
                headers: { 'Content-Type': 'application/json' },
                tags: { name: 'booking_under_load' }
            }
        );

        apiLatency.add(bookingResponse.timings.duration);

        const bookingCheck = check(bookingResponse, {
            'booking request processed': (r) => r.status === 200,
            'booking response valid JSON': (r) => {
                try {
                    JSON.parse(r.body);
                    return true;
                } catch {
                    return false;
                }
            }
        });

        if (bookingCheck) {
            try {
                const body = JSON.parse(bookingResponse.body);

                if (body.success) {
                    bookingSuccessRate.add(1);
                } else {
                    bookingSuccessRate.add(0);

                    // Track concurrent booking conflicts
                    if (body.status === 'slot_taken' || body.reason === 'race_condition_detected') {
                        concurrentBookingConflicts.add(1);
                    }
                }
            } catch (e) {
                errorRate.add(1);
            }
        } else {
            errorRate.add(1);
        }
    });

    sleep(1);
}

/**
 * Smoke test - quick sanity check before load test
 */
export function setup() {
    console.log('Running smoke test before load test...');

    const smokeTestResponse = http.get(`${BASE_URL}/api/health`);

    if (smokeTestResponse.status !== 200) {
        throw new Error('Smoke test failed - application not healthy');
    }

    console.log('Smoke test passed, starting load test');
}

/**
 * Teardown - clean up test data
 */
export function teardown(data) {
    console.log('Load test completed');

    // Could trigger cleanup endpoint here
    // http.post(`${BASE_URL}/api/test/cleanup`, ...);
}

export function handleSummary(data) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');

    return {
        [`load-test-report-${timestamp}.html`]: htmlReport(data),
        [`load-test-summary-${timestamp}.json`]: JSON.stringify(data),
        stdout: textSummary(data, { indent: ' ', enableColors: true }),
    };
}

// Helper functions
function getNextBusinessDay() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dayOfWeek = tomorrow.getDay();
    if (dayOfWeek === 0) tomorrow.setDate(tomorrow.getDate() + 1);
    else if (dayOfWeek === 6) tomorrow.setDate(tomorrow.getDate() + 2);
    return tomorrow.toISOString().split('T')[0];
}

function textSummary(data, options) {
    // Simple text summary for stdout
    return `
Load Test Summary
=================
Total Requests: ${data.metrics.http_reqs.values.count}
Failed Requests: ${data.metrics.http_req_failed.values.rate * 100}%
Avg Response Time: ${data.metrics.http_req_duration.values.avg}ms
P95 Response Time: ${data.metrics['http_req_duration{p(95)}']}ms
Booking Success Rate: ${data.metrics.booking_success?.values?.rate * 100 || 0}%
Concurrent Conflicts: ${data.metrics.concurrent_booking_conflicts?.values?.count || 0}
    `;
}
