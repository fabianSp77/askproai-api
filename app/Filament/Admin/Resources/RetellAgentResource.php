<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RetellAgentResource\Pages;
use App\Filament\Admin\Resources\RetellAgentResource\RelationManagers;
use App\Models\RetellAgent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RetellAgentResource extends Resource
{
    protected static ?string $model = RetellAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = null;
    
    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.ai_services');
    }
    
    public static function getNavigationLabel(): string
    {
        return __('admin.resources.retell_agents');
    }

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_published', true)->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('agent_id')
                            ->label('Retell Agent ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('The agent ID from Retell.ai'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),



                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive agents will not be used for calls'),

                    ])
                    ->columns(2),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('agent_id')
                    ->label('Agent ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Agent ID copied'),



                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sync_status')
                    ->label('Sync Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'synced' => 'success',
                        'error' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([


                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(fn ($record) => redirect()->route('filament.admin.pages.ai-call-center', [
                        'test_agent_id' => $record->agent_id,
                    ])),
                Tables\Actions\Action::make('sync')
                    ->label('Sync from Retell')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->syncFromRetell()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\AssignmentsRelationManager::class, // Disabled - assignments() relationship doesn't exist
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRetellAgents::route('/'),
            'create' => Pages\CreateRetellAgent::route('/create'),
            'view' => Pages\ViewRetellAgent::route('/{record}'),
            'edit' => Pages\EditRetellAgent::route('/{record}/edit'),
        ];
    }

}
