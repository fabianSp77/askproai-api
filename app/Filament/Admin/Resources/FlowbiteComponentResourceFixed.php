<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FlowbiteComponentResourceFixed extends Resource
{
    protected static ?string $model = null;
    
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static ?string $navigationGroup = 'Flowbite Pro';
    
    protected static ?string $navigationLabel = 'Components (Fixed)';
    
    protected static ?int $navigationSort = 3;
    
    protected static bool $shouldRegisterNavigation = false; // Hide from navigation
    
    protected static ?string $slug = 'flowbite-fixed';
    
    // Authorization for model-less resource
    public static function canViewAny(): bool
    {
        return true; // Allow all access
    }
    
    public static function getNavigationBadge(): ?string
    {
        $count = 0;
        $flowbitePath = resource_path('views/components/flowbite');
        if (File::exists($flowbitePath)) {
            $count += count(File::allFiles($flowbitePath));
        }
        $flowbiteProPath = resource_path('views/components/flowbite-pro');
        if (File::exists($flowbiteProPath)) {
            $count += count(File::allFiles($flowbiteProPath));
        }
        return (string) $count;
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Component'),
                Tables\Columns\TextColumn::make('category')
                    ->badge(),
            ])
            ->paginated(false);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\FlowbiteComponentResourceFixed\Pages\ListFixed::route('/'),
        ];
    }
}