<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use App\Models\Call;
use Illuminate\Support\Facades\Log;

class TableDebug extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    protected static ?string $navigationLabel = 'Table Debug';
    protected static string $view = 'filament.admin.pages.table-debug';
    protected static ?string $navigationGroup = 'Entwicklung';

    public function table(Table $table): Table
    {
        Log::info('TableDebug::table() called');
        
        return $table
            ->query(Call::query())
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('created_at')->label('Date'),
            ])
            ->paginated([10])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}