<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FlowbiteComponentResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FlowbiteComponentResource extends Resource
{
    // No model - filesystem based resource
    protected static ?string $model = null;
    
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static ?string $navigationGroup = 'Flowbite Pro';
    
    protected static ?string $navigationLabel = 'Component Library';
    
    protected static ?int $navigationSort = 1;
    
    // Authorization for model-less resource
    public static function canViewAny(): bool
    {
        return true; // Allow all access
    }
    
    public static function getNavigationBadge(): ?string
    {
        // Dynamic component counting
        $count = 0;
        
        // Count Flowbite components
        $flowbitePath = resource_path('views/components/flowbite');
        if (File::exists($flowbitePath)) {
            $count += count(File::allFiles($flowbitePath));
        }
        
        // Count Flowbite Pro components
        $flowbiteProPath = resource_path('views/components/flowbite-pro');
        if (File::exists($flowbiteProPath)) {
            $count += count(File::allFiles($flowbiteProPath));
        }
        
        return (string) $count ?: '0';
    }
    
    // Table definition moved to ListFlowbiteComponents page class
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowbiteComponents::route('/'),
        ];
    }
    // All component scanning and data processing methods moved to ListFlowbiteComponents page class
}