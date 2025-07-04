<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Call;
use App\Services\RetellV2Service;
use App\Jobs\ProcessRetellCallEndedJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FetchRetellCallsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Company $company;
    
    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;
    
    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->queue = 'high';
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('FetchRetellCallsJob started', ['company_id' => $this->company->id]);
        
        try {
            // Get Retell API key
            $apiKey = $this->company->retell_api_key ?? config('services.retell.api_key');
            
            if (!$apiKey) {
                throw new \Exception('No Retell API key found for company');
            }
            
            // Initialize Retell service with API key
            $retellService = new RetellV2Service($apiKey);
            
            // Fetch calls from the last 7 days
            $fromDate = Carbon::now()->subDays(7)->startOfDay();
            $toDate = Carbon::now()->endOfDay();
            
            // Get calls from Retell API - Direct API call like in the working command
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://api.retellai.com/v2/list-calls', [
                'limit' => 100,
                'sort_order' => 'descending'
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('API Error: ' . $response->body());
            }
            
            $calls = $response->json();
            
            $imported = 0;
            $skipped = 0;
            
            // Process calls using the same approach as the working command
            if (is_array($calls) && count($calls) > 0) {
                foreach ($calls as $callData) {
                    // Check if call already exists
                    $existingCall = Call::where('call_id', $callData['call_id'])->first();
                    
                    if ($existingCall) {
                        $skipped++;
                        continue;
                    }
                    
                    // Use ProcessRetellCallEndedJob for consistent processing
                    $job = new ProcessRetellCallEndedJob([
                        'event' => 'call_ended',
                        'call' => $callData
                    ]);
                    
                    // Set company ID using the CompanyAwareJob trait
                    if ($this->company) {
                        $job->setCompanyId($this->company->id);
                    }
                    
                    dispatch($job);
                    $imported++;
                    
                    Log::info('Call dispatched for processing', [
                        'call_id' => $callData['call_id'] ?? 'unknown',
                        'company_id' => $this->company->id
                    ]);
                }
            }
            
            Log::info('FetchRetellCallsJob completed', [
                'company_id' => $this->company->id,
                'imported' => $imported,
                'skipped' => $skipped
            ]);
            
            // Send notification to user
            if (auth()->user()) {
                \Filament\Notifications\Notification::make()
                    ->title('Anrufe erfolgreich abgerufen')
                    ->body("Es wurden {$imported} neue Anrufe importiert. {$skipped} Anrufe existierten bereits.")
                    ->success()
                    ->sendToDatabase(auth()->user());
            }
            
        } catch (\Exception $e) {
            Log::error('FetchRetellCallsJob failed', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}