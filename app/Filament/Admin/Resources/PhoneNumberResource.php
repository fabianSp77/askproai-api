<?php

namespace App\Filament\Admin\Resources;

use App\Models\PhoneNumber;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\PhoneNumberResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class PhoneNumberResource extends Resource
{
    protected static ?string $model = PhoneNumber::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'Unternehmensstruktur';
    protected static ?string $navigationLabel = 'Telefonnummern';
    protected static ?int $navigationSort = 50;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('view_any_phone_number')) {
            return true;
        }
        
        // Company admins can view phone numbers
        return $user->company_id !== null && $user->hasRole('company_admin');
    }
    
    public static function canView($record): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('view_phone_number')) {
            return true;
        }
        
        // Company admins can view phone numbers from their branches
        if ($record->branch && $user->company_id === $record->branch->company_id) {
            return $user->hasRole('company_admin');
        }
        
        return false;
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        
        // Super admin can edit all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('update_phone_number')) {
            return true;
        }
        
        // Company admins can edit phone numbers from their branches
        if ($record->branch && $user->company_id === $record->branch->company_id) {
            return $user->hasRole('company_admin');
        }
        
        return false;
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        // Super admin can create
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('create_phone_number')) {
            return true;
        }
        
        // Company admins can create phone numbers
        return $user->company_id !== null && $user->hasRole('company_admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Phone Number Details')
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('Telefonnummer')
                        ->required()
                        ->placeholder('+49 30 123456')
                        ->helperText('International format (e.g., +49 for Germany)')
                        ->tel()
                        ->unique(ignoreRecord: true),
                        
                    Forms\Components\Select::make('branch_id')
                        ->label('Filiale')
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $branch = Branch::find($state);
                                if ($branch) {
                                    $set('company_id', $branch->company_id);
                                    if ($branch->retell_agent_id) {
                                        $set('retell_agent_id', $branch->retell_agent_id);
                                    }
                                }
                            }
                        }),
                        
                    Forms\Components\Hidden::make('company_id'),
                    
                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options([
                            'main' => 'Hauptnummer',
                            'support' => 'Support',
                            'sales' => 'Vertrieb',
                            'mobile' => 'Mobil',
                            'test' => 'Test',
                        ])
                        ->default('main')
                        ->required(),
                        
                    Forms\Components\Toggle::make('is_primary')
                        ->label('Primäre Nummer')
                        ->helperText('Dies wird die Hauptkontaktnummer für die Filiale')
                        ->default(false),
                        
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')
                        ->default(true)
                        ->required(),
                ])
                ->columns(2),
                
            Forms\Components\Section::make('Retell Configuration')
                ->schema([
                    Forms\Components\TextInput::make('retell_agent_id')
                        ->label('Retell Agent ID')
                        ->placeholder('agent_xxxxxxxxxxxxx')
                        ->helperText('Der Retell AI Agent für diese Nummer'),
                        
                    Forms\Components\TextInput::make('retell_phone_id')
                        ->label('Retell Phone ID')
                        ->placeholder('phone_xxxxxxxxxxxxx')
                        ->helperText('Die Retell Telefonnummer ID (falls über Retell gekauft)'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Telefonnummer')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),
                    
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('branch.company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'main',
                        'secondary' => 'support',
                        'success' => 'sales',
                        'warning' => 'mobile',
                        'danger' => 'test',
                    ]),
                    
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primär')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('retell_agent_id')
                    ->label('Retell Agent')
                    ->searchable()
                    ->toggleable()
                    ->limit(20)
                    ->tooltip(function (PhoneNumber $record): ?string {
                        return $record->retell_agent_id;
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'main' => 'Hauptnummer',
                        'support' => 'Support',
                        'sales' => 'Vertrieb',
                        'mobile' => 'Mobil',
                        'test' => 'Test',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                    
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primär'),
                    
                Tables\Filters\Filter::make('has_retell_agent')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('retell_agent_id'))
                    ->label('Hat Retell Agent'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('test_resolution')
                    ->label('Test')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->action(function (PhoneNumber $record) {
                        $output = shell_exec("php artisan phone:test-resolution {$record->number} 2>&1");
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Phone Resolution Test')
                            ->body("Test abgeschlossen für {$record->number}. Details in den Logs.")
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation()
                        ->color('success'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhoneNumbers::route('/'),
            'create' => Pages\CreatePhoneNumber::route('/create'),
            'edit' => Pages\EditPhoneNumber::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_active', true)->count() > 0 ? 'success' : 'danger';
    }
}
