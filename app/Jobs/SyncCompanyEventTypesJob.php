<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Company;
use App\Models\CalcomEventType;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncCompanyEventTypesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Company $company
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting Event Type sync for company', [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name
        ]);
        
        try {
            // Initialize Cal.com service
            $calcomService = new CalcomV2Service(
                $this->company->calcom_api_key,
                $this->company->calcom_team_slug
            );
            
            // Fetch event types from Cal.com
            $eventTypesData = $calcomService->getEventTypes();
            
            if (!$eventTypesData['success']) {
                throw new \Exception($eventTypesData['error'] ?? 'Failed to fetch event types');
            }
            
            $eventTypes = $eventTypesData['data'] ?? [];
            $syncedCount = 0;
            $errors = [];
            
            foreach ($eventTypes as $eventTypeData) {
                try {
                    $this->syncEventType($eventTypeData);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'event_type_id' => $eventTypeData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Update sync status - use withoutGlobalScopes to avoid tenant scope issues
            CalcomEventType::withoutGlobalScopes()
                ->where('company_id', $this->company->id)
                ->update([
                    'last_synced_at' => now(),
                    'sync_status' => 'synced'
                ]);
            
            // Mark deleted event types
            $calcomIds = collect($eventTypes)->pluck('id')->filter();
            CalcomEventType::withoutGlobalScopes()
                ->where('company_id', $this->company->id)
                ->whereNotIn('calcom_numeric_event_type_id', $calcomIds)
                ->update([
                    'sync_status' => 'deleted',
                    'is_active' => false
                ]);
            
            Log::info('Event Type sync completed', [
                'company_id' => $this->company->id,
                'synced_count' => $syncedCount,
                'errors_count' => count($errors),
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            Log::error('Event Type sync failed', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }
    
    protected function syncEventType(array $data): void
    {
        $eventType = CalcomEventType::withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $this->company->id,
                'calcom_numeric_event_type_id' => $data['id']
            ],
            [
                'name' => $data['title'] ?? $data['slug'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? '',
                'duration_minutes' => $data['length'] ?? 30,
                'price' => $data['price'] ?? 0,
                'is_active' => $data['hidden'] !== true,
                'is_team_event' => $data['schedulingType'] === 'COLLECTIVE' || $data['schedulingType'] === 'ROUND_ROBIN',
                'requires_confirmation' => $data['requiresConfirmation'] ?? false,
                'minimum_booking_notice' => $data['minimumBookingNotice'] ?? 0,
                'booking_future_limit' => $data['periodDays'] ?? null,
                'time_slot_interval' => $data['slotInterval'] ?? null,
                'buffer_before' => $data['beforeEventBuffer'] ?? 0,
                'buffer_after' => $data['afterEventBuffer'] ?? 0,
                'locations' => $data['locations'] ?? [],
                'custom_fields' => $data['customInputs'] ?? [],
                'max_bookings_per_day' => $data['bookingLimits']?->PER_DAY ?? null,
                'schedule_id' => $data['schedule']?->id ?? null,
                'metadata' => [
                    'schedulingType' => $data['schedulingType'] ?? null,
                    'teamId' => $data['teamId'] ?? null,
                    'userId' => $data['userId'] ?? null,
                    'hosts' => $data['hosts'] ?? [],
                    'eventName' => $data['eventName'] ?? null,
                ],
                'sync_status' => 'synced',
                'last_synced_at' => now()
            ]
        );
        
        // Check and initialize setup checklist if needed
        if (empty($eventType->setup_checklist)) {
            $eventType->initializeChecklist();
        }
    }
    
    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Event Type sync job failed permanently', [
            'company_id' => $this->company->id,
            'error' => $exception->getMessage()
        ]);
        
        // Update sync status to failed
        CalcomEventType::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->update([
                'sync_status' => 'failed',
                'sync_error' => $exception->getMessage()
            ]);
    }
}