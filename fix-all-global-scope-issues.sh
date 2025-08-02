#!/bin/bash

echo "🔧 Fixing ALL withoutGlobalScopes() patterns..."
echo ""

# Fix BranchController
echo "Fixing BranchController..."
sed -i 's/Branch::withoutGlobalScopes()/Branch::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/BranchController.php

# Fix CustomerController  
echo "Fixing CustomerController..."
sed -i 's/Customer::withoutGlobalScopes()/Customer::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/CustomerController.php

# Fix CallController
echo "Fixing CallController..."
sed -i 's/Call::withoutGlobalScopes()/Call::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/CallController.php

# Fix AppointmentController
echo "Fixing AppointmentController..."
sed -i 's/Appointment::withoutGlobalScopes()/Appointment::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/AppointmentController.php

# Fix StaffController
echo "Fixing StaffController..."
sed -i 's/Staff::withoutGlobalScopes()/Staff::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/StaffController.php

# Fix InvoiceController
echo "Fixing InvoiceController..."
sed -i 's/Invoice::withoutGlobalScopes()/Invoice::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/InvoiceController.php

# Fix IntegrationController
echo "Fixing IntegrationController..."
sed -i 's/Integration::withoutGlobalScopes()/Integration::where("company_id", auth()->user()->company_id)/g' app/Http/Controllers/Admin/Api/IntegrationController.php

# Fix relationship withoutGlobalScopes in closures
echo "Fixing relationship scopes..."
find app/Http/Controllers/Admin/Api -name "*.php" -exec sed -i 's/function($q) { $q->withoutGlobalScopes(); }/function($q) { $q->where("company_id", auth()->user()->company_id); }/g' {} \;

# Fix RetellEnhancedWebhookController remaining issue
echo "Fixing RetellEnhancedWebhookController..."
sed -i 's/Customer::withoutGlobalScope(\\App\\Scopes\\TenantScope::class)->find/Customer::find/g' app/Http/Controllers/RetellEnhancedWebhookController.php

# Fix CompactOperationsWidget
echo "Fixing CompactOperationsWidget..."
# Already has proper company filtering, just need to ensure no withoutGlobalScope calls

echo ""
echo "📊 Checking results..."
TOTAL_WITHOUT=$(grep -r "withoutGlobalScope" app/Http/Controllers/ --include="*.php" | wc -l)
CRITICAL_WITHOUT=$(grep -r "withoutGlobalScopes()" app/Http/Controllers/ --include="*.php" | wc -l)

echo "- Total withoutGlobalScope calls: $TOTAL_WITHOUT"
echo "- Critical withoutGlobalScopes() calls: $CRITICAL_WITHOUT"

echo ""
echo "🧪 Testing syntax on critical files..."
for file in app/Http/Controllers/Admin/Api/BranchController.php app/Http/Controllers/Admin/Api/CustomerController.php app/Http/Controllers/Admin/Api/CallController.php; do
    if [ -f "$file" ]; then
        if php -l "$file" >/dev/null 2>&1; then
            echo "✅ $file - OK"
        else
            echo "❌ $file - Syntax Error"
            php -l "$file"
        fi
    fi
done

echo ""
echo "✅ Comprehensive fix complete!"