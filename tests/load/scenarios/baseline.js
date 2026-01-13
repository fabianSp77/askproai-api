/**
 * Baseline Load Test
 *
 * Purpose: Establish baseline metrics with minimal load
 * VUs: 5
 * Duration: 2 minutes
 *
 * Run: k6 run tests/load/scenarios/baseline.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { config, getHeaders, generateCallId, generatePhoneNumber } from '../utils/config.js';
import {
    voiceCheckAvailability,
    voiceCheckCustomer,
    bookingCreate,
    voiceSuccessRate,
    bookingSuccessRate,
    thresholds
} from '../utils/metrics.js';

export const options = {
    vus: 5,
    duration: '2m',
    thresholds: thresholds,
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(50)', 'p(90)', 'p(95)', 'p(99)'],
};

export default function () {
    const baseUrl = config.baseUrl;
    const headers = getHeaders();

    // 1. Health Check
    const healthRes = http.get(`${baseUrl}/api/health`, { headers });
    check(healthRes, {
        'health check status is 200': (r) => r.status === 200,
    });

    sleep(0.5);

    // 2. Check Customer (Voice Hot Path)
    const checkCustomerPayload = JSON.stringify({
        phone_number: generatePhoneNumber(),
        company_id: config.testCompanyId,
    });

    const customerStart = Date.now();
    const customerRes = http.post(`${baseUrl}/api/retell/check-customer`, checkCustomerPayload, { headers });
    const customerDuration = Date.now() - customerStart;

    voiceCheckCustomer.add(customerDuration);
    const customerSuccess = check(customerRes, {
        'check-customer status is 200': (r) => r.status === 200,
        'check-customer duration < 300ms': () => customerDuration < 300,
    });
    voiceSuccessRate.add(customerSuccess);

    sleep(0.5);

    // 3. Check Availability (Voice Hot Path - Cal.com dependent)
    const availabilityPayload = JSON.stringify({
        company_id: config.testCompanyId,
        service_id: '1',
        date_range: {
            start: new Date().toISOString(),
            end: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
        }
    });

    const availStart = Date.now();
    const availRes = http.post(`${baseUrl}/api/retell/check-availability`, availabilityPayload, { headers });
    const availDuration = Date.now() - availStart;

    voiceCheckAvailability.add(availDuration);
    check(availRes, {
        'check-availability status is 200 or 422': (r) => r.status === 200 || r.status === 422,
        'check-availability duration < 500ms': () => availDuration < 500,
    });

    sleep(1);

    // 4. Simulate Retell Webhook (call_started event)
    const callId = generateCallId();
    const webhookPayload = JSON.stringify({
        event: 'call_started',
        call: {
            call_id: callId,
            agent_id: config.retellAgentId,
            from_number: generatePhoneNumber(),
            to_number: config.testPhoneNumber,
            direction: 'inbound',
            call_status: 'ongoing',
        }
    });

    const webhookRes = http.post(`${baseUrl}/api/webhooks/retell`, webhookPayload, { headers });
    check(webhookRes, {
        'webhook status is 200': (r) => r.status === 200,
    });

    sleep(2);
}

export function handleSummary(data) {
    return {
        'stdout': textSummary(data, { indent: ' ', enableColors: true }),
        'tests/load/results/baseline-summary.json': JSON.stringify(data, null, 2),
    };
}

// Text summary helper (ES5 compatible for k6)
function textSummary(data, opts) {
    var rootGroup = data.root_group || {};
    var checks = rootGroup.checks || [];
    var metrics = data.metrics || {};

    var output = '\n=== BASELINE TEST SUMMARY ===\n\n';

    // Check results
    output += 'CHECKS:\n';
    for (var i = 0; i < checks.length; i++) {
        var c = checks[i];
        var status = c.fails === 0 ? '✓' : '✗';
        output += '  ' + status + ' ' + c.name + ': ' + c.passes + '/' + (c.passes + c.fails) + '\n';
    }

    // Key metrics
    output += '\nVOICE HOT PATH METRICS:\n';
    if (metrics.voice_check_customer_duration) {
        var m1 = metrics.voice_check_customer_duration.values;
        output += '  check-customer: p50=' + (m1['p(50)'] ? m1['p(50)'].toFixed(0) : 'N/A') + 'ms p95=' + (m1['p(95)'] ? m1['p(95)'].toFixed(0) : 'N/A') + 'ms p99=' + (m1['p(99)'] ? m1['p(99)'].toFixed(0) : 'N/A') + 'ms\n';
    }
    if (metrics.voice_check_availability_duration) {
        var m2 = metrics.voice_check_availability_duration.values;
        output += '  check-availability: p50=' + (m2['p(50)'] ? m2['p(50)'].toFixed(0) : 'N/A') + 'ms p95=' + (m2['p(95)'] ? m2['p(95)'].toFixed(0) : 'N/A') + 'ms p99=' + (m2['p(99)'] ? m2['p(99)'].toFixed(0) : 'N/A') + 'ms\n';
    }

    output += '\nHTTP METRICS:\n';
    if (metrics.http_req_duration) {
        var m3 = metrics.http_req_duration.values;
        output += '  All requests: p50=' + (m3['p(50)'] ? m3['p(50)'].toFixed(0) : 'N/A') + 'ms p95=' + (m3['p(95)'] ? m3['p(95)'].toFixed(0) : 'N/A') + 'ms p99=' + (m3['p(99)'] ? m3['p(99)'].toFixed(0) : 'N/A') + 'ms\n';
    }
    if (metrics.http_req_failed) {
        output += '  Error rate: ' + (metrics.http_req_failed.values.rate * 100).toFixed(2) + '%\n';
    }

    return output;
}
