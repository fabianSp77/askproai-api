<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\CalcomService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportEventTypeJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 120;

    protected $eventTypeData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $eventTypeData)
    {
        $this->eventTypeData = $eventTypeData;
        $this->queue = 'calcom-sync';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $eventTypeId = $this->eventTypeData['id'] ?? null;

            if (!$eventTypeId) {
                Log::error('[Cal.com Import] No Event Type ID provided', $this->eventTypeData);
                return;
            }

            // Check if service already exists
            $service = Service::where('calcom_event_type_id', $eventTypeId)->first();

            // Prepare service data
            $serviceData = [
                'name' => $this->eventTypeData['title'] ?? 'Unnamed Service',
                'calcom_name' => $this->eventTypeData['title'] ?? 'Unnamed Service',
                'slug' => $this->eventTypeData['slug'] ?? null,
                'description' => $this->eventTypeData['description'] ?? null,
                'duration_minutes' => $this->eventTypeData['length'] ?? 30,
                'price' => $this->eventTypeData['price'] ?? 0,
                'is_active' => !($this->eventTypeData['hidden'] ?? false),
                'is_online' => $this->hasOnlineLocation($this->eventTypeData['locations'] ?? []),
                'schedule_id' => $this->eventTypeData['scheduleId'] ?? null,
                'minimum_booking_notice' => $this->eventTypeData['minimumBookingNotice'] ?? 120,
                'before_event_buffer' => $this->eventTypeData['beforeEventBuffer'] ?? 0,
                'buffer_time_minutes' => $this->eventTypeData['afterEventBuffer'] ?? 0,
                'requires_confirmation' => $this->eventTypeData['requiresConfirmation'] ?? false,
                'disable_guests' => $this->eventTypeData['disableGuests'] ?? false,
                'booking_link' => $this->eventTypeData['link'] ?? null,
                'locations_json' => $this->eventTypeData['locations'] ?? null,
                'metadata_json' => $this->eventTypeData['metadata'] ?? null,
                'booking_fields_json' => $this->eventTypeData['bookingFields'] ?? null,
                'last_calcom_sync' => now(),
                'sync_status' => 'synced',
                'sync_error' => null,
            ];

            // Try to determine company_id from metadata or use default
            $companyId = $this->extractCompanyId();
            if ($companyId) {
                $serviceData['company_id'] = $companyId;
            }

            if ($service) {
                // Update existing service
                $service->update($serviceData);
                Log::info("[Cal.com Import] Updated Service ID {$service->id} from Event Type {$eventTypeId}");
            } else {
                // Create new service
                $serviceData['calcom_event_type_id'] = $eventTypeId;

                // Set default company_id if not determined
                if (!isset($serviceData['company_id'])) {
                    // Use first company as default (should be improved based on business logic)
                    $defaultCompany = \App\Models\Company::first();
                    if ($defaultCompany) {
                        $serviceData['company_id'] = $defaultCompany->id;
                    }
                }

                $service = Service::create($serviceData);
                Log::info("[Cal.com Import] Created Service ID {$service->id} from Event Type {$eventTypeId}");
            }

        } catch (\Exception $e) {
            Log::error('[Cal.com Import] Failed to import Event Type', [
                'event_type_id' => $eventTypeId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update service with error if it exists
            if (isset($service)) {
                $service->update([
                    'sync_status' => 'error',
                    'sync_error' => $e->getMessage()
                ]);
            }

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Check if event type has online location
     */
    private function hasOnlineLocation(array $locations): bool
    {
        foreach ($locations as $location) {
            $type = $location['type'] ?? '';
            if (str_contains($type, 'integrations:') ||
                str_contains($type, 'video') ||
                str_contains($type, 'meet') ||
                str_contains($type, 'zoom')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract company ID from metadata or other sources
     */
    private function extractCompanyId(): ?int
    {
        // Check metadata for company_id
        if (isset($this->eventTypeData['metadata']['company_id'])) {
            return (int) $this->eventTypeData['metadata']['company_id'];
        }

        // Check team information
        if (isset($this->eventTypeData['team']['id'])) {
            // Map team to company if relationship exists
            // This would need custom logic based on your business rules
        }

        // Check owner information
        if (isset($this->eventTypeData['owner']['username'])) {
            // Could map username to company if needed
            // For now, return null to use default
        }

        return null;
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[Cal.com Import] Job failed', [
            'event_type_data' => $this->eventTypeData,
            'exception' => $exception->getMessage()
        ]);
    }
}