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
        $this->company = Company::with('apiCredentials')->findOrFail($record);
        
        // Cal.com Daten laden
        $this->calcomData = [
            'api_key' => $this->company->apiCredentials()->where('service', 'calcom')->where('key_type', 'api_key')->first()?->value ?? '',
            'user_id' => $this->company->apiCredentials()->where('service', 'calcom')->where('key_type', 'user_id')->first()?->value ?? '',
        ];
        
        // Retell Daten laden
        $this->retellData = [
            'api_key' => $this->company->apiCredentials()->where('service', 'retell')->where('key_type', 'api_key')->first()?->value ?? '',
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
        // Cal.com Credentials speichern
        $this->saveCredential('calcom', 'api_key', $this->calcomData['api_key']);
        $this->saveCredential('calcom', 'user_id', $this->calcomData['user_id']);
        
        // Retell Credentials speichern
        $this->saveCredential('retell', 'api_key', $this->retellData['api_key']);
        
        Notification::make()
            ->title('Zugangsdaten gespeichert')
            ->success()
            ->send();
    }

    private function saveCredential(string $service, string $keyType, ?string $value): void
    {
        if (!$value) {
            $this->company->apiCredentials()
                ->where('service', $service)
                ->where('key_type', $keyType)
                ->delete();
            return;
        }

        $this->company->apiCredentials()->updateOrCreate(
            [
                'service' => $service,
                'key_type' => $keyType,
            ],
            [
                'value' => $value,
                'is_inherited' => false,
            ]
        );
    }

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
