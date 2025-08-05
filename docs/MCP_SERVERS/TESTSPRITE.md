# TestSprite MCP Server Integration

## Overview

TestSprite MCP Server provides AI-powered automated testing capabilities for AskProAI. It generates comprehensive test plans, creates test code, runs tests, and diagnoses failures automatically.

## Features

- **Test Plan Generation**: Create comprehensive test plans from requirements
- **Automatic Test Generation**: Generate PHPUnit/Pest tests for any component
- **Test Execution**: Run tests with parallel execution support
- **Failure Diagnosis**: AI-powered analysis of test failures with fix suggestions
- **Coverage Reports**: Generate code coverage in multiple formats

## Configuration

### 1. Add Environment Variables

```bash
# .env
TESTSPRITE_API_KEY=your_api_key_here
TESTSPRITE_API_URL=https://api.testsprite.com/v1
```

### 2. Get API Key

1. Sign up at [TestSprite Dashboard](https://app.testsprite.com)
2. Navigate to Settings → API Keys
3. Create new API key
4. Copy to `.env` file

## Usage

### Via Artisan Command

```bash
# Generate complete test workflow
php artisan testsprite:test RetellService

# Generate test plan only
php artisan testsprite:test RetellService --plan

# Generate tests only
php artisan testsprite:test RetellService --generate

# Run existing tests
php artisan testsprite:test RetellService --run

# Diagnose failures
php artisan testsprite:test RetellService --diagnose

# Generate coverage report
php artisan testsprite:test RetellService --coverage
```

### Via MCP Tools

The TestSprite MCP server exposes the following tools:

#### create_test_plan
Generate a comprehensive test plan from requirements.

```php
$toolCall = new ToolCall(
    name: 'create_test_plan',
    arguments: [
        'requirements' => 'Test appointment booking with edge cases',
        'test_type' => 'all' // unit, integration, e2e, all
    ]
);
```

#### generate_tests
Generate test code for a component.

```php
$toolCall = new ToolCall(
    name: 'generate_tests',
    arguments: [
        'component' => 'AppointmentService',
        'framework' => 'pest' // phpunit, pest, laravel
    ]
);
```

#### run_tests
Execute tests with detailed results.

```php
$toolCall = new ToolCall(
    name: 'run_tests',
    arguments: [
        'test_path' => 'tests/Feature/AppointmentTest.php',
        'parallel' => true
    ]
);
```

#### diagnose_failure
Analyze test failures and suggest fixes.

```php
$toolCall = new ToolCall(
    name: 'diagnose_failure',
    arguments: [
        'test_output' => $failureOutput
    ]
);
```

#### coverage_report
Generate test coverage report.

```php
$toolCall = new ToolCall(
    name: 'coverage_report',
    arguments: [
        'format' => 'html' // text, html, json
    ]
);
```

## Example Workflow

### 1. Test a New Feature

```bash
# Generate test plan for new feature
php artisan testsprite:test WhatsAppIntegration --plan

# Generate tests based on plan
php artisan testsprite:test WhatsAppIntegration --generate

# Run the generated tests
php artisan testsprite:test WhatsAppIntegration --run
```

### 2. Fix Failing Tests

```bash
# Run tests and capture output
php artisan test > test_output.txt

# Diagnose failures
php artisan testsprite:test ServiceName --diagnose
# Paste the failure output when prompted

# TestSprite will provide:
# - Root cause analysis
# - Suggested code fixes
# - Additional context
```

### 3. Improve Test Coverage

```bash
# Check current coverage
php artisan testsprite:test AppointmentService --coverage

# Generate additional tests for uncovered code
php artisan testsprite:test AppointmentService --generate
```

## Integration with CI/CD

Add to your GitHub Actions workflow:

```yaml
- name: Run TestSprite Tests
  env:
    TESTSPRITE_API_KEY: ${{ secrets.TESTSPRITE_API_KEY }}
  run: |
    php artisan testsprite:test all --run
    php artisan testsprite:test all --coverage
```

## Best Practices

1. **Use Test Plans**: Always generate a test plan before writing tests
2. **Review Generated Tests**: AI-generated tests should be reviewed
3. **Diagnose Failures**: Use the diagnosis tool for complex failures
4. **Monitor Coverage**: Aim for >80% code coverage
5. **Integrate Early**: Add TestSprite to CI/CD pipeline

## Troubleshooting

### API Key Issues
```bash
# Verify API key is set
php artisan tinker
>>> config('services.testsprite.api_key')
```

### Network Issues
```bash
# Test API connectivity
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://api.testsprite.com/v1/health
```

### Generated Tests Not Working
- Ensure component path is correct
- Check Laravel/PHP version compatibility
- Review generated test code for syntax errors

## Resources

- [TestSprite Documentation](https://docs.testsprite.com)
- [MCP Integration Guide](https://docs.testsprite.com/mcp/overview)
- [Support](https://support.testsprite.com)