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

                Forms\Components\RichEditor::make('body_html')
                    ->label('Email Body')
                    ->required()
                    ->helperText('The HTML body of the email (can use variables like {{customer_name}}, {{case_number}}, etc.)')
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('available_variables')
                    ->label('📋 Available Template Variables')
                    ->content(new \Illuminate\Support\HtmlString('
                        <div class="text-sm space-y-2">
                            <p class="font-semibold text-gray-700 dark:text-gray-300">Use <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">{{variable_name}}</code> in subject or body:</p>
                            <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                                <li><strong>{{customer_name}}</strong> - Name of the customer</li>
                                <li><strong>{{customer_email}}</strong> - Customer\'s email address</li>
                                <li><strong>{{company_name}}</strong> - Name of your company</li>
                                <li><strong>{{case_number}}</strong> - Unique case/ticket number</li>
                                <li><strong>{{case_subject}}</strong> - Subject/title of the case</li>
                                <li><strong>{{case_description}}</strong> - Detailed description of the case</li>
                                <li><strong>{{case_status}}</strong> - Current status of the case</li>
                                <li><strong>{{case_priority}}</strong> - Priority level of the case</li>
                                <li><strong>{{created_at}}</strong> - Date and time when the case was created</li>
                            </ul>
                        </div>
                    '))
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
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
