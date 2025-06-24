<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CustomerResource\Pages;
use App\Filament\Admin\Resources\Concerns\MultiTenantResource;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\Customer;
use App\Models\Company;
use App\Filament\Components\StatusBadge;
use App\Filament\Components\ActionButton;
use App\Filament\Components\DateRangePicker;
use App\Filament\Components\SearchableSelect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\IconPosition;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Services\CustomerPortalService;

class CustomerResource extends EnhancedResourceSimple
{

    public static function canViewAny(): bool
    {
        return true;
    }

    use MultiTenantResource;
    
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Kunden';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();
        
        if ($user && !$user->hasRole('super_admin') && !$user->hasRole('reseller')) {
            return parent::getEloquentQuery()->where('tenant_id', $user->tenant_id);
        }
        
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Hilfe-Banner am Anfang
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('')
                            ->content(new HtmlString('
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-start space-x-3">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-200">Kunden-Einrichtung</h3>
                                            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                                Hier können Sie Ihre Endkunden (Anrufer) verwalten. Diese Daten werden automatisch aus den Anrufen erfasst.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            '))
                            ->columnSpanFull(),
                    ])
                    ->hiddenOn('edit')
                    ->collapsible()
                    ->collapsed(false),

                // Hauptformular
                Forms\Components\Section::make('Kundendaten')
                    ->description('Grundlegende Informationen zum Kunden')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->placeholder('Max Mustermann')
                                    ->helperText('Der vollständige Name des Kunden')
                                    ->suffixIcon('heroicon-m-user')
                                    ->suffixIconColor('primary'),

                                Forms\Components\TextInput::make('email')
                                    ->label('E-Mail')
                                    ->email()
                                    ->placeholder('kunde@beispiel.de')
                                    ->helperText('Wird für Terminbestätigungen verwendet')
                                    ->suffixIcon('heroicon-m-envelope')
                                    ->suffixIconColor('primary')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('sendTestMail')
                                            ->icon('heroicon-m-paper-airplane')
                                            ->label('Test-Mail')
                                            ->action(function ($state) {
                                                if ($state) {
                                                    Notification::make()
                                                        ->title('Test-Mail würde gesendet an: ' . $state)
                                                        ->success()
                                                        ->send();
                                                }
                                            })
                                            ->requiresConfirmation()
                                            ->visible(fn ($state) => filled($state))
                                    ),

                                Forms\Components\TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->placeholder('+49 123 456789')
                                    ->helperText('Format: +49 123 456789')
                                    ->suffixIcon('heroicon-m-phone')
                                    ->suffixIconColor('primary')
                                    ->mask('+99 999 9999999')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('callPhone')
                                            ->icon('heroicon-m-phone-arrow-up-right')
                                            ->label('Anrufen')
                                            ->url(fn ($state) => $state ? "tel:{$state}" : null)
                                            ->openUrlInNewTab()
                                            ->visible(fn ($state) => filled($state))
                                    ),

                                SearchableSelect::company('company_id')
                                    ->helperText(new HtmlString('
                                        <span class="text-xs">
                                            Zu welchem Ihrer Unternehmenskunden gehört dieser Endkunde? 
                                            <a href="/admin/companies" target="_blank" class="text-primary-600 hover:text-primary-500 underline">
                                                Unternehmen verwalten →
                                            </a>
                                        </span>
                                    '))
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('viewCompany')
                                            ->icon('heroicon-m-arrow-top-right-on-square')
                                            ->label('Öffnen')
                                            ->url(fn ($state) => $state ? "/admin/companies/{$state}/edit" : null)
                                            ->openUrlInNewTab()
                                            ->visible(fn ($state) => filled($state))
                                    ),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->placeholder('Besondere Wünsche, Präferenzen oder wichtige Informationen...')
                            ->helperText('Interne Notizen - werden dem Kunden nicht angezeigt')
                            ->columnSpanFull(),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('birthdate')
                                    ->label('Geburtsdatum')
                                    ->native(false)
                                    ->displayFormat('d.m.Y')
                                    ->maxDate(now())
                                    ->closeOnDateSelection(),
                                    
                                Forms\Components\TagsInput::make('tags')
                                    ->label('Tags')
                                    ->separator(',')
                                    ->suggestions([
                                        'VIP',
                                        'Stammkunde',
                                        'Neukunde',
                                        'Problemkunde',
                                        'Zahlt bar',
                                        'Sensibel',
                                        'Termin-Erinnerung',
                                    ])
                                    ->helperText('Verwenden Sie Tags zur besseren Organisation'),
                                    
                                Forms\Components\Select::make('preferred_contact')
                                    ->label('Bevorzugter Kontakt')
                                    ->options([
                                        'phone' => 'Telefon',
                                        'email' => 'E-Mail',
                                        'sms' => 'SMS',
                                        'whatsapp' => 'WhatsApp',
                                    ])
                                    ->default('phone'),
                            ]),
                    ]),

                // Hilfe-Sektion
                Forms\Components\Section::make('Hilfe & Tipps')
                    ->description('Nützliche Informationen und Links')
                    ->icon('heroicon-o-question-mark-circle')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('')
                            ->content(new HtmlString('
                                <div class="space-y-4">
                                    <!-- Tipp 1 -->
                                    <div class="flex items-start space-x-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <h4 class="text-sm font-medium text-green-900 dark:text-green-200">Automatische Erfassung</h4>
                                            <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                                                Kundendaten werden automatisch aus Telefonanrufen über Retell.ai erfasst.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Tipp 2 -->
                                    <div class="flex items-start space-x-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <h4 class="text-sm font-medium text-amber-900 dark:text-amber-200">Datenschutz beachten</h4>
                                            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                                Stellen Sie sicher, dass Kunden über die Datenspeicherung informiert sind.
                                                <a href="https://askproai.de/datenschutz" target="_blank" class="underline hover:no-underline">
                                                    Datenschutzerklärung ansehen →
                                                </a>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Links -->
                                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nützliche Links:</h4>
                                        <div class="space-y-2">
                                            <a href="https://docs.askproai.de/customers" target="_blank" class="flex items-center space-x-2 text-sm text-primary-600 hover:text-primary-500">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 00-2 2v6a2 2 0 002 2h2a1 1 0 100-2H6V7h11a1 1 0 110 2H9a1 1 0 000 2h8a2 2 0 002-2V5a2 2 0 00-2-2H6z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span>Dokumentation: Kundenverwaltung</span>
                                            </a>
                                            <a href="https://support.askproai.de" target="_blank" class="flex items-center space-x-2 text-sm text-primary-600 hover:text-primary-500">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span>Support kontaktieren</span>
                                            </a>
                                            <a href="/admin/appointments" class="flex items-center space-x-2 text-sm text-primary-600 hover:text-primary-500">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span>Termine dieses Kunden anzeigen</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Aktionen -->
                                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 flex flex-wrap gap-2">
                                        <button type="button" onclick="navigator.clipboard.writeText(window.location.href)" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>
                                                <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"></path>
                                            </svg>
                                            Link kopieren
                                        </button>
                                        <button type="button" onclick="window.print()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Drucken
                                        </button>
                                    </div>
                                </div>
                            '))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = parent::enhanceTable($table);
        
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['company', 'appointments' => fn($q) => $q->latest()->limit(5)])
                ->withCount(['appointments', 'calls']))
            ->striped()
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->extremePaginationLinks()
            ->paginated([10, 25, 50, 100])
            ->paginationPageOptions([10, 25, 50, 100])
            ->recordClasses(fn ($record) => match(true) {
                $record->tags && in_array('VIP', $record->tags) => 'border-l-4 border-yellow-500',
                $record->tags && in_array('Problemkunde', $record->tags) => 'border-l-4 border-red-500',
                default => '',
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->extraAttributes(['data-column-group' => 'basis']),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->iconPosition(IconPosition::Before)
                    ->iconColor('primary'),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->iconPosition(IconPosition::Before)
                    ->iconColor('gray')
                    ->copyable()
                    ->copyMessage('E-Mail kopiert!')
                    ->copyMessageDuration(1500),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->iconPosition(IconPosition::Before)
                    ->iconColor('gray')
                    ->copyable()
                    ->copyMessage('Telefonnummer kopiert!')
                    ->url(fn ($record) => $record->phone ? "tel:{$record->phone}" : null),
                    
                static::getCompanyColumn(),
                static::getBranchColumn(),
                    
                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Termine')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'warning',
                        default => 'success'
                    })
                    ->formatStateUsing(fn ($state) => $state . ' Termine'),
                    
                Tables\Columns\TextColumn::make('last_appointment')
                    ->label('Letzter Termin')
                    ->getStateUsing(fn ($record) => $record->appointments()->latest('starts_at')->first()?->starts_at)
                    ->dateTime('d.m.Y')
                    ->placeholder('Noch kein Termin')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->color('info')
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('portal_enabled')
                    ->label('Portal')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->portal_enabled ? 'Portal aktiviert' : 'Portal deaktiviert'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(array_merge(
                static::getMultiTenantFilters(),
                [
                    
                Tables\Filters\Filter::make('has_appointments')
                    ->label('Mit Terminen')
                    ->query(fn ($query) => $query->has('appointments')),
                    
                Tables\Filters\Filter::make('no_appointments')
                    ->label('Ohne Termine')
                    ->query(fn ($query) => $query->doesntHave('appointments')),
                    
                DateRangePicker::make('created_at', 'Registriert'),
                
                Tables\Filters\SelectFilter::make('tags')
                    ->label('Tags')
                    ->options([
                        'VIP' => 'VIP',
                        'Stammkunde' => 'Stammkunde',
                        'Neukunde' => 'Neukunde',
                        'Problemkunde' => 'Problemkunde',
                    ])
                    ->multiple(),
                ]
            ), layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square'),
                    Tables\Actions\Action::make('timeline')
                        ->label('Timeline')
                        ->icon('heroicon-m-clock')
                        ->color('info')
                        ->modalHeading(fn ($record) => 'Timeline: ' . $record->name)
                        ->modalContent(fn ($record) => view('filament.customer.timeline', ['customer' => $record]))
                        ->modalWidth('7xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Schließen'),
                        
                    ActionButton::quickBooking(),
                    
                    Tables\Actions\Action::make('showAppointments')
                        ->label('Termine anzeigen')
                        ->icon('heroicon-m-calendar')
                        ->url(fn ($record) => "/admin/appointments?tableFilters[customer][value]={$record->id}")
                        ->openUrlInNewTab(),
                        
                    ActionButton::sendEmail(),
                    ActionButton::sendSms(),
                    ActionButton::call(),
                    
                    // Portal Actions
                    Tables\Actions\Action::make('enablePortal')
                        ->label('Portal aktivieren')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Portal-Zugang aktivieren')
                        ->modalDescription('Dem Kunden wird ein Login-Link per E-Mail gesendet.')
                        ->visible(fn ($record) => !$record->portal_enabled && !empty($record->email))
                        ->action(function ($record) {
                            $portalService = app(CustomerPortalService::class);
                            
                            // Need to use CustomerAuth model for portal
                            $customerAuth = \App\Models\CustomerAuth::find($record->id);
                            
                            if ($portalService->enablePortalAccess($customerAuth)) {
                                Notification::make()
                                    ->title('Portal-Zugang aktiviert')
                                    ->body('Dem Kunden wurde eine E-Mail mit den Zugangsdaten gesendet.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body('Portal-Zugang konnte nicht aktiviert werden.')
                                    ->danger()
                                    ->send();
                            }
                        }),
                        
                    Tables\Actions\Action::make('disablePortal')
                        ->label('Portal deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->portal_enabled)
                        ->action(function ($record) {
                            $portalService = app(CustomerPortalService::class);
                            
                            // Need to use CustomerAuth model for portal
                            $customerAuth = \App\Models\CustomerAuth::find($record->id);
                            
                            if ($portalService->disablePortalAccess($customerAuth)) {
                                Notification::make()
                                    ->title('Portal-Zugang deaktiviert')
                                    ->body('Der Kunde kann sich nicht mehr im Portal anmelden.')
                                    ->success()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('merge')
                        ->label('Zusammenführen')
                        ->icon('heroicon-m-arrows-pointing-in')
                        ->color('warning')
                        ->modalHeading('Kunden zusammenführen')
                        ->modalDescription('Führen Sie diesen Kunden mit einem anderen zusammen. Alle Termine und Daten werden übertragen.')
                        ->form([
                            SearchableSelect::customer('target_customer_id')
                                ->label('Zielkunde')
                                ->helperText('Wählen Sie den Kunden, mit dem dieser zusammengeführt werden soll')
                                ->required()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->getSearchResultsUsing(fn (string $search, $record) => 
                                    \App\Models\Customer::where('id', '!=', $record->id)
                                        ->where(function ($query) use ($search) {
                                            $query->where('name', 'like', "%{$search}%")
                                                ->orWhere('email', 'like', "%{$search}%")
                                                ->orWhere('phone', 'like', "%{$search}%");
                                        })
                                        ->limit(50)
                                        ->pluck('name', 'id')
                                ),
                        ])
                        ->action(function ($record, array $data) {
                            // Merge logic
                            $targetCustomer = \App\Models\Customer::find($data['target_customer_id']);
                            
                            // Transfer appointments
                            $record->appointments()->update(['customer_id' => $targetCustomer->id]);
                            
                            // Merge contact info if target is missing
                            if (empty($targetCustomer->email) && !empty($record->email)) {
                                $targetCustomer->email = $record->email;
                            }
                            if (empty($targetCustomer->phone) && !empty($record->phone)) {
                                $targetCustomer->phone = $record->phone;
                            }
                            
                            $targetCustomer->save();
                            
                            // Delete the source customer
                            $record->delete();
                            
                            Notification::make()
                                ->title('Kunden zusammengeführt')
                                ->body('Die Kunden wurden erfolgreich zusammengeführt.')
                                ->success()
                                ->send();
                                
                            return redirect(CustomerResource::getUrl('edit', ['record' => $targetCustomer]));
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->emptyStateHeading('Noch keine Kunden')
            ->emptyStateDescription('Kunden werden automatisch angelegt, wenn sie über die Telefon-KI anrufen.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Kunde manuell anlegen')
                    ->url(fn (): string => static::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CustomerResource\RelationManagers\AppointmentsRelationManager::class,
            CustomerResource\RelationManagers\CallsRelationManager::class,
            CustomerResource\RelationManagers\NotesRelationManager::class,
        ];
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Kundendaten')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name')
                            ->icon('heroicon-m-user'),
                            
                        Infolists\Components\TextEntry::make('email')
                            ->label('E-Mail')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                            
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Telefon')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->url(fn ($record) => "tel:{$record->phone}"),
                            
                        Infolists\Components\TextEntry::make('company.name')
                            ->label('Unternehmen')
                            ->badge(),
                            
                        Infolists\Components\TextEntry::make('birthdate')
                            ->label('Geburtsdatum')
                            ->date('d.m.Y')
                            ->placeholder('Nicht angegeben'),
                            
                        Infolists\Components\TextEntry::make('tags')
                            ->label('Tags')
                            ->badge()
                            ->separator(','),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Statistiken')
                    ->schema([
                        Infolists\Components\TextEntry::make('appointments_count')
                            ->label('Gesamte Termine')
                            ->state(fn ($record) => $record->appointments()->count())
                            ->badge()
                            ->color('success'),
                            
                        Infolists\Components\TextEntry::make('completed_appointments')
                            ->label('Abgeschlossene Termine')
                            ->state(fn ($record) => $record->appointments()->where('status', 'completed')->count())
                            ->badge()
                            ->color('info'),
                            
                        Infolists\Components\TextEntry::make('no_shows')
                            ->label('Nicht erschienen')
                            ->state(fn ($record) => $record->appointments()->where('status', 'no_show')->count())
                            ->badge()
                            ->color('danger'),
                            
                        Infolists\Components\TextEntry::make('total_revenue')
                            ->label('Gesamtumsatz')
                            ->state(fn ($record) => '€ ' . number_format($record->calculateTotalRevenue() ?? 0, 2, ',', '.'))
                            ->badge()
                            ->color('warning'),
                    ])
                    ->columns(4),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'duplicates' => Pages\FindDuplicates::route('/duplicates'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Kunde';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kunden';
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'company.name', 'tags'];
    }
    
    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'E-Mail' => $record->email,
            'Telefon' => $record->phone,
            'Unternehmen' => $record->company?->name,
        ];
    }
    
    protected static function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'E-Mail',
            'phone' => 'Telefon',
            'company.name' => 'Unternehmen',
            'birthdate' => 'Geburtsdatum',
            'tags' => 'Tags',
            'appointments_count' => 'Anzahl Termine',
            'created_at' => 'Registriert am',
        ];
    }
}
