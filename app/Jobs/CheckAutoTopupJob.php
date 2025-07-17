<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\AutoTopupService;
use App\Services\SpendingLimitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAutoTopupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Company $company;
    protected ?float $callCharge;

    /**
     * Create a new job instance.
     */
    public function __construct(Company $company, ?float $callCharge = null)
    {
        $this->company = $company;
        $this->callCharge = $callCharge;
    }

    /**
     * Execute the job.
     */
    public function handle(AutoTopupService $autoTopupService, SpendingLimitService $spendingLimitService)
    {
        try {
            // First, record spending if there was a charge
            if ($this->callCharge > 0) {
                $spendingResult = $spendingLimitService->recordSpending($this->company, $this->callCharge);
                
                // Log if limits were exceeded
                if (!empty($spendingResult['violations'])) {
                    Log::warning('Spending limits exceeded after call', [
                        'company_id' => $this->company->id,
                        'charge' => $this->callCharge,
                        'violations' => $spendingResult['violations'],
                    ]);
                }
            }
            
            // Then check for auto-topup
            $result = $autoTopupService->checkAndExecuteAutoTopup($this->company);
            
            if ($result && $result['success']) {
                Log::info('Auto-topup executed after call', [
                    'company_id' => $this->company->id,
                    'amount' => $result['amount'],
                    'new_balance' => $result['new_balance'],
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in CheckAutoTopupJob', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['auto-topup', 'company:' . $this->company->id];
    }
}