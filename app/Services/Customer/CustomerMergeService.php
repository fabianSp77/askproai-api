<?php

namespace App\Services\Customer;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Customer Merge Service
 *
 * Safely merges duplicate customers:
 * - Transfers all calls, appointments, notes
 * - Merges contact information
 * - Updates journey status to most advanced
 * - Maintains audit trail
 */
class CustomerMergeService
{
    /**
     * Merge duplicate customer into primary customer
     *
     * @param Customer $primary The customer to keep
     * @param Customer $duplicate The customer to merge and delete
     * @return array Result with stats
     */
    public function merge(Customer $primary, Customer $duplicate): array
    {
        if ($primary->id === $duplicate->id) {
            throw new \InvalidArgumentException('Cannot merge customer with itself');
        }

        if ($primary->company_id !== $duplicate->company_id) {
            throw new \InvalidArgumentException('Cannot merge customers from different companies');
        }

        $stats = [
            'calls_transferred' => 0,
            'appointments_transferred' => 0,
            'notes_transferred' => 0,
            'data_merged' => [],
        ];

        DB::beginTransaction();

        try {
            // 1. Transfer Calls
            $callsCount = $duplicate->calls()->update(['customer_id' => $primary->id]);
            $stats['calls_transferred'] = $callsCount;

            // 2. Transfer Appointments
            $appointmentsCount = $duplicate->appointments()->update(['customer_id' => $primary->id]);
            $stats['appointments_transferred'] = $appointmentsCount;

            // 3. Transfer Notes (if relation exists)
            if (method_exists($duplicate, 'notes')) {
                $notesCount = $duplicate->notes()->update(['customer_id' => $primary->id]);
                $stats['notes_transferred'] = $notesCount;
            }

            // 4. Merge Contact Information (keep non-empty values)
            $mergedData = $this->mergeContactData($primary, $duplicate);
            $primary->update($mergedData);
            $stats['data_merged'] = array_keys($mergedData);

            // 5. Update Journey Status (keep most advanced)
            $newJourneyStatus = $this->getMostAdvancedJourneyStatus(
                $primary->journey_status,
                $duplicate->journey_status
            );
            if ($newJourneyStatus !== $primary->journey_status) {
                $primary->update(['journey_status' => $newJourneyStatus]);
                $stats['journey_updated'] = true;
            }

            // 6. Merge Revenue
            $primary->total_revenue = ($primary->total_revenue ?? 0) + ($duplicate->total_revenue ?? 0);
            $primary->save();

            // 7. Add merge note to audit trail
            $this->addMergeNote($primary, $duplicate, $stats);

            // 8. Soft delete duplicate
            $duplicate->delete();

            DB::commit();

            Log::info('Customer merge completed', [
                'primary_id' => $primary->id,
                'duplicate_id' => $duplicate->id,
                'stats' => $stats,
            ]);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Customer merge failed', [
                'primary_id' => $primary->id,
                'duplicate_id' => $duplicate->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Merge contact data from duplicate into primary
     */
    private function mergeContactData(Customer $primary, Customer $duplicate): array
    {
        $merged = [];

        // Email: Keep primary's email unless it's empty
        if (empty($primary->email) && !empty($duplicate->email)) {
            $merged['email'] = $duplicate->email;
        }

        // Phone: Keep primary's phone unless it's empty
        if (empty($primary->phone) && !empty($duplicate->phone)) {
            $merged['phone'] = $duplicate->phone;
        }

        // Address fields
        if (empty($primary->address) && !empty($duplicate->address)) {
            $merged['address'] = $duplicate->address;
        }

        if (empty($primary->city) && !empty($duplicate->city)) {
            $merged['city'] = $duplicate->city;
        }

        if (empty($primary->postal_code) && !empty($duplicate->postal_code)) {
            $merged['postal_code'] = $duplicate->postal_code;
        }

        // Birthday
        if (empty($primary->birthday) && !empty($duplicate->birthday)) {
            $merged['birthday'] = $duplicate->birthday;
        }

        // Tags: Merge arrays if exists
        if (isset($primary->tags) && isset($duplicate->tags)) {
            $mergedTags = array_unique(array_merge(
                is_array($primary->tags) ? $primary->tags : [],
                is_array($duplicate->tags) ? $duplicate->tags : []
            ));
            if (count($mergedTags) > count($primary->tags ?? [])) {
                $merged['tags'] = $mergedTags;
            }
        } elseif (!empty($duplicate->tags)) {
            $merged['tags'] = $duplicate->tags;
        }

        return $merged;
    }

    /**
     * Determine the most advanced journey status
     */
    private function getMostAdvancedJourneyStatus(string $status1, string $status2): string
    {
        $hierarchy = [
            'initial_contact' => 1,
            'lead' => 2,
            'prospect' => 3,
            'customer' => 4,
            'regular' => 5,
            'vip' => 6,
            'at_risk' => 3, // Same as prospect
            'churned' => 0, // Lowest
        ];

        $rank1 = $hierarchy[$status1] ?? 0;
        $rank2 = $hierarchy[$status2] ?? 0;

        return $rank1 >= $rank2 ? $status1 : $status2;
    }

    /**
     * Add merge note to customer's history
     */
    private function addMergeNote(Customer $primary, Customer $duplicate, array $stats): void
    {
        if (method_exists($primary, 'notes')) {
            $noteContent = "Kunde #{$duplicate->id} ({$duplicate->name}) wurde zusammengeführt.\n\n";
            $noteContent .= "Übertragen:\n";
            $noteContent .= "- {$stats['calls_transferred']} Anruf(e)\n";
            $noteContent .= "- {$stats['appointments_transferred']} Termin(e)\n";
            if (isset($stats['notes_transferred'])) {
                $noteContent .= "- {$stats['notes_transferred']} Notiz(en)\n";
            }
            if (!empty($stats['data_merged'])) {
                $noteContent .= "\nDaten aktualisiert: " . implode(', ', $stats['data_merged']);
            }

            $primary->notes()->create([
                'subject' => 'Kunde zusammengeführt',
                'content' => $noteContent,
                'type' => 'system',
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Preview merge without executing
     */
    public function previewMerge(Customer $primary, Customer $duplicate): array
    {
        return [
            'primary' => [
                'id' => $primary->id,
                'name' => $primary->name,
                'email' => $primary->email,
                'phone' => $primary->phone,
                'calls' => $primary->calls()->count(),
                'appointments' => $primary->appointments()->count(),
                'revenue' => $primary->total_revenue ?? 0,
            ],
            'duplicate' => [
                'id' => $duplicate->id,
                'name' => $duplicate->name,
                'email' => $duplicate->email,
                'phone' => $duplicate->phone,
                'calls' => $duplicate->calls()->count(),
                'appointments' => $duplicate->appointments()->count(),
                'revenue' => $duplicate->total_revenue ?? 0,
            ],
            'result' => [
                'name' => $primary->name,
                'email' => $primary->email ?: $duplicate->email,
                'phone' => $primary->phone ?: $duplicate->phone,
                'calls' => $primary->calls()->count() + $duplicate->calls()->count(),
                'appointments' => $primary->appointments()->count() + $duplicate->appointments()->count(),
                'revenue' => ($primary->total_revenue ?? 0) + ($duplicate->total_revenue ?? 0),
                'journey_status' => $this->getMostAdvancedJourneyStatus(
                    $primary->journey_status,
                    $duplicate->journey_status
                ),
            ],
        ];
    }
}
