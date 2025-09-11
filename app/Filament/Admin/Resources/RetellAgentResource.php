<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RetellAgentResource\Pages;
use App\Models\RetellAgent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RetellAgentResource extends Resource
{
    protected static ?string $model = RetellAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    
    protected static ?string $navigationLabel = 'AI Agents';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 1;

    public static function getLabel(): string
    {
        return 'AI Agent';
    }

    public static function getPluralLabel(): string
    {
        return 'AI Agents';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Agent Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Agent Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('agent_id')
                            ->label('Retell Agent ID')
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\Select::make('company_id')
                            ->label('Company')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('settings')
                            ->label('Settings')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Agent Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->agent_id),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status_badge')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        if (str_starts_with($record->name, 'Online:')) {
                            return 'Production';
                        } elseif (str_starts_with($record->name, 'Test:')) {
                            return 'Testing';
                        } elseif (str_starts_with($record->name, 'WIP:')) {
                            return 'Development';
                        }
                        return 'Unknown';
                    })
                    ->colors([
                        'success' => 'Production',
                        'warning' => 'Testing',
                        'danger' => 'Development',
                        'secondary' => 'Unknown',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Agent Status')
                    ->options([
                        'online' => 'Production',
                        'test' => 'Testing',
                        'wip' => 'Development',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match($data['value'] ?? null) {
                            'online' => $query->where('name', 'like', 'Online:%'),
                            'test' => $query->where('name', 'like', 'Test:%'),
                            'wip' => $query->where('name', 'like', 'WIP:%'),
                            default => $query,
                        };
                    }),
                Tables\Filters\Filter::make('active_only')
                    ->label('Active Agents Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }


    public static function getRelations(): array
    {
        return [
            //
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
    
    public static function hasInfolist(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('name', 'like', 'Online:%')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}