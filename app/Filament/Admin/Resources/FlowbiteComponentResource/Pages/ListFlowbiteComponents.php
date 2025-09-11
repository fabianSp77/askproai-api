<?php

namespace App\Filament\Admin\Resources\FlowbiteComponentResource\Pages;

use App\Filament\Admin\Resources\FlowbiteComponentResource;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

class ListFlowbiteComponents extends Page
{
    protected static string $resource = FlowbiteComponentResource::class;
    
    // protected static string $view = 'filament.admin.resources.flowbite-component-resource.pages.list-flowbite-components';
    
    protected static ?string $title = 'Flowbite Pro Component Library';
    
    public $components = [];
    public $searchTerm = '';
    public $selectedCategory = '';
    public $selectedType = '';
    
    public function mount(): void
    {
        $this->components = $this->loadComponents();
    }
    
    public function updatedSearchTerm(): void
    {
        $this->components = $this->loadComponents();
    }
    
    public function updatedSelectedCategory(): void
    {
        $this->components = $this->loadComponents();
    }
    
    public function updatedSelectedType(): void  
    {
        $this->components = $this->loadComponents();
    }
    
    private function loadComponents(): array
    {
        $components = [];
        $id = 1;
        
        // Scan Flowbite components
        $flowbitePath = resource_path('views/components/flowbite');
        if (File::exists($flowbitePath)) {
            $components = array_merge($components, $this->scanDirectory($flowbitePath, 'flowbite', $id));
            $id += count($components);
        }
        
        // Scan Flowbite Pro components
        $flowbiteProPath = resource_path('views/components/flowbite-pro');
        if (File::exists($flowbiteProPath)) {
            $components = array_merge($components, $this->scanDirectory($flowbiteProPath, 'flowbite-pro', $id));
        }
        
        // Apply filters
        if ($this->searchTerm) {
            $components = array_filter($components, function ($component) {
                return str_contains(strtolower($component['name']), strtolower($this->searchTerm)) ||
                       str_contains(strtolower($component['category']), strtolower($this->searchTerm));
            });
        }
        
        if ($this->selectedCategory) {
            $components = array_filter($components, function ($component) {
                return str_contains(strtolower($component['category']), strtolower($this->selectedCategory));
            });
        }
        
        if ($this->selectedType) {
            $components = array_filter($components, function ($component) {
                return $component['type'] === $this->selectedType;
            });
        }
        
        return array_values($components);
    }
    
    private function scanDirectory($basePath, $prefix, $startId = 1): array
    {
        $components = [];
        $files = File::allFiles($basePath);
        $id = $startId;
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php' && str_contains($file->getFilename(), '.blade.php')) {
                $relativePath = str_replace($basePath . '/', '', $file->getPath());
                $category = $relativePath ?: 'general';
                $name = str_replace('.blade.php', '', $file->getFilename());
                
                $components[] = [
                    'id' => $id++,
                    'name' => Str::title(str_replace('-', ' ', $name)),
                    'category' => Str::title(str_replace('-', ' ', $category)),
                    'type' => $this->detectComponentType($file),
                    'file_size' => $file->getSize(),
                    'interactive' => $this->hasInteractivity($file),
                    'path' => "{$prefix}.{$category}.{$name}",
                    'file_path' => $file->getPathname(),
                    'prefix' => $prefix,
                    'formatted_size' => $file->getSize() > 1024 
                        ? round($file->getSize() / 1024, 2) . ' KB'
                        : $file->getSize() . ' B',
                ];
            }
        }
        
        return $components;
    }
    
    private function detectComponentType($file): string
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
    
    private function hasInteractivity($file): bool
    {
        $content = File::get($file->getPathname());
        
        return str_contains($content, 'x-data') || 
               str_contains($content, '@click') ||
               str_contains($content, 'x-model') ||
               str_contains($content, 'wire:');
    }
    
    public function getCategories(): array
    {
        return [
            '' => 'All Categories',
            'authentication' => 'Authentication',
            'e-commerce' => 'E-Commerce', 
            'homepages' => 'Dashboards',
            'marketing-ui' => 'Marketing',
            'application-ui' => 'Application',
            'layouts' => 'Layouts',
            'content' => 'Content',
            'admin-dashboard' => 'Admin Dashboard',
        ];
    }
    
    public function getTypes(): array
    {
        return [
            '' => 'All Types',
            'blade' => 'Blade Component',
            'alpine' => 'Alpine.js Component',
            'react-converted' => 'Converted from React',
        ];
    }
    
    public function previewComponent($componentId)
    {
        $component = collect($this->components)->firstWhere('id', $componentId);
        
        if (!$component) {
            Notification::make()
                ->title('Component not found')
                ->danger()
                ->send();
            return;
        }
        
        $this->dispatchBrowserEvent('open-component-modal', [
            'component' => $component,
            'type' => 'preview'
        ]);
    }
    
    public function viewCode($componentId) 
    {
        $component = collect($this->components)->firstWhere('id', $componentId);
        
        if (!$component) {
            Notification::make()
                ->title('Component not found')
                ->danger()
                ->send();
            return;
        }
        
        $code = File::get($component['file_path']);
        
        $this->dispatchBrowserEvent('open-component-modal', [
            'component' => $component,
            'code' => $code,
            'type' => 'code'
        ]);
    }
}