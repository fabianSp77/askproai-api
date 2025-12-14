<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

/**
 * Retell V128 Settings Page
 *
 * Minimal admin interface for V128 conversation flow settings:
 * - Time-Shift Communication (Vormittag→Abend)
 * - Name-Skip for known customers
 * - Full booking confirmation
 * - Silence handling
 *
 * @version 1.1.0
 * @since 2025-12-14
 * @security IDOR protection added, XSS sanitization, input validation
 */
class RetellV128Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Retell V128';
    protected static ?string $title = 'Retell AI Gesprächsoptimierungen (V128)';
    protected static string $view = 'filament.pages.retell-v128-settings';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?int $navigationSort = 50;

    public ?int $selectedCompanyId = null;
    public ?array $data = [];

    /**
     * Authorization check - company_admin and super_admin only
     */
    public static function canAccess(): bool
    {
        $user = Auth::guard('admin')->user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'company_admin']);
    }

    /**
     * Mount the page and load settings
     */
    public function mount(): void
    {
        $user = Auth::guard('admin')->user();

        if ($user && $user->hasRole('super_admin')) {
            $this->selectedCompanyId = Company::first()?->id;
        } elseif ($user) {
            $this->selectedCompanyId = $user->company_id;
        }

        $this->loadSettings();
    }

    /**
     * 🔒 SECURITY: Verify user has permission to access this company
     */
    protected function authorizeCompanyAccess(?int $companyId): bool
    {
        if (!$companyId) {
            return false;
        }

        $user = Auth::guard('admin')->user();
        if (!$user) {
            return false;
        }

        // Super admin can access any company
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Company admin can ONLY access their own company
        if ($user->hasRole('company_admin')) {
            if ($user->company_id !== $companyId) {
                Log::warning('🚨 SECURITY: IDOR attempt detected in V128 settings', [
                    'user_id' => $user->id,
                    'user_company_id' => $user->company_id,
                    'attempted_company_id' => $companyId,
                    'ip' => request()->ip(),
                ]);
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Load V128 settings from company
     * 🔒 SECURITY: Added authorization check
     */
    protected function loadSettings(): void
    {
        if (!$this->selectedCompanyId) {
            $this->data = [];
            return;
        }

        // 🔒 SECURITY: Verify authorization before loading
        if (!$this->authorizeCompanyAccess($this->selectedCompanyId)) {
            $this->data = [];
            Notification::make()
                ->title('Nicht autorisiert')
                ->body('Sie haben keine Berechtigung, diese Firma zu bearbeiten')
                ->danger()
                ->send();

            // Reset to user's own company
            $user = Auth::guard('admin')->user();
            $this->selectedCompanyId = $user?->company_id;
            return;
        }

        $company = Company::find($this->selectedCompanyId);
        if (!$company) {
            $this->data = [];
            return;
        }

        $this->data = $company->getV128ConfigWithDefaults();
        $this->form->fill($this->data);
    }

    /**
     * Handle company selection change (for super_admin)
     */
    public function updatedSelectedCompanyId(): void
    {
        $this->loadSettings();
    }

    /**
     * Define the form schema
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Company selector for super_admin
                $this->getCompanySelectorSection(),

                // Main settings sections
                Section::make('Zeit-Shift Kommunikation')
                    ->description('Wenn Kunde "Vormittag" wollte aber nur Abend-Termine verfügbar sind')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Toggle::make('time_shift_enabled')
                            ->label('Zeit-Shift Hinweise aktivieren')
                            ->helperText('Agent erklärt dem Kunden, wenn die gewünschte Tageszeit ausgebucht ist')
                            ->default(true),

                        Textarea::make('time_shift_message')
                            ->label('Nachrichtenvorlage')
                            ->helperText('Platzhalter: {label} = Tageszeit (z.B. "Vormittags"), {alternatives} = verfügbare Zeiten')
                            ->placeholder('{label} ist leider schon ausgebucht. Soll ich am nächsten Tag {label} schauen, oder würde heute Abend auch passen? Heute hätte ich noch {alternatives} frei.')
                            ->rows(3)
                            ->maxLength(500)
                            // 🔒 SECURITY: XSS protection - strip HTML tags
                            ->dehydrateStateUsing(fn ($state) => $state ? strip_tags($state) : null)
                            ->rules([
                                'nullable',
                                'string',
                                'max:500',
                                function ($attribute, $value, $fail) {
                                    // Block any potential XSS patterns
                                    if ($value && preg_match('/<|>|javascript:|on\w+=/i', $value)) {
                                        $fail('Der Text enthält unzulässige Zeichen.');
                                    }
                                },
                            ])
                            ->visible(fn ($get) => $get('time_shift_enabled')),
                    ])
                    ->collapsible(),

                Section::make('Bestandskunden-Erkennung')
                    ->description('Optimierungen für wiederkehrende Kunden')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Toggle::make('name_skip_enabled')
                            ->label('Name-Abfrage überspringen')
                            ->helperText('Agent fragt nicht nach dem Namen, wenn der Kunde bereits bekannt ist')
                            ->default(true),
                    ])
                    ->collapsible(),

                Section::make('Buchungsbestätigung')
                    ->description('Details in der Terminbestätigung')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Toggle::make('full_confirmation_enabled')
                            ->label('Vollständige Bestätigung')
                            ->helperText('Agent nennt Service, Dauer, Datum, Uhrzeit und Name bei der Bestätigung')
                            ->default(true),
                    ])
                    ->collapsible(),

                Section::make('Stille-Handling')
                    ->description('Verhalten bei längerer Stille im Gespräch')
                    ->icon('heroicon-o-speaker-x-mark')
                    ->schema([
                        Toggle::make('silence_handling_enabled')
                            ->label('Auto-Hangup bei Stille')
                            ->helperText('Gespräch wird nach wiederholter Stille höflich beendet')
                            ->default(true),

                        TextInput::make('silence_timeout_seconds')
                            ->label('Stille-Timeout (Sekunden)')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(60)
                            ->default(20)
                            ->suffix('Sekunden')
                            // 🔒 SECURITY: Server-side validation
                            ->rules(['nullable', 'integer', 'min:10', 'max:60'])
                            ->visible(fn ($get) => $get('silence_handling_enabled')),

                        TextInput::make('max_silence_repeats')
                            ->label('Max. Wiederholungen')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(2)
                            ->helperText('Nach so vielen Stille-Nachfragen wird aufgelegt')
                            // 🔒 SECURITY: Server-side validation
                            ->rules(['nullable', 'integer', 'min:1', 'max:5'])
                            ->visible(fn ($get) => $get('silence_handling_enabled')),
                    ])
                    ->collapsible(),

                // Info section
                Section::make('Version Info')
                    ->schema([
                        Placeholder::make('version_info')
                            ->content(new HtmlString('
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <strong>V128 Optimierungen</strong> (2025-12-14)<br>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Name-Skip für Bestandskunden</li>
                                        <li>Intelligente Zeit-Shift Kommunikation</li>
                                        <li>Vollständige Buchungsbestätigung</li>
                                        <li>Stille-Handling mit Auto-Hangup</li>
                                        <li>Verbesserte Filler-Phrases</li>
                                    </ul>
                                </div>
                            ')),
                    ])
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    /**
     * Get company selector section (only for super_admin)
     */
    protected function getCompanySelectorSection(): Section
    {
        $user = Auth::guard('admin')->user();
        $isSuperAdmin = $user && $user->hasRole('super_admin');

        return Section::make('Firma auswählen')
            ->schema([
                Select::make('selectedCompanyId')
                    ->label('Firma')
                    ->options(Company::pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->selectedCompanyId = (int) $state),
            ])
            ->visible($isSuperAdmin)
            ->collapsed(false);
    }

    /**
     * Save settings to company
     * 🔒 SECURITY: Added authorization check and exception handling
     */
    public function save(): void
    {
        try {
            if (!$this->selectedCompanyId) {
                Notification::make()
                    ->title('Fehler')
                    ->body('Keine Firma ausgewählt')
                    ->danger()
                    ->send();
                return;
            }

            // 🔒 SECURITY: CRITICAL - Verify user has permission to modify this company
            if (!$this->authorizeCompanyAccess($this->selectedCompanyId)) {
                Notification::make()
                    ->title('Nicht autorisiert')
                    ->body('Sie haben keine Berechtigung, diese Firma zu bearbeiten')
                    ->danger()
                    ->send();

                abort(403, 'Unauthorized access attempt');
            }

            $company = Company::find($this->selectedCompanyId);
            if (!$company) {
                Notification::make()
                    ->title('Fehler')
                    ->body('Firma nicht gefunden')
                    ->danger()
                    ->send();
                return;
            }

            // Get form data with validation
            $formData = $this->form->getState();

            // Remove the company selector from saved data (if present)
            unset($formData['selectedCompanyId']);

            // 🔒 SECURITY: Sanitize time_shift_message (double protection)
            if (isset($formData['time_shift_message'])) {
                $formData['time_shift_message'] = strip_tags($formData['time_shift_message']);
            }

            // Save to company
            $company->v128_config = $formData;
            $company->save();

            Notification::make()
                ->title('Gespeichert')
                ->body('V128 Einstellungen wurden aktualisiert')
                ->success()
                ->send();

            // Log the change (without exposing full config values)
            activity()
                ->performedOn($company)
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties([
                    'updated_fields' => array_keys($formData),
                    'timestamp' => now()->toIso8601String(),
                ])
                ->log('V128 settings updated');

        } catch (\Exception $e) {
            Log::error('V128 settings save failed', [
                'company_id' => $this->selectedCompanyId,
                'error' => $e->getMessage(),
                'user_id' => Auth::guard('admin')->id(),
            ]);

            Notification::make()
                ->title('Speichern fehlgeschlagen')
                ->body('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.')
                ->danger()
                ->send();
        }
    }

    /**
     * Reset to defaults
     * 🔒 SECURITY: Added authorization check
     */
    public function resetToDefaults(): void
    {
        try {
            if (!$this->selectedCompanyId) {
                return;
            }

            // 🔒 SECURITY: Verify authorization
            if (!$this->authorizeCompanyAccess($this->selectedCompanyId)) {
                Notification::make()
                    ->title('Nicht autorisiert')
                    ->body('Sie haben keine Berechtigung, diese Firma zu bearbeiten')
                    ->danger()
                    ->send();
                return;
            }

            $company = Company::find($this->selectedCompanyId);
            if (!$company) {
                return;
            }

            // Clear custom config (defaults will be used)
            $company->v128_config = null;
            $company->save();

            // Reload settings
            $this->loadSettings();

            // Force Livewire re-render
            $this->dispatch('$refresh');

            Notification::make()
                ->title('Zurückgesetzt')
                ->body('Einstellungen wurden auf Standard zurückgesetzt')
                ->info()
                ->send();

        } catch (\Exception $e) {
            Log::error('V128 settings reset failed', [
                'company_id' => $this->selectedCompanyId,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Zurücksetzen fehlgeschlagen')
                ->body('Ein Fehler ist aufgetreten.')
                ->danger()
                ->send();
        }
    }

    /**
     * Get the view data
     */
    protected function getViewData(): array
    {
        return [
            'company' => $this->selectedCompanyId ? Company::find($this->selectedCompanyId) : null,
        ];
    }
}
