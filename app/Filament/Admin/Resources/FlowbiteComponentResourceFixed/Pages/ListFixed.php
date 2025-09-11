<?php

namespace App\Filament\Admin\Resources\FlowbiteComponentResourceFixed\Pages;

use App\Filament\Admin\Resources\FlowbiteComponentResourceFixed;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Reactive;

class ListFixed extends Page
{
    protected static string $resource = FlowbiteComponentResourceFixed::class;
    
    // protected static string $view = 'filament.admin.resources.flowbite-fixed.list';
    
    protected static ?string $title = 'Flowbite Component Library (Fixed)';
    
    public $components = [];
    public $filteredCount = 0;
    public $totalScanned = 0;
    
    public function mount(): void
    {
        $this->loadComponents();
    }
    
    private function loadComponents(): void
    {
        $this->components = [];
        
        // Load Flowbite components
        $flowbitePath = resource_path('views/components/flowbite');
        if (is_dir($flowbitePath)) {
            $this->scanDirectory($flowbitePath, 'Flowbite');
        }
        
        // Load Flowbite Pro components
        $flowbiteProPath = resource_path('views/components/flowbite-pro');
        if (is_dir($flowbiteProPath)) {
            $this->scanDirectory($flowbiteProPath, 'Flowbite Pro');
        }
    }
    
    private function scanDirectory($path, $prefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && str_contains($file->getFilename(), '.blade.php')) {
                $this->totalScanned++;
                $name = str_replace('.blade.php', '', $file->getFilename());
                
                // Skip special components (starting with underscore)
                if (str_starts_with($name, '_')) {
                    $this->filteredCount++;
                    continue;
                }
                
                // Skip extremely large files (likely full pages, not components)
                if ($file->getSize() > 50000) { // 50KB threshold
                    $this->filteredCount++;
                    continue;
                }
                
                // Skip empty stub files (less than 200 bytes)
                if ($file->getSize() < 200) {
                    $this->filteredCount++;
                    continue;
                }
                
                $relativePath = str_replace($path . '/', '', $file->getPath());
                $category = $relativePath ?: 'General';
                
                $this->components[] = [
                    'name' => Str::title(str_replace('-', ' ', $name)),
                    'path' => $file->getPathname(),
                    'category' => $prefix . ' / ' . Str::title(str_replace(['/', '-'], [' / ', ' '], $category)),
                    'size' => $this->formatFileSize($file->getSize()),
                    'type' => $this->detectType($file->getPathname()),
                    'isPreviewable' => true, // All remaining components are previewable
                ];
            }
        }
    }
    
    private function formatFileSize($size): string
    {
        if ($size > 1024 * 1024) {
            return round($size / (1024 * 1024), 2) . ' MB';
        } elseif ($size > 1024) {
            return round($size / 1024, 2) . ' KB';
        }
        return $size . ' B';
    }
    
    private function detectType($filepath): string
    {
        $content = file_get_contents($filepath);
        
        if (str_contains($content, 'x-data')) {
            return 'alpine';
        } elseif (str_contains($content, 'wire:')) {
            return 'livewire';
        } elseif (str_contains($content, '// Converted from React')) {
            return 'react-converted';
        }
        
        return 'blade';
    }
}