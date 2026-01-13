/**
 * Normal Load Test
 *
 * Purpose: Simulate typical production load (30 concurrent calls)
 * VUs: 30
 * Duration: 5 minutes
 *
 * Run: k6 run tests/load/scenarios/normal-load.js
 */
import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { config, getHeaders, generateCallId, generatePhoneNumber } from '../utils/config.js';
import {
    voiceCheckAvailability,
    voiceCheckCustomer,
    voiceCollectAppointment,
    bookingCreate,
    webhookProcess,
    voiceSuccessRate,
    bookingSuccessRate,
    voiceErrors,
    bookingErrors,
    thresholds
} from '../utils/metrics.js';

export const options = {
    stages: [
        { duration: '30s', target: 30 },  // Ramp up
        { duration: '4m', target: 30 },   // Stay at 30 VUs
        { duration: '30s', target: 0 },   // Ramp down
    ],
    thresholds: thresholds,
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(50)', 'p(90)', 'p(95)', 'p(99)'],
};

export default function () {
    const baseUrl = config.baseUrl;
    const headers = getHeaders();
    const callId = generateCallId();
    const phoneNumber = generatePhoneNumber();

    // Simulate a complete call flow
    group('Voice Call Flow', function () {

        // 1. Call Started Webhook
        group('call_started', function () {
            const payload = JSON.stringify({
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

            const start = Date.now();
            const res = http.post(`${baseUrl}/api/webhooks/retell`, payload, { headers });
            webhookProcess.add(Date.now() - start);

            check(res, { 'call_started webhook OK': (r) => r.status === 200 });
        });

        sleep(0.5);

        // 2. Check Customer (simulates Retell function call)
        group('check_customer', function () {
            const payload = JSON.stringify({
                phone_number: phoneNumber,
                company_id: config.testCompanyId,
            });

            const start = Date.now();
            const res = http.post(`${baseUrl}/api/retell/check-customer`, payload, { headers });
            const duration = Date.now() - start;

            voiceCheckCustomer.add(duration);
            const success = check(res, {
                'check-customer OK': (r) => r.status === 200,
                'check-customer < 300ms': () => duration < 300,
            });

            voiceSuccessRate.add(success);
            if (!success) voiceErrors.add(1);
        });

        sleep(0.3);

        // 3. Check Availability (Cal.com API call)
        group('check_availability', function () {
            const payload = JSON.stringify({
                company_id: config.testCompanyId,
                service_id: '1',
                date_range: {
                    start: new Date().toISOString(),
                    end: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
                }
            });

            const start = Date.now();
            const res = http.post(`${baseUrl}/api/retell/check-availability`, payload, { headers });
            const duration = Date.now() - start;

            voiceCheckAvailability.add(duration);
            const success = check(res, {
                'check-availability OK': (r) => r.status === 200 || r.status === 422,
                'check-availability < 500ms': () => duration < 500,
            });

            voiceSuccessRate.add(success);
            if (!success) voiceErrors.add(1);
        });

        sleep(0.5);

        // 4. Collect Appointment Info
        group('collect_appointment', function () {
            const payload = JSON.stringify({
                call_id: callId,
                customer_name: 'Load Test User',
                customer_phone: phoneNumber,
                service_id: '1',
                preferred_date: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                preferred_time: '10:00',
                notes: 'Load test appointment',
            });

            const start = Date.now();
            const res = http.post(`${baseUrl}/api/retell/collect-appointment`, payload, { headers });
            const duration = Date.now() - start;

            voiceCollectAppointment.add(duration);
            check(res, {
                'collect-appointment OK': (r) => r.status === 200 || r.status === 422,
                'collect-appointment < 400ms': () => duration < 400,
            });
        });

        sleep(1);

        // 5. Call Ended Webhook
        group('call_ended', function () {
            const payload = JSON.stringify({
                event: 'call_ended',
                call: {
                    call_id: callId,
                    agent_id: config.retellAgentId,
                    from_number: phoneNumber,
                    to_number: config.testPhoneNumber,
                    direction: 'inbound',
                    call_status: 'ended',
                    end_timestamp: Date.now(),
                    disconnection_reason: 'user_hangup',
                }
            });

            const start = Date.now();
            const res = http.post(`${baseUrl}/api/webhooks/retell`, payload, { headers });
            webhookProcess.add(Date.now() - start);

            check(res, { 'call_ended webhook OK': (r) => r.status === 200 });
        });
    });

    // Wait between call simulations
    sleep(Math.random() * 2 + 1); // 1-3 seconds
}

export function handleSummary(data) {
    return {
        'stdout': generateSummary(data),
        'tests/load/results/normal-load-summary.json': JSON.stringify(data, null, 2),
    };
}

function generateSummary(data) {
    const metrics = data.metrics || {};

    let output = '\n=== NORMAL LOAD TEST SUMMARY (30 VUs) ===\n\n';

    output += 'VOICE HOT PATH (target: p99 < 500ms):\n';
    ['voice_check_customer_duration', 'voice_check_availability_duration', 'voice_collect_appointment_duration'].forEach(name => {
        if (metrics[name]) {
            const m = metrics[name].values;
            const status = m['p(99)'] < 500 ? '✓' : '✗';
            output += `  ${status} ${name.replace('voice_', '').replace('_duration', '')}: p50=${m['p(50)']?.toFixed(0)}ms p95=${m['p(95)']?.toFixed(0)}ms p99=${m['p(99)']?.toFixed(0)}ms\n`;
        }
    });

    output += '\nWEBHOOK PROCESSING:\n';
    if (metrics.webhook_process_duration) {
        const m = metrics.webhook_process_duration.values;
        output += `  webhooks: p50=${m['p(50)']?.toFixed(0)}ms p95=${m['p(95)']?.toFixed(0)}ms p99=${m['p(99)']?.toFixed(0)}ms\n`;
    }

    output += '\nSUCCESS RATES:\n';
    if (metrics.voice_success_rate) {
        const rate = (metrics.voice_success_rate.values.rate * 100).toFixed(2);
        const status = rate > 99 ? '✓' : '✗';
        output += `  ${status} Voice calls: ${rate}%\n`;
    }

    output += '\nERROR COUNTS:\n';
    if (metrics.voice_errors) {
        output += `  Voice errors: ${metrics.voice_errors.values.count}\n`;
    }

    return output;
}
