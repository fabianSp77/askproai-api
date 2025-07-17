import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const apiLatency = new Trend('api_latency');
const loginLatency = new Trend('login_latency');
const appointmentCreationLatency = new Trend('appointment_creation_latency');

// Test configuration
export const options = {
  stages: [
    { duration: '2m', target: 10 },  // Ramp up to 10 users
    { duration: '5m', target: 50 },  // Ramp up to 50 users
    { duration: '10m', target: 100 }, // Stay at 100 users
    { duration: '5m', target: 50 },  // Ramp down to 50 users
    { duration: '2m', target: 0 },   // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests must complete below 500ms
    http_req_failed: ['rate<0.1'],    // http errors should be less than 10%
    errors: ['rate<0.1'],             // custom error rate should be less than 10%
  },
};

// Test data
const BASE_URL = __ENV.BASE_URL || 'https://api.askproai.de';
const TEST_USER = {
  email: __ENV.TEST_EMAIL || 'test@example.com',
  password: __ENV.TEST_PASSWORD || 'password123',
};

// Helper function to handle responses
function handleResponse(response, metricName) {
  const success = check(response, {
    'status is 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
  });

  errorRate.add(!success);
  
  if (metricName) {
    eval(`${metricName}.add(response.timings.duration)`);
  }

  return success;
}

// Setup function - runs once per VU
export function setup() {
  // Login to get auth token
  const loginRes = http.post(`${BASE_URL}/api/auth/login`, JSON.stringify(TEST_USER), {
    headers: { 'Content-Type': 'application/json' },
  });

  if (loginRes.status !== 200) {
    throw new Error('Setup failed: Unable to authenticate');
  }

  const authToken = loginRes.json('token');
  return { authToken };
}

// Main test scenario
export default function (data) {
  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${data.authToken}`,
    },
  };

  group('Dashboard API', () => {
    const dashboardRes = http.get(`${BASE_URL}/api/dashboard/overview`, params);
    handleResponse(dashboardRes, 'apiLatency');
    sleep(1);
  });

  group('Appointments API', () => {
    // List appointments
    const listRes = http.get(`${BASE_URL}/api/appointments`, params);
    handleResponse(listRes, 'apiLatency');
    sleep(0.5);

    // Check availability
    const availabilityData = {
      staff_id: 1,
      service_id: 1,
      date: new Date().toISOString().split('T')[0],
    };
    
    const availabilityRes = http.post(
      `${BASE_URL}/api/appointments/check-availability`,
      JSON.stringify(availabilityData),
      params
    );
    handleResponse(availabilityRes, 'apiLatency');
    sleep(0.5);

    // Create appointment (10% of users)
    if (Math.random() < 0.1) {
      const appointmentData = {
        customer_id: Math.floor(Math.random() * 100) + 1,
        service_id: 1,
        staff_id: 1,
        branch_id: 1,
        appointment_datetime: new Date(Date.now() + 86400000).toISOString(),
        duration_minutes: 60,
        notes: 'Performance test appointment',
      };

      const createRes = http.post(
        `${BASE_URL}/api/appointments`,
        JSON.stringify(appointmentData),
        params
      );
      handleResponse(createRes, 'appointmentCreationLatency');
    }
  });

  group('Calls API', () => {
    // List calls
    const callsRes = http.get(`${BASE_URL}/api/calls?limit=20`, params);
    handleResponse(callsRes, 'apiLatency');
    sleep(0.5);

    // Get call statistics
    const statsRes = http.get(`${BASE_URL}/api/calls/statistics`, params);
    handleResponse(statsRes, 'apiLatency');
  });

  group('Customers API', () => {
    // Search customers
    const searchRes = http.get(`${BASE_URL}/api/customers?search=test`, params);
    handleResponse(searchRes, 'apiLatency');
    sleep(0.5);

    // Get customer details (random customer)
    const customerId = Math.floor(Math.random() * 50) + 1;
    const customerRes = http.get(`${BASE_URL}/api/customers/${customerId}`, params);
    handleResponse(customerRes, 'apiLatency');
  });

  // Think time between iterations
  sleep(Math.random() * 3 + 2);
}

// Teardown function - runs once per test
export function teardown(data) {
  // Logout
  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${data.authToken}`,
    },
  };

  http.post(`${BASE_URL}/api/auth/logout`, null, params);
}