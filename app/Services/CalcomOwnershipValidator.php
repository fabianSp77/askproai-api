<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CalcomOwnershipValidator
{
    /**
     * Validate that an event type belongs to a company's Cal.com team
     *
     * @param string|int $eventTypeId Cal.com Event Type ID
     * @param int $companyId Company ID
     * @return bool True if valid, false if invalid
     */
    public function validateEventTypeBelongsToCompany($eventTypeId, int $companyId): bool
    {
        return DB::table('calcom_event_mappings')
            ->where('calcom_event_type_id', (string)$eventTypeId)
            ->where('company_id', $companyId)
            ->exists();
    }

    /**
     * Get all valid event type IDs for a company
     *
     * @param int $companyId Company ID
     * @return array Array of event type IDs
     */
    public function getValidEventTypesForCompany(int $companyId): array
    {
        return DB::table('calcom_event_mappings')
            ->where('company_id', $companyId)
            ->pluck('calcom_event_type_id')
            ->toArray();
    }
}
