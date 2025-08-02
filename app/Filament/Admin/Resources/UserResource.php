<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 10;
    use HasConsistentNavigation;
    
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = null;
    
    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.system');
    }
    
    public static function getNavigationLabel(): string
    {
        return __('admin.resources.users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->label('Name')->required(),
                Forms\Components\TextInput::make('email')->label('E-Mail')->email()->required(),
                Forms\Components\Select::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\TextInput::make('password')->label('Passwort')->password()->required()->maxLength(255),
                Forms\Components\Section::make('Two-Factor Authentication')
                    ->schema([
                        Forms\Components\Toggle::make('two_factor_enforced')
                            ->label('Enforce 2FA')
                            ->helperText('Require this user to enable two-factor authentication')
                            ->columnSpan('full'),
                        Forms\Components\Placeholder::make('two_factor_status')
                            ->label('2FA Status')
                            ->content(fn (?User $record): string => 
                                $record?->hasEnabledTwoFactorAuthentication() 
                                    ? '✅ Enabled' 
                                    : '❌ Not enabled'
                            ),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable(),
                Tables\Columns\TextColumn::make('company.name')->label('Unternehmen'),
                Tables\Columns\IconColumn::make('two_factor_confirmed_at')
                    ->label('2FA')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (User $record): string => 
                        $record->hasEnabledTwoFactorAuthentication() 
                            ? '2FA enabled' 
                            : ($record->two_factor_enforced ? '2FA required but not enabled' : '2FA not enabled')
                    ),
                Tables\Columns\IconColumn::make('two_factor_enforced')
                    ->label('2FA Required')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')->label('Erstellt')->dateTime('d.m.Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle2FA')
                    ->label(fn (User $record): string => 
                        $record->two_factor_enforced ? 'Disable 2FA Requirement' : 'Require 2FA'
                    )
                    ->icon(fn (User $record): string => 
                        $record->two_factor_enforced ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed'
                    )
                    ->color(fn (User $record): string => 
                        $record->two_factor_enforced ? 'danger' : 'warning'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => 
                        $record->two_factor_enforced 
                            ? 'Disable 2FA Requirement' 
                            : 'Require Two-Factor Authentication'
                    )
                    ->modalDescription(fn (User $record): string => 
                        $record->two_factor_enforced 
                            ? 'Are you sure you want to disable the 2FA requirement for this user?' 
                            : 'This will require the user to enable two-factor authentication on their next login.'
                    )
                    ->action(function (User $record): void {
                        if ($record->two_factor_enforced) {
                            $record->disableTwoFactorEnforcement();
                            Notification::make()
                                ->title('2FA requirement disabled')
                                ->success()
                                ->send();
                        } else {
                            $record->enforceTwoFactor();
                            Notification::make()
                                ->title('2FA requirement enabled')
                                ->success()
                                ->body('The user will be required to enable 2FA on their next login.')
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('enforce2FA')
                        ->label('Require 2FA')
                        ->icon('heroicon-o-lock-closed')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Require Two-Factor Authentication')
                        ->modalDescription('This will require all selected users to enable two-factor authentication on their next login.')
                        ->action(function ($records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!$record->two_factor_enforced) {
                                    $record->enforceTwoFactor();
                                    $count++;
                                }
                            }
                            
                            if ($count > 0) {
                                Notification::make()
                                    ->title('2FA requirement enabled')
                                    ->success()
                                    ->body("2FA requirement enabled for {$count} user(s).")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('disable2FARequirement')
                        ->label('Disable 2FA Requirement')
                        ->icon('heroicon-o-lock-open')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Disable 2FA Requirement')
                        ->modalDescription('This will remove the 2FA requirement for all selected users.')
                        ->action(function ($records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->two_factor_enforced) {
                                    $record->disableTwoFactorEnforcement();
                                    $count++;
                                }
                            }
                            
                            if ($count > 0) {
                                Notification::make()
                                    ->title('2FA requirement disabled')
                                    ->success()
                                    ->body("2FA requirement disabled for {$count} user(s).")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('two_factor_confirmed_at')
                    ->label('2FA Status')
                    ->placeholder('All users')
                    ->trueLabel('2FA enabled')
                    ->falseLabel('2FA not enabled')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('two_factor_confirmed_at'),
                        false: fn ($query) => $query->whereNull('two_factor_confirmed_at'),
                    ),
                Tables\Filters\TernaryFilter::make('two_factor_enforced')
                    ->label('2FA Required')
                    ->placeholder('All users')
                    ->trueLabel('Required')
                    ->falseLabel('Not required'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
