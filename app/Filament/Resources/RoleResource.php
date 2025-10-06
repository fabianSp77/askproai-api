<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Rollen & Rechte';

    protected static ?string $modelLabel = 'Rolle';

    protected static ?string $pluralModelLabel = 'Rollen';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Rollen-Verwaltung')
                    ->tabs([
                        Tabs\Tab::make('🎭 Grunddaten')
                            ->schema([
                                Forms\Components\Section::make('Rollen-Informationen')
                                    ->description('Grundlegende Einstellungen der Rolle')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Rollenname')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(125)
                                            ->placeholder('z.B. content-manager')
                                            ->helperText('Eindeutiger technischer Name (lowercase, keine Leerzeichen)')
                                            ->regex('/^[a-z0-9\-]+$/')
                                            ->validationMessages([
                                                'regex' => 'Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt',
                                            ])
                                            ->disabled(fn (?Role $record) => $record?->is_system ?? false),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('Beschreiben Sie die Hauptaufgaben dieser Rolle')
                                            ->helperText('Hilft Benutzern zu verstehen, wofür diese Rolle gedacht ist')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),

                                Forms\Components\Section::make('Darstellung')
                                    ->description('Visuelle Gestaltung der Rolle')
                                    ->schema([
                                        Forms\Components\Select::make('color')
                                            ->label('Farbe')
                                            ->options([
                                                'primary' => '🔵 Primär',
                                                'secondary' => '⚫ Sekundär',
                                                'success' => '🟢 Erfolg',
                                                'warning' => '🟡 Warnung',
                                                'danger' => '🔴 Gefahr',
                                                'info' => '🔷 Info',
                                                'gray' => '⚪ Grau',
                                            ])
                                            ->default('secondary')
                                            ->native(false)
                                            ->required(),

                                        Forms\Components\Select::make('icon')
                                            ->label('Icon')
                                            ->options([
                                                'heroicon-o-shield-exclamation' => '🛡️ Super Admin',
                                                'heroicon-o-shield-check' => '✅ Admin',
                                                'heroicon-o-briefcase' => '💼 Manager',
                                                'heroicon-o-cog' => '⚙️ Operator',
                                                'heroicon-o-eye' => '👁️ Viewer',
                                                'heroicon-o-user-group' => '👥 Team',
                                                'heroicon-o-academic-cap' => '🎓 Experte',
                                                'heroicon-o-clipboard-document-check' => '📋 Prüfer',
                                            ])
                                            ->default('heroicon-o-user')
                                            ->native(false),

                                        Forms\Components\TextInput::make('priority')
                                            ->label('Priorität')
                                            ->numeric()
                                            ->default(99)
                                            ->minValue(1)
                                            ->maxValue(999)
                                            ->helperText('Niedrigere Werte = höhere Priorität'),

                                        Forms\Components\Toggle::make('is_system')
                                            ->label('Systemrolle')
                                            ->helperText('Systemrollen können nicht gelöscht werden')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('🔐 Berechtigungen')
                            ->schema([
                                Forms\Components\Section::make('Berechtigungen zuweisen')
                                    ->description('Wählen Sie die Berechtigungen für diese Rolle')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('Verfügbare Berechtigungen')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                $permissions = Permission::all()->groupBy(function ($permission) {
                                                    $parts = explode('.', $permission->name);
                                                    return ucfirst($parts[0] ?? 'Allgemein');
                                                });

                                                $options = [];
                                                foreach ($permissions as $group => $items) {
                                                    foreach ($items as $permission) {
                                                        $label = str_replace('.', ' › ', $permission->name);
                                                        $options[$permission->id] = "[$group] $label";
                                                    }
                                                }
                                                return $options;
                                            })
                                            ->searchable()
                                            ->bulkToggleable()
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->helperText('Aktivieren Sie alle benötigten Berechtigungen')
                                            ->disabled(fn (?Role $record) => $record?->is_system && $record->name === Role::SUPER_ADMIN),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Vorkonfigurierte Sets')
                                    ->description('Schnell-Vorlagen für häufige Berechtigungskombinationen')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('apply_read_only')
                                                ->label('🔍 Nur Lesen')
                                                ->color('info')
                                                ->action(function ($set) {
                                                    $permissions = Permission::where('name', 'like', '%.view')
                                                        ->orWhere('name', 'like', '%.read')
                                                        ->pluck('id')
                                                        ->toArray();
                                                    $set('permissions', $permissions);
                                                    Notification::make()
                                                        ->title('Leserechte angewendet')
                                                        ->success()
                                                        ->send();
                                                }),

                                            Forms\Components\Actions\Action::make('apply_content_manager')
                                                ->label('📝 Content Manager')
                                                ->color('success')
                                                ->action(function ($set) {
                                                    $permissions = Permission::where(function ($query) {
                                                        $query->where('name', 'like', 'company.%')
                                                            ->orWhere('name', 'like', 'branch.%')
                                                            ->orWhere('name', 'like', 'staff.%')
                                                            ->orWhere('name', 'like', 'service.%');
                                                    })->pluck('id')->toArray();
                                                    $set('permissions', $permissions);
                                                    Notification::make()
                                                        ->title('Content Manager Rechte angewendet')
                                                        ->success()
                                                        ->send();
                                                }),

                                            Forms\Components\Actions\Action::make('apply_system_admin')
                                                ->label('⚙️ System Admin')
                                                ->color('warning')
                                                ->action(function ($set) {
                                                    $permissions = Permission::where(function ($query) {
                                                        $query->where('name', 'like', 'user.%')
                                                            ->orWhere('name', 'like', 'role.%')
                                                            ->orWhere('name', 'like', 'setting.%')
                                                            ->orWhere('name', 'like', 'system.%');
                                                    })->pluck('id')->toArray();
                                                    $set('permissions', $permissions);
                                                    Notification::make()
                                                        ->title('System Admin Rechte angewendet')
                                                        ->success()
                                                        ->send();
                                                }),

                                            Forms\Components\Actions\Action::make('clear_all')
                                                ->label('🗑️ Alle entfernen')
                                                ->color('danger')
                                                ->requiresConfirmation()
                                                ->action(function ($set) {
                                                    $set('permissions', []);
                                                    Notification::make()
                                                        ->title('Alle Berechtigungen entfernt')
                                                        ->warning()
                                                        ->send();
                                                }),
                                        ])
                                        ->columnSpanFull()
                                        ->fullWidth(),
                                    ])
                                    ->columns(1)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('👥 Benutzer')
                            ->schema([
                                Forms\Components\Section::make('Benutzer mit dieser Rolle')
                                    ->description('Übersicht der zugewiesenen Benutzer')
                                    ->schema([
                                        Forms\Components\Placeholder::make('users_list')
                                            ->label('Zugewiesene Benutzer')
                                            ->content(function (?Role $record) {
                                                if (!$record || $record->users->isEmpty()) {
                                                    return 'Keine Benutzer zugewiesen';
                                                }

                                                $users = $record->users->take(10);
                                                $html = '<div class="space-y-2">';
                                                foreach ($users as $user) {
                                                    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&color=7F9CF5&background=EBF4FF';
                                                    $html .= '<div class="flex items-center space-x-3">';
                                                    $html .= '<img src="' . $avatar . '" class="w-8 h-8 rounded-full">';
                                                    $html .= '<div>';
                                                    $html .= '<div class="font-medium">' . e($user->name) . '</div>';
                                                    $html .= '<div class="text-sm text-gray-500">' . e($user->email) . '</div>';
                                                    $html .= '</div>';
                                                    $html .= '</div>';
                                                }
                                                if ($record->users->count() > 10) {
                                                    $html .= '<div class="text-sm text-gray-500 mt-2">... und ' . ($record->users->count() - 10) . ' weitere</div>';
                                                }
                                                $html .= '</div>';
                                                return new \Illuminate\Support\HtmlString($html);
                                            }),

                                        Forms\Components\Select::make('assign_users')
                                            ->label('Benutzer hinzufügen')
                                            ->multiple()
                                            ->relationship('users', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Wählen Sie Benutzer aus, um ihnen diese Rolle zuzuweisen')
                                            ->visible(fn (?Role $record) => $record !== null),
                                    ])
                                    ->columns(1),

                                Forms\Components\Section::make('Statistiken')
                                    ->description('Nutzungsstatistiken dieser Rolle')
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_users')
                                            ->label('Gesamtbenutzer')
                                            ->content(fn (?Role $record) => $record ? $record->users()->count() : 0),

                                        Forms\Components\Placeholder::make('active_users')
                                            ->label('Aktive Benutzer')
                                            ->content(fn (?Role $record) => $record ? $record->users()->where('is_active', true)->count() : 0),

                                        Forms\Components\Placeholder::make('last_assigned')
                                            ->label('Zuletzt zugewiesen')
                                            ->content(fn (?Role $record) => $record && $record->users()->latest()->first()
                                                ? $record->users()->latest()->first()->created_at->format('d.m.Y H:i')
                                                : 'Noch nie'),
                                    ])
                                    ->columns(3),
                            ]),

                        Tabs\Tab::make('⚙️ Erweitert')
                            ->schema([
                                Forms\Components\Section::make('Metadaten')
                                    ->description('Zusätzliche Konfigurationen')
                                    ->schema([
                                        Forms\Components\KeyValue::make('metadata')
                                            ->label('Benutzerdefinierte Attribute')
                                            ->keyLabel('Schlüssel')
                                            ->valueLabel('Wert')
                                            ->addActionLabel('Attribut hinzufügen')
                                            ->deletable()
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('System-Informationen')
                                    ->description('Technische Details')
                                    ->schema([
                                        Forms\Components\Placeholder::make('id')
                                            ->label('Rollen-ID')
                                            ->content(fn (?Role $record) => $record?->id ?? '-'),

                                        Forms\Components\Placeholder::make('guard_name')
                                            ->label('Guard')
                                            ->content(fn (?Role $record) => $record?->guard_name ?? 'web'),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Erstellt am')
                                            ->content(fn (?Role $record) => $record?->created_at?->format('d.m.Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Aktualisiert am')
                                            ->content(fn (?Role $record) => $record?->updated_at?->format('d.m.Y H:i:s') ?? '-'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Rollenname')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn (Role $record) => $record->icon_name)
                    ->badge()
                    ->color(fn (Role $record) => $record->badge_color)
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('-', ' ', $state))),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (Role $record) => $record->description),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Benutzer')
                    ->counts('users')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state > 0,
                        'gray' => fn ($state) => $state === 0,
                    ])
                    ->icon('heroicon-m-users'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Berechtigungen')
                    ->counts('permissions')
                    ->badge()
                    ->colors([
                        'primary' => fn ($state) => $state > 20,
                        'success' => fn ($state) => $state >= 10 && $state <= 20,
                        'warning' => fn ($state) => $state >= 5 && $state < 10,
                        'gray' => fn ($state) => $state < 5,
                    ])
                    ->icon('heroicon-m-key'),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (bool $state) => $state ? 'Systemrolle (geschützt)' : 'Benutzerdefiniert'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->sortable()
                    ->badge()
                    ->color('secondary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'system' => '🔒 Systemrollen',
                        'custom' => '👤 Benutzerdefiniert',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match($data['value'] ?? null) {
                            'system' => $query->where('is_system', true),
                            'custom' => $query->where('is_system', false),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('has_users')
                    ->label('Mit Benutzern')
                    ->query(fn (Builder $query): Builder => $query->has('users'))
                    ->toggle(),

                Tables\Filters\Filter::make('no_users')
                    ->label('Ohne Benutzer')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('users'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('priority_range')
                    ->label('Prioritätsbereich')
                    ->options([
                        '1-10' => 'Hoch (1-10)',
                        '11-50' => 'Mittel (11-50)',
                        '51-100' => 'Niedrig (51-100)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match($data['value'] ?? null) {
                            '1-10' => $query->whereBetween('priority', [1, 10]),
                            '11-50' => $query->whereBetween('priority', [11, 50]),
                            '51-100' => $query->whereBetween('priority', [51, 100]),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Anzeigen'),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Bearbeiten')
                    ->visible(fn (Role $record) => !$record->is_system || auth()->user()->hasRole(Role::SUPER_ADMIN)),

                Tables\Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-m-document-duplicate')
                    ->tooltip('Rolle duplizieren')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Rolle duplizieren')
                    ->modalDescription('Möchten Sie diese Rolle wirklich duplizieren?')
                    ->modalSubmitActionLabel('Duplizieren')
                    ->action(function (Role $record) {
                        $newRole = $record->replicate();
                        $newRole->name = $record->name . '-copy-' . time();
                        $newRole->is_system = false;
                        $newRole->save();
                        $newRole->syncPermissions($record->permissions);

                        Notification::make()
                            ->title('Rolle dupliziert')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Role $record) => !$record->is_system),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Löschen')
                    ->visible(fn (Role $record) => $record->can_delete),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assign_permissions')
                        ->label('Berechtigungen zuweisen')
                        ->icon('heroicon-m-key')
                        ->color('primary')
                        ->form([
                            Forms\Components\CheckboxList::make('permissions')
                                ->label('Berechtigungen')
                                ->options(Permission::pluck('name', 'id'))
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2),
                        ])
                        ->action(function (array $records, array $data) {
                            foreach ($records as $record) {
                                if (!$record->is_system) {
                                    $record->syncPermissions($data['permissions'] ?? []);
                                }
                            }
                            Notification::make()
                                ->title('Berechtigungen zugewiesen')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole(Role::SUPER_ADMIN)),
                ])
            ])
            ->defaultSort('priority', 'asc')
            ->reorderable('priority')
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('Keine Rollen vorhanden')
            ->emptyStateDescription('Erstellen Sie Ihre erste Rolle')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Rolleninformationen')
                    ->schema([
                        Split::make([
                            Section::make([
                                TextEntry::make('name')
                                    ->label('Rollenname')
                                    ->badge()
                                    ->color(fn (Role $record) => $record->badge_color)
                                    ->icon(fn (Role $record) => $record->icon_name),

                                TextEntry::make('description')
                                    ->label('Beschreibung')
                                    ->default('Keine Beschreibung'),

                                TextEntry::make('guard_name')
                                    ->label('Guard')
                                    ->badge()
                                    ->color('secondary'),

                                TextEntry::make('is_system')
                                    ->label('Systemrolle')
                                    ->badge()
                                    ->color(fn (bool $state) => $state ? 'warning' : 'success')
                                    ->formatStateUsing(fn (bool $state) => $state ? 'Ja (geschützt)' : 'Nein'),

                                TextEntry::make('priority')
                                    ->label('Priorität')
                                    ->badge()
                                    ->color('info'),
                            ])->grow(false),

                            Section::make([
                                TextEntry::make('users_count')
                                    ->label('Anzahl Benutzer')
                                    ->state(fn (Role $record) => $record->users()->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-m-users'),

                                TextEntry::make('permissions_count')
                                    ->label('Anzahl Berechtigungen')
                                    ->state(fn (Role $record) => $record->permissions()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-m-key'),

                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->since(),
                            ]),
                        ])->from('md'),
                    ])
                    ->collapsible(),

                Section::make('Berechtigungen')
                    ->schema([
                        RepeatableEntry::make('permissions')
                            ->label(false)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Berechtigung')
                                    ->badge()
                                    ->color('primary'),
                            ])
                            ->columns(3)
                            ->grid(3),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Zugewiesene Benutzer')
                    ->schema([
                        RepeatableEntry::make('users')
                            ->label(false)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->weight('bold'),
                                TextEntry::make('email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-m-envelope'),
                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (bool $state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state) => $state ? 'Aktiv' : 'Inaktiv'),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (Role $record) => $record->users()->exists()),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['users', 'permissions']);
    }
}