<?php

namespace App\Filament\Admin\Resources\FlowbiteSimpleResource\Pages;

use App\Filament\Admin\Resources\FlowbiteSimpleResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ListComponents extends Page
{
    protected static string $resource = FlowbiteSimpleResource::class;
    
    // protected static string $view = 'filament.admin.resources.flowbite-simple.list-components';
    
    protected static ?string $title = 'Flowbite Component Gallery';
    
    public $components = [];
    
    public function mount(): void
    {
        $this->components = $this->loadComponents();
    }
    
    private function loadComponents(): array
    {
        $components = [];
        
        // Load Flowbite components
        $flowbitePath = resource_path('views/components/flowbite');
        if (File::exists($flowbitePath)) {
            $files = File::allFiles($flowbitePath);
            foreach ($files as $file) {
                if (str_contains($file->getFilename(), '.blade.php')) {
                    $name = str_replace('.blade.php', '', $file->getFilename());
                    $components[] = [
                        'name' => Str::title(str_replace('-', ' ', $name)),
                        'path' => $file->getPathname(),
                        'category' => 'Flowbite',
                        'type' => $this->detectComponentType($file),
                        'size' => $this->formatFileSize($file->getSize()),
                    ];
                }
            }
        }
        
        // Load Flowbite Pro components
        $flowbiteProPath = resource_path('views/components/flowbite-pro');
        if (File::exists($flowbiteProPath)) {
            $files = File::allFiles($flowbiteProPath);
            foreach ($files as $file) {
                if (str_contains($file->getFilename(), '.blade.php')) {
                    $name = str_replace('.blade.php', '', $file->getFilename());
                    $relativePath = str_replace(resource_path('views/components/flowbite-pro/'), '', $file->getPath());
                    $components[] = [
                        'name' => Str::title(str_replace('-', ' ', $name)),
                        'path' => $file->getPathname(),
                        'category' => 'Pro: ' . Str::title(str_replace('/', ' / ', $relativePath)),
                        'type' => $this->detectComponentType($file),
                        'size' => $this->formatFileSize($file->getSize()),
                    ];
                }
            }
        }
        
        return $components;
    }
    
    private function formatFileSize($size): string
    {
        if ($size > 1024) {
            return round($size / 1024, 2) . ' KB';
        }
        return $size . ' B';
    }
    
    private function detectComponentType($file): string
    {
        $content = File::get($file->getPathname());
        
        if (str_contains($content, 'x-data')) {
            return 'alpine';
        }
        
        if (str_contains($content, 'wire:')) {
            return 'livewire';
        }
        
        if (str_contains($content, '// Converted from React')) {
            return 'react-converted';
        }
        
        return 'blade';
    }
}