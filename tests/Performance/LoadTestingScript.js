import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { SharedArray } from 'k6/data';

// Custom metrics
const errorRate = new Rate('errors');
const appointmentCreationTime = new Trend('appointment_creation_time');
const customerSearchTime = new Trend('customer_search_time');
const dashboardLoadTime = new Trend('dashboard_load_time');
const webhookProcessingTime = new Trend('webhook_processing_time');

// Test configuration
export const options = {
    scenarios: {
        // Scenario 1: Normal business hours load
        normal_load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '2m', target: 50 },   // Ramp up to 50 users
                { duration: '5m', target: 50 },   // Stay at 50 users
                { duration: '2m', target: 100 },  // Ramp up to 100 users
                { duration: '5m', target: 100 },  // Stay at 100 users
                { duration: '2m', target: 0 },    // Ramp down to 0 users
            ],
            gracefulRampDown: '30s',
        },
        
        // Scenario 2: Peak load simulation
        peak_load: {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: 200,
            maxVUs: 500,
            stages: [
                { duration: '30s', target: 50 },   // 50 requests/sec
                { duration: '1m', target: 100 },   // 100 requests/sec
                { duration: '2m', target: 200 },   // 200 requests/sec (peak)
                { duration: '1m', target: 100 },   // Back to 100 requests/sec
                { duration: '30s', target: 0 },    // Ramp down
            ],
        },
        
        // Scenario 3: Sustained load test
        sustained_load: {
            executor: 'constant-vus',
            vus: 150,
            duration: '30m',
            startTime: '20m', // Start after other scenarios
        },
        
        // Scenario 4: Webhook burst simulation
        webhook_burst: {
            executor: 'shared-iterations',
            vus: 100,
            iterations: 1000,
            startTime: '10m',
        }
    },
    
    thresholds: {
        'http_req_duration': ['p(95)<500', 'p(99)<1000'], // 95% of requests under 500ms
        'http_req_failed': ['rate<0.05'],                  // Error rate under 5%
        'appointment_creation_time': ['p(95)<2000'],       // 95% appointments created under 2s
        'customer_search_time': ['p(95)<300'],             // 95% searches under 300ms
        'dashboard_load_time': ['p(95)<1000'],             // 95% dashboard loads under 1s
        'webhook_processing_time': ['p(95)<500'],          // 95% webhooks processed under 500ms
    },
};

// Test data
const testUsers = new SharedArray('users', function () {
    return JSON.parse(open('./test-data/users.json'));
});

const testCustomers = new SharedArray('customers', function () {
    return JSON.parse(open('./test-data/customers.json'));
});

const BASE_URL = __ENV.BASE_URL || 'https://api.askproai.de';

// Helper functions
function authenticateUser() {
    const user = testUsers[Math.floor(Math.random() * testUsers.length)];
    
    const loginRes = http.post(`${BASE_URL}/api/login`, JSON.stringify({
        email: user.email,
        password: user.password
    }), {
        headers: { 'Content-Type': 'application/json' },
    });
    
    check(loginRes, {
        'login successful': (r) => r.status === 200,
        'token received': (r) => r.json('data.token') !== '',
    });
    
    errorRate.add(loginRes.status !== 200);
    
    return loginRes.json('data.token');
}

function getAuthHeaders(token) {
    return {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
    };
}

// Main test scenarios
export default function () {
    const token = authenticateUser();
    const headers = getAuthHeaders(token);
    
    group('Dashboard Operations', () => {
        const start = new Date();
        
        const dashboardRes = http.get(`${BASE_URL}/api/dashboard`, { headers });
        
        dashboardLoadTime.add(new Date() - start);
        
        check(dashboardRes, {
            'dashboard loaded': (r) => r.status === 200,
            'has appointment stats': (r) => r.json('data.stats.appointments') !== undefined,
            'has revenue data': (r) => r.json('data.stats.revenue') !== undefined,
        });
        
        errorRate.add(dashboardRes.status !== 200);
    });
    
    sleep(1);
    
    group('Customer Search', () => {
        const searchTerm = testCustomers[Math.floor(Math.random() * testCustomers.length)].name.split(' ')[0];
        const start = new Date();
        
        const searchRes = http.get(`${BASE_URL}/api/customers?search=${searchTerm}`, { headers });
        
        customerSearchTime.add(new Date() - start);
        
        check(searchRes, {
            'search successful': (r) => r.status === 200,
            'has results': (r) => r.json('data.customers') !== undefined,
            'pagination present': (r) => r.json('data.pagination') !== undefined,
        });
        
        errorRate.add(searchRes.status !== 200);
    });
    
    sleep(1);
    
    group('Appointment Creation', () => {
        const customer = testCustomers[Math.floor(Math.random() * testCustomers.length)];
        const appointmentData = {
            customer_id: customer.id,
            service_id: Math.floor(Math.random() * 5) + 1,
            staff_id: Math.floor(Math.random() * 3) + 1,
            branch_id: 1,
            starts_at: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
            ends_at: new Date(Date.now() + 24 * 60 * 60 * 1000 + 60 * 60 * 1000).toISOString(),
            notes: 'Performance test appointment',
        };
        
        const start = new Date();
        
        const appointmentRes = http.post(
            `${BASE_URL}/api/appointments`,
            JSON.stringify(appointmentData),
            { headers }
        );
        
        appointmentCreationTime.add(new Date() - start);
        
        check(appointmentRes, {
            'appointment created': (r) => r.status === 201,
            'has appointment id': (r) => r.json('data.appointment.id') !== undefined,
            'correct status': (r) => r.json('data.appointment.status') === 'scheduled',
        });
        
        errorRate.add(appointmentRes.status !== 201);
    });
    
    sleep(2);
    
    group('Appointment List with Filters', () => {
        const filters = [
            'status=scheduled',
            'date_from=' + new Date().toISOString().split('T')[0],
            'branch_id=1',
            'page=1',
            'per_page=20'
        ].join('&');
        
        const listRes = http.get(`${BASE_URL}/api/appointments?${filters}`, { headers });
        
        check(listRes, {
            'list loaded': (r) => r.status === 200,
            'has appointments': (r) => r.json('data.appointments').length > 0,
            'correct pagination': (r) => r.json('data.pagination.per_page') === 20,
        });
        
        errorRate.add(listRes.status !== 200);
    });
    
    sleep(1);
}

// Webhook simulation (runs in separate scenario)
export function webhookBurst() {
    const webhookData = {
        call_id: `call_perf_${__VU}_${__ITER}`,
        from_number: `+123456${String(__VU).padStart(4, '0')}`,
        to_number: '+0987654321',
        status: 'ended',
        duration: Math.floor(Math.random() * 300) + 60,
        transcript: 'Performance test call transcript',
        extracted_data: {
            customer_name: `Test User ${__VU}`,
            service_requested: 'Haircut',
            preferred_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            preferred_time: '14:00'
        }
    };
    
    const start = new Date();
    
    const webhookRes = http.post(
        `${BASE_URL}/api/retell/webhook`,
        JSON.stringify(webhookData),
        {
            headers: {
                'Content-Type': 'application/json',
                'x-retell-signature': 'test_signature'
            }
        }
    );
    
    webhookProcessingTime.add(new Date() - start);
    
    check(webhookRes, {
        'webhook accepted': (r) => r.status === 200 || r.status === 202,
    });
    
    errorRate.add(webhookRes.status !== 200 && webhookRes.status !== 202);
}

// Stress test function
export function stressTest() {
    const token = authenticateUser();
    const headers = getAuthHeaders(token);
    
    // Rapid fire requests without sleep
    for (let i = 0; i < 10; i++) {
        http.get(`${BASE_URL}/api/dashboard`, { headers });
        http.get(`${BASE_URL}/api/appointments`, { headers });
        http.get(`${BASE_URL}/api/customers`, { headers });
    }
}