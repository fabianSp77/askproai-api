<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FlowbiteComponent extends Model
{
    // No database table - this is a virtual model
    
    /**
     * Model properties for in-memory data
     */
    protected $schema = [
        'id' => 'integer',
        'name' => 'string',
        'category' => 'string',
        'type' => 'string',
        'file_size' => 'integer',
        'interactive' => 'boolean',
        'path' => 'string',
        'file_path' => 'string',
    ];
    
    /**
     * Get the data rows for the in-memory database
     */
    public function getRows()
    {
        $components = [];
        $basePath = resource_path('views/components/flowbite');
        
        if (!File::exists($basePath)) {
            return [];
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
                    $components[] = [
                        'id' => $id++,
                        'name' => Str::title(str_replace('-', ' ', $name)),
                        'category' => Str::title(str_replace('-', ' ', $category)),
                        'type' => $this->detectComponentType($file),
                        'file_size' => $file->getSize(),
                        'interactive' => $this->hasInteractivity($file),
                        'path' => "flowbite.{$category}.{$name}",
                        'file_path' => $file->getPathname(),
                    ];
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