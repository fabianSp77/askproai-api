/**
 * Peak Load Test - 100 VUs
 * Find system capacity limits
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

var voiceLatency = new Trend('voice_latency', true);
var successRate = new Rate('success_rate');
var totalRequests = new Counter('total_requests');

var baseUrl = __ENV.K6_BASE_URL || 'https://api.askproai.de';
var companyId = __ENV.K6_TEST_COMPANY_ID || '1';

export var options = {
    scenarios: {
        peak_load: {
            executor: 'ramping-vus',
            startVUs: 10,
            stages: [
                { duration: '30s', target: 50 },   // Ramp up to 50
                { duration: '30s', target: 100 },  // Ramp up to 100
                { duration: '2m', target: 100 },   // Stay at 100
                { duration: '30s', target: 0 },    // Ramp down
            ],
        }
    },
    thresholds: {
        'voice_latency': ['p(99)<500'],  // Voice apps need < 500ms
        'http_req_duration': ['p(95)<500'],
    },
};

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

    // Test check-customer endpoint (primary voice endpoint)
    var payload = JSON.stringify({
        phone_number: generatePhone(),
        company_id: companyId
    });

    var start = Date.now();
    var res = http.post(baseUrl + '/api/retell/check-customer', payload, { headers: headers });
    var duration = Date.now() - start;

    voiceLatency.add(duration);
    totalRequests.add(1);

    var ok = check(res, {
        'status 200': function(r) { return r.status === 200; },
        'latency < 500ms': function() { return duration < 500; }
    });
    successRate.add(ok);

    sleep(0.2);
}

export function handleSummary(data) {
    var metrics = data.metrics || {};
    var output = '\n=== PEAK LOAD TEST SUMMARY (100 VUs) ===\n\n';

    output += 'LATENCY:\n';
    if (metrics.voice_latency) {
        var m = metrics.voice_latency.values;
        output += '  p50: ' + (m['p(50)'] ? m['p(50)'].toFixed(0) : 'N/A') + 'ms\n';
        output += '  p95: ' + (m['p(95)'] ? m['p(95)'].toFixed(0) : 'N/A') + 'ms\n';
        output += '  p99: ' + (m['p(99)'] ? m['p(99)'].toFixed(0) : 'N/A') + 'ms\n';
        output += '  avg: ' + (m['avg'] ? m['avg'].toFixed(0) : 'N/A') + 'ms\n';
        output += '  max: ' + (m['max'] ? m['max'].toFixed(0) : 'N/A') + 'ms\n';
    }

    output += '\nTHROUGHPUT:\n';
    if (metrics.total_requests) {
        output += '  Total: ' + metrics.total_requests.values.count + ' requests\n';
    }
    if (metrics.http_reqs) {
        output += '  Rate: ' + (metrics.http_reqs.values.rate ? metrics.http_reqs.values.rate.toFixed(2) : 'N/A') + ' req/sec\n';
    }

    output += '\nERRORS:\n';
    if (metrics.http_req_failed) {
        output += '  HTTP errors: ' + (metrics.http_req_failed.values.rate * 100).toFixed(2) + '%\n';
    }
    if (metrics.success_rate) {
        output += '  Success rate: ' + (metrics.success_rate.values.rate * 100).toFixed(2) + '%\n';
    }

    console.log(output);

    return {
        'tests/load/results/peak-load-summary.json': JSON.stringify(data, null, 2),
    };
}
