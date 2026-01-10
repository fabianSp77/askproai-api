<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Email Templates';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin', 'super_admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Template Name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('A descriptive name for this email template'),

                Forms\Components\TextInput::make('subject')
                    ->label('Email Subject')
                    ->required()
                    ->maxLength(500)
                    ->helperText('The subject line of the email (can use variables like {{customer_name}})'),

                Forms\Components\Radio::make('template_type')
                    ->label('Template Type')
                    ->options([
                        'internal' => 'Internal',
                        'customer' => 'Kunde',
                        'both' => 'Beides',
                    ])
                    ->default('both')
                    ->inline()
                    ->required()
                    ->helperText('Internal = nur für Mitarbeiter, Kunde = für Kunden sichtbar, Beides = für beide Zielgruppen'),

                Forms\Components\RichEditor::make('body_html')
                    ->label('Email Body')
                    ->required()
                    ->helperText('The HTML body of the email (can use variables like {{customer_name}}, {{case_number}}, etc.)')
                    ->columnSpanFull(),

                Forms\Components\ViewField::make('available_variables')
                    ->label('📋 Verfügbare Template-Variablen (48+ Variablen)')
                    ->view('filament.forms.components.template-variables')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active templates can be used'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('template_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'internal',
                        'success' => 'customer',
                        'secondary' => 'both',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'internal' => 'Internal',
                        'customer' => 'Kunde',
                        'both' => 'Beides',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\SelectFilter::make('template_type')
                    ->label('Template Type')
                    ->options([
                        'internal' => 'Internal',
                        'customer' => 'Kunde',
                        'both' => 'Beides',
                    ])
                    ->placeholder('All types'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Template duplizieren?')
                    ->modalDescription('Dies erstellt eine Kopie des Templates mit dem Suffix " (Kopie)".')
                    ->action(function (EmailTemplate $record) {
                        // Create a copy of the template
                        $duplicate = $record->replicate();
                        $duplicate->name = $record->name.' (Kopie)';
                        $duplicate->is_active = false; // Set as draft
                        $duplicate->save();

                        // Success notification
                        \Filament\Notifications\Notification::make()
                            ->title('Template wurde dupliziert')
                            ->success()
                            ->send();

                        // Redirect to edit page of the duplicate
                        return redirect()->route('filament.admin.resources.email-templates.edit', ['record' => $duplicate]);
                    }),
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
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
