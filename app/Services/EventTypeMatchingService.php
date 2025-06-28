<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventTypeMatchingService
{
    /**
     * Find matching event type based on service request and optional staff preference.
     */
    public function findMatchingEventType(
        string $serviceRequest,
        Branch $branch,
        ?string $staffName = null,
        ?array $timePreference = null
    ): ?array {
        Log::info('EventTypeMatchingService: Finding match', [
            'service_request' => $serviceRequest,
            'branch_id' => $branch->id,
            'staff_name' => $staffName,
            'time_preference' => $timePreference,
        ]);

        // Step 1: Find matching services
        $matchingServices = $this->findMatchingServices($serviceRequest, $branch);

        if ($matchingServices->isEmpty()) {
            Log::warning('No matching services found', [
                'service_request' => $serviceRequest,
                'branch_id' => $branch->id,
            ]);

            return null;
        }

        // Step 2: Get event types for these services
        $eventTypes = $this->getEventTypesForServices($matchingServices, $branch);

        if ($eventTypes->isEmpty()) {
            Log::warning('No event types found for matching services');

            return null;
        }

        // Step 3: Filter by staff if specified
        if ($staffName) {
            $eventTypes = $this->filterByStaff($eventTypes, $staffName, $branch);

            if ($eventTypes->isEmpty()) {
                Log::warning('No event types found for specified staff', [
                    'staff_name' => $staffName,
                ]);

                return null;
            }
        }

        // Step 4: Select best match
        $bestMatch = $this->selectBestMatch($eventTypes, $timePreference);

        Log::info('Found best match', [
            'event_type_id' => $bestMatch['event_type']->id ?? null,
            'service_name' => $bestMatch['service']->name ?? null,
        ]);

        return $bestMatch;
    }

    /**
     * Find services that match the customer's request.
     */
    private function findMatchingServices(string $serviceRequest, Branch $branch): Collection
    {
        $normalizedRequest = $this->normalizeString($serviceRequest);

        // First try exact match
        $exactMatches = Service::where('company_id', $branch->company_id)
            ->where(function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)
                    ->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->where(function ($q) use ($normalizedRequest) {
                SafeQueryHelper::whereLower($q, 'name', $normalizedRequest, '=');
            })
            ->get();

        if ($exactMatches->isNotEmpty()) {
            return $exactMatches;
        }

        // Then try partial match
        $partialMatches = Service::where('company_id', $branch->company_id)
            ->where(function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)
                    ->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->where(function ($query) use ($normalizedRequest) {
                SafeQueryHelper::whereLike($query, DB::raw('LOWER(name)'), $normalizedRequest, 'both');
                $query->orWhere(function ($q) use ($normalizedRequest) {
                    SafeQueryHelper::whereLike($q, DB::raw('LOWER(description)'), $normalizedRequest, 'both');
                });
            })
            ->orderByRaw('
                CASE 
                    WHEN LOWER(name) LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    WHEN LOWER(name) LIKE ? THEN 3
                    ELSE 4
                END
            ', [
                $normalizedRequest . '%',
                '%' . $normalizedRequest . '%',
                '%' . $normalizedRequest,
            ])
            ->get();

        if ($partialMatches->isNotEmpty()) {
            return $partialMatches;
        }

        // Finally try fuzzy matching on keywords
        return $this->fuzzyMatchServices($serviceRequest, $branch);
    }

    /**
     * Fuzzy match services based on keywords.
     */
    private function fuzzyMatchServices(string $serviceRequest, Branch $branch): Collection
    {
        $normalizedRequest = $this->normalizeString($serviceRequest);
        $keywords = $this->extractKeywords($serviceRequest);

        // First, try to find services by checking keyword mappings
        $serviceIdsFromKeywords = DB::table('service_event_type_mappings')
            ->where('company_id', $branch->company_id)
            ->where(function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)
                    ->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->where(function ($query) use ($normalizedRequest, $keywords) {
                // Check if request matches any keyword in the JSON array
                SafeQueryHelper::whereJsonContains($query, 'keywords', $normalizedRequest);

                // Also check each extracted keyword
                foreach ($keywords as $keyword) {
                    $query->orWhere(function ($q) use ($keyword) {
                        SafeQueryHelper::whereJsonContains($q, 'keywords', $keyword);
                    });
                }
            })
            ->pluck('service_id')
            ->unique();

        if ($serviceIdsFromKeywords->isNotEmpty()) {
            return Service::whereIn('id', $serviceIdsFromKeywords)
                ->where('is_active', true)
                ->get();
        }

        // Fallback to original fuzzy matching
        if (empty($keywords)) {
            return collect();
        }

        $query = Service::where('company_id', $branch->company_id)
            ->where(function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)
                    ->orWhereNull('branch_id');
            })
            ->where('is_active', true);

        foreach ($keywords as $keyword) {
            $escapedKeyword = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);
            $query->where(function ($q) use ($escapedKeyword) {
                $q->where(DB::raw('LOWER(name)'), 'LIKE', '%' . $escapedKeyword . '%')
                    ->orWhere(DB::raw('LOWER(description)'), 'LIKE', '%' . $escapedKeyword . '%');
            });
        }

        return $query->get();
    }

    /**
     * Get event types associated with services.
     */
    private function getEventTypesForServices(Collection $services, Branch $branch): Collection
    {
        $serviceIds = $services->pluck('id');

        // Get event types through service_event_type_mappings
        $eventTypes = DB::table('service_event_type_mappings as sem')
            ->join('calcom_event_types as cet', 'sem.calcom_event_type_id', '=', 'cet.calcom_numeric_event_type_id')
            ->join('services as s', 'sem.service_id', '=', 's.id')
            ->where('sem.company_id', $branch->company_id)
            ->where(function ($query) use ($branch) {
                $query->where('sem.branch_id', $branch->id)
                    ->orWhereNull('sem.branch_id');
            })
            ->whereIn('sem.service_id', $serviceIds)
            ->where('sem.is_active', true)
            ->where('cet.is_active', true)
            ->select(
                'cet.*',
                's.id as service_id',
                's.name as service_name',
                'sem.priority',
                'sem.keywords'
            )
            ->orderBy('sem.priority', 'desc')
            ->get();

        // Convert to CalcomEventType models
        return $eventTypes->map(function ($item) {
            $eventType = new CalcomEventType((array) $item);
            $eventType->exists = true;
            $eventType->service_id = $item->service_id;
            $eventType->service_name = $item->service_name;
            $eventType->mapping_priority = $item->priority;

            return $eventType;
        });
    }

    /**
     * Filter event types by staff preference.
     */
    private function filterByStaff(Collection $eventTypes, string $staffName, Branch $branch): Collection
    {
        $normalizedStaffName = $this->normalizeString($staffName);

        // Find matching staff
        // Escape special characters in LIKE pattern to prevent wildcard injection
        $escapedStaffName = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $normalizedStaffName);

        $staff = Staff::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->where(function ($query) use ($escapedStaffName) {
                $query->where(DB::raw('LOWER(CONCAT(first_name, " ", last_name))'), 'LIKE', '%' . $escapedStaffName . '%')
                    ->orWhere(DB::raw('LOWER(first_name)'), 'LIKE', '%' . $escapedStaffName . '%')
                    ->orWhere(DB::raw('LOWER(last_name)'), 'LIKE', '%' . $escapedStaffName . '%');
            })
            ->first();

        if (! $staff) {
            return collect();
        }

        // Filter event types where this staff is assigned
        $staffEventTypeIds = DB::table('staff_event_types')
            ->where('staff_id', $staff->id)
            ->pluck('event_type_id');

        return $eventTypes->filter(function ($eventType) use ($staffEventTypeIds) {
            return $staffEventTypeIds->contains($eventType->calcom_numeric_event_type_id);
        });
    }

    /**
     * Select the best matching event type.
     */
    private function selectBestMatch(Collection $eventTypes, ?array $timePreference = null): ?array
    {
        if ($eventTypes->isEmpty()) {
            return null;
        }

        // For now, return the highest priority match
        // In the future, this could consider time preference and availability
        $bestEventType = $eventTypes->first();

        $service = Service::find($bestEventType->service_id);

        return [
            'event_type' => $bestEventType,
            'service' => $service,
            'duration_minutes' => $bestEventType->duration_minutes,
            'requires_confirmation' => $bestEventType->requires_confirmation ?? false,
        ];
    }

    /**
     * Normalize string for comparison.
     */
    private function normalizeString(string $str): string
    {
        return trim(strtolower($str));
    }

    /**
     * Extract keywords from service request.
     */
    private function extractKeywords(string $serviceRequest): array
    {
        // Remove common words
        $stopWords = ['der', 'die', 'das', 'ein', 'eine', 'einen', 'ich', 'möchte', 'gerne', 'bitte', 'termin', 'für'];

        $words = preg_split('/\s+/', strtolower($serviceRequest));
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 2 && ! in_array($word, $stopWords);
        });

        return array_values($keywords);
    }

    /**
     * Create or update service to event type mapping.
     */
    public function createMapping(
        Service $service,
        CalcomEventType $eventType,
        ?array $keywords = null,
        int $priority = 0
    ): void {
        DB::table('service_event_type_mappings')->updateOrInsert(
            [
                'service_id' => $service->id,
                'calcom_event_type_id' => $eventType->calcom_numeric_event_type_id,
                'branch_id' => $service->branch_id,
            ],
            [
                'company_id' => $service->company_id,
                'keywords' => $keywords ? json_encode($keywords) : null,
                'priority' => $priority,
                'is_active' => true,
                'updated_at' => now(),
            ]
        );
    }
}
