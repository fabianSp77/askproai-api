<?php

namespace App\Filament\Resources\MasterServiceResource\Pages;

use App\Filament\Resources\MasterServiceResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class TestServiceAI extends Page
{
    protected static string $resource = MasterServiceResource::class;

    protected static string $view = 'filament.pages.test-service-ai';

    public ?array $data = [];
    
    public $testScenario = 'booking_standard';
    
    public $chatHistory = [];
    
    public $userMessage = '';

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
        
        $this->form->fill([
            'test_scenario' => 'booking_standard',
            'user_message' => '',
        ]);
        
        $this->chatHistory = [
            [
                'role' => 'assistant',
                'message' => 'Guten Tag! Wie kann ich Ihnen heute helfen?',
                'timestamp' => now(),
            ],
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return "AI-Test: {$this->record->name}";
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Test-Szenario')
                    ->schema([
                        Select::make('test_scenario')
                            ->label('Wählen Sie ein Test-Szenario')
                            ->options([
                                'booking_standard' => 'Standard Terminbuchung',
                                'booking_urgent' => 'Dringende Terminanfrage',
                                'price_inquiry' => 'Preisanfrage',
                                'service_details' => 'Service-Details erfragen',
                                'reschedule' => 'Termin verschieben',
                                'cancellation' => 'Termin absagen',
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn ($state) => $this->testScenario = $state),
                            
                        Textarea::make('user_message')
                            ->label('Ihre Nachricht')
                            ->placeholder('Geben Sie Ihre Testnachricht ein...')
                            ->rows(3),
                    ]),
            ])
            ->statePath('data');
    }

    public function sendMessage(): void
    {
        $message = $this->data['user_message'] ?? '';
        
        if (empty($message)) {
            return;
        }
        
        // Add user message to chat
        $this->chatHistory[] = [
            'role' => 'user',
            'message' => $message,
            'timestamp' => now(),
        ];
        
        // Generate AI response based on scenario
        $response = $this->generateAIResponse($message, $this->testScenario);
        
        // Add AI response to chat
        $this->chatHistory[] = [
            'role' => 'assistant',
            'message' => $response,
            'timestamp' => now(),
        ];
        
        // Clear input
        $this->data['user_message'] = '';
        
        Notification::make()
            ->title('Nachricht gesendet')
            ->success()
            ->duration(1000)
            ->send();
    }

    protected function generateAIResponse(string $message, string $scenario): string
    {
        return match ($scenario) {
            'booking_standard' => "Gerne helfe ich Ihnen bei der Terminbuchung für {$this->record->name}. Wann hätten Sie denn Zeit? Wir haben morgen um 10:00 Uhr oder 14:00 Uhr noch Termine frei.",
            'booking_urgent' => "Ich verstehe, dass es dringend ist. Lassen Sie mich kurz die heutigen Verfügbarkeiten prüfen... Wir hätten heute noch um 16:30 Uhr einen Termin frei. Passt Ihnen das?",
            'price_inquiry' => "Der {$this->record->name} kostet bei uns {$this->record->base_price}€. Die Behandlung dauert etwa {$this->record->duration} Minuten. Möchten Sie direkt einen Termin vereinbaren?",
            default => "Entschuldigung, ich habe Ihre Anfrage nicht ganz verstanden. Könnten Sie sie bitte wiederholen?",
        };
    }
}