<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PromptTemplateResource\Pages;
use App\Models\PromptTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromptTemplateResource extends Resource
{
    protected static ?string $model = PromptTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = '🧠 Prompt Templates';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Template Name')
                            ->placeholder('z.B. Retell Agent Greeting'),
                            
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Slug')
                            ->placeholder('retell-agent-greeting'),
                            
                        Forms\Components\Select::make('category')
                            ->options([
                                'general' => 'General',
                                'retell' => 'Retell.ai',
                                'calcom' => 'Cal.com',
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'system' => 'System',
                            ])
                            ->default('general')
                            ->required()
                            ->label('Category'),
                            
                        Forms\Components\TextInput::make('version')
                            ->default('1.0.0')
                            ->label('Version')
                            ->placeholder('1.0.0'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Template Hierarchy')
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Parent Template')
                            ->helperText('Inherit variables and content from parent template')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $parent = PromptTemplate::find($state);
                                    if ($parent && !$get('content')) {
                                        $set('content', "{{parent}}\n\n");
                                    }
                                }
                            }),
                            
                        Forms\Components\Placeholder::make('hierarchy')
                            ->label('Template Hierarchy')
                            ->content(function ($record) {
                                if (!$record || !$record->parent) {
                                    return 'Root Template';
                                }
                                
                                $hierarchy = $record->ancestors()
                                    ->reverse()
                                    ->pluck('name')
                                    ->push($record->name)
                                    ->join(' → ');
                                    
                                return $hierarchy;
                            }),
                    ]),
                    
                Forms\Components\Section::make('Template Content')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->label('Description')
                            ->placeholder('Describe what this template is used for'),
                            
                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->rows(10)
                            ->label('Template Content')
                            ->helperText('Use {{variable}} for variables, {{parent}} to include parent content')
                            ->placeholder("Hallo {{customer_name}},\n\nVielen Dank für Ihren Anruf bei {{company_name}}.\n\n{{parent}}"),
                            
                        Forms\Components\TagsInput::make('variables')
                            ->label('Variables')
                            ->placeholder('Add variable names used in this template')
                            ->helperText('Variables that can be replaced in this template'),
                    ]),
                    
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active')
                            ->helperText('Inactive templates cannot be used'),
                            
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->helperText('Additional configuration options'),
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
                    ->sortable()
                    ->label('Name'),
                    
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'primary' => 'general',
                        'success' => 'retell',
                        'warning' => 'calcom',
                        'info' => 'email',
                        'danger' => 'system',
                    ])
                    ->label('Category'),
                    
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent Template')
                    ->placeholder('Root')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('variables')
                    ->label('Variables')
                    ->badge()
                    ->separator(',')
                    ->limit(3),
                    
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->label('Updated')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'general' => 'General',
                        'retell' => 'Retell.ai',
                        'calcom' => 'Cal.com',
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'system' => 'System',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                    
                Tables\Filters\Filter::make('has_parent')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('parent_id'))
                    ->label('Has Parent'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Template Preview')
                    ->modalContent(function (PromptTemplate $record) {
                        $testVars = [
                            'customer_name' => 'Max Mustermann',
                            'company_name' => 'AskProAI GmbH',
                            'appointment_date' => '15.01.2025',
                            'appointment_time' => '14:00 Uhr',
                            'service_name' => 'Beratungsgespräch',
                        ];
                        
                        $compiled = $record->compile($testVars);
                        
                        return view('filament.components.prompt-preview', [
                            'template' => $record,
                            'compiled' => $compiled,
                            'testVars' => $testVars,
                        ]);
                    })
                    ->modalWidth('5xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListPromptTemplates::route('/'),
            'create' => Pages\CreatePromptTemplate::route('/create'),
            'edit' => Pages\EditPromptTemplate::route('/{record}/edit'),
            'view' => Pages\ViewPromptTemplate::route('/{record}'),
        ];
    }
}