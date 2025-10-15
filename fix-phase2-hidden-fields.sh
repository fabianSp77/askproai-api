#!/bin/bash
# Fix Phase 2: Hidden Fields Architecture
# Priority: 🔴 CRITICAL - Execute after Phase 1
# Time: 15 minutes
# Risk: Medium (requires code change + testing)

set -e

echo "======================================"
echo "Phase 2: Hidden Fields Architecture Fix"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Backing up current AppointmentResource.php...${NC}"
BACKUP_FILE="/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php.backup-$(date +%Y%m%d_%H%M%S)"
cp /var/www/api-gateway/app/Filament/Resources/AppointmentResource.php "$BACKUP_FILE"
echo -e "${GREEN}✅ Backup created: $BACKUP_FILE${NC}"

echo ""
echo -e "${YELLOW}Step 2: Applying fix to AppointmentResource.php...${NC}"
echo ""
echo "This fix will:"
echo "  1. Move hidden fields (service_id, staff_id, branch_id, customer_id) to TOP LEVEL of form schema"
echo "  2. Ensure fields are ALWAYS rendered to DOM (even in CREATE context)"
echo "  3. Allow Alpine.js querySelector to find and populate fields"
echo ""

read -p "Press Enter to apply fix (or Ctrl+C to cancel)..."

# Apply the fix using a temporary PHP script
cat > /tmp/fix-hidden-fields.php << 'EOPHP'
<?php

$file = '/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php';
$content = file_get_contents($file);

// Find the form() method and extract its schema
$pattern = '/(public static function form\(Form \$form\): Form\s*\{[^}]*return \$form\s*->schema\(\[)(.*?)(\]\);[^}]*\})/s';

if (!preg_match($pattern, $content, $matches)) {
    echo "❌ ERROR: Could not find form() method pattern\n";
    exit(1);
}

$beforeSchema = $matches[1];
$schemaContent = $matches[2];
$afterSchema = $matches[3];

// Define the hidden fields to add at top level
$hiddenFields = <<<'HIDDEN'

                // ✅ GLOBAL HIDDEN FIELDS (2025-10-15 Fix)
                // Must be at TOP LEVEL to ensure DOM rendering in ALL contexts (create, edit, view)
                // Alpine.js in BookingFlowWrapper depends on these being in DOM
                Forms\Components\Hidden::make('service_id')
                    ->default(null)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $service = Service::find($state);
                            if ($service) {
                                $set('duration_minutes', $service->duration_minutes ?? 30);
                                $set('price', $service->price);
                            }
                        }
                    }),

                Forms\Components\Hidden::make('staff_id')
                    ->default(null),

                Forms\Components\Hidden::make('branch_id')
                    ->default(null),

                Forms\Components\Hidden::make('customer_id')
                    ->default(null),

HIDDEN;

// Remove OLD hidden fields from inside the "💇 Was wird gemacht?" section
// Pattern: Find the section and remove hidden fields inside it
$schemaContent = preg_replace(
    '/\/\/ Hidden Fields: For BookingFlowWrapper.*?Forms\\\\Components\\\\Hidden::make\(\'staff_id\'\)[^,]*,/s',
    '// (Hidden fields moved to top-level schema - see above)',
    $schemaContent
);

// Also remove individual Hidden field declarations if they exist
$schemaContent = preg_replace(
    '/Forms\\\\Components\\\\Hidden::make\(\'(service_id|staff_id|branch_id|customer_id)\'\)[^,]*,\s*/s',
    '',
    $schemaContent
);

// Rebuild the form method with hidden fields at the top
$newFormMethod = $beforeSchema . $hiddenFields . $schemaContent . $afterSchema;

// Replace in original content
$newContent = preg_replace($pattern, $newFormMethod, $content);

// Write back
file_put_contents($file, $newContent);

echo "✅ AppointmentResource.php updated\n";
echo "   - Hidden fields moved to top-level schema\n";
echo "   - Old hidden field declarations removed from sections\n";

EOPHP

php /tmp/fix-hidden-fields.php

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Fix script failed. Restoring backup...${NC}"
    cp "$BACKUP_FILE" /var/www/api-gateway/app/Filament/Resources/AppointmentResource.php
    exit 1
fi

echo -e "${GREEN}✅ Code changes applied${NC}"

echo ""
echo -e "${YELLOW}Step 3: Clearing caches...${NC}"
php artisan view:clear
echo -e "${GREEN}✅ View cache cleared${NC}"

php artisan cache:clear
echo -e "${GREEN}✅ Application cache cleared${NC}"

# Try to clear Filament cache if artisan command exists
if php artisan list | grep -q "filament:cache-components"; then
    php artisan filament:cache-components
    echo -e "${GREEN}✅ Filament cache cleared${NC}"
fi

echo ""
echo -e "${YELLOW}Step 4: Verifying fix...${NC}"

# Create a simple verification script
cat > /tmp/verify-hidden-fields.php << 'EOPHP'
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$resource = new \App\Filament\Resources\AppointmentResource();
$form = $resource::form(\Filament\Forms\Form::make());
$schema = $form->getSchema();

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Form Schema Analysis\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total top-level components: " . count($schema) . "\n";

$hiddenFieldsFound = [];
foreach ($schema as $component) {
    if ($component instanceof \Filament\Forms\Components\Hidden) {
        $hiddenFieldsFound[] = $component->getName();
    }
}

echo "Hidden fields at top level: " . count($hiddenFieldsFound) . "\n";
if (count($hiddenFieldsFound) > 0) {
    echo "  - " . implode("\n  - ", $hiddenFieldsFound) . "\n";
}

$requiredFields = ['service_id', 'staff_id', 'branch_id', 'customer_id'];
$missing = array_diff($requiredFields, $hiddenFieldsFound);

if (empty($missing)) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ All required hidden fields found at top level\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    exit(0);
} else {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "❌ Missing hidden fields at top level:\n";
    echo "  - " . implode("\n  - ", $missing) . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    exit(1);
}

EOPHP

cd /var/www/api-gateway && php /tmp/verify-hidden-fields.php

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ Phase 2 Complete: Hidden Fields Fixed${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "Next: Browser Testing"
    echo ""
    echo "Test in browser:"
    echo "  1. Navigate to /admin/appointments/create"
    echo "  2. Open browser console (F12)"
    echo "  3. Select service → Check console for errors"
    echo "  4. Should see: '[BookingFlowWrapper] service_id updated: ...'"
    echo "  5. NO MORE: '[BookingFlowWrapper] service_id field not found'"
    echo ""
else
    echo ""
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${RED}❌ Phase 2 Failed: Verification Failed${NC}"
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "Manual intervention required."
    echo "Backup available at: $BACKUP_FILE"
    exit 1
fi

# Cleanup
rm -f /tmp/fix-hidden-fields.php /tmp/verify-hidden-fields.php
