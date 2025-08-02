/**
 * Business Portal API Security Testing Suite
 * Tests input validation, authentication, authorization, and security vulnerabilities
 */
import http from 'k6/http';
import { check, group } from 'k6';
import { Counter } from 'k6/metrics';

// Security metrics
const securityIssues = new Counter('security_issues');
const authBypassAttempts = new Counter('auth_bypass_attempts');
const injectionAttempts = new Counter('injection_attempts');

const BASE_URL = __ENV.BASE_URL || 'https://api.askproai.de';
const API_BASE = `${BASE_URL}/business/api`;

// Test payloads for various attacks
const SQL_INJECTION_PAYLOADS = [
  "'; DROP TABLE users; --",
  "' OR '1'='1",
  "' UNION SELECT * FROM users --",
  "1' OR 1=1 --",
  "admin'--",
  "admin'/*",
  "' OR 1=1#",
];

const XSS_PAYLOADS = [
  "<script>alert('XSS')</script>",
  "<img src=x onerror=alert('XSS')>",
  "javascript:alert('XSS')",
  "<svg onload=alert('XSS')>",
  "'\"><script>alert('XSS')</script>",
];

const COMMAND_INJECTION_PAYLOADS = [
  "; cat /etc/passwd",
  "| whoami",
  "&& ls -la",
  "`id`",
  "$(whoami)",
];

const INVALID_DATA_PAYLOADS = [
  { email: "invalid-email" },
  { email: "" },
  { password: "" },
  { phone: "not-a-phone" },
  { date: "invalid-date" },
  { id: -1 },
  { id: "abc" },
  { amount: -999 },
];

export default function() {
  
  // Test authentication bypasses
  group('Authentication Bypass Tests', () => {
    
    // Test access without authentication
    const unauthResponse = http.get(`${API_BASE}/dashboard`);
    check(unauthResponse, {
      'dashboard requires authentication': (r) => r.status === 401 || r.status === 403,
    }) || securityIssues.add(1);

    // Test with invalid tokens
    const invalidTokenHeaders = {
      'Authorization': 'Bearer invalid-token-123',
      'Accept': 'application/json'
    };
    
    const invalidTokenResponse = http.get(`${API_BASE}/dashboard`, { headers: invalidTokenHeaders });
    check(invalidTokenResponse, {
      'invalid token rejected': (r) => r.status === 401 || r.status === 403,
    }) || securityIssues.add(1);

    // Test with expired sessions
    const expiredSessionHeaders = {
      'Cookie': 'laravel_session=expired-session-123',
      'Accept': 'application/json'
    };
    
    const expiredSessionResponse = http.get(`${API_BASE}/dashboard`, { headers: expiredSessionHeaders });
    check(expiredSessionResponse, {
      'expired session rejected': (r) => r.status === 401 || r.status === 403,
    }) || securityIssues.add(1);

    authBypassAttempts.add(3);
  });

  // Test SQL Injection vulnerabilities
  group('SQL Injection Tests', () => {
    SQL_INJECTION_PAYLOADS.forEach((payload, index) => {
      
      // Test in query parameters
      const queryResponse = http.get(`${API_BASE}/calls?search=${encodeURIComponent(payload)}`);
      check(queryResponse, {
        [`SQL injection ${index + 1} blocked in query`]: (r) => 
          r.status !== 200 || !r.body.includes('mysql') && !r.body.includes('SQL'),
      }) || securityIssues.add(1);

      // Test in POST data
      const postPayload = JSON.stringify({ search: payload });
      const postResponse = http.post(`${API_BASE}/customers`, postPayload, {
        headers: { 'Content-Type': 'application/json' }
      });
      
      check(postResponse, {
        [`SQL injection ${index + 1} blocked in POST`]: (r) => 
          r.status === 400 || r.status === 422 || !r.body.includes('mysql'),
      }) || securityIssues.add(1);

      injectionAttempts.add(2);
    });
  });

  // Test XSS vulnerabilities
  group('XSS Tests', () => {
    XSS_PAYLOADS.forEach((payload, index) => {
      
      const xssPayload = JSON.stringify({ 
        name: payload,
        email: 'test@example.com',
        message: payload
      });
      
      const xssResponse = http.post(`${API_BASE}/feedback`, xssPayload, {
        headers: { 'Content-Type': 'application/json' }
      });
      
      check(xssResponse, {
        [`XSS payload ${index + 1} sanitized`]: (r) => 
          !r.body.includes('<script>') && !r.body.includes('javascript:'),
      }) || securityIssues.add(1);

      injectionAttempts.add(1);
    });
  });

  // Test Command Injection
  group('Command Injection Tests', () => {
    COMMAND_INJECTION_PAYLOADS.forEach((payload, index) => {
      
      const cmdPayload = JSON.stringify({ 
        filename: payload,
        command: payload
      });
      
      const cmdResponse = http.post(`${API_BASE}/calls/export`, cmdPayload, {
        headers: { 'Content-Type': 'application/json' }
      });
      
      check(cmdResponse, {
        [`Command injection ${index + 1} blocked`]: (r) => 
          r.status === 400 || r.status === 422 || !r.body.includes('root:'),
      }) || securityIssues.add(1);

      injectionAttempts.add(1);
    });
  });

  // Test Input Validation
  group('Input Validation Tests', () => {
    INVALID_DATA_PAYLOADS.forEach((payload, index) => {
      
      const validationResponse = http.post(`${API_BASE}/appointments`, JSON.stringify(payload), {
        headers: { 'Content-Type': 'application/json' }
      });
      
      check(validationResponse, {
        [`Invalid data ${index + 1} rejected`]: (r) => 
          r.status === 400 || r.status === 422,
      }) || securityIssues.add(1);
    });
  });

  // Test CORS Configuration
  group('CORS Tests', () => {
    const corsResponse = http.options(`${API_BASE}/dashboard`, null, {
      headers: {
        'Origin': 'https://malicious-site.com',
        'Access-Control-Request-Method': 'GET',
        'Access-Control-Request-Headers': 'authorization'
      }
    });
    
    check(corsResponse, {
      'CORS properly configured': (r) => {
        const allowOrigin = r.headers['Access-Control-Allow-Origin'];
        return !allowOrigin || allowOrigin !== '*' || allowOrigin.includes('askproai.de');
      }
    }) || securityIssues.add(1);
  });

  // Test HTTP Security Headers
  group('Security Headers Tests', () => {
    const headersResponse = http.get(`${API_BASE}/dashboard`);
    
    const securityHeaders = [
      'X-Content-Type-Options',
      'X-Frame-Options',
      'X-XSS-Protection',
      'Strict-Transport-Security',
      'Content-Security-Policy'
    ];
    
    securityHeaders.forEach(header => {
      check(headersResponse, {
        [`${header} header present`]: (r) => r.headers[header] !== undefined,
      }) || securityIssues.add(1);
    });
  });

  // Test File Upload Vulnerabilities
  group('File Upload Security Tests', () => {
    
    // Test malicious file extensions
    const maliciousFiles = [
      { name: 'malware.exe', content: 'MZ...' },
      { name: 'script.php', content: '<?php system($_GET["cmd"]); ?>' },
      { name: 'shell.jsp', content: '<% Runtime.getRuntime().exec(request.getParameter("cmd")); %>' },
      { name: '../../../etc/passwd', content: 'root:x:0:0:root:/root:/bin/bash' }
    ];
    
    maliciousFiles.forEach(file => {
      const formData = {
        file: http.file(file.content, file.name, 'application/octet-stream')
      };
      
      const uploadResponse = http.post(`${API_BASE}/email/csv/upload`, formData);
      
      check(uploadResponse, {
        [`Malicious file ${file.name} rejected`]: (r) => 
          r.status === 400 || r.status === 422 || r.status === 415,
      }) || securityIssues.add(1);
    });
  });

  // Test Mass Assignment Vulnerabilities
  group('Mass Assignment Tests', () => {
    const massAssignmentPayload = JSON.stringify({
      name: 'Test User',
      email: 'test@example.com',
      is_admin: true,           // Should not be assignable
      company_id: 999,          // Should not be assignable
      role: 'admin',            // Should not be assignable
      permissions: ['*'],       // Should not be assignable
      created_at: '2020-01-01', // Should not be assignable
      updated_at: '2020-01-01'  // Should not be assignable
    });
    
    const massAssignResponse = http.post(`${API_BASE}/team`, massAssignmentPayload, {
      headers: { 'Content-Type': 'application/json' }
    });
    
    check(massAssignResponse, {
      'Mass assignment prevented': (r) => {
        if (r.status === 200 || r.status === 201) {
          const body = JSON.parse(r.body || '{}');
          return !body.is_admin && body.company_id !== 999 && body.role !== 'admin';
        }
        return true; // If request failed, that's also good
      }
    }) || securityIssues.add(1);
  });

  // Test API Rate Limiting
  group('Rate Limiting Tests', () => {
    let rateLimitHit = false;
    let consecutiveRequests = 0;
    
    // Make rapid requests to trigger rate limiting
    for (let i = 0; i < 100; i++) {
      const response = http.get(`${API_BASE}/dashboard`);
      consecutiveRequests++;
      
      if (response.status === 429) {
        rateLimitHit = true;
        break;
      }
      
      // Stop if we get other errors
      if (response.status >= 500) {
        break;
      }
    }
    
    check({ rateLimitHit, consecutiveRequests }, {
      'Rate limiting active': (obj) => obj.rateLimitHit || obj.consecutiveRequests < 50,
      'Rate limit reasonable': (obj) => obj.consecutiveRequests > 10, // Not too aggressive
    }) || securityIssues.add(1);
  });

  // Test Information Disclosure
  group('Information Disclosure Tests', () => {
    
    // Test error message disclosure
    const errorResponse = http.get(`${API_BASE}/nonexistent-endpoint`);
    check(errorResponse, {
      'No sensitive info in 404 errors': (r) => 
        !r.body.includes('database') && 
        !r.body.includes('password') && 
        !r.body.includes('secret') &&
        !r.body.includes('stack trace'),
    }) || securityIssues.add(1);

    // Test debug information disclosure
    const debugResponse = http.get(`${API_BASE}/dashboard?debug=1`);
    check(debugResponse, {
      'No debug info disclosure': (r) => 
        !r.body.includes('XDEBUG') && 
        !r.body.includes('var_dump') && 
        !r.body.includes('print_r'),
    }) || securityIssues.add(1);
  });
}

export function handleSummary(data) {
  const securityReport = {
    timestamp: new Date().toISOString(),
    summary: {
      total_checks: data.metrics.checks ? data.metrics.checks.values.passes + data.metrics.checks.values.fails : 0,
      security_issues: data.metrics.security_issues ? data.metrics.security_issues.values.count : 0,
      auth_bypass_attempts: data.metrics.auth_bypass_attempts ? data.metrics.auth_bypass_attempts.values.count : 0,
      injection_attempts: data.metrics.injection_attempts ? data.metrics.injection_attempts.values.count : 0,
    },
    thresholds: data.thresholds,
    recommendations: generateSecurityRecommendations(data)
  };

  return {
    'security-report.json': JSON.stringify(securityReport, null, 2),
  };
}

function generateSecurityRecommendations(data) {
  const recommendations = [];
  
  if (data.metrics.security_issues && data.metrics.security_issues.values.count > 0) {
    recommendations.push("Review and fix identified security vulnerabilities");
  }
  
  if (data.metrics.auth_bypass_attempts && data.metrics.auth_bypass_attempts.values.count > 0) {
    recommendations.push("Strengthen authentication mechanisms");
  }
  
  if (data.metrics.injection_attempts && data.metrics.injection_attempts.values.count > 0) {
    recommendations.push("Implement better input validation and sanitization");
  }
  
  recommendations.push("Regular security audits recommended");
  recommendations.push("Consider implementing Web Application Firewall (WAF)");
  recommendations.push("Monitor and log all security events");
  
  return recommendations;
}