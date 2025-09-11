# AskProAI Comprehensive Testing Framework

**Version:** 1.0  
**Created:** 2025-09-03  
**Author:** Claude Code Analysis  

## Overview

This document describes the comprehensive testing framework created for the AskProAI system. The framework provides automated testing across multiple dimensions: system health, security, functionality, performance, and asset management.

## Framework Components

### 1. Master Test Runner (`master_test_runner.sh`)

The central orchestration script that coordinates all testing activities.

**Features:**
- Unified test execution interface
- Configurable test suite selection
- Comprehensive reporting
- SuperClaude integration commands
- Multiple execution modes (full, quick, targeted)

**Usage:**
```bash
# Run all tests
./scripts/master_test_runner.sh

# Quick health check
./scripts/master_test_runner.sh --quick

# Security audit only
./scripts/master_test_runner.sh --security-only

# Skip performance tests
./scripts/master_test_runner.sh --skip-performance
```

### 2. System Health Tests (`comprehensive_health_check.sh`)

Monitors critical system components and validates operational status.

**Test Categories:**
- ✅ **System Services**: nginx, PHP-FPM, MySQL, Redis
- 🌐 **HTTP Endpoints**: Main site, admin panel, API health
- 🗄️ **Database & Cache**: Connection tests, table counts, Redis status
- 📱 **Laravel Application**: Configuration, queue status, environment
- 🔒 **Security**: File protection, access controls
- 💾 **System Resources**: Disk space, memory usage, asset availability

**Critical Thresholds:**
- Disk usage: < 80% (warning), < 90% (critical)
- Response time: < 2s for pages, < 1s for APIs
- Database queries: < 500ms for complex operations

### 3. Security Audit (`security_audit.sh`)

Comprehensive security assessment covering multiple attack vectors.

**Security Checks:**
- 📂 **File Permissions**: .env, storage, cache directories
- 🌐 **Web Exposure**: Sensitive file accessibility (.env, .git)
- ⚙️ **Laravel Security**: Debug mode, environment, app key
- 🗄️ **Database Security**: Password strength, host configuration
- 🔑 **API Key Security**: Placeholder detection, key length validation
- 📋 **Security Headers**: X-Frame-Options, HSTS, CSP, etc.
- 🐛 **Vulnerability Assessment**: Hardcoded secrets, SQL injection risks
- 📝 **Log File Security**: Sensitive data exposure, file permissions

**Risk Levels:**
- 🔴 **HIGH**: Immediate security threats requiring urgent attention
- 🟡 **MEDIUM**: Security improvements recommended
- 🟢 **LOW**: Minor security considerations

### 4. Functional Tests (`functional_test_suite.sh`)

End-to-end functionality validation across all system components.

**Test Suites:**
- 🌐 **HTTP Endpoints**: Status codes, response content validation
- 🔐 **Authentication**: Login pages, protected route security
- 📡 **API Endpoints**: Webhook validation, CORS handling
- 🗄️ **Database Connectivity**: Connection tests, table operations
- 📁 **File System Operations**: Write permissions, cache directories
- 🎨 **Asset Loading**: CSS/JS availability, missing asset detection
- 📥 **Queue System**: Queue connectivity, Horizon status
- 🔄 **Cache System**: Cache operations, Redis functionality
- ⚙️ **Configuration**: Laravel config validation, environment checks
- ⚡ **Performance Benchmarks**: Response times, load handling

### 5. Performance Testing (`performance_test_suite.sh`)

Comprehensive performance analysis with detailed metrics and load testing.

**Performance Areas:**
- 📊 **Page Load Performance**: Main pages, admin interface
- 🚀 **API Performance**: Response times, throughput testing
- 🗄️ **Database Performance**: Query optimization, join operations
- 🎨 **Asset Performance**: CSS/JS loading times, compression
- 💻 **System Resources**: Memory usage, CPU load, disk I/O
- 📈 **Load Testing**: Concurrent user simulation, stress testing
- 🔄 **Cache Performance**: Cache hit rates, Redis operations

**Performance Thresholds:**
- Page loads: < 3s (good), < 2s (excellent)
- API responses: < 1s (standard), < 0.5s (optimal)
- Database queries: < 0.5s (simple), < 1s (complex)
- Asset loading: < 2s (acceptable), < 1s (fast)

### 6. Asset Detection (`missing_asset_detector.sh`)

Advanced asset management with intelligent detection and recovery.

**Features:**
- 📊 **Log Analysis**: Nginx error log parsing for 404 assets
- 🔍 **Asset Discovery**: Multiple search path checking
- 🧠 **Fuzzy Matching**: Similar asset identification
- 🔧 **Automatic Recovery**: Asset rebuilding, placeholder creation
- 📋 **Manifest Generation**: Comprehensive asset inventory
- 🎯 **Vite Integration**: Manifest analysis, build validation

**Known Issues Addressed:**
- `wizard-progress-enhancer-BntUnTIW.js`
- `askproai-state-manager-BtNc_89J.js`
- `responsive-zoom-handler-DaecGYuG.js`

## Integration & Usage

### Quick Start

```bash
# Make scripts executable
chmod +x /var/www/api-gateway/scripts/*.sh

# Run comprehensive test suite
cd /var/www/api-gateway/scripts
./master_test_runner.sh

# View results
cat /var/www/api-gateway/storage/logs/test_results_*/comprehensive_test_report.md
```

### SuperClaude Commands

The framework includes SuperClaude integration for advanced automation:

```bash
# Load testing framework
/sc:load testing-framework

# Automated system optimization
/sc:optimize --target=performance --scope=system

# Security hardening
/sc:secure --audit --recommendations

# Asset management
/sc:assets --detect-missing --optimize --rebuild

# Comprehensive health monitoring
/sc:health --full-check --security-audit
```

### Scheduled Testing

For production monitoring, implement scheduled testing:

```bash
# Add to crontab
# Daily health check at 6 AM
0 6 * * * /var/www/api-gateway/scripts/master_test_runner.sh --quick > /var/www/api-gateway/storage/logs/daily_health.log 2>&1

# Weekly comprehensive test on Sundays at 3 AM
0 3 * * 0 /var/www/api-gateway/scripts/master_test_runner.sh > /var/www/api-gateway/storage/logs/weekly_comprehensive.log 2>&1
```

## Critical Issues Analysis

Based on log analysis, the following critical issues were identified and addressed:

### 1. Missing JavaScript Assets (HIGH PRIORITY)

**Issue:** Multiple JavaScript files returning 404 errors
- `wizard-progress-enhancer-BntUnTIW.js`
- `askproai-state-manager-BtNc_89J.js`  
- `responsive-zoom-handler-DaecGYuG.js`

**Impact:** Broken frontend functionality, poor user experience

**Solution:** 
- Asset detection script with automatic recovery
- Vite manifest validation
- Build process verification

### 2. Security Vulnerabilities (MEDIUM PRIORITY)

**Issues Found:**
- Multiple attempts to access `.env` files (403 blocked ✅)
- `.git` directory access attempts (403 blocked ✅)
- SSL handshake failures from various IPs

**Status:** Security measures are working correctly, but monitoring should continue

### 3. Database Authentication Issues (LOW PRIORITY)

**Issue:** Occasional `Access denied for user 'askproai_user'@'localhost'`

**Analysis:** Intermittent connection issues, possibly related to connection pooling

**Monitoring:** Database performance testing included in framework

### 4. SSL Certificate Management (MEDIUM PRIORITY)

**Issue:** SSL handshake failures indicating certificate/TLS configuration issues

**Recommendation:** Regular certificate renewal monitoring and TLS configuration review

## Test Results Interpretation

### Exit Codes
- **0**: All tests passed, system optimal
- **1**: Some tests failed, issues require attention  
- **2**: Warnings detected, maintenance recommended
- **3+**: Critical failures, immediate intervention required

### Log Files Structure
```
/var/www/api-gateway/storage/logs/
├── master_test_YYYYMMDD_HHMMSS.log          # Master orchestration log
├── test_results_YYYYMMDD_HHMMSS/            # Test session directory
│   ├── comprehensive_test_report.md          # Executive summary
│   ├── health_check_output.log               # Health test details
│   ├── security_audit_output.log             # Security findings
│   ├── functional_tests_output.log           # Functionality results
│   ├── performance_tests_output.log          # Performance metrics
│   └── asset_detection_output.log            # Asset analysis
└── performance_results_YYYYMMDD_HHMMSS.json # Metrics for analysis
```

## Maintenance & Updates

### Framework Maintenance
- Review and update security checks monthly
- Adjust performance thresholds based on system evolution
- Expand test coverage for new features
- Update asset detection patterns for new frameworks

### System Monitoring
- Implement alerting based on test results
- Archive old test results (>30 days)
- Monitor test execution times for framework optimization
- Regular review of false positives/negatives

## Troubleshooting

### Common Issues

**Permission Errors:**
```bash
chmod +x /var/www/api-gateway/scripts/*.sh
chown -R www-data:www-data /var/www/api-gateway/storage/logs
```

**Missing Dependencies:**
```bash
# Required packages
apt install -y bc curl jq apache2-utils

# Node.js for asset building
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
apt install -y nodejs
```

**Database Connection Issues:**
```bash
# Test database connectivity
cd /var/www/api-gateway
php artisan tinker --execute="DB::connection()->getPdo();"
```

## Advanced Usage

### Custom Test Development

Create custom tests following the framework patterns:

```bash
#!/bin/bash
# custom_test.sh

# Follow framework logging patterns
print_test() {
    case "$1" in
        "PASS") echo -e "\033[0;32m✓\033[0m $2" ;;
        "FAIL") echo -e "\033[0;31m✗\033[0m $2" ;;
    esac
}

# Test implementation
my_custom_test() {
    if [ condition ]; then
        print_test "PASS" "Custom test description"
        return 0
    else
        print_test "FAIL" "Custom test description"
        return 1
    fi
}
```

### Integration with CI/CD

```yaml
# GitHub Actions example
name: AskProAI System Tests
on: [push, pull_request]

jobs:
  system-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run comprehensive tests
        run: |
          chmod +x scripts/*.sh
          ./scripts/master_test_runner.sh --quick
      - name: Upload test results
        uses: actions/upload-artifact@v2
        with:
          name: test-results
          path: storage/logs/test_results_*
```

## Conclusion

This comprehensive testing framework provides a robust foundation for monitoring and maintaining the AskProAI system. It addresses the specific issues identified in the system logs while providing a scalable structure for ongoing quality assurance.

The framework is designed to be:
- **Comprehensive**: Covers all critical system aspects
- **Automated**: Minimal manual intervention required
- **Scalable**: Easy to extend with new tests
- **Actionable**: Provides clear remediation guidance
- **Integrated**: Works with existing tools and workflows

Regular execution of this testing framework will ensure system reliability, security, and performance while providing early warning of potential issues.

---

**Next Steps:**
1. Execute initial comprehensive test run: `./scripts/master_test_runner.sh`
2. Review generated reports and address any critical issues
3. Implement scheduled testing for ongoing monitoring
4. Customize thresholds and tests based on operational experience
5. Integrate with monitoring and alerting systems

For questions or framework enhancements, refer to the individual script documentation and logging outputs.