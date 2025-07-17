#!/bin/bash

# Run all currently working tests
# Total expected: 40 tests (32 passing + 8 from SensitiveDataMasker)

echo "Running AskProAI Working Test Suite..."
echo "====================================="

php artisan test \
  tests/Unit/Dashboard/DashboardMetricsServiceTest.php \
  tests/Feature/SimpleTest.php \
  tests/Feature/CriticalFixesTest.php \
  tests/Unit/Services/Webhook/WebhookDeduplicationServiceTest.php \
  tests/Unit/Mocks/MockServicesTest.php \
  tests/Unit/DatabaseConnectionTest.php \
  tests/Unit/Security/SensitiveDataMaskerTest.php \
  --no-coverage

echo ""
echo "Test run complete!"
echo "Expected: ~40 tests total"
echo "If you see fewer tests, check for new failures."