import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

// Spike test - sudden increase and decrease in traffic
export const options = {
  stages: [
    { duration: '10s', target: 10 },    // Warm up
    { duration: '1m', target: 10 },     // Stay at 10 users
    { duration: '10s', target: 500 },   // Spike to 500 users
    { duration: '3m', target: 500 },    // Stay at 500 users
    { duration: '10s', target: 10 },    // Scale down to 10 users
    { duration: '3m', target: 10 },     // Continue at 10 users
    { duration: '10s', target: 0 },     // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<1000'], // 95% of requests must complete below 1s
    http_req_failed: ['rate<0.2'],     // http errors should be less than 20%
  },
};

const BASE_URL = __ENV.BASE_URL || 'https://api.askproai.de';

export default function () {
  // Simple endpoint that should handle spikes well
  const response = http.get(`${BASE_URL}/api/health`);
  
  const success = check(response, {
    'status is 200': (r) => r.status === 200,
    'response time < 1000ms': (r) => r.timings.duration < 1000,
  });
  
  errorRate.add(!success);
  
  sleep(1);
}