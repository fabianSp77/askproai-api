#!/bin/bash

echo "🔧 Fixing Test Suite and Migration Issues..."

# 1. Fix SQLite-incompatible migrations
echo "📝 Fixing SQLite-incompatible migrations..."

# Fix staff company_id migration
cat > /tmp/fix_staff_migration.php << 'EOF'
<?php
$file = '/var/www/api-gateway/database/migrations/2025_06_18_add_company_id_to_staff_table.php';
$content = file_get_contents($file);

// Replace JOIN UPDATE with SQLite-compatible version
$content = preg_replace(
    '/DB::statement\(\'[^\']*UPDATE staff s[^\']*JOIN branches b[^\']*\'\);/s',
    "if (DB::getDriverName() === 'sqlite') {
                DB::statement('
                    UPDATE staff
                    SET company_id = (SELECT company_id FROM branches WHERE branches.id = staff.branch_id)
                    WHERE company_id IS NULL AND branch_id IS NOT NULL
                ');
            } else {
                DB::statement('
                    UPDATE staff s
                    JOIN branches b ON s.branch_id = b.id
                    SET s.company_id = b.company_id
                    WHERE s.company_id IS NULL AND s.branch_id IS NOT NULL
                ');
            }",
    $content
);

file_put_contents($file, $content);
echo "Fixed: $file\n";
EOF

php /tmp/fix_staff_migration.php

# Fix phone number migration
cat > /tmp/fix_phone_migration.php << 'EOF'
<?php
$file = '/var/www/api-gateway/database/migrations/2025_06_22_090808_move_retell_agent_id_from_branches_to_phone_numbers.php';
$content = file_get_contents($file);

// Replace JOIN UPDATE with SQLite-compatible version
$content = preg_replace(
    '/DB::statement\(\'[^\']*UPDATE phone_numbers pn[^\']*JOIN branches b[^\']*\'\);/s',
    "if (DB::getDriverName() === 'sqlite') {
                DB::statement('
                    UPDATE phone_numbers
                    SET retell_agent_id = (SELECT retell_agent_id FROM branches WHERE branches.id = phone_numbers.branch_id)
                    WHERE retell_agent_id IS NULL AND branch_id IS NOT NULL
                ');
            } else {
                DB::statement('
                    UPDATE phone_numbers pn
                    JOIN branches b ON pn.branch_id = b.id
                    SET pn.retell_agent_id = b.retell_agent_id
                    WHERE pn.retell_agent_id IS NULL AND b.retell_agent_id IS NOT NULL
                ');
            }",
    $content
);

file_put_contents($file, $content);
echo "Fixed: $file\n";
EOF

php /tmp/fix_phone_migration.php

# Fix populate missing phone numbers migration
cat > /tmp/fix_populate_phone_migration.php << 'EOF'
<?php
$file = '/var/www/api-gateway/database/migrations/2025_06_26_172057_populate_missing_phone_numbers.php';
$content = file_get_contents($file);

// Replace JOIN UPDATE with SQLite-compatible version
$content = preg_replace(
    '/DB::statement\(\'[^\']*UPDATE phone_numbers pn[^\']*JOIN branches b[^\']*\'\);/s',
    "if (DB::getDriverName() === 'sqlite') {
                DB::statement('
                    UPDATE phone_numbers
                    SET branch_id = (SELECT id FROM branches WHERE branches.phone_number = phone_numbers.number AND phone_numbers.branch_id IS NULL)
                    WHERE branch_id IS NULL
                ');
            } else {
                DB::statement('
                    UPDATE phone_numbers pn
                    JOIN branches b ON b.phone_number = pn.number
                    SET pn.branch_id = b.id
                    WHERE pn.branch_id IS NULL
                ');
            }",
    $content
);

file_put_contents($file, $content);
echo "Fixed: $file\n";
EOF

php /tmp/fix_populate_phone_migration.php

# 2. Create test helper to seed test data
echo "📊 Creating test data seeder..."

cat > /var/www/api-gateway/tests/Helpers/TestDataSeeder.php << 'EOF'
<?php

namespace Tests\Helpers;

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use Illuminate\Support\Str;

class TestDataSeeder
{
    public static function createCompleteTestScenario(): array
    {
        // Create Company
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'retell_api_key' => 'test_key_123',
            'calcom_api_key' => 'cal_test_key_123',
            'is_active' => true,
        ]);

        // Create Branch
        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Branch Berlin',
            'phone_number' => '+493012345678',
            'is_active' => true,
            'calcom_event_type_id' => 2026361,
        ]);

        // Create Phone Number
        $phoneNumber = PhoneNumber::create([
            'number' => '+493012345678',
            'branch_id' => $branch->id,
            'company_id' => $company->id,
            'retell_agent_id' => 'agent_test_123',
            'is_active' => true,
        ]);

        // Create Staff
        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Dr. Test Staff',
            'email' => 'staff@test.com',
        ]);

        // Create Service
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Service',
            'duration' => 30,
            'price' => 50.00,
        ]);

        // Create Customer
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Customer',
            'phone' => '+491234567890',
            'email' => 'customer@test.com',
        ]);

        // Create CalcomEventType
        $eventType = CalcomEventType::create([
            'calcom_event_type_id' => 2026361,
            'company_id' => $company->id,
            'title' => 'Test Event Type',
            'slug' => 'test-event-type',
            'length' => 30,
        ]);

        return compact('company', 'branch', 'phoneNumber', 'staff', 'service', 'customer', 'eventType');
    }

    public static function createRetellWebhookPayload(): array
    {
        return [
            'event_type' => 'call_ended',
            'call_id' => 'call_' . Str::random(10),
            'retell_call_id' => 'retell_' . Str::random(10),
            'agent_id' => 'agent_test_123',
            'phone_number' => '+493012345678',
            'from_number' => '+491234567890',
            'start_timestamp' => now()->subMinutes(5)->timestamp * 1000,
            'end_timestamp' => now()->timestamp * 1000,
            'duration_seconds' => 300,
            'transcript' => 'Test conversation transcript',
            'recording_url' => 'https://example.com/recording.mp3',
            'summary' => 'Customer wants to book an appointment',
            'custom_data' => [
                'extracted_info' => [
                    'customer_name' => 'Test Customer',
                    'requested_date' => now()->addDays(3)->format('Y-m-d'),
                    'requested_time' => '14:00',
                    'service' => 'Test Service',
                ]
            ]
        ];
    }
}
EOF

# 3. Run test suite with fixes
echo "🧪 Running tests to verify fixes..."

cd /var/www/api-gateway

# Clear all caches
php artisan optimize:clear

# Run specific Retell tests
php artisan test --filter="Retell" --stop-on-failure

echo "✅ Test suite fixes applied!"
echo ""
echo "📋 Summary of fixes:"
echo "1. Fixed SQLite-incompatible JOIN UPDATE statements in migrations"
echo "2. Created TestDataSeeder helper for consistent test data"
echo "3. All Retell-related tests should now pass"
echo ""
echo "🚀 Next steps:"
echo "1. Run full test suite: php artisan test"
echo "2. Create production webhook test: php artisan test:webhook"
echo "3. Import Retell calls: php artisan retell:import-calls"