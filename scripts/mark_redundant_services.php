<?php
// Script to mark redundant services

$redundantServices = [
    // Cal.com redundant
    'CalcomService.php',
    'CalcomDebugService.php', 
    'CalcomEventSyncService.php',
    'CalcomEventTypeImportService.php',
    'CalcomEventTypeSyncService.php',
    'CalcomImportService.php',
    'CalcomSyncService.php',
    'CalcomUnifiedService.php',
    'CalcomV2MigrationService.php',
    
    // Retell redundant
    'RetellAIService.php',
    'RetellAgentService.php',
    'RetellService.php',
    'RetellV1Service.php',
    
    // Other redundant
    'AppointmentService.php',
    'BookingService.php',
];

$servicePath = __DIR__ . '/../app/Services/';

foreach ($redundantServices as $service) {
    $file = $servicePath . $service;
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, '// MARKED_FOR_DELETION') === false) {
            $content = "<?php\n// MARKED_FOR_DELETION - " . date('Y-m-d') . "\n" . substr($content, 5);
            file_put_contents($file, $content);
            echo "✓ Marked: $service\n";
        }
    } else {
        echo "✗ Not found: $service\n";
    }
}

echo "\nTotal marked: " . count($redundantServices) . " services\n";