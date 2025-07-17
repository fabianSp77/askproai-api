<?php

namespace App\Listeners;

use App\Events\CallCreated;
use App\Events\CallUpdated;
use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class UpdateCustomerCallStats implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $call = $event->call;
        
        // Nur wenn ein Kunde zugeordnet ist
        if (!$call->customer_id) {
            return;
        }
        
        // Update customer stats
        DB::table('customers')
            ->where('id', $call->customer_id)
            ->update([
                'call_count' => DB::raw('(SELECT COUNT(*) FROM calls WHERE customer_id = ' . $call->customer_id . ')'),
                'last_call_at' => $call->start_timestamp ?? $call->created_at,
                'updated_at' => now(),
            ]);
        
        // Wenn Firmendaten vorhanden sind, speichere sie beim Kunden
        if (isset($call->metadata['customer_data']['company']) && $call->metadata['customer_data']['company']) {
            Customer::where('id', $call->customer_id)
                ->whereNull('company_name')
                ->update([
                    'company_name' => $call->metadata['customer_data']['company']
                ]);
        }
        
        // Wenn Kundennummer vorhanden ist, speichere sie
        if (isset($call->metadata['customer_data']['customer_number']) && $call->metadata['customer_data']['customer_number']) {
            Customer::where('id', $call->customer_id)
                ->whereNull('customer_number')
                ->update([
                    'customer_number' => $call->metadata['customer_data']['customer_number']
                ]);
        }
    }
}