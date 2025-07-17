<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallCustomerAssignmentController extends Controller
{
    public function assign(Request $request, $callId)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id'
        ]);
        
        $call = Call::findOrFail($callId);
        $customer = Customer::findOrFail($validated['customer_id']);
        
        // Prüfe ob beide zur gleichen Company gehören
        if ($call->company_id !== $customer->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Kunde und Anruf gehören nicht zur gleichen Firma'
            ], 403);
        }
        
        // Zuordnung durchführen
        $call->customer_id = $customer->id;
        $call->save();
        
        // Update customer tracking data
        if (!empty($call->metadata['customer_data'])) {
            $data = $call->metadata['customer_data'];
            
            if (!empty($data['company']) && empty($customer->company_name)) {
                $customer->company_name = $data['company'];
            }
            
            if (!empty($data['customer_number']) && empty($customer->customer_number)) {
                $customer->customer_number = $data['customer_number'];
            }
            
            $customer->save();
        }
        
        // Create touchpoint
        DB::table('customer_touchpoints')->insert([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'type' => 'call',
            'channel' => 'phone',
            'direction' => 'inbound',
            'status' => 'completed',
            'data' => json_encode([
                'call_id' => $call->id,
                'duration' => $call->duration_sec,
                'summary' => $call->summary
            ]),
            'occurred_at' => $call->start_timestamp ?? $call->created_at,
            'touchpointable_type' => 'App\\Models\\Call',
            'touchpointable_id' => $call->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Fire event to update stats
        event(new \App\Events\CallUpdated($call));
        
        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich zugeordnet'
        ]);
    }
}