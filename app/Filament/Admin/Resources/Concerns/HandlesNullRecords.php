<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

trait HandlesNullRecords
{
    protected static function configureTableWithNullSafety(Table $table): Table
    {
        return $table->modifyQueryUsing(function (Builder $query) {
            // Ensure we never get null records
            return $query->whereNotNull($query->getModel()->getTable() . '.id');
        });
    }
}