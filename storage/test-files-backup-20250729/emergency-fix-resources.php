<?php
/**
 * Emergency Fix for Filament Resources not loading
 * This script adds company context initialization to resource files
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== Emergency Fix for Filament Resources ===\n\n";

// Resources to fix
$resources = [
    '/var/www/api-gateway/app/Filament/Admin/Resources/CallResource/Pages/ListCalls.php',
    '/var/www/api-gateway/app/Filament/Admin/Resources/AppointmentResource/Pages/ListAppointments.php',
    '/var/www/api-gateway/app/Filament/Admin/Resources/CustomerResource/Pages/ListCustomers.php',
    '/var/www/api-gateway/app/Filament/Admin/Resources/BranchResource/Pages/ListBranches.php',
];

$fixCode = '
        // Emergency Company Context Fix
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance(\'current_company_id\', auth()->user()->company_id);
            app()->instance(\'company_context_source\', \'web_auth\');
        }';

foreach ($resources as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if already fixed
    if (strpos($content, 'Emergency Company Context Fix') !== false) {
        echo "✓ Already fixed: $file\n";
        continue;
    }
    
    // Find mount() method or create one
    if (strpos($content, 'public function mount()') !== false) {
        // Add to existing mount method
        $content = preg_replace(
            '/public function mount\(\)[^{]*\{/',
            "public function mount(): void\n    {" . $fixCode,
            $content
        );
        echo "✓ Added to existing mount(): $file\n";
    } else {
        // Add new mount method before getHeaderActions or at end of class
        $pattern = '/(protected function getHeaderActions\(\)|}\s*$)/';
        $replacement = "public function mount(): void\n    {" . $fixCode . "\n        parent::mount();\n    }\n\n    $1";
        $content = preg_replace($pattern, $replacement, $content, 1);
        echo "✓ Added new mount() method: $file\n";
    }
    
    // Write back
    file_put_contents($file, $content);
}

echo "\n=== Creating simplified CallResource test ===\n";

// Create a test file
$testContent = '<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Resources\Pages\ListRecords;

class ListCallsSimple extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    public function mount(): void
    {
        // Force company context
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance(\'current_company_id\', auth()->user()->company_id);
            app()->instance(\'company_context_source\', \'web_auth\');
            
            \Log::info(\'ListCallsSimple: Set company context\', [
                \'user_id\' => auth()->id(),
                \'company_id\' => auth()->user()->company_id,
            ]);
        }
        
        parent::mount();
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
}
';

file_put_contents('/var/www/api-gateway/app/Filament/Admin/Resources/CallResource/Pages/ListCallsSimple.php', $testContent);
echo "✓ Created ListCallsSimple.php\n";

echo "\n=== Clearing caches ===\n";
exec('php artisan optimize:clear');
echo "✓ Caches cleared\n";

echo "\n=== Emergency Fix Complete ===\n";
echo "Please try accessing the pages again.\n";
echo "If still not working, check the browser console for JavaScript errors.\n";