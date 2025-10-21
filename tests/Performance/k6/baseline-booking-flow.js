import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';
import { htmlReport } from "https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js";

// Custom metrics
const bookingSuccessRate = new Rate('booking_success');
const bookingDuration = new Trend('booking_duration');
const calcomLatency = new Trend('calcom_api_latency');
const raceConditionsDetected = new Counter('race_conditions');
const staleBookingRejections = new Counter('stale_booking_rejections');

export const options = {
    stages: [
        { duration: '1m', target: 5 },   // Warm up to 5 users
        { duration: '3m', target: 10 },  // Ramp to 10 users
        { duration: '5m', target: 10 },  // Stay at 10 users (baseline load)
        { duration: '1m', target: 0 },   // Ramp down
    ],
    thresholds: {
        // RCA Target: Reduce from 144s to <45s
        'booking_duration': [
            'p(50)<30000',  // P50 < 30s
            'p(95)<45000',  // P95 < 45s (PRIMARY TARGET)
            'p(99)<60000',  // P99 < 60s
        ],
        'booking_success': ['rate>0.95'],  // 95% success rate
        'http_req_duration': ['p(99)<10000'], // Individual API calls < 10s
        'http_req_failed': ['rate<0.05'],  // <5% HTTP failures
        'calcom_api_latency': ['avg<2000'], // Cal.com API avg < 2s
    },
    ext: {
        loadimpact: {
            projectID: 3649635,
            name: "AskPro Appointment Booking - Baseline Test"
        }
    }
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

/**
 * Main test scenario: Complete booking flow
 *
 * Simulates real user journey from availability check to booking confirmation
 */
export default function () {
    const startTime = Date.now();
    const userId = `k6_user_${__VU}_${__ITER}`;

    // Generate unique test data
    const appointmentData = {
        datum: getNextBusinessDay(),
        uhrzeit: getRandomTimeSlot(),
        name: `K6 Test User ${__VU}`,
        telefon: `+49151${String(__VU).padStart(8, '0')}`,
        dienstleistung: 'Beratung',
        email: `k6test${__VU}@askproai.de`
    };

    // ====================================================
    // STEP 1: Check Availability
    // ====================================================
    const availabilityStart = Date.now();

    const availabilityResponse = http.post(
        `${BASE_URL}/api/retell/check-availability`,
        JSON.stringify({
            args: {
                datum: appointmentData.datum,
                uhrzeit: appointmentData.uhrzeit
            }
        }),
        {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Test-User': userId
            },
            tags: { name: 'check_availability' }
        }
    );

    const availabilityCheck = check(availabilityResponse, {
        'availability check status is 200': (r) => r.status === 200,
        'availability check response time < 3s': (r) => r.timings.duration < 3000,
        'slot availability returned': (r) => {
            try {
                const body = JSON.parse(r.body);
                return typeof body.available !== 'undefined';
            } catch {
                return false;
            }
        }
    });

    calcomLatency.add(availabilityResponse.timings.duration);

    if (!availabilityCheck || availabilityResponse.status !== 200) {
        console.error(`Availability check failed for ${userId}`);
        return;
    }

    const isAvailable = JSON.parse(availabilityResponse.body).available;

    // ====================================================
    // STEP 2: Simulate User Decision Time (RCA: 14s gap)
    // ====================================================
    // RCA Reference: RCA_AVAILABILITY_RACE_CONDITION_2025-10-14.md
    // User thinking time creates race condition window
    sleep(getRandomThinkingTime()); // 10-18s to simulate real behavior

    // ====================================================
    // STEP 3: Book Appointment (if available)
    // ====================================================
    if (isAvailable) {
        const bookingResponse = http.post(
            `${BASE_URL}/api/retell/collect-appointment`,
            JSON.stringify({
                args: {
                    ...appointmentData,
                    bestaetigung: true,
                    call_id: `call_${userId}_${Date.now()}`
                }
            }),
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Test-User': userId
                },
                tags: { name: 'book_appointment' }
            }
        );

        const totalDuration = Date.now() - startTime;
        bookingDuration.add(totalDuration);

        const bookingSuccess = check(bookingResponse, {
            'booking status is 200': (r) => r.status === 200,
            'booking response time < 5s': (r) => r.timings.duration < 5000,
            'booking completed successfully': (r) => {
                try {
                    const body = JSON.parse(r.body);
                    return body.success === true;
                } catch {
                    return false;
                }
            },
            'booking duration < 45s (RCA target)': () => totalDuration < 45000
        });

        bookingSuccessRate.add(bookingSuccess);

        // Track RCA-specific metrics
        try {
            const responseBody = JSON.parse(bookingResponse.body);

            // Race condition detected by V85 double-check
            if (responseBody.reason === 'race_condition_detected') {
                raceConditionsDetected.add(1);
                console.log(`Race condition detected and handled for ${userId}`);
            }

            // Stale booking rejection (RCA: DUPLICATE_BOOKING_BUG)
            if (responseBody.status === 'stale_booking_data') {
                staleBookingRejections.add(1);
                console.log(`Stale booking rejected for ${userId}`);
            }

            // Log performance outliers
            if (totalDuration > 45000) {
                console.warn(`Performance threshold exceeded: ${totalDuration}ms for ${userId}`);
            }

        } catch (e) {
            console.error(`Failed to parse booking response for ${userId}`, e);
        }

    } else {
        console.log(`Slot not available for ${userId}, skipping booking attempt`);
    }

    // ====================================================
    // STEP 4: Cooldown between iterations
    // ====================================================
    sleep(1);
}

/**
 * Generate report after test completion
 */
export function handleSummary(data) {
    return {
        "summary.html": htmlReport(data),
        "summary.json": JSON.stringify(data),
    };
}

// ====================================================
// Helper Functions
// ====================================================

function getNextBusinessDay() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    // Skip weekends
    const dayOfWeek = tomorrow.getDay();
    if (dayOfWeek === 0) { // Sunday
        tomorrow.setDate(tomorrow.getDate() + 1);
    } else if (dayOfWeek === 6) { // Saturday
        tomorrow.setDate(tomorrow.getDate() + 2);
    }

    return tomorrow.toISOString().split('T')[0];
}

function getRandomTimeSlot() {
    const slots = [
        '09:00', '09:30', '10:00', '10:30',
        '11:00', '11:30', '13:00', '13:30',
        '14:00', '14:30', '15:00', '15:30',
        '16:00', '16:30'
    ];
    return slots[Math.floor(Math.random() * slots.length)];
}

function getRandomThinkingTime() {
    // RCA: 14s average thinking time, randomize 10-18s
    return 10 + Math.random() * 8;
}
