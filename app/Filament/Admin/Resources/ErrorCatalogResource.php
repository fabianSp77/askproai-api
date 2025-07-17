<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ErrorCatalogResource\Pages;
use App\Filament\Admin\Resources\ErrorCatalogResource\RelationManagers;
use App\Models\ErrorCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ErrorCatalogResource extends Resource
{
    protected static ?string $model = ErrorCatalog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $navigationLabel = 'Error Catalog';
    
    protected static ?string $navigationGroup = 'System Management';
    
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('error_code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., RETELL_001')
                                    ->helperText('Unique error code following pattern: SERVICE_XXX')
                                    ->maxLength(255),
                                Forms\Components\Select::make('category')
                                    ->required()
                                    ->options([
                                        'AUTH' => 'Authentication',
                                        'API' => 'API Integration',
                                        'INTEGRATION' => 'External Integration',
                                        'DB' => 'Database',
                                        'QUEUE' => 'Queue/Jobs',
                                        'UI' => 'User Interface',
                                    ])
                                    ->native(false),
                                Forms\Components\Select::make('service')
                                    ->options([
                                        'retell' => 'Retell.ai',
                                        'calcom' => 'Cal.com',
                                        'stripe' => 'Stripe',
                                        'webhook' => 'Webhook',
                                        'internal' => 'Internal',
                                    ])
                                    ->native(false),
                                Forms\Components\Select::make('severity')
                                    ->required()
                                    ->options([
                                        'critical' => 'Critical',
                                        'high' => 'High',
                                        'medium' => 'Medium',
                                        'low' => 'Low',
                                    ])
                                    ->native(false),
                            ]),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'strike',
                                'link',
                                'bulletList',
                                'orderedList',
                                'codeBlock',
                            ])
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Error Details')
                    ->schema([
                        Forms\Components\Textarea::make('symptoms')
                            ->helperText('What the user sees or experiences')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('stack_pattern')
                            ->helperText('Regex pattern for auto-detection (optional)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('root_causes')
                            ->required()
                            ->reorderable()
                            ->addButtonLabel('Add Root Cause')
                            ->keyLabel('Cause')
                            ->valueLabel('Description')
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->helperText('Active errors appear in searches'),
                                Forms\Components\Toggle::make('auto_detectable')
                                    ->default(false)
                                    ->helperText('Can be detected by stack pattern'),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('occurrence_count')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('last_occurred_at')
                                    ->disabled(),
                                Forms\Components\TextInput::make('avg_resolution_time')
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->disabled(),
                            ]),
                    ])
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('error_code')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (ErrorCatalog $record): string {
                        return $record->title;
                    }),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'AUTH' => 'warning',
                        'API' => 'info',
                        'INTEGRATION' => 'success',
                        'DB' => 'danger',
                        'QUEUE' => 'gray',
                        'UI' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('service')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\IconColumn::make('auto_detectable')
                    ->boolean()
                    ->label('Auto-Detect'),
                Tables\Columns\TextColumn::make('occurrence_count')
                    ->numeric()
                    ->sortable()
                    ->label('Occurrences'),
                Tables\Columns\TextColumn::make('last_occurred_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->label('Last Seen')
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('avg_resolution_time')
                    ->numeric()
                    ->suffix(' min')
                    ->sortable()
                    ->label('Avg Resolution'),
                Tables\Columns\TextColumn::make('solutions_count')
                    ->counts('solutions')
                    ->label('Solutions'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'AUTH' => 'Authentication',
                        'API' => 'API Integration',
                        'INTEGRATION' => 'External Integration',
                        'DB' => 'Database',
                        'QUEUE' => 'Queue/Jobs',
                        'UI' => 'User Interface',
                    ]),
                Tables\Filters\SelectFilter::make('service')
                    ->options([
                        'retell' => 'Retell.ai',
                        'calcom' => 'Cal.com',
                        'stripe' => 'Stripe',
                        'webhook' => 'Webhook',
                        'internal' => 'Internal',
                    ]),
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('auto_detectable')
                    ->label('Auto-Detectable'),
                Tables\Filters\Filter::make('frequent')
                    ->query(fn (Builder $query): Builder => $query->where('occurrence_count', '>=', 10))
                    ->label('Frequent (10+ occurrences)'),
                Tables\Filters\Filter::make('recent')
                    ->query(fn (Builder $query): Builder => $query->where('last_occurred_at', '>=', now()->subDays(7)))
                    ->label('Recent (last 7 days)'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_solutions')
                    ->label('Solutions')
                    ->icon('heroicon-o-light-bulb')
                    ->color('success')
                    ->url(fn (ErrorCatalog $record): string => route('filament.admin.resources.error-catalogs.solutions', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_occurred_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SolutionsRelationManager::class,
            RelationManagers\PreventionTipsRelationManager::class,
            RelationManagers\TagsRelationManager::class,
            RelationManagers\OccurrencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListErrorCatalogs::route('/'),
            'create' => Pages\CreateErrorCatalog::route('/create'),
            'view' => Pages\ViewErrorCatalog::route('/{record}'),
            'edit' => Pages\EditErrorCatalog::route('/{record}/edit'),
            'solutions' => Pages\ManageErrorSolutions::route('/{record}/solutions'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)
            ->where('severity', 'critical')
            ->where('last_occurred_at', '>=', now()->subHours(24))
            ->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}