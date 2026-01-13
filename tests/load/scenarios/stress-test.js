/**
 * Stress Test
 *
 * Purpose: Find breaking point and saturation
 * VUs: 200 (beyond expected capacity)
 * Duration: 10 minutes
 *
 * ⚠️  WARNING: This test WILL cause system degradation!
 * Run only with Cal.com/Retell mocking enabled or during maintenance windows.
 *
 * Run: K6_MOCK_MODE=true k6 run tests/load/scenarios/stress-test.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Counter, Rate } from 'k6/metrics';
import { config, getHeaders, generateCallId, generatePhoneNumber } from '../utils/config.js';

// Custom metrics for stress analysis
const responseTime = new Trend('stress_response_time', true);
const errorCount = new Counter('stress_errors');
const successRate = new Rate('stress_success_rate');
const timeoutCount = new Counter('stress_timeouts');

export const options = {
    stages: [
        { duration: '1m', target: 50 },    // Warm up
        { duration: '2m', target: 100 },   // Normal peak
        { duration: '3m', target: 200 },   // Beyond capacity
        { duration: '2m', target: 200 },   // Hold at stress level
        { duration: '2m', target: 0 },     // Recovery
    ],
    // No thresholds - we want to observe failures
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(50)', 'p(90)', 'p(95)', 'p(99)'],
};

// Mock mode check
const MOCK_MODE = __ENV.K6_MOCK_MODE === 'true';

export default function () {
    const baseUrl = config.baseUrl;
    const headers = getHeaders();
    const phoneNumber = generatePhoneNumber();

    // Primary stress target: check-availability (most expensive)
    const payload = JSON.stringify({
        company_id: config.testCompanyId,
        service_id: '1',
        date_range: {
            start: new Date().toISOString(),
            end: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString(),
        }
    });

    const start = Date.now();
    let res;

    try {
        if (MOCK_MODE) {
            // Use health endpoint as mock target (doesn't hit Cal.com)
            res = http.get(`${baseUrl}/api/health`, {
                headers,
                timeout: '10s',
            });
        } else {
            res = http.post(`${baseUrl}/api/retell/check-availability`, payload, {
                headers,
                timeout: '15s', // Longer timeout for stress
            });
        }
    } catch (e) {
        timeoutCount.add(1);
        errorCount.add(1);
        successRate.add(false);
        return;
    }

    const duration = Date.now() - start;
    responseTime.add(duration);

    const success = res.status === 200 || res.status === 422;
    successRate.add(success);

    if (!success) {
        errorCount.add(1);
    }

    // Minimal sleep - maximum pressure
    sleep(Math.random() * 0.3);
}

export function handleSummary(data) {
    const metrics = data.metrics || {};

    let output = '\n';
    output += '╔═══════════════════════════════════════════════════════════════════╗\n';
    output += '║              🔥 STRESS TEST RESULTS (200 VUs) 🔥                  ║\n';
    output += '╚═══════════════════════════════════════════════════════════════════╝\n\n';

    if (MOCK_MODE) {
        output += '⚠️  MOCK MODE: Testing against /api/health (no Cal.com calls)\n\n';
    }

    // Response times
    output += '⏱️  RESPONSE TIMES:\n';
    output += '───────────────────────────────────────────────────────────────────\n';

    if (metrics.stress_response_time) {
        const m = metrics.stress_response_time.values;
        output += `  Average:  ${(m.avg || 0).toFixed(0)}ms\n`;
        output += `  p50:      ${(m['p(50)'] || 0).toFixed(0)}ms\n`;
        output += `  p95:      ${(m['p(95)'] || 0).toFixed(0)}ms\n`;
        output += `  p99:      ${(m['p(99)'] || 0).toFixed(0)}ms\n`;
        output += `  Max:      ${(m.max || 0).toFixed(0)}ms\n`;
    }

    // Error analysis
    output += '\n❌ ERROR ANALYSIS:\n';
    output += '───────────────────────────────────────────────────────────────────\n';

    const errors = metrics.stress_errors?.values.count || 0;
    const timeouts = metrics.stress_timeouts?.values.count || 0;
    const successRateVal = (metrics.stress_success_rate?.values.rate || 0) * 100;

    output += `  Success rate: ${successRateVal.toFixed(2)}%\n`;
    output += `  Total errors: ${errors}\n`;
    output += `  Timeouts:     ${timeouts}\n`;

    // Throughput
    output += '\n📈 THROUGHPUT:\n';
    output += '───────────────────────────────────────────────────────────────────\n';

    if (metrics.http_reqs) {
        output += `  Total requests: ${metrics.http_reqs.values.count}\n`;
        output += `  Requests/sec:   ${(metrics.http_reqs.values.rate || 0).toFixed(2)}\n`;
    }

    // Saturation analysis
    output += '\n🎯 SATURATION ANALYSIS:\n';
    output += '───────────────────────────────────────────────────────────────────\n';

    const p99 = metrics.stress_response_time?.values['p(99)'] || 0;
    const errorRate = 100 - successRateVal;

    if (p99 < 500 && errorRate < 1) {
        output += '  ✅ System handled stress well! Consider higher VU count.\n';
    } else if (p99 < 2000 && errorRate < 5) {
        output += '  ⚠️  System showing degradation but functional.\n';
        output += '     Saturation point approaching.\n';
    } else if (p99 < 5000 && errorRate < 15) {
        output += '  🔶 System saturated. Performance significantly degraded.\n';
        output += '     This is likely your effective capacity limit.\n';
    } else {
        output += '  🔴 System overwhelmed. Failures occurring.\n';
        output += '     Infrastructure scaling required for this load.\n';
    }

    // Recommendations
    output += '\n💡 RECOMMENDATIONS:\n';
    output += '───────────────────────────────────────────────────────────────────\n';

    if (errorRate > 10) {
        output += '  • Increase queue worker count (currently 4, recommend 6-8)\n';
    }
    if (p99 > 2000) {
        output += '  • Consider caching Cal.com availability longer\n';
        output += '  • Evaluate connection pooling (PgBouncer)\n';
    }
    if (timeouts > 10) {
        output += '  • External API (Cal.com) may be rate limiting\n';
        output += '  • Consider request queuing for availability checks\n';
    }

    return output + '\n';
}
