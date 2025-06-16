<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Tables;

abstract class EnhancedResourceSimple extends Resource
{
    /**
     * Configure common table features with safe defaults
     */
    public static function enhanceTable(Tables\Table $table): Tables\Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->striped()
            ->poll('30s');
    }
}