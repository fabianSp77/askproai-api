<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FlowbiteSimpleResource extends Resource
{
    protected static ?string $model = null;
    
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static ?string $navigationGroup = 'Flowbite Pro';
    
    protected static ?string $navigationLabel = 'Component Gallery';
    
    protected static ?int $navigationSort = 2;
    
    protected static bool $shouldRegisterNavigation = false; // Hide from navigation
    
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
                Tables\Columns\TextColumn::make('size')
                    ->label('Size'),
            ])
            ->paginated(false);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\FlowbiteSimpleResource\Pages\ListComponents::route('/'),
        ];
    }
}