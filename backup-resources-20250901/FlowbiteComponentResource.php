<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlowbiteComponentResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FlowbiteComponentResource extends Resource
{
    protected static ?string $model = null;
    
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static ?string $navigationGroup = 'Flowbite Pro';
    
    protected static ?string $navigationLabel = 'Component Library';
    
    protected static ?int $navigationSort = 1;
    
    public static function getNavigationBadge(): ?string
    {
        return '556'; // Total components available
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Component Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record['category']),
                    
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'blade',
                        'success' => 'alpine',
                        'warning' => 'react-converted',
                    ]),
                    
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => Str::of($state)->pipe(function ($size) {
                        return $size > 1024 
                            ? round($size / 1024, 2) . ' KB'
                            : $size . ' B';
                    })),
                    
                Tables\Columns\IconColumn::make('interactive')
                    ->label('Interactive')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                    
                Tables\Columns\ViewColumn::make('preview')
                    ->label('Preview')
                    ->view('filament.tables.columns.flowbite-preview'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'authentication' => 'Authentication',
                        'e-commerce' => 'E-Commerce',
                        'homepages' => 'Dashboards',
                        'marketing-ui' => 'Marketing',
                        'application-ui' => 'Application',
                        'layouts' => 'Layouts',
                    ]),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'blade' => 'Blade Component',
                        'alpine' => 'Alpine.js Component',
                        'react-converted' => 'Converted from React',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('interactive')
                    ->label('Has Interactivity'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Component Preview')
                    ->modalContent(fn ($record) => view('filament.modals.flowbite-preview', [
                        'component' => $record
                    ])),
                    
                Tables\Actions\Action::make('copy_code')
                    ->label('Copy Code')
                    ->icon('heroicon-o-clipboard')
                    ->action(fn ($record) => null)
                    ->requiresConfirmation(false)
                    ->extraAttributes([
                        'x-on:click' => 'copyComponentCode($wire.record)',
                    ]),
                    
                Tables\Actions\Action::make('use_in_page')
                    ->label('Use in Page')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => route('filament.admin.resources.pages.create', [
                        'component' => $record['path']
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn ($records) => static::exportComponents($records)),
            ])
            ->defaultSort('category')
            ->paginated([10, 25, 50, 100])
            ->poll('60s');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowbiteComponents::route('/'),
        ];
    }
    
    /**
     * Get component data from filesystem
     */
    public static function getComponentData(): array
    {
        $components = [];
        $basePath = resource_path('views/components/flowbite');
        
        // Scan all component directories
        $categories = File::directories($basePath);
        
        foreach ($categories as $categoryPath) {
            $category = basename($categoryPath);
            $files = File::allFiles($categoryPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $name = str_replace('.blade.php', '', $file->getFilename());
                    $components[] = [
                        'name' => Str::title(str_replace('-', ' ', $name)),
                        'category' => $category,
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
    protected static function detectComponentType($file): string
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
    protected static function hasInteractivity($file): bool
    {
        $content = File::get($file->getPathname());
        
        return str_contains($content, 'x-data') || 
               str_contains($content, '@click') ||
               str_contains($content, 'x-model') ||
               str_contains($content, 'wire:');
    }
    
    /**
     * Export selected components
     */
    protected static function exportComponents($records): void
    {
        // Implementation for exporting components
        // Could create a zip file or copy to a specific location
    }
}