<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class CalcomApiTest extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static ?string $navigationLabel = 'Cal.com API Test';
    protected static ?int $navigationSort = 10;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    
    protected static string $view = 'filament.admin.pages.calcom-api-test';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $company = auth()->user()->company;
        $this->form->fill([
            'api_key' => $company->calcom_api_key ?? config('services.calcom.api_key'),
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('api_key')
                    ->label('Cal.com API Key')
                    ->placeholder('cal_live_...')
                    ->required()
                    ->password()
                    ->revealable()
                    ->helperText('Your Cal.com API key. Get it from: https://app.cal.com/settings/admin/apps'),
                    
                Textarea::make('test_results')
                    ->label('Test Results')
                    ->rows(15)
                    ->disabled()
                    ->default('Click "Test API Connection" to begin...'),
            ])
            ->statePath('data');
    }
    
    public function testConnection(): void
    {
        $data = $this->form->getState();
        $apiKey = $data['api_key'];
        
        $results = "Testing Cal.com API Connection...\n";
        $results .= "=====================================\n\n";
        
        // Test 1: Event Types (v1)
        $results .= "Test 1: Event Types (API v1)\n";
        try {
            $response = Http::get("https://api.cal.com/v1/event-types?apiKey={$apiKey}");
            if ($response->successful()) {
                $data = $response->json();
                $count = count($data['event_types'] ?? $data);
                $results .= "✓ SUCCESS - Found {$count} event types\n";
            } else {
                $results .= "✗ FAILED - Status: {$response->status()}\n";
                $results .= "Response: " . substr($response->body(), 0, 200) . "\n";
            }
        } catch (\Exception $e) {
            $results .= "✗ ERROR - " . $e->getMessage() . "\n";
        }
        
        $results .= "\n";
        
        // Test 2: Users (v1)
        $results .= "Test 2: Users (API v1)\n";
        try {
            $response = Http::get("https://api.cal.com/v1/users?apiKey={$apiKey}");
            if ($response->successful()) {
                $data = $response->json();
                $count = count($data['users'] ?? $data);
                $results .= "✓ SUCCESS - Found {$count} users\n";
            } else {
                $results .= "✗ FAILED - Status: {$response->status()}\n";
                $results .= "Response: " . substr($response->body(), 0, 200) . "\n";
            }
        } catch (\Exception $e) {
            $results .= "✗ ERROR - " . $e->getMessage() . "\n";
        }
        
        $results .= "\n";
        
        // Test 3: Bookings (v1)
        $results .= "Test 3: Bookings (API v1)\n";
        try {
            $response = Http::get("https://api.cal.com/v1/bookings?apiKey={$apiKey}");
            if ($response->successful()) {
                $data = $response->json();
                $bookings = $data['bookings'] ?? $data;
                $count = is_array($bookings) ? count($bookings) : 0;
                $results .= "✓ SUCCESS - Found {$count} bookings\n";
                
                if ($count > 0 && is_array($bookings)) {
                    $results .= "\nFirst booking sample:\n";
                    $first = $bookings[0];
                    $results .= "- ID: " . ($first['id'] ?? 'N/A') . "\n";
                    $results .= "- Title: " . ($first['title'] ?? 'N/A') . "\n";
                    $results .= "- Start: " . ($first['startTime'] ?? 'N/A') . "\n";
                    $results .= "- Status: " . ($first['status'] ?? 'N/A') . "\n";
                }
            } else {
                $results .= "✗ FAILED - Status: {$response->status()}\n";
                $results .= "Response: " . substr($response->body(), 0, 200) . "\n";
            }
        } catch (\Exception $e) {
            $results .= "✗ ERROR - " . $e->getMessage() . "\n";
        }
        
        $results .= "\n";
        
        // Test 4: Me endpoint (v1)
        $results .= "Test 4: Me/Profile (API v1)\n";
        try {
            $response = Http::get("https://api.cal.com/v1/me?apiKey={$apiKey}");
            if ($response->successful()) {
                $data = $response->json();
                $user = $data['user'] ?? $data;
                $results .= "✓ SUCCESS - Authenticated as: " . ($user['email'] ?? 'Unknown') . "\n";
                $results .= "- Name: " . ($user['name'] ?? 'N/A') . "\n";
                $results .= "- Username: " . ($user['username'] ?? 'N/A') . "\n";
            } else {
                $results .= "✗ FAILED - Status: {$response->status()}\n";
                $results .= "Response: " . substr($response->body(), 0, 200) . "\n";
            }
        } catch (\Exception $e) {
            $results .= "✗ ERROR - " . $e->getMessage() . "\n";
        }
        
        $results .= "\n=====================================\n";
        $results .= "API v2 TESTS\n";
        $results .= "=====================================\n\n";
        
        // Test 5: Event Types (v2)
        $results .= "Test 5: Event Types (API v2)\n";
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get("https://api.cal.com/v2/event-types");
            
            if ($response->successful()) {
                $data = $response->json();
                $results .= "✓ SUCCESS - API v2 works\n";
                $results .= "Response: " . json_encode($data) . "\n";
            } else {
                $results .= "✗ FAILED - Status: {$response->status()}\n";
                $results .= "Response: " . substr($response->body(), 0, 200) . "\n";
            }
        } catch (\Exception $e) {
            $results .= "✗ ERROR - " . $e->getMessage() . "\n";
        }
        
        $results .= "\n";
        
        // Test 6: Slots Available (v2)
        $results .= "Test 6: Slots Available (API v2)\n";
        try {
            $tomorrow = now()->addDay()->format('Y-m-d');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get("https://api.cal.com/v2/slots/available", [
                'startTime' => $tomorrow . 'T09:00:00.000Z',
                'endTime' => $tomorrow . 'T17:00:00.000Z',
                'eventTypeId' => 1, // Test with a default ID
                'timeZone' => 'Europe/Berlin',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $results .= "✓ SUCCESS - Slots endpoint works\n";
                $slots = $data['data'] ?? $data;
                if (is_array($slots)) {
                    $results .= "Found " . count($slots) . " available slots\n";
                }
            } else {
                $results .= "✗ FAILED - Status: {$response->status()}\n";
                $results .= "Response: " . substr($response->body(), 0, 200) . "\n";
            }
        } catch (\Exception $e) {
            $results .= "✗ ERROR - " . $e->getMessage() . "\n";
        }
        
        $results .= "\n";
        
        // Test 7: Bookings (v2)
        $results .= "Test 7: Bookings (API v2)\n";
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get("https://api.cal.com/v2/bookings");
            
            if ($response->successful()) {
                $data = $response->json();
                $results .= "✓ SUCCESS - v2 Bookings endpoint works\n";
                $results .= "Response structure: " . json_encode(array_keys($data), JSON_PRETTY_PRINT) . "\n";
            } else {
                $results .= "✗ FAILED - Status: {$response->status()}\n";
                $results .= "Response: " . substr($response->body(), 0, 200) . "\n";
            }
        } catch (\Exception $e) {
            $results .= "✗ ERROR - " . $e->getMessage() . "\n";
        }
        
        $results .= "\n";
        
        // Test 8: Me endpoint (v2)
        $results .= "Test 8: Me/Profile (API v2)\n";
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get("https://api.cal.com/v2/me");
            
            if ($response->successful()) {
                $data = $response->json();
                $results .= "✓ SUCCESS - v2 Authentication works\n";
                $results .= "User data: " . json_encode($data) . "\n";
            } else {
                $results .= "✗ FAILED - Status: {$response->status()}\n";
                $results .= "Response: " . substr($response->body(), 0, 200) . "\n";
            }
        } catch (\Exception $e) {
            $results .= "✗ ERROR - " . $e->getMessage() . "\n";
        }
        
        $results .= "\n=====================================\n";
        $results .= "Test completed at: " . now()->format('Y-m-d H:i:s');
        
        $this->form->fill([
            'api_key' => $apiKey,
            'test_results' => $results,
        ]);
        
        // Save valid API key to company if all tests pass
        if (str_contains($results, '✓ SUCCESS')) {
            $company = auth()->user()->company;
            if ($company && $company->calcom_api_key !== $apiKey) {
                $company->update(['calcom_api_key' => $apiKey]);
                
                Notification::make()
                    ->title('API Key Updated')
                    ->body('The Cal.com API key has been saved to your company settings.')
                    ->success()
                    ->send();
            }
        }
    }
    
    public function clearResults(): void
    {
        $this->form->fill([
            'api_key' => $this->data['api_key'] ?? '',
            'test_results' => 'Click "Test API Connection" to begin...',
        ]);
    }
}