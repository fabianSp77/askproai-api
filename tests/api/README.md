# API Test Suite

## Overview

This directory contains the Postman/Newman test suite for the AskProAI API. Tests cover all major endpoints including authentication, appointments, calls, customers, and webhooks.

## Structure

```
tests/api/
├── askproai-api-collection.json  # Main Postman collection
├── environments/                 # Environment configurations
│   ├── local.json               # Local development
│   ├── ci.json                  # CI/CD pipeline
│   └── production.json          # Production (not committed)
├── run-api-tests.js             # Newman test runner
├── results/                     # Test results (gitignored)
└── scripts/                     # Additional test scripts
```

## Running Tests

### Using npm scripts

```bash
# Run tests with local environment
npm run test:api

# Run tests for CI
npm run test:api:ci
```

### Using Newman directly

```bash
# Install Newman globally
npm install -g newman

# Run with local environment
newman run askproai-api-collection.json -e environments/local.json

# Run with custom reporter
newman run askproai-api-collection.json -e environments/local.json \
  --reporters cli,html \
  --reporter-html-export results/test-report.html
```

### Using the custom runner

```bash
# Run with default (local) environment
node run-api-tests.js

# Run with CI environment
NODE_ENV=ci node run-api-tests.js
```

## Environment Variables

Each environment file contains:

- `base_url` - API base URL
- `test_email` - Test user email
- `test_password` - Test user password
- `auth_token` - Dynamic auth token (set by login)
- `user_id` - Dynamic user ID (set by login)
- `retell_webhook_secret` - Webhook signature secret

## Test Coverage

### Authentication
- ✅ Portal login
- ✅ Get current user
- ✅ Logout

### Dashboard
- ✅ Get dashboard statistics

### Appointments
- ✅ List appointments with pagination
- ✅ Create new appointment
- ✅ Get appointment details
- ✅ Update appointment
- ✅ Delete appointment

### Calls
- ✅ List calls
- ✅ Get call details

### Customers
- ✅ List customers
- ✅ Search customers

### Webhooks
- ✅ Retell webhook processing
- ✅ Cal.com webhook (TODO)
- ✅ Stripe webhook (TODO)

### Health Checks
- ✅ API health endpoint

## Writing New Tests

1. Add new requests to the appropriate folder in the collection
2. Include pre-request scripts for setup
3. Add comprehensive test scripts with assertions
4. Use environment variables for dynamic values
5. Clean up test data in teardown

### Example Test Script

```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has required fields", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('id');
    pm.expect(jsonData).to.have.property('name');
});

pm.test("Response time is less than 500ms", function () {
    pm.expect(pm.response.responseTime).to.be.below(500);
});
```

## CI/CD Integration

Tests run automatically in GitHub Actions:

```yaml
- name: Run API Tests
  run: |
    npm ci
    npm run test:api:ci
```

## Debugging Failed Tests

1. Check the response body in test results
2. Verify environment variables are set correctly
3. Check API logs for errors
4. Run individual requests in Postman GUI
5. Use `console.log()` in test scripts

## Best Practices

1. **Isolation**: Each test should be independent
2. **Cleanup**: Delete test data after creation
3. **Assertions**: Test both positive and negative cases
4. **Performance**: Include response time checks
5. **Documentation**: Comment complex test logic
6. **Variables**: Use environment variables for configuration
7. **Error Handling**: Test error responses and edge cases