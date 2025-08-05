<?php

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use App\Models\Company;
use App\Models\ApiCredential;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Http;

class ManageApiCredentials extends Page
{
    protected static string $resource = CompanyResource::class;
    protected static string $view = 'filament.admin.resources.company-resource.pages.manage-api-credentials';
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $title = 'API Zugangsdaten';

    public ?Company $company = null;
    public array $calcomData = [];
    public array $retellData = [];

    public function mount($record): void
    {
        $this->company = Company::findOrFail($record);
        
        // Cal.com Daten laden
        $this->calcomData = [
            'api_key' => $this->company->calcom_api_key ?? '',
            'user_id' => $this->company->calcom_user_id ?? '',
        ];
        
        // Retell Daten laden
        $this->retellData = [
            'api_key' => $this->company->retell_api_key ?? '',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Cal.com Integration')
                    ->description('Verwalten Sie die Cal.com API-Zugangsdaten')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('calcomData.api_key')
                                ->label('API Key')
                                ->password()
                                ->revealable()
                                ->required(),
                            TextInput::make('calcomData.user_id')
                                ->label('User ID')
                                ->required(),
                        ]),
                    ]),
                
                Section::make('Retell.ai Integration')
                    ->description('Verwalten Sie die Retell.ai API-Zugangsdaten')
                    ->schema([
                        TextInput::make('retellData.api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Speichern')
                ->action('save')
                ->icon('heroicon-o-check'),
            
            Action::make('testCalcom')
                ->label('Cal.com testen')
                ->action('testCalcomConnection')
                ->icon('heroicon-o-beaker')
                ->color('info'),
                
            Action::make('testRetell')
                ->label('Retell testen')
                ->action('testRetellConnection')
                ->icon('heroicon-o-beaker')
                ->color('info'),
        ];
    }

        public function save(): void
    {
        // Update company fields directly
        $this->company->calcom_api_key = $this->calcomData['api_key'] ?: null;
        $this->company->calcom_user_id = $this->calcomData['user_id'] ?: null;
        $this->company->retell_api_key = $this->retellData['api_key'] ?: null;
        
        $this->company->save();
        
        Notification::make()
            ->title('Zugangsdaten gespeichert')
            ->success()
            ->send();
    }

    // saveCredential method removed - using direct field updates

    public function testCalcomConnection(): void
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->calcomData['api_key'],
            ])->get('https://api.cal.com/v1/users/' . $this->calcomData['user_id']);

            if ($response->successful()) {
                Notification::make()
                    ->title('Cal.com Verbindung erfolgreich')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Cal.com Verbindung fehlgeschlagen')
                    ->body('Status: ' . $response->status())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Testen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testRetellConnection(): void
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->retellData['api_key'],
            ])->get('https://api.retellai.com/v1/agents');

            if ($response->successful()) {
                Notification::make()
                    ->title('Retell Verbindung erfolgreich')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Retell Verbindung fehlgeschlagen')
                    ->body('Status: ' . $response->status())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Testen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
