<?php

namespace App\Observers;

use App\Models\Call;
use Illuminate\Support\Facades\Log;

/**
 * Call Observer
 *
 * Monitors call data integrity and logs warnings for missing critical fields
 *
 * FEATURES:
 * - Validates phone_number_id is set on create
 * - Validates duration_sec is set when call completes
 * - Alerts on anonymous callers (from_number = 'anonymous')
 * - Tracks data quality metrics
 */
class CallObserver
{
    /**
     * Handle the Call "created" event.
     *
     * @param  \App\Models\Call  $call
     * @return void
     */
    public function created(Call $call): void
    {
        $missingFields = [];
        $warnings = [];

        // Critical: phone_number_id should always be set
        if (!$call->phone_number_id) {
            $missingFields[] = 'phone_number_id';

            Log::critical('🚨 INCOMPLETE CALL DATA: phone_number_id missing', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'to_number' => $call->to_number,
                'from_number' => $call->from_number,
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'status' => $call->status,
            ]);
        }

        // Warning: retell_call_id should be set (unless temporary call)
        if (!$call->retell_call_id || str_starts_with($call->retell_call_id, 'temp_')) {
            $warnings[] = 'retell_call_id_temp';
        }

        // Info: Anonymous caller detected
        if ($call->from_number === 'anonymous') {
            Log::info('ℹ️ Anonymous caller detected', [
                'call_id' => $call->id,
                'to_number' => $call->to_number,
                'company_id' => $call->company_id,
            ]);
        }

        // If we have missing critical fields, potentially send notification
        if (!empty($missingFields)) {
            // Optional: Send notification to admin
            // Notification::route('mail', config('alerts.admin_email'))
            //     ->notify(new IncompleteCallDataAlert($call, $missingFields));
        }
    }

    /**
     * Handle the Call "updated" event.
     *
     * @param  \App\Models\Call  $call
     * @return void
     */
    public function updated(Call $call): void
    {
        // Check if call just completed
        if ($call->isDirty('status') && $call->status === 'completed') {
            $missingFields = [];

            // Duration should be set when call completes
            if (!$call->duration_sec && !$call->duration_ms) {
                $missingFields[] = 'duration';

                Log::warning('⚠️ COMPLETED CALL WITHOUT DURATION', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'status' => $call->status,
                    'disconnection_reason' => $call->disconnection_reason,
                ]);
            }

            // Check if phone_number_id is still missing after call completes
            if (!$call->phone_number_id) {
                Log::critical('🚨 COMPLETED CALL STILL MISSING phone_number_id', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'to_number' => $call->to_number,
                    'company_id' => $call->company_id,
                ]);
            }
        }
    }

    /**
     * Handle the Call "deleted" event.
     *
     * @param  \App\Models\Call  $call
     * @return void
     */
    public function deleted(Call $call): void
    {
        Log::info('🗑️ Call soft-deleted', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
        ]);
    }

    /**
     * Handle the Call "restored" event.
     *
     * @param  \App\Models\Call  $call
     * @return void
     */
    public function restored(Call $call): void
    {
        Log::info('♻️ Call restored', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
        ]);
    }

    /**
     * Handle the Call "force deleted" event.
     *
     * @param  \App\Models\Call  $call
     * @return void
     */
    public function forceDeleted(Call $call): void
    {
        Log::warning('💀 Call permanently deleted', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
        ]);
    }
}
