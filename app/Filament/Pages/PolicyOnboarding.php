<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\PolicyConfiguration;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;

class PolicyOnboarding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Setup-Assistent (Anfänger)';

    protected static ?string $title = 'Richtlinien-Assistent';

    protected static ?string $navigationGroup = '⚙️ Termin-Richtlinien';

    protected static ?int $navigationSort = 100;

    // Hide from navigation by default - can be accessed via direct URL
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.policy-onboarding';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Willkommen')
                        ->description('Willkommen im Policy Management System')
                        ->schema([
                            Placeholder::make('intro')
                                ->content(new \Illuminate\Support\HtmlString('
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold">Willkommen zum Policy Setup Wizard!</h3>
                                        <p>Dieser Assistent hilft Ihnen, Ihre erste Stornierungsrichtlinie in 3 einfachen Schritten zu erstellen.</p>
                                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                            <h4 class="font-medium mb-2">Was Sie lernen werden:</h4>
                                            <ul class="list-disc list-inside space-y-1 text-sm">
                                                <li>Wie Policy-Hierarchien funktionieren (Company → Branch → Service → Staff)</li>
                                                <li>Wie Sie Stornierungsfristen und Gebühren konfigurieren</li>
                                                <li>Wie Sie monatliche Kontingente festlegen</li>
                                            </ul>
                                        </div>
                                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                                            <p class="text-sm">
                                                <strong>Tipp:</strong> Beginnen Sie mit einer Company-weiten Policy als Baseline,
                                                und verfeinern Sie diese später für spezifische Branches oder Services.
                                            </p>
                                        </div>
                                    </div>
                                ')),
                        ]),

                    Step::make('Entität auswählen')
                        ->description('Wählen Sie aus, wo diese Policy gelten soll')
                        ->schema([
                            Section::make('Policy-Hierarchie verstehen')
                                ->description('Policies werden hierarchisch vererbt: Company → Branch → Service → Staff')
                                ->schema([
                                    Placeholder::make('hierarchy_info')
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <div class="bg-gray-50 dark:bg-gray-900/20 p-4 rounded-lg space-y-2">
                                                <p class="text-sm"><strong>Company-Policy:</strong> Gilt für alle Branches, Services und Staff</p>
                                                <p class="text-sm"><strong>Branch-Policy:</strong> Überschreibt Company-Policy für diese Filiale</p>
                                                <p class="text-sm"><strong>Service-Policy:</strong> Überschreibt Company/Branch für diesen Service</p>
                                                <p class="text-sm"><strong>Staff-Policy:</strong> Höchste Priorität, gilt nur für diesen Mitarbeiter</p>
                                            </div>
                                        ')),
                                ]),

                            Select::make('entity_type')
                                ->label('Entitätstyp')
                                ->options([
                                    'company' => 'Company (Alle Branches & Services)',
                                    'branch' => 'Branch (Spezifische Filiale)',
                                    'service' => 'Service (Spezifischer Service)',
                                    'staff' => 'Staff (Spezifischer Mitarbeiter)',
                                ])
                                ->default('company')
                                ->required()
                                ->reactive()
                                ->helperText('Empfehlung: Starten Sie mit "Company" für eine globale Baseline-Policy'),

                            Select::make('entity_id')
                                ->label('Entität auswählen')
                                ->options(function (callable $get) {
                                    $type = $get('entity_type');

                                    return match($type) {
                                        'company' => Company::where('is_active', true)->pluck('name', 'id'),
                                        'branch' => Branch::where('is_active', true)->pluck('name', 'id'),
                                        'service' => Service::where('is_active', true)->pluck('name', 'id'),
                                        'staff' => Staff::where('is_active', true)->pluck('name', 'id'),
                                        default => [],
                                    };
                                })
                                ->required()
                                ->reactive()
                                ->hidden(fn (callable $get) => !$get('entity_type')),

                            Checkbox::make('is_override')
                                ->label('Override aktivieren')
                                ->helperText('Aktivieren Sie diese Option, um eine übergeordnete Policy zu überschreiben')
                                ->default(false)
                                ->hidden(fn (callable $get) => $get('entity_type') === 'company'),
                        ]),

                    Step::make('Regeln konfigurieren')
                        ->description('Legen Sie Ihre Stornierungsregeln fest')
                        ->schema([
                            Select::make('policy_type')
                                ->label('Policy-Typ')
                                ->options([
                                    'cancellation' => 'Stornierung (Termin absagen)',
                                    'reschedule' => 'Umbuchung (Termin verschieben)',
                                ])
                                ->default('cancellation')
                                ->required()
                                ->helperText('Stornierung = Termin komplett absagen, Umbuchung = Termin auf neues Datum verschieben'),

                            Section::make('Vorlaufzeit & Gebühren')
                                ->schema([
                                    TextInput::make('hours_before')
                                        ->label('Vorlauf (Stunden)')
                                        ->numeric()
                                        ->default(24)
                                        ->required()
                                        ->minValue(0)
                                        ->helperText('Wie viele Stunden im Voraus muss der Kunde stornieren/umbuchen?'),

                                    Select::make('fee_type')
                                        ->label('Gebührentyp')
                                        ->options([
                                            'none' => 'Keine Gebühr',
                                            'percentage' => 'Prozentual',
                                            'fixed' => 'Festbetrag',
                                        ])
                                        ->default('none')
                                        ->required()
                                        ->reactive(),

                                    TextInput::make('fee_amount')
                                        ->label(fn (callable $get) => $get('fee_type') === 'percentage' ? 'Gebühr (%)' : 'Gebühr (€)')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->maxValue(fn (callable $get) => $get('fee_type') === 'percentage' ? 100 : null)
                                        ->hidden(fn (callable $get) => $get('fee_type') === 'none')
                                        ->helperText(fn (callable $get) =>
                                            $get('fee_type') === 'percentage'
                                                ? 'Prozentsatz des Terminpreises (0-100%)'
                                                : 'Fester Betrag in Euro'
                                        ),
                                ]),

                            Section::make('Monatliches Kontingent (Optional)')
                                ->description('Begrenzen Sie die Anzahl der erlaubten Stornierungen/Umbuchungen pro Monat')
                                ->schema([
                                    Checkbox::make('enable_quota')
                                        ->label('Monatliches Limit aktivieren')
                                        ->default(false)
                                        ->reactive(),

                                    TextInput::make('max_per_month')
                                        ->label('Maximale Anzahl pro Monat')
                                        ->numeric()
                                        ->default(3)
                                        ->minValue(1)
                                        ->hidden(fn (callable $get) => !$get('enable_quota'))
                                        ->helperText('Beispiel: 3 = Kunde darf max. 3x pro Monat stornieren/umbuchen'),
                                ]),
                        ]),

                    Step::make('Abschluss')
                        ->description('Überprüfen und aktivieren')
                        ->schema([
                            Placeholder::make('review')
                                ->content(function (callable $get) {
                                    $entityType = $get('entity_type');
                                    $policyType = $get('policy_type');
                                    $hoursBefore = $get('hours_before');
                                    $feeType = $get('fee_type');
                                    $feeAmount = $get('fee_amount');
                                    $enableQuota = $get('enable_quota');
                                    $maxPerMonth = $get('max_per_month');

                                    $entityLabel = match($entityType) {
                                        'company' => 'Company',
                                        'branch' => 'Branch',
                                        'service' => 'Service',
                                        'staff' => 'Staff',
                                        default => 'Unbekannt',
                                    };

                                    $policyLabel = $policyType === 'cancellation' ? 'Stornierung' : 'Umbuchung';

                                    $feeText = match($feeType) {
                                        'none' => 'Keine Gebühr',
                                        'percentage' => "$feeAmount% des Terminpreises",
                                        'fixed' => "€$feeAmount Festbetrag",
                                        default => 'Keine',
                                    };

                                    $quotaText = $enableQuota
                                        ? "Ja, max. $maxPerMonth pro Monat"
                                        : 'Nein, unbegrenzt';

                                    return new \Illuminate\Support\HtmlString("
                                        <div class='space-y-4'>
                                            <h3 class='text-lg font-semibold'>Ihre Policy ist bereit!</h3>
                                            <div class='bg-green-50 dark:bg-green-900/20 p-4 rounded-lg space-y-2'>
                                                <p class='text-sm'><strong>Gilt für:</strong> $entityLabel</p>
                                                <p class='text-sm'><strong>Typ:</strong> $policyLabel</p>
                                                <p class='text-sm'><strong>Vorlaufzeit:</strong> $hoursBefore Stunden</p>
                                                <p class='text-sm'><strong>Gebühr:</strong> $feeText</p>
                                                <p class='text-sm'><strong>Monatliches Limit:</strong> $quotaText</p>
                                            </div>
                                            <p class='text-sm text-gray-600 dark:text-gray-400'>
                                                Klicken Sie auf \"Abschließen\", um diese Policy zu speichern und zu aktivieren.
                                            </p>
                                        </div>
                                    ");
                                }),
                        ]),
                ])
                ->submitAction(new \Illuminate\Support\HtmlString('
                    <button type="submit" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-action fi-ac-btn-action">
                        <span>Policy erstellen & aktivieren</span>
                    </button>
                ')),
            ])
            ->statePath('data');
    }

    public function create()
    {
        $data = $this->form->getState();

        // Build config JSON
        $config = [
            'hours_before' => (int)$data['hours_before'],
        ];

        if ($data['fee_type'] !== 'none') {
            $config['fee_type'] = $data['fee_type'];
            $config['fee_amount'] = (float)$data['fee_amount'];
        }

        if ($data['enable_quota'] ?? false) {
            if ($data['policy_type'] === 'cancellation') {
                $config['max_cancellations_per_month'] = (int)$data['max_per_month'];
            } else {
                $config['max_reschedules_per_month'] = (int)$data['max_per_month'];
            }
        }

        // Determine configurable type and ID
        $configurableType = match($data['entity_type']) {
            'company' => Company::class,
            'branch' => Branch::class,
            'service' => Service::class,
            'staff' => Staff::class,
        };

        // Get user's company for multi-tenant safety
        $userCompanyId = Auth::user()->company_id;

        // Create policy
        try {
            PolicyConfiguration::create([
                'company_id' => $userCompanyId,
                'configurable_type' => $configurableType,
                'configurable_id' => $data['entity_id'],
                'policy_type' => $data['policy_type'],
                'config' => $config,
                'is_override' => $data['is_override'] ?? false,
                'is_active' => true,
            ]);

            Notification::make()
                ->title('Policy erfolgreich erstellt!')
                ->success()
                ->body('Ihre neue Policy wurde aktiviert und ist nun wirksam.')
                ->send();

            // Redirect to policy list
            return redirect()->route('filament.admin.resources.policy-configurations.index');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Erstellen der Policy')
                ->danger()
                ->body('Es ist ein Fehler aufgetreten: ' . $e->getMessage())
                ->send();
        }
    }
}
