<?php

namespace App\Observers;

use App\Jobs\UpdateCalcomEventTypeJob;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class ServiceObserver
{
    /**
     * Handle the Service "creating" event.
     */
    public function creating(Service $service): void
    {
        // Enforce that services must be created with a Cal.com Event Type ID
        if (!$service->calcom_event_type_id) {
            throw new \Exception('Services must be created through Cal.com first. Please create an Event Type in Cal.com and it will be automatically imported.');
        }

        // Set default sync status for new services
        if (!$service->sync_status) {
            $service->sync_status = 'synced';
        }
    }

    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        Log::info('[Service Observer] Service created', [
            'service_id' => $service->id,
            'calcom_event_type_id' => $service->calcom_event_type_id
        ]);
    }

    /**
     * Handle the Service "updating" event.
     */
    public function updating(Service $service): void
    {
        // Only sync if service has Cal.com ID and basic fields changed
        if (!$service->calcom_event_type_id) {
            return;
        }

        // Check if any syncable fields have changed
        $syncableFields = ['name', 'duration_minutes', 'price', 'description', 'is_active', 'buffer_time_minutes'];
        $hasChanges = false;

        foreach ($syncableFields as $field) {
            if ($service->isDirty($field)) {
                $hasChanges = true;
                break;
            }
        }

        if ($hasChanges) {
            // Mark as pending sync
            $service->sync_status = 'pending';

            Log::info('[Service Observer] Service needs Cal.com sync', [
                'service_id' => $service->id,
                'changed_fields' => array_keys($service->getDirty())
            ]);
        }
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        // If sync status is pending, dispatch sync job
        if ($service->sync_status === 'pending' && $service->calcom_event_type_id) {
            UpdateCalcomEventTypeJob::dispatch($service);

            Log::info('[Service Observer] Dispatched Cal.com sync job', [
                'service_id' => $service->id,
                'calcom_event_type_id' => $service->calcom_event_type_id
            ]);
        }
    }

    /**
     * Handle the Service "deleting" event.
     */
    public function deleting(Service $service): void
    {
        // Prevent deletion if service has Cal.com ID
        if ($service->calcom_event_type_id) {
            throw new \Exception('Cannot delete services that are synced with Cal.com. Please deactivate the service instead or delete the Event Type in Cal.com.');
        }
    }

    /**
     * Handle the Service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        Log::info('[Service Observer] Service deleted', [
            'service_id' => $service->id
        ]);
    }

    /**
     * Handle the Service "restored" event.
     */
    public function restored(Service $service): void
    {
        Log::info('[Service Observer] Service restored', [
            'service_id' => $service->id
        ]);
    }

    /**
     * Handle the Service "force deleted" event.
     */
    public function forceDeleted(Service $service): void
    {
        Log::info('[Service Observer] Service force deleted', [
            'service_id' => $service->id
        ]);
    }
}