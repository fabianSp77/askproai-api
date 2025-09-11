<?php

namespace App\Filament\Admin\Resources\FlowbiteComponentResource\Pages;

use App\Filament\Admin\Resources\FlowbiteComponentResource;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ListFlowbiteComponents extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = FlowbiteComponentResource::class;
    
    protected static string $view = 'filament.admin.resources.flowbite-component-resource.pages.list-flowbite-components';

    public function getTitle(): string
    {
        return 'Flowbite Pro Component Library';
    }
    
    /**
     * Get component data from filesystem
     */
    public function getTableRecords(): Collection
    {
        $components = collect();
        $basePath = resource_path('views/components/flowbite');
        
        if (!File::exists($basePath)) {
            return $components;
        }
        
        // Scan all component directories
        $categories = File::directories($basePath);
        $id = 1;
        
        foreach ($categories as $categoryPath) {
            $category = basename($categoryPath);
            $files = File::allFiles($categoryPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $name = str_replace('.blade.php', '', $file->getFilename());
                    $components->push((object)[
                        'id' => $id++,
                        'name' => Str::title(str_replace('-', ' ', $name)),
                        'category' => Str::title(str_replace('-', ' ', $category)),
                        'type' => $this->detectComponentType($file),
                        'file_size' => $file->getSize(),
                        'interactive' => $this->hasInteractivity($file),
                        'path' => "flowbite.{$category}.{$name}",
                        'file_path' => $file->getPathname(),
                    ]);
                }
            }
        }
        
        return $components;
    }
    
    /**
     * Detect component type based on content
     */
    protected function detectComponentType($file): string
    {
        $content = File::get($file->getPathname());
        
        if (str_contains($content, 'x-data')) {
            return 'alpine';
        }
        
        if (str_contains($content, '// Converted from React')) {
            return 'react-converted';
        }
        
        return 'blade';
    }
    
    /**
     * Check if component has interactivity
     */
    protected function hasInteractivity($file): bool
    {
        $content = File::get($file->getPathname());
        
        return str_contains($content, 'x-data') || 
               str_contains($content, '@click') ||
               str_contains($content, 'x-model') ||
               str_contains($content, 'wire:');
    }
}
