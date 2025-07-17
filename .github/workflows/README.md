# GitHub Actions CI/CD Workflows

## Overview

This directory contains the CI/CD workflows for the AskProAI project. These workflows automate testing, code quality checks, security scanning, and deployment processes.

## Workflows

### 1. Tests (`tests.yml`)
**Trigger**: Push to main/develop, PRs to main, daily schedule
**Purpose**: Run all test suites and ensure code quality

Jobs:
- **PHP Tests**: PHPUnit tests with coverage
- **JavaScript Tests**: Vitest tests with coverage
- **API Tests**: Newman/Postman API tests
- **Code Quality**: Linting and static analysis
- **Build**: Asset compilation
- **Deploy**: Production deployment (main branch only)

### 2. Security (`security.yml`)
**Trigger**: Push to main/develop, PRs, weekly schedule
**Purpose**: Security vulnerability scanning and analysis

Jobs:
- **Security Scan**: Trivy vulnerability scanner
- **Dependency Check**: Composer/npm audit
- **Code Security**: CodeQL analysis
- **Secrets Scan**: TruffleHog and Gitleaks
- **Docker Scan**: Container security (if applicable)
- **Security Headers**: HTTP header validation
- **OWASP Check**: Dependency vulnerability analysis

### 3. Performance (`performance.yml`)
**Trigger**: Push to main, PRs with 'performance' label, weekly schedule
**Purpose**: Performance testing and monitoring

Jobs:
- **Lighthouse**: Web performance metrics
- **K6 Load Test**: Load testing (with 'load-test' label)
- **PHP Benchmark**: PHPBench performance tests
- **Database Performance**: Query analysis and N+1 detection
- **Bundle Size**: JavaScript bundle size analysis

### 4. Documentation (`documentation.yml`)
**Trigger**: Changes to markdown files or docs/
**Purpose**: Documentation quality and maintenance

Jobs:
- **Check Docs**: Documentation health check
- **Spell Check**: CSpell spelling validation
- **Markdown Lint**: Markdown formatting
- **Broken Links**: Link validation

## Required Secrets

Configure these in GitHub repository settings:

```yaml
# Deployment
DEPLOY_KEY        # SSH key for deployment
DEPLOY_HOST       # Server hostname
DEPLOY_USER       # SSH username
DEPLOY_PATH       # Deployment directory

# External Services
CODECOV_TOKEN     # Codecov integration
SNYK_TOKEN        # Snyk security scanning

# Optional
SLACK_WEBHOOK     # Slack notifications
DISCORD_WEBHOOK   # Discord notifications
```

## Environment Setup

### Local Testing

```bash
# Test workflow locally with act
brew install act
act -j php-tests

# Validate workflow syntax
npm install -g actionlint
actionlint
```

### Required Services

The workflows expect these services:
- MySQL 8.0
- Redis 7
- PHP 8.3
- Node.js 20

## Workflow Management

### Skip CI

Add `[skip ci]` to commit message to skip workflows:
```bash
git commit -m "docs: Update README [skip ci]"
```

### Manual Triggers

Some workflows can be triggered manually:
1. Go to Actions tab
2. Select workflow
3. Click "Run workflow"

### Status Badges

Add to README.md:
```markdown
![Tests](https://github.com/yourusername/askproai/workflows/Tests/badge.svg)
![Security](https://github.com/yourusername/askproai/workflows/Security/badge.svg)
![Documentation](https://github.com/yourusername/askproai/workflows/Documentation/badge.svg)
```

## Debugging Failed Workflows

### Common Issues

1. **Test Failures**
   - Check test logs in workflow run
   - Download artifacts for detailed reports
   - Run tests locally to reproduce

2. **Timeout Issues**
   - Increase timeout in workflow
   - Check for hanging processes
   - Review service health checks

3. **Permission Errors**
   - Verify GITHUB_TOKEN permissions
   - Check file permissions in repo
   - Review deployment keys

### Debug Mode

Enable debug logging:
1. Go to Settings → Secrets
2. Add secret: `ACTIONS_STEP_DEBUG` = `true`
3. Re-run workflow

## Performance Optimization

- Use caching for dependencies
- Run jobs in parallel when possible
- Use matrix builds for multiple versions
- Optimize Docker layers
- Use artifact sharing between jobs

## Best Practices

1. **Keep workflows DRY**: Use composite actions for repeated steps
2. **Version actions**: Pin to specific versions (e.g., `actions/checkout@v4`)
3. **Secure secrets**: Never log sensitive data
4. **Monitor usage**: Check Actions billing regularly
5. **Clean artifacts**: Set retention policies
6. **Document changes**: Update this README when modifying workflows

## Contributing

When adding new workflows:
1. Follow existing naming conventions
2. Add documentation in this README
3. Test locally with `act` if possible
4. Add status badge to main README
5. Configure required secrets