/**
 * Voice Hot Path Load Test
 *
 * Tests the critical voice call endpoints:
 * - /api/health
 * - /api/retell/check-customer
 * - /api/retell/check-availability (optional)
 *
 * Target: < 500ms p99 latency for voice applications
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

// Custom metrics for voice latency tracking
const voiceCheckCustomer = new Trend('voice_check_customer_duration', true);
const voiceSuccessRate = new Rate('voice_success_rate');
const voiceRequests = new Counter('voice_requests');

// Configuration
var baseUrl = __ENV.K6_BASE_URL || 'https://api.askproai.de';
var companyId = __ENV.K6_TEST_COMPANY_ID || '1';

export var options = {
    scenarios: {
        normal_load: {
            executor: 'constant-vus',
            vus: 30,
            duration: '5m',
        }
    },
    thresholds: {
        'voice_check_customer_duration': ['p(99)<300'],
        'voice_success_rate': ['rate>0.95'],
        'http_req_failed': ['rate<0.05'],
        'http_req_duration': ['p(95)<500'],
    },
};

// Generate random phone number
function generatePhone() {
    var prefix = '+49176';
    var num = Math.floor(10000000 + Math.random() * 90000000);
    return prefix + num.toString();
}

export default function() {
    var headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };

    // 1. Health check (lightweight)
    var healthRes = http.get(baseUrl + '/api/health', { headers: headers });
    check(healthRes, {
        'health status 200': function(r) { return r.status === 200; }
    });

    sleep(0.3);

    // 2. Check Customer (Primary voice endpoint)
    var payload = JSON.stringify({
        phone_number: generatePhone(),
        company_id: companyId
    });

    var start = Date.now();
    var res = http.post(baseUrl + '/api/retell/check-customer', payload, { headers: headers });
    var duration = Date.now() - start;

    voiceCheckCustomer.add(duration);
    voiceRequests.add(1);

    var success = check(res, {
        'check-customer status 200': function(r) { return r.status === 200; },
        'check-customer < 300ms': function() { return duration < 300; }
    });
    voiceSuccessRate.add(success);

    sleep(0.5);
}

export function handleSummary(data) {
    var metrics = data.metrics || {};
    var output = '\n=== VOICE HOT PATH TEST SUMMARY (30 VUs) ===\n\n';

    output += 'PERFORMANCE:\n';
    if (metrics.voice_check_customer_duration) {
        var m = metrics.voice_check_customer_duration.values;
        output += '  check-customer:\n';
        output += '    p50: ' + (m['p(50)'] ? m['p(50)'].toFixed(0) : 'N/A') + 'ms\n';
        output += '    p95: ' + (m['p(95)'] ? m['p(95)'].toFixed(0) : 'N/A') + 'ms\n';
        output += '    p99: ' + (m['p(99)'] ? m['p(99)'].toFixed(0) : 'N/A') + 'ms\n';
        output += '    avg: ' + (m['avg'] ? m['avg'].toFixed(0) : 'N/A') + 'ms\n';
    }

    output += '\nTHROUGHPUT:\n';
    if (metrics.voice_requests) {
        output += '  Total requests: ' + metrics.voice_requests.values.count + '\n';
    }
    if (metrics.http_reqs) {
        output += '  Requests/sec: ' + (metrics.http_reqs.values.rate ? metrics.http_reqs.values.rate.toFixed(2) : 'N/A') + '\n';
    }

    output += '\nSUCCESS RATE:\n';
    if (metrics.voice_success_rate) {
        output += '  Voice calls: ' + (metrics.voice_success_rate.values.rate * 100).toFixed(2) + '%\n';
    }
    if (metrics.http_req_failed) {
        output += '  HTTP errors: ' + (metrics.http_req_failed.values.rate * 100).toFixed(2) + '%\n';
    }

    output += '\nTHRESHOLDS:\n';
    var thresholds = data.thresholds || {};
    for (var key in thresholds) {
        var passed = thresholds[key].ok ? 'PASS' : 'FAIL';
        output += '  ' + key + ': ' + passed + '\n';
    }

    console.log(output);

    return {
        'tests/load/results/voice-hotpath-summary.json': JSON.stringify(data, null, 2),
    };
}
