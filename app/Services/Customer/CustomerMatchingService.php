<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\CustomerRelationship;
use App\Models\Call;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerMatchingService
{
    /**
     * Find related customers based on various criteria
     */
    public function findRelatedCustomers(
        int $companyId,
        ?string $toNumber,
        ?string $phoneNumber,
        ?string $companyName = null,
        ?string $customerNumber = null
    ): Collection {
        $query = Customer::where('company_id', $companyId);
        
        $matches = collect();
        
        // 1. Exact phone match
        if ($phoneNumber) {
            $phoneMatches = (clone $query)
                ->where(function ($q) use ($phoneNumber) {
                    $q->where('phone', $phoneNumber)
                      ->orWhereJsonContains('phone_variants', $phoneNumber);
                })
                ->get()
                ->map(function ($customer) {
                    $customer->match_confidence = 100;
                    $customer->match_reason = 'phone_exact';
                    return $customer;
                });
            
            $matches = $matches->merge($phoneMatches);
        }
        
        // 2. Customer number match
        if ($customerNumber && $matches->isEmpty()) {
            $numberMatches = (clone $query)
                ->where('customer_number', $customerNumber)
                ->get()
                ->map(function ($customer) {
                    $customer->match_confidence = 90;
                    $customer->match_reason = 'customer_number';
                    return $customer;
                });
            
            $matches = $matches->merge($numberMatches);
        }
        
        // 3. Company name match
        if ($companyName && $matches->isEmpty()) {
            $companyMatches = (clone $query)
                ->where('company_name', 'LIKE', '%' . $companyName . '%')
                ->get()
                ->map(function ($customer) use ($companyName) {
                    $similarity = similar_text(
                        strtolower($customer->company_name),
                        strtolower($companyName),
                        $percent
                    );
                    $customer->match_confidence = (int) $percent;
                    $customer->match_reason = 'company_name';
                    return $customer;
                });
            
            $matches = $matches->merge($companyMatches);
        }
        
        // 4. Partial phone match (last 6 digits)
        if ($phoneNumber && $matches->isEmpty()) {
            $lastDigits = substr(preg_replace('/[^0-9]/', '', $phoneNumber), -6);
            if (strlen($lastDigits) >= 6) {
                $partialMatches = (clone $query)
                    ->where('phone', 'LIKE', '%' . $lastDigits)
                    ->get()
                    ->map(function ($customer) {
                        $customer->match_confidence = 70;
                        $customer->match_reason = 'phone_partial';
                        return $customer;
                    });
                
                $matches = $matches->merge($partialMatches);
            }
        }
        
        // Remove duplicates and sort by confidence
        return $matches
            ->unique('id')
            ->sortByDesc('match_confidence')
            ->values();
    }
    
    /**
     * Get related interactions for a customer
     */
    public function getRelatedInteractions(Customer $customer): array
    {
        // Get related customers through relationships
        $relatedCustomerIds = CustomerRelationship::where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhere('related_customer_id', $customer->id);
            })
            ->where('status', '!=', 'user_rejected')
            ->get()
            ->flatMap(function ($rel) use ($customer) {
                return [
                    $rel->customer_id,
                    $rel->related_customer_id
                ];
            })
            ->unique()
            ->reject(fn($id) => $id == $customer->id)
            ->values();
        
        // Get related customers
        $relatedCustomers = Customer::whereIn('id', $relatedCustomerIds)->get();
        
        // Count total interactions
        $customerIds = $relatedCustomerIds->push($customer->id);
        
        $totalCalls = Call::whereIn('customer_id', $customerIds)->count();
        $totalAppointments = DB::table('appointments')
            ->whereIn('customer_id', $customerIds)
            ->count();
        
        return [
            'related_customers' => $relatedCustomers,
            'total_calls' => $totalCalls,
            'total_appointments' => $totalAppointments,
        ];
    }
    
    /**
     * Auto-detect and create customer relationships
     */
    public function detectRelationships(Customer $customer): void
    {
        // Skip if no identifying data
        if (!$customer->phone && !$customer->company_name) {
            return;
        }
        
        // Find potential matches
        $query = Customer::where('company_id', $customer->company_id)
            ->where('id', '!=', $customer->id);
        
        $potentialMatches = collect();
        
        // Phone-based matches
        if ($customer->phone) {
            $phoneMatches = (clone $query)
                ->where(function ($q) use ($customer) {
                    $q->where('phone', $customer->phone)
                      ->orWhereJsonContains('phone_variants', $customer->phone);
                })
                ->get();
            
            foreach ($phoneMatches as $match) {
                $this->createRelationship($customer, $match, 'phone_match', 95);
            }
        }
        
        // Company name matches
        if ($customer->company_name) {
            $companyMatches = (clone $query)
                ->where('company_name', 'LIKE', '%' . $customer->company_name . '%')
                ->get();
            
            foreach ($companyMatches as $match) {
                similar_text(
                    strtolower($customer->company_name),
                    strtolower($match->company_name),
                    $percent
                );
                
                if ($percent > 80) {
                    $this->createRelationship($customer, $match, 'same_company', (int) $percent);
                }
            }
        }
    }
    
    /**
     * Create or update a customer relationship
     */
    private function createRelationship(
        Customer $customer1,
        Customer $customer2,
        string $type,
        int $confidence
    ): void {
        // Ensure consistent ordering (lower ID first)
        if ($customer1->id > $customer2->id) {
            [$customer1, $customer2] = [$customer2, $customer1];
        }
        
        // Check if relationship already exists
        $existing = CustomerRelationship::where('customer_id', $customer1->id)
            ->where('related_customer_id', $customer2->id)
            ->first();
        
        if ($existing) {
            // Update confidence if higher
            if ($confidence > $existing->confidence_score) {
                $existing->update([
                    'confidence_score' => $confidence,
                    'relationship_type' => $type,
                    'matching_details' => array_merge($existing->matching_details ?? [], [
                        'updated_at' => now()->toIso8601String(),
                        'new_confidence' => $confidence,
                        'reason' => $type
                    ])
                ]);
            }
        } else {
            // Create new relationship
            CustomerRelationship::create([
                'customer_id' => $customer1->id,
                'related_customer_id' => $customer2->id,
                'company_id' => $customer1->company_id,
                'relationship_type' => $type,
                'confidence_score' => $confidence,
                'status' => 'auto_detected',
                'matching_details' => [
                    'detected_at' => now()->toIso8601String(),
                    'method' => $type,
                    'data' => [
                        'phone_match' => $customer1->phone === $customer2->phone,
                        'company_match' => $customer1->company_name === $customer2->company_name,
                    ]
                ]
            ]);
        }
    }
    
    /**
     * Merge two customers
     */
    public function mergeCustomers(Customer $primary, Customer $secondary): Customer
    {
        DB::transaction(function () use ($primary, $secondary) {
            // Update all references
            Call::where('customer_id', $secondary->id)
                ->update(['customer_id' => $primary->id]);
            
            DB::table('appointments')
                ->where('customer_id', $secondary->id)
                ->update(['customer_id' => $primary->id]);
            
            DB::table('customer_touchpoints')
                ->where('customer_id', $secondary->id)
                ->update(['customer_id' => $primary->id]);
            
            DB::table('customer_journey_events')
                ->where('customer_id', $secondary->id)
                ->update(['customer_id' => $primary->id]);
            
            // Merge data
            $primary->phone_variants = array_unique(array_merge(
                $primary->phone_variants ?? [],
                $secondary->phone_variants ?? [],
                [$secondary->phone]
            ));
            
            if (!$primary->company_name && $secondary->company_name) {
                $primary->company_name = $secondary->company_name;
            }
            
            if (!$primary->customer_number && $secondary->customer_number) {
                $primary->customer_number = $secondary->customer_number;
            }
            
            // Update stats
            $primary->call_count = Call::where('customer_id', $primary->id)->count();
            $primary->appointment_count = DB::table('appointments')
                ->where('customer_id', $primary->id)
                ->count();
            
            $primary->save();
            
            // Mark relationship as merged
            CustomerRelationship::where(function ($q) use ($primary, $secondary) {
                    $q->where(['customer_id' => $primary->id, 'related_customer_id' => $secondary->id])
                      ->orWhere(['customer_id' => $secondary->id, 'related_customer_id' => $primary->id]);
                })
                ->update(['status' => 'merged']);
            
            // Delete secondary customer
            $secondary->delete();
        });
        
        return $primary->fresh();
    }
}