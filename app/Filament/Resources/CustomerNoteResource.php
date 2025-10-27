<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerNoteResource\Pages;
use App\Filament\Resources\CustomerNoteResource\RelationManagers;
use App\Models\CustomerNote;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Cache;

class CustomerNoteResource extends Resource
{
    protected static ?string $model = CustomerNote::class;

    /**
     * Resource disabled - customer_notes table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false; // Prevents all access to this resource
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Kundennotizen';

    protected static ?string $modelLabel = 'Kundennotiz';

    protected static ?string $pluralModelLabel = 'Kundennotizen';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Note Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'general' => 'General Note',
                                        'call' => 'Call Note',
                                        'email' => 'Email',
                                        'meeting' => 'Meeting',
                                        'task' => 'Task',
                                        'followup' => 'Follow-up',
                                        'complaint' => 'Complaint',
                                        'feedback' => 'Feedback',
                                    ])
                                    ->default('general')
                                    ->required(),

                                Forms\Components\Select::make('category')
                                    ->label('Category')
                                    ->options([
                                        'sales' => 'Sales',
                                        'support' => 'Support',
                                        'technical' => 'Technical',
                                        'billing' => 'Billing',
                                        'general' => 'General',
                                        'important' => 'Important',
                                    ])
                                    ->default('general'),

                                Forms\Components\Select::make('visibility')
                                    ->label('Visibility')
                                    ->options([
                                        'public' => 'Public (All users can see)',
                                        'internal' => 'Internal (Staff only)',
                                        'private' => 'Private (Creator only)',
                                    ])
                                    ->default('public')
                                    ->required(),
                            ])
                            ->columns(4),

                        Forms\Components\TextInput::make('subject')
                            ->label('Subject')
                            ->placeholder('Brief subject/title for the note')
                            ->maxLength(255),

                        Forms\Components\RichEditor::make('content')
                            ->label('Note Content')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'link',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Note Settings')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Forms\Components\Toggle::make('is_important')
                                    ->label('Mark as Important')
                                    ->helperText('Important notes are highlighted')
                                    ->inline(false),

                                Forms\Components\Toggle::make('is_pinned')
                                    ->label('Pin this Note')
                                    ->helperText('Pinned notes appear at the top')
                                    ->inline(false),
                            ])
                            ->columns(2),
                    ])
                    ->collapsed(),

                Forms\Components\Hidden::make('created_by')
                    ->default(auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->subject;
                    }),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'call',
                        'warning' => 'email',
                        'danger' => 'complaint',
                        'secondary' => 'meeting',
                    ])
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Category')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'sales',
                        'warning' => 'support',
                        'danger' => 'billing',
                        'secondary' => 'technical',
                    ])
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('Visibility')
                    ->colors([
                        'success' => 'public',
                        'warning' => 'internal',
                        'danger' => 'private',
                    ]),

                Tables\Columns\IconColumn::make('is_important')
                    ->label('Important')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('Pinned')
                    ->boolean()
                    ->trueIcon('heroicon-o-bookmark')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Customer'),

                SelectFilter::make('type')
                    ->options([
                        'general' => 'General Note',
                        'call' => 'Call Note',
                        'email' => 'Email',
                        'meeting' => 'Meeting',
                        'task' => 'Task',
                        'followup' => 'Follow-up',
                        'complaint' => 'Complaint',
                        'feedback' => 'Feedback',
                    ])
                    ->label('Type'),

                SelectFilter::make('category')
                    ->options([
                        'sales' => 'Sales',
                        'support' => 'Support',
                        'technical' => 'Technical',
                        'billing' => 'Billing',
                        'general' => 'General',
                        'important' => 'Important',
                    ])
                    ->label('Category'),

                TernaryFilter::make('is_important')
                    ->label('Important Only')
                    ->placeholder('All notes')
                    ->trueLabel('Important notes')
                    ->falseLabel('Regular notes'),

                TernaryFilter::make('is_pinned')
                    ->label('Pinned')
                    ->placeholder('All notes')
                    ->trueLabel('Pinned notes')
                    ->falseLabel('Unpinned notes'),

                Tables\Filters\Filter::make('recent')
                    ->label('Recent Notes')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
                Tables\Actions\Action::make('toggleImportant')
                    ->label('Toggle Important')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color(fn ($record) => $record->is_important ? 'danger' : 'gray')
                    ->action(function ($record) {
                        $record->update(['is_important' => !$record->is_important]);
                    })
                    ->iconButton(),
                Tables\Actions\Action::make('togglePin')
                    ->label('Toggle Pin')
                    ->icon('heroicon-o-bookmark')
                    ->color(fn ($record) => $record->is_pinned ? 'warning' : 'gray')
                    ->action(function ($record) {
                        $record->update(['is_pinned' => !$record->is_pinned]);
                    })
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markImportant')
                        ->label('Mark as Important')
                        ->icon('heroicon-o-exclamation-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_important' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('removeImportant')
                        ->label('Remove Important')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_important' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('showPinned')
                        ->label('Show Pinned')
                        ->icon('heroicon-o-bookmark'),
                    Tables\Actions\Action::make('showImportant')
                        ->label('Show Important')
                        ->icon('heroicon-o-exclamation-circle'),
                    Tables\Actions\Action::make('showRecent')
                        ->label('Show Recent (7 days)')
                        ->icon('heroicon-o-clock'),
                ])
                ->label('Quick Filters')
                ->icon('heroicon-o-funnel')
                ->color('gray'),
            ]);
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
            'index' => Pages\ListCustomerNotes::route('/'),
            'create' => Pages\CreateCustomerNote::route('/create'),
            'edit' => Pages\EditCustomerNote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'creator']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['subject', 'content', 'customer.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }
}