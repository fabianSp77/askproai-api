import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

// Stress test configuration - push system to its limits
export const options = {
  stages: [
    { duration: '2m', target: 100 },   // Below normal load
    { duration: '5m', target: 200 },   // Normal load
    { duration: '2m', target: 300 },   // Around breaking point
    { duration: '5m', target: 400 },   // Beyond breaking point
    { duration: '2m', target: 500 },   // Push to failure
    { duration: '10m', target: 0 },    // Recovery stage
  ],
  thresholds: {
    http_req_duration: ['p(99)<2000'], // 99% of requests must complete below 2s
    errors: ['rate<0.5'],              // Error rate must be below 50%
  },
};

const BASE_URL = __ENV.BASE_URL || 'https://api.askproai.de';

export function setup() {
  // Create test data for stress testing
  const loginRes = http.post(`${BASE_URL}/api/auth/login`, JSON.stringify({
    email: 'stress-test@example.com',
    password: 'stresstest123',
  }), {
    headers: { 'Content-Type': 'application/json' },
  });

  if (loginRes.status === 200) {
    return { authToken: loginRes.json('token') };
  }
  
  return { authToken: null };
}

export default function (data) {
  const params = data.authToken ? {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${data.authToken}`,
    },
  } : {
    headers: { 'Content-Type': 'application/json' },
  };

  // Heavy operations to stress the system
  const responses = http.batch([
    ['GET', `${BASE_URL}/api/appointments?limit=100`, null, params],
    ['GET', `${BASE_URL}/api/calls?limit=100`, null, params],
    ['GET', `${BASE_URL}/api/customers?limit=100`, null, params],
    ['GET', `${BASE_URL}/api/dashboard/overview`, null, params],
  ]);

  responses.forEach(response => {
    const success = check(response, {
      'status is not 5xx': (r) => r.status < 500,
    });
    errorRate.add(!success);
  });

  // Minimal think time to maintain pressure
  sleep(0.1);
}