<?php

namespace App\Filament\Admin\Resources\ResellerResource\Widgets;

use App\Filament\Admin\Resources\ResellerResource\Pages\ResellerDashboard;
use App\Models\Company;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ResellerClientsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Client Companies';
    
    public ?Company $record = null;

    public function table(Table $table): Table
    {
        $reseller = $this->record;
        
        if (!$reseller) {
            return $table->query(Company::query()->whereRaw('1 = 0')); // Empty query
        }

        return $table
            ->query(
                Company::query()
                    ->where('parent_company_id', $reseller->id)
                    ->where('company_type', 'client')
                    ->withCount(['branches', 'staff', 'customers', 'appointments'])
            )
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->defaultImageUrl(url('/images/default-company.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Company $record): string => $record->email ?? ''),

                Tables\Columns\TextColumn::make('industry')
                    ->label('Industry')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('branches_count')
                    ->label('Branches')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Staff')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('customers_count')
                    ->label('Customers')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Appointments')
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All clients')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\SelectFilter::make('industry')
                    ->label('Industry')
                    ->options([
                        'healthcare' => 'Healthcare',
                        'beauty' => 'Beauty & Wellness',
                        'professional' => 'Professional Services',
                        'retail' => 'Retail',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Company $record): string => 
                        route('filament.admin.resources.companies.view', $record)
                    ),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn (Company $record): string => 
                        route('filament.admin.resources.companies.edit', $record)
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('No clients yet')
            ->emptyStateDescription('This reseller doesn\'t have any clients yet.')
            ->emptyStateIcon('heroicon-o-building-office')
            ->emptyStateActions([
                Tables\Actions\Action::make('add_client')
                    ->label('Add First Client')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->url(fn () => route('filament.admin.resources.companies.create', [
                        'parent_company_id' => $reseller->id
                    ])),
            ]);
    }

}