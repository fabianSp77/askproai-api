/**
 * Business Portal API Performance Testing Suite
 * Tests all business portal endpoints for performance, authentication, and functionality
 */
import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const responseTime = new Trend('response_time');
const apiCallsTotal = new Counter('api_calls_total');
const authFailures = new Counter('auth_failures');

// Configuration
const BASE_URL = __ENV.BASE_URL || 'https://api.askproai.de';
const API_BASE = `${BASE_URL}/business/api`;
const LOGIN_URL = `${BASE_URL}/business/api/auth/login`;

// Test configuration
export const options = {
  stages: [
    { duration: '30s', target: 5 },   // Ramp up to 5 users
    { duration: '2m', target: 5 },    // Stay at 5 users
    { duration: '30s', target: 10 },  // Ramp up to 10 users
    { duration: '2m', target: 10 },   // Stay at 10 users
    { duration: '30s', target: 0 },   // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% of requests must complete below 2s
    http_req_failed: ['rate<0.05'],    // Error rate must be below 5%
    'response_time': ['p(95)<1000'],   // Custom metric threshold
    'errors': ['rate<0.05'],           // Error rate threshold
  },
};

// Test data
const testUser = {
  email: __ENV.TEST_EMAIL || 'test@askproai.de',
  password: __ENV.TEST_PASSWORD || 'testpassword123'
};

let authToken = null;
let sessionCookie = null;

// Authentication function
function authenticate() {
  const loginPayload = JSON.stringify(testUser);
  
  const loginResponse = http.post(LOGIN_URL, loginPayload, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  check(loginResponse, {
    'login status is 200': (r) => r.status === 200,
    'login has success response': (r) => {
      const body = JSON.parse(r.body || '{}');
      return body.success === true;
    },
  }) || authFailures.add(1);

  if (loginResponse.status === 200) {
    const cookies = loginResponse.cookies;
    sessionCookie = cookies['laravel_session'] ? cookies['laravel_session'][0].value : null;
    
    const responseBody = JSON.parse(loginResponse.body || '{}');
    authToken = responseBody.token || null;
  }

  return loginResponse.status === 200;
}

// Helper function to make authenticated requests
function makeAuthenticatedRequest(url, method = 'GET', payload = null) {
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  if (sessionCookie) {
    headers['Cookie'] = `laravel_session=${sessionCookie}`;
  }

  if (authToken) {
    headers['Authorization'] = `Bearer ${authToken}`;
  }

  const options = { headers };

  let response;
  if (method === 'GET') {
    response = http.get(url, options);
  } else if (method === 'POST') {
    response = http.post(url, payload ? JSON.stringify(payload) : null, options);
  } else if (method === 'PUT') {
    response = http.put(url, payload ? JSON.stringify(payload) : null, options);
  } else if (method === 'DELETE') {
    response = http.del(url, null, options);
  }

  apiCallsTotal.add(1);
  responseTime.add(response.timings.duration);
  
  if (response.status >= 400) {
    errorRate.add(1);
  } else {
    errorRate.add(0);
  }

  return response;
}

// Main test function
export default function () {
  // Authenticate if not already authenticated
  if (!sessionCookie && !authToken) {
    if (!authenticate()) {
      console.error('Authentication failed, skipping tests');
      return;
    }
  }

  // Test Dashboard API
  group('Dashboard API', () => {
    const dashboardResponse = makeAuthenticatedRequest(`${API_BASE}/dashboard`);
    
    check(dashboardResponse, {
      'dashboard status is 200': (r) => r.status === 200,
      'dashboard response time < 1s': (r) => r.timings.duration < 1000,
      'dashboard has stats': (r) => {
        const body = JSON.parse(r.body || '{}');
        return body.stats !== undefined;
      },
      'dashboard has proper structure': (r) => {
        const body = JSON.parse(r.body || '{}');
        return body.stats && body.trends && body.chartData;
      },
    });

    // Test dashboard stats endpoint
    const statsResponse = makeAuthenticatedRequest(`${API_BASE}/dashboard/stats`);
    check(statsResponse, {
      'stats endpoint works': (r) => r.status === 200,
      'stats response time < 500ms': (r) => r.timings.duration < 500,
    });
  });

  // Test Calls API
  group('Calls API', () => {
    const callsResponse = makeAuthenticatedRequest(`${API_BASE}/calls`);
    
    check(callsResponse, {
      'calls list status is 200': (r) => r.status === 200,
      'calls response time < 1s': (r) => r.timings.duration < 1000,
      'calls has pagination': (r) => {
        const body = JSON.parse(r.body || '{}');
        return body.data && body.pagination;
      },
    });

    // Test specific call endpoint (if we have calls)
    const body = JSON.parse(callsResponse.body || '{}');
    if (body.data && body.data.length > 0) {
      const firstCallId = body.data[0].id;
      const callDetailResponse = makeAuthenticatedRequest(`${API_BASE}/calls/${firstCallId}`);
      
      check(callDetailResponse, {
        'call detail status is 200': (r) => r.status === 200,
        'call detail response time < 500ms': (r) => r.timings.duration < 500,
      });
    }
  });

  // Test Appointments API
  group('Appointments API', () => {
    const appointmentsResponse = makeAuthenticatedRequest(`${API_BASE}/appointments`);
    
    check(appointmentsResponse, {
      'appointments list status is 200': (r) => r.status === 200,
      'appointments response time < 1s': (r) => r.timings.duration < 1000,
      'appointments has pagination': (r) => {
        const body = JSON.parse(r.body || '{}');
        return body.data && body.pagination;
      },
    });

    // Test appointments filters
    const filtersResponse = makeAuthenticatedRequest(`${API_BASE}/appointments/filters`);
    check(filtersResponse, {
      'appointments filters work': (r) => r.status === 200,
    });
  });

  // Test Customers API
  group('Customers API', () => {
    const customersResponse = makeAuthenticatedRequest(`${API_BASE}/customers`);
    
    check(customersResponse, {
      'customers list status is 200': (r) => r.status === 200,
      'customers response time < 1s': (r) => r.timings.duration < 1000,
      'customers has pagination': (r) => {
        const body = JSON.parse(r.body || '{}');
        return body.data && body.pagination;
      },
    });
  });

  // Test Settings API
  group('Settings API', () => {
    const settingsResponse = makeAuthenticatedRequest(`${API_BASE}/settings`);
    
    check(settingsResponse, {
      'settings status is 200': (r) => r.status === 200,
      'settings response time < 500ms': (r) => r.timings.duration < 500,
    });

    // Test profile endpoint
    const profileResponse = makeAuthenticatedRequest(`${API_BASE}/settings/profile`);
    check(profileResponse, {
      'profile endpoint works': (r) => r.status === 200,
    });

    // Test company settings
    const companyResponse = makeAuthenticatedRequest(`${API_BASE}/settings/company`);
    check(companyResponse, {
      'company settings work': (r) => r.status === 200,
    });
  });

  // Test Team API
  group('Team API', () => {
    const teamResponse = makeAuthenticatedRequest(`${API_BASE}/team`);
    
    check(teamResponse, {
      'team list status is 200': (r) => r.status === 200,
      'team response time < 500ms': (r) => r.timings.duration < 500,
    });
  });

  // Test Analytics API
  group('Analytics API', () => {
    const analyticsResponse = makeAuthenticatedRequest(`${API_BASE}/analytics/overview`);
    
    check(analyticsResponse, {
      'analytics overview status is 200': (r) => r.status === 200,
      'analytics response time < 1s': (r) => r.timings.duration < 1000,
    });

    // Test specific analytics endpoints
    const callsAnalyticsResponse = makeAuthenticatedRequest(`${API_BASE}/analytics/calls`);
    check(callsAnalyticsResponse, {
      'calls analytics work': (r) => r.status === 200,
    });
  });

  // Test Billing API
  group('Billing API', () => {
    const billingResponse = makeAuthenticatedRequest(`${API_BASE}/billing`);
    
    check(billingResponse, {
      'billing status is 200': (r) => r.status === 200,
      'billing response time < 500ms': (r) => r.timings.duration < 500,
    });

    // Test usage endpoint
    const usageResponse = makeAuthenticatedRequest(`${API_BASE}/billing/usage`);
    check(usageResponse, {
      'usage endpoint works': (r) => r.status === 200,
    });
  });

  // Test rate limiting
  group('Rate Limiting', () => {
    let rateLimitHit = false;
    
    // Make many requests quickly to test rate limiting
    for (let i = 0; i < 20; i++) {
      const response = makeAuthenticatedRequest(`${API_BASE}/dashboard/stats`);
      if (response.status === 429) {
        rateLimitHit = true;
        break;
      }
    }
    
    check({ rateLimitHit }, {
      'rate limiting is implemented': (obj) => obj.rateLimitHit === true,
    });
  });

  // Add a small delay between iterations
  sleep(1);
}

// Setup function - runs once before all tests
export function setup() {
  console.log('Starting Business Portal API Performance Tests');
  console.log(`Base URL: ${BASE_URL}`);
  console.log(`API Base: ${API_BASE}`);
  
  return {};
}

// Teardown function - runs once after all tests
export function teardown(data) {
  console.log('Business Portal API Performance Tests completed');
}