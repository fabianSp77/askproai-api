/**
 * Peak Load Test
 *
 * Purpose: Test target capacity (50-100 concurrent calls)
 * VUs: 100
 * Duration: 5 minutes
 *
 * WARNING: This test may cause system degradation. Run during off-peak hours.
 *
 * Run: k6 run tests/load/scenarios/peak-load.js
 */
import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { config, getHeaders, generateCallId, generatePhoneNumber } from '../utils/config.js';
import {
    voiceCheckAvailability,
    voiceCheckCustomer,
    voiceCollectAppointment,
    webhookProcess,
    voiceSuccessRate,
    voiceErrors,
    thresholds
} from '../utils/metrics.js';

export const options = {
    stages: [
        { duration: '1m', target: 50 },   // Ramp to 50 VUs
        { duration: '1m', target: 100 },  // Ramp to 100 VUs
        { duration: '2m', target: 100 },  // Stay at peak
        { duration: '1m', target: 0 },    // Ramp down
    ],
    thresholds: {
        // Relaxed thresholds for peak load
        'voice_check_customer_duration': ['p(95)<500', 'p(99)<1000'],
        'voice_check_availability_duration': ['p(95)<800', 'p(99)<1500'],
        'http_req_failed': ['rate<0.10'], // Allow up to 10% errors at peak
    },
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(50)', 'p(90)', 'p(95)', 'p(99)'],
};

export default function () {
    const baseUrl = config.baseUrl;
    const headers = getHeaders();
    const callId = generateCallId();
    const phoneNumber = generatePhoneNumber();

    // Simplified call flow for high load
    group('Peak Load Call', function () {

        // 1. Check Customer
        const customerPayload = JSON.stringify({
            phone_number: phoneNumber,
            company_id: config.testCompanyId,
        });

        const customerStart = Date.now();
        const customerRes = http.post(`${baseUrl}/api/retell/check-customer`, customerPayload, {
            headers,
            timeout: '5s', // Shorter timeout for load test
        });
        const customerDuration = Date.now() - customerStart;

        voiceCheckCustomer.add(customerDuration);
        const customerSuccess = customerRes.status === 200;
        voiceSuccessRate.add(customerSuccess);
        if (!customerSuccess) voiceErrors.add(1);

        sleep(0.2);

        // 2. Check Availability (most expensive operation)
        const availPayload = JSON.stringify({
            company_id: config.testCompanyId,
            service_id: '1',
            date_range: {
                start: new Date().toISOString(),
                end: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString(), // Shorter range
            }
        });

        const availStart = Date.now();
        const availRes = http.post(`${baseUrl}/api/retell/check-availability`, availPayload, {
            headers,
            timeout: '10s',
        });
        const availDuration = Date.now() - availStart;

        voiceCheckAvailability.add(availDuration);
        const availSuccess = availRes.status === 200 || availRes.status === 422;
        voiceSuccessRate.add(availSuccess);
        if (!availSuccess) voiceErrors.add(1);

        sleep(0.2);

        // 3. Webhook simulation
        const webhookPayload = JSON.stringify({
            event: 'call_started',
            call: {
                call_id: callId,
                agent_id: config.retellAgentId,
                from_number: phoneNumber,
                to_number: config.testPhoneNumber,
                direction: 'inbound',
                call_status: 'ongoing',
            }
        });

        const webhookStart = Date.now();
        const webhookRes = http.post(`${baseUrl}/api/webhooks/retell`, webhookPayload, {
            headers,
            timeout: '5s',
        });
        webhookProcess.add(Date.now() - webhookStart);

        check(webhookRes, { 'webhook OK': (r) => r.status === 200 });
    });

    // Minimal sleep for maximum pressure
    sleep(Math.random() * 0.5 + 0.5); // 0.5-1 second
}

export function handleSummary(data) {
    const metrics = data.metrics || {};

    let output = '\n';
    output += '╔══════════════════════════════════════════════════════════════╗\n';
    output += '║           PEAK LOAD TEST RESULTS (100 VUs)                   ║\n';
    output += '╚══════════════════════════════════════════════════════════════╝\n\n';

    // Voice metrics
    output += '📞 VOICE HOT PATH:\n';
    output += '─────────────────────────────────────────────────────────────────\n';

    const voiceMetrics = [
        { name: 'voice_check_customer_duration', label: 'check-customer', target: 500 },
        { name: 'voice_check_availability_duration', label: 'check-availability', target: 1000 },
    ];

    voiceMetrics.forEach(({ name, label, target }) => {
        if (metrics[name]) {
            const m = metrics[name].values;
            const p99 = m['p(99)'] || 0;
            const status = p99 < target ? '✅' : '⚠️';
            output += `  ${status} ${label.padEnd(20)} p50: ${(m['p(50)'] || 0).toFixed(0).padStart(5)}ms  p95: ${(m['p(95)'] || 0).toFixed(0).padStart(5)}ms  p99: ${p99.toFixed(0).padStart(5)}ms\n`;
        }
    });

    // Error analysis
    output += '\n📊 ERROR ANALYSIS:\n';
    output += '─────────────────────────────────────────────────────────────────\n';

    if (metrics.voice_success_rate) {
        const rate = metrics.voice_success_rate.values.rate * 100;
        const status = rate > 90 ? '✅' : '⚠️';
        output += `  ${status} Success rate: ${rate.toFixed(2)}%\n`;
    }
    if (metrics.voice_errors) {
        output += `  Total errors: ${metrics.voice_errors.values.count}\n`;
    }
    if (metrics.http_req_failed) {
        const failRate = metrics.http_req_failed.values.rate * 100;
        output += `  HTTP error rate: ${failRate.toFixed(2)}%\n`;
    }

    // Throughput
    output += '\n⚡ THROUGHPUT:\n';
    output += '─────────────────────────────────────────────────────────────────\n';
    if (metrics.http_reqs) {
        output += `  Total requests: ${metrics.http_reqs.values.count}\n`;
        output += `  Requests/sec: ${metrics.http_reqs.values.rate?.toFixed(2)}\n`;
    }

    // Saturation warning
    const p99Avail = metrics.voice_check_availability_duration?.values['p(99)'] || 0;
    if (p99Avail > 1500) {
        output += '\n⚠️  WARNING: System showing signs of saturation!\n';
        output += '   Consider scaling workers or optimizing Cal.com API calls.\n';
    }

    return output + '\n';
}
