/**
 * k6 Load Test Configuration
 * AskPro AI Gateway
 */

// Environment configuration
export const config = {
    // Base URL - override with K6_BASE_URL env var
    baseUrl: __ENV.K6_BASE_URL || 'http://localhost',

    // API authentication (if needed)
    apiKey: __ENV.K6_API_KEY || '',

    // Test data
    testCompanyId: __ENV.K6_TEST_COMPANY_ID || '1',
    testPhoneNumber: __ENV.K6_TEST_PHONE || '+4917612345678',

    // Retell webhook simulation
    retellAgentId: __ENV.K6_RETELL_AGENT_ID || 'test-agent-id',

    // Thresholds (ms)
    thresholds: {
        voiceHotPath: {
            p50: 200,
            p95: 400,
            p99: 500,
        },
        bookingFlow: {
            p50: 1000,
            p95: 2500,
            p99: 3000,
        }
    }
};

// Standard headers for API requests
export function getHeaders() {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };

    if (config.apiKey) {
        headers['Authorization'] = `Bearer ${config.apiKey}`;
    }

    return headers;
}

// Generate unique call ID for testing
export function generateCallId() {
    return `test-call-${Date.now()}-${Math.random().toString(36).substring(2, 11)}`;
}

// Generate random phone number for testing
export function generatePhoneNumber() {
    const prefix = '+49176';
    const number = Math.floor(10000000 + Math.random() * 90000000);
    return `${prefix}${number}`;
}
