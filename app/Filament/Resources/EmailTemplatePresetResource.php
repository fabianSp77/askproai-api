<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplatePresetResource\Pages;
use App\Models\EmailTemplatePreset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplatePresetResource extends Resource
{
    protected static ?string $model = EmailTemplatePreset::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Template Presets';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('Preset Key')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($livewire) => $livewire instanceof Pages\EditEmailTemplatePreset)
                    ->helperText('Unique identifier for this preset (cannot be changed after creation)'),

                Forms\Components\TextInput::make('name')
                    ->label('Preset Name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('A descriptive name for this preset'),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->rows(3)
                    ->helperText('Describe the purpose and use case for this preset'),

                Forms\Components\TextInput::make('subject')
                    ->label('Email Subject')
                    ->required()
                    ->maxLength(500)
                    ->helperText('The subject line of the email (use Mustache syntax {{variable}})'),

                Forms\Components\RichEditor::make('body_html')
                    ->label('Email Body')
                    ->required()
                    ->helperText('The HTML body of the email (use Mustache syntax {{variable}})')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('variables_hint')
                    ->label('Variables Hint')
                    ->required()
                    ->rows(8)
                    ->helperText('Documentation of available variables for this preset')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('key', 'asc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplatePresets::route('/'),
            'create' => Pages\CreateEmailTemplatePreset::route('/create'),
            'edit' => Pages\EditEmailTemplatePreset::route('/{record}/edit'),
        ];
    }
}
