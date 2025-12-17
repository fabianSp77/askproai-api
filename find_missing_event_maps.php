<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find all composite services where staff can perform but CalcomEventMaps are missing
$results = DB::select("
    SELECT DISTINCT
        s.id as service_id,
        s.name as service_name,
        st.id as staff_id,
        st.name as staff_name,
        st.email as staff_email,
        st.calcom_user_id
    FROM services s
    JOIN service_staff ss ON s.id = ss.service_id
    JOIN staff st ON ss.staff_id = st.id
    WHERE s.composite = 1
      AND ss.is_active = 1
      AND st.deleted_at IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM calcom_event_map cem
          WHERE cem.service_id = s.id
            AND cem.staff_id = st.id
            LIMIT 1
      )
    ORDER BY s.name, st.name
");

echo "Missing CalcomEventMaps Analysis:\n";
echo "=================================\n\n";

if (empty($results)) {
    echo "✅ No missing CalcomEventMaps found!\n";
    exit(0);
}

echo "Found " . count($results) . " Staff/Service combinations without CalcomEventMaps:\n\n";

// Group by service
$grouped = [];
foreach ($results as $result) {
    $serviceKey = $result->service_id . '|' . $result->service_name;
    if (!isset($grouped[$serviceKey])) {
        $grouped[$serviceKey] = [];
    }
    $grouped[$serviceKey][] = $result;
}

foreach ($grouped as $serviceKey => $staffList) {
    list($serviceId, $serviceName) = explode('|', $serviceKey);

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Service: {$serviceName} (ID: {$serviceId})\n";
    echo "Missing for " . count($staffList) . " staff member(s):\n";

    foreach ($staffList as $staff) {
        echo "  • {$staff->staff_name}\n";
        echo "    Staff ID: {$staff->staff_id}\n";
        echo "    Email: {$staff->staff_email}\n";
        echo "    Cal.com User: " . ($staff->calcom_user_id ?? 'NULL') . "\n";
    }
    echo "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total: " . count($results) . " missing CalcomEventMap configurations\n";
