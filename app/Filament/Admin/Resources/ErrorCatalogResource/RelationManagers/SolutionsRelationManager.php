<?php

namespace App\Filament\Admin\Resources\ErrorCatalogResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SolutionsRelationManager extends RelationManager
{
    protected static string $relationship = 'solutions';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(1)
                            ->helperText('Order of execution'),
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'manual' => 'Manual Steps',
                                'script' => 'Script/Command',
                                'command' => 'CLI Command',
                                'config' => 'Configuration',
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
                        'link',
                        'bulletList',
                        'orderedList',
                        'codeBlock',
                    ])
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('steps')
                    ->required()
                    ->reorderable()
                    ->keyLabel('Step Number')
                    ->valueLabel('Step Description')
                    ->addButtonLabel('Add Step')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('code_snippet')
                    ->rows(5)
                    ->helperText('Code example or command to run')
                    ->columnSpanFull(),
                Forms\Components\Section::make('Automation')
                    ->schema([
                        Forms\Components\Toggle::make('is_automated')
                            ->reactive()
                            ->helperText('Enable automated execution'),
                        Forms\Components\TextInput::make('automation_script')
                            ->visible(fn ($get) => $get('is_automated'))
                            ->helperText('Path to automation script relative to project root')
                            ->placeholder('scripts/fixes/fix-error-xxx.php'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable()
                    ->label('#'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual' => 'gray',
                        'script' => 'warning',
                        'command' => 'info',
                        'config' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_automated')
                    ->boolean()
                    ->label('Automated'),
                Tables\Columns\TextColumn::make('success_rate')
                    ->numeric(1)
                    ->suffix('%')
                    ->color(fn ($state) => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('success_count')
                    ->numeric()
                    ->label('Success'),
                Tables\Columns\TextColumn::make('failure_count')
                    ->numeric()
                    ->label('Failed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'manual' => 'Manual Steps',
                        'script' => 'Script/Command',
                        'command' => 'CLI Command',
                        'config' => 'Configuration',
                    ]),
                Tables\Filters\TernaryFilter::make('is_automated')
                    ->label('Automated'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn ($record) => $record->is_automated)
                    ->action(function ($record) {
                        $result = $record->executeAutomation();
                        
                        if ($result['success']) {
                            $this->notify('success', 'Solution executed successfully!');
                        } else {
                            $this->notify('danger', 'Solution failed: ' . ($result['message'] ?? 'Unknown error'));
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}