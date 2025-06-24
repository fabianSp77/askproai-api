<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class BasicCompanyConfig extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Basic Company Config';
    protected static ?string $title = 'Basic Company Configuration';
    protected static ?string $navigationGroup = 'Einrichtung & Konfiguration';
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.admin.pages.basic-company-config';
    
    // Form data
    public ?array $data = [];
    
    // State
    public ?Company $company = null;
    
    public static function canAccess(): bool
    {
        return auth()->check();
    }
    
    public function mount(): void
    {
        $user = auth()->user();
        
        // Get the company
        if ($user->hasRole('super_admin')) {
            // For super admin, let them choose
            $this->company = Company::first();
        } else {
            // For regular users, use their company
            $this->company = Company::find($user->company_id);
        }
        
        if ($this->company) {
            $this->form->fill([
                'name' => $this->company->name,
                'slug' => $this->company->slug,
                'is_active' => $this->company->is_active,
                'calcom_api_key' => $this->company->calcom_api_key,
                'calcom_team_slug' => $this->company->calcom_team_slug,
                'retell_api_key' => $this->company->retell_api_key,
                'retell_agent_id' => $this->company->retell_agent_id,
            ]);
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('slug')
                            ->label('Company Slug')
                            ->disabled(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->disabled(),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Cal.com Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('calcom_api_key')
                            ->label('Cal.com API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('cal_live_xxxxxxxxxxxxxxxxx')
                            ->helperText('Get this from Cal.com > Settings > Developer > API Keys'),
                            
                        Forms\Components\TextInput::make('calcom_team_slug')
                            ->label('Cal.com Team Slug (optional)')
                            ->placeholder('my-team')
                            ->helperText('Only needed if using Cal.com teams'),
                    ]),
                    
                Forms\Components\Section::make('Retell.ai Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('retell_api_key')
                            ->label('Retell.ai API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('key_xxxxxxxxxxxxxxxxx')
                            ->helperText('Get this from Retell.ai > Settings > API Keys'),
                            
                        Forms\Components\TextInput::make('retell_agent_id')
                            ->label('Default Retell Agent ID')
                            ->placeholder('agent_xxxxxxxxxxxxxxxxx')
                            ->helperText('The ID of your configured agent in Retell.ai'),
                    ]),
            ])
            ->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Configuration')
                ->action('save'),
        ];
    }
    
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            $this->company->update([
                'calcom_api_key' => $data['calcom_api_key'],
                'calcom_team_slug' => $data['calcom_team_slug'],
                'retell_api_key' => $data['retell_api_key'],
                'retell_agent_id' => $data['retell_agent_id'],
            ]);
            
            Notification::make()
                ->title('Configuration saved')
                ->body('Your company configuration has been updated successfully.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Error saving company configuration: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error saving configuration')
                ->body('There was an error saving your configuration. Please try again.')
                ->danger()
                ->send();
        }
    }
    
    public function getSubheading(): ?string
    {
        return $this->company ? 'Configuring: ' . $this->company->name : 'No company selected';
    }
}