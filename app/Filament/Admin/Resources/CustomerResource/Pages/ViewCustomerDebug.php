<?php

namespace App\Filament\Admin\Resources\CustomerResource\Pages;

use App\Filament\Admin\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class ViewCustomerDebug extends ViewRecord
{
    protected static string $resource = CustomerResource::class;
    
    // Force hasInfolist to return true
    protected function hasInfolist(): bool
    {
        \Log::info('ViewCustomerDebug: hasInfolist() called, returning true');
        return true;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        \Log::info('ViewCustomerDebug: infolist() called');
        
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Section::make('Debug Test')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->label('Email'),
                    ])
                    ->columns(2),
            ]);
    }
    
    public function mount(int|string $record): void
    {
        parent::mount($record);
        \Log::info('ViewCustomerDebug: mount() called with record: ' . $record);
        \Log::info('ViewCustomerDebug: Record loaded: ' . ($this->record ? 'Yes' : 'No'));
    }
}