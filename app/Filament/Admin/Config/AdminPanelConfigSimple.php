<?php

namespace App\Filament\Admin\Config;

class AdminPanelConfigSimple
{
    public static function getAllResources(): array
    {
        $resourcePath = app_path('Filament/Admin/Resources');
        $resources = [];
        
        // Scan for all resource files
        $files = glob($resourcePath . '/*Resource.php');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            
            // Skip base classes and backups
            if (in_array($filename, ['BaseResource', 'BaseAdminResource', 'EnhancedResource']) 
                || str_contains($filename, 'backup') 
                || str_contains($filename, 'disabled')) {
                continue;
            }
            
            $className = "\\App\\Filament\\Admin\\Resources\\{$filename}";
            
            // Check if class exists
            if (class_exists($className)) {
                $resources[] = $className;
            }
        }
        
        return $resources;
    }
    
    public static function getAllPages(): array
    {
        return [
            \App\Filament\Admin\Pages\Dashboard::class,
            \App\Filament\Admin\Pages\QuickSetupWizard::class,
            \App\Filament\Admin\Pages\QuickSetupWizardV2::class,
            \App\Filament\Admin\Pages\DataSync::class,
            \App\Filament\Admin\Pages\WebhookAnalysis::class,
            \App\Filament\Admin\Pages\SimpleSyncManager::class,
        ];
    }
}