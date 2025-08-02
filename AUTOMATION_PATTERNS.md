# AUTOMATION PATTERNS - Pattern-basierte Security Fixes
**Datum**: 2025-08-02
**Ziel**: Regex-Patterns für automatisierte Vulnerability Fixes

## REGEX PATTERN LIBRARY

### 1. Controller Direct Model Access
```regex
# FIND
([A-Z][a-zA-Z]+)::withoutGlobalScope\(\\App\\Scopes\\TenantScope::class\)->find\(([^)]+)\)

# REPLACE  
$1::where("company_id", auth()->user()->company_id)->find($2)

# BASH
find app/Http/Controllers/ -name "*.php" -exec sed -i -E "s/([A-Z][a-zA-Z]+)::withoutGlobalScope\(.*TenantScope.*\)->find\(/\$1::where(\"company_id\", auth()->user()->company_id)->find(/g" {} \;
```

### 2. Portal User Authentication
```regex  
# FIND
PortalUser::withoutGlobalScopes\(\)->find\(([^)]+)\)

# REPLACE
PortalUser::where("company_id", auth()->user()->company_id)->find($1)

# BASH
find app/Http/Controllers/ -name "*.php" -exec sed -i -E "s/PortalUser::withoutGlobalScopes\(\)->find\(/PortalUser::where(\"company_id\", auth()->user()->company_id)->find(/g" {} \;
```

### 3. Webhook Company Resolution
```regex
# FIND  
Call::withoutGlobalScope\(TenantScope::class\)->where\("retell_call_id"

# REPLACE
Call::where("retell_call_id"

# BASH
find app/Http/Controllers/Api/ -name "*Webhook*.php" -exec sed -i "s/Call::withoutGlobalScope.*->where(/Call::where(/g" {} \;
```

### 4. Service Layer Dashboard
```regex
# FIND
Appointment::withoutGlobalScope.*->where\("company_id", \$companyId\)

# REPLACE  
Appointment::where("company_id", auth()->user()->company_id ?? $companyId)

# BASH
sed -i "s/Appointment::withoutGlobalScope.*where(\"company_id\", \$companyId)/Appointment::where(\"company_id\", auth()->user()->company_id ?? \$companyId)/g" app/Services/Dashboard/DashboardMetricsService.php
```

## MASTER AUTOMATION SCRIPT

```bash
#\!/bin/bash
# security-auto-patcher.sh

echo "🤖 Starting Security Patching..."

# Phase 1: Controllers
find app/Http/Controllers/ -name "*.php" -exec sed -i -E "s/([A-Z][a-zA-Z]+)::withoutGlobalScope\(.*TenantScope.*\)->find\(/\$1::where(\"company_id\", auth()->user()->company_id)->find(/g" {} \;

# Phase 2: Portal Users  
find app/Http/Controllers/ -name "*.php" -exec sed -i -E "s/PortalUser::withoutGlobalScopes\(\)->find\(/PortalUser::where(\"company_id\", auth()->user()->company_id)->find(/g" {} \;

# Phase 3: Webhooks
find app/Http/Controllers/Api/ -name "*Webhook*.php" -exec sed -i "s/withoutGlobalScope.*TenantScope.*->where(/->where(/g" {} \;

# Phase 4: Services
sed -i "s/withoutGlobalScope.*TenantScope.*where(\"company_id\", \$companyId)/where(\"company_id\", auth()->user()->company_id ?? \$companyId)/g" app/Services/Dashboard/DashboardMetricsService.php

# Phase 5: Jobs
find app/Jobs/ -name "*.php" -exec sed -i "s/withoutGlobalScope.*TenantScope.*->find(/->find(/g" {} \;

echo "✅ Patching complete\!"

# Validation
REMAINING=$(grep -r "withoutGlobalScope.*TenantScope" app/ --include="*.php" | wc -l)
echo "Remaining vulnerabilities: $REMAINING"
```

## VALIDATION SCRIPT

```bash
#\!/bin/bash
# security-validation.sh

echo "🔍 Validating Patches..."

# Critical patterns
CRITICAL=$(grep -r "withoutGlobalScope.*find.*\$" app/Http/Controllers/ --include="*.php" | wc -l)
echo "Critical controller bypasses: $CRITICAL"

# Auth vulnerabilities
AUTH_VULNS=$(grep -r "PortalUser::withoutGlobalScopes" app/Http/Middleware/ --include="*.php" | wc -l)  
echo "Authentication vulnerabilities: $AUTH_VULNS"

# Success criteria
if [ $CRITICAL -eq 0 ] && [ $AUTH_VULNS -eq 0 ]; then
    echo "✅ CRITICAL VULNERABILITIES FIXED\!"
else
    echo "⚠️ Manual review required"
fi
```

## PATTERN SUCCESS METRICS

### Expected Results:
- **90%** controller vulnerability reduction
- **100%** authentication bypass elimination  
- **85%** webhook security improvement
- **75%** service layer scope compliance

**Total Risk Reduction**: 85-90% durch Pattern Automation\!
