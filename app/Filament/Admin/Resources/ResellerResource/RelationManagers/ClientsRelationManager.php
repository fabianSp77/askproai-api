<?php

namespace App\Filament\Admin\Resources\ResellerResource\RelationManagers;

use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClientsRelationManager extends RelationManager
{
    protected static string $relationship = 'childCompanies';

    protected static ?string $title = 'Client Companies';

    protected static ?string $modelLabel = 'Client';

    protected static ?string $pluralModelLabel = 'Clients';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client Information')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Company Name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('email')
                                ->label('Contact Email')
                                ->email()
                                ->required()
                                ->columnSpan(1),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->columnSpan(1),

                            Forms\Components\Select::make('industry')
                                ->label('Industry')
                                ->options([
                                    'healthcare' => 'Healthcare',
                                    'beauty' => 'Beauty & Wellness',
                                    'professional' => 'Professional Services',
                                    'retail' => 'Retail',
                                    'restaurant' => 'Restaurant & Food',
                                    'automotive' => 'Automotive',
                                    'other' => 'Other',
                                ])
                                ->columnSpan(1),
                        ]),

                        Forms\Components\Textarea::make('address')
                            ->label('Business Address')
                            ->rows(3),

                        Forms\Components\Hidden::make('company_type')
                            ->default('client'),

                        Forms\Components\Hidden::make('parent_company_id')
                            ->default(fn ($livewire) => $livewire->ownerRecord->id),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Client')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->icon('heroicon-o-phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('branches_count')
                    ->label('Branches')
                    ->counts('branches')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Staff')
                    ->counts('staff')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
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
                        'restaurant' => 'Restaurant & Food',
                        'automotive' => 'Automotive',
                        'other' => 'Other',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Client')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure the client is linked to this reseller
                        $data['company_type'] = 'client';
                        $data['parent_company_id'] = $this->ownerRecord->id;
                        
                        // Generate slug from name
                        if (empty($data['slug']) && !empty($data['name'])) {
                            $data['slug'] = \Str::slug($data['name']);
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Company $record): string => 
                        route('filament.admin.resources.companies.view', $record)
                    ),

                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('manage')
                    ->label('Manage')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->url(fn (Company $record): string => 
                        route('filament.admin.resources.companies.edit', $record)
                    ),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
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
            ->emptyStateDescription('Add your first client company to get started.')
            ->emptyStateIcon('heroicon-o-building-office')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add First Client')
                    ->icon('heroicon-o-plus-circle'),
            ]);
    }
}