import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const memoryUsage = new Trend('memory_usage_mb');

// Soak test - extended duration at normal load
export const options = {
  stages: [
    { duration: '5m', target: 50 },    // Ramp up to 50 users
    { duration: '4h', target: 50 },    // Stay at 50 users for 4 hours
    { duration: '5m', target: 0 },     // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],  // 95% of requests should be below 500ms
    http_req_failed: ['rate<0.01'],    // Error rate should be below 1%
    errors: ['rate<0.01'],             // Custom error rate below 1%
  },
};

const BASE_URL = __ENV.BASE_URL || 'https://api.askproai.de';

export function setup() {
  const loginRes = http.post(`${BASE_URL}/api/auth/login`, JSON.stringify({
    email: 'soak-test@example.com',
    password: 'soaktest123',
  }), {
    headers: { 'Content-Type': 'application/json' },
  });

  if (loginRes.status === 200) {
    return { authToken: loginRes.json('token') };
  }
  
  return { authToken: null };
}

export default function (data) {
  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${data.authToken}`,
    },
  };

  // Regular user behavior pattern
  const actions = [
    () => {
      // View dashboard
      const res = http.get(`${BASE_URL}/api/dashboard/overview`, params);
      check(res, { 'dashboard loaded': (r) => r.status === 200 });
      return res;
    },
    () => {
      // List appointments
      const res = http.get(`${BASE_URL}/api/appointments?limit=20`, params);
      check(res, { 'appointments loaded': (r) => r.status === 200 });
      return res;
    },
    () => {
      // View customer details
      const customerId = Math.floor(Math.random() * 100) + 1;
      const res = http.get(`${BASE_URL}/api/customers/${customerId}`, params);
      check(res, { 'customer loaded': (r) => r.status === 200 });
      return res;
    },
    () => {
      // Check system stats (includes memory usage)
      const res = http.get(`${BASE_URL}/api/system/stats`, params);
      if (res.status === 200 && res.json('memory_usage_mb')) {
        memoryUsage.add(res.json('memory_usage_mb'));
      }
      return res;
    },
  ];

  // Execute random action
  const action = actions[Math.floor(Math.random() * actions.length)];
  const response = action();
  
  errorRate.add(response.status !== 200);
  
  // Realistic think time
  sleep(Math.random() * 5 + 5);
}