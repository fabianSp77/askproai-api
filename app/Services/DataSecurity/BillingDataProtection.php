<?php

namespace App\Services\DataSecurity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BillingDataProtection
{
    /**
     * Create immutable billing snapshot for invoicing
     */
    public function createBillingSnapshot($companyId, $month, $year)
    {
        DB::beginTransaction();
        
        try {
            // Get company data
            $company = DB::table('companies')->find($companyId);
            $branches = DB::table('branches')
                ->where('company_id', $companyId)
                ->get();
            
            // Calculate period
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            
            $snapshot = [
                'company_id' => $companyId,
                'month' => $month,
                'year' => $year,
                'company_data' => json_encode($company),
                'branches_data' => json_encode($branches),
                'metrics' => [],
                'created_at' => now(),
            ];
            
            // Collect metrics per branch
            foreach ($branches as $branch) {
                $branchMetrics = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'appointments' => $this->getAppointmentMetrics($branch->id, $startDate, $endDate),
                    'calls' => $this->getCallMetrics($branch->id, $startDate, $endDate),
                    'revenue' => $this->getRevenueMetrics($branch->id, $startDate, $endDate),
                ];
                
                $snapshot['metrics'][] = $branchMetrics;
            }
            
            // Store immutable snapshot
            $snapshotId = DB::table('billing_snapshots')->insertGetId([
                'company_id' => $companyId,
                'period' => "{$year}-{$month}",
                'snapshot_data' => json_encode($snapshot),
                'checksum' => hash('sha256', json_encode($snapshot)),
                'is_finalized' => false,
                'created_at' => now(),
            ]);
            
            // Create detailed line items
            $this->createBillingLineItems($snapshotId, $snapshot);
            
            DB::commit();
            
            Log::info('Billing snapshot created', [
                'company_id' => $companyId,
                'period' => "{$year}-{$month}",
                'snapshot_id' => $snapshotId,
            ]);
            
            return $snapshotId;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create billing snapshot', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    private function getAppointmentMetrics($branchId, $startDate, $endDate)
    {
        return [
            'total_appointments' => DB::table('appointments')
                ->where('branch_id', $branchId)
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->count(),
                
            'completed_appointments' => DB::table('appointments')
                ->where('branch_id', $branchId)
                ->where('status', 'completed')
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->count(),
                
            'no_shows' => DB::table('appointments')
                ->where('branch_id', $branchId)
                ->where('status', 'no_show')
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->count(),
                
            'revenue' => DB::table('appointments')
                ->where('branch_id', $branchId)
                ->where('status', 'completed')
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->sum('price') ?? 0,
        ];
    }
    
    private function getCallMetrics($branchId, $startDate, $endDate)
    {
        return [
            'total_calls' => DB::table('calls')
                ->where('branch_id', $branchId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'total_duration_minutes' => DB::table('calls')
                ->where('branch_id', $branchId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum(DB::raw('duration_sec / 60')) ?? 0,
                
            'appointments_booked' => DB::table('calls')
                ->where('branch_id', $branchId)
                ->where('appointment_requested', true)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'cost' => $this->calculateCallCosts($branchId, $startDate, $endDate),
        ];
    }
    
    private function getRevenueMetrics($branchId, $startDate, $endDate)
    {
        $pricing = DB::table('branch_pricing_overrides')
            ->where('branch_id', $branchId)
            ->first();
            
        if (!$pricing) {
            $pricing = DB::table('company_pricing')
                ->where('company_id', DB::table('branches')->find($branchId)->company_id)
                ->first();
        }
        
        $callMinutes = DB::table('calls')
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('duration_sec / 60')) ?? 0;
            
        $appointments = DB::table('appointments')
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->count();
        
        return [
            'base_fee' => $pricing->base_monthly_fee ?? 0,
            'per_minute_charge' => $callMinutes * ($pricing->price_per_minute ?? 0.15),
            'per_appointment_charge' => $appointments * ($pricing->price_per_appointment ?? 2.00),
            'total' => ($pricing->base_monthly_fee ?? 0) + 
                      ($callMinutes * ($pricing->price_per_minute ?? 0.15)) +
                      ($appointments * ($pricing->price_per_appointment ?? 2.00)),
        ];
    }
    
    private function calculateCallCosts($branchId, $startDate, $endDate)
    {
        $minutes = DB::table('calls')
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('duration_sec / 60')) ?? 0;
            
        // Retell.ai costs approximately 0.10€ per minute
        return $minutes * 0.10;
    }
    
    private function createBillingLineItems($snapshotId, $snapshot)
    {
        foreach ($snapshot['metrics'] as $branchMetrics) {
            // Call charges
            DB::table('billing_line_items')->insert([
                'billing_snapshot_id' => $snapshotId,
                'branch_id' => $branchMetrics['branch_id'],
                'item_type' => 'call_minutes',
                'description' => "Anrufminuten - {$branchMetrics['branch_name']}",
                'quantity' => $branchMetrics['calls']['total_duration_minutes'],
                'unit_price' => 0.15,
                'total' => $branchMetrics['revenue']['per_minute_charge'],
                'created_at' => now(),
            ]);
            
            // Appointment charges
            DB::table('billing_line_items')->insert([
                'billing_snapshot_id' => $snapshotId,
                'branch_id' => $branchMetrics['branch_id'],
                'item_type' => 'appointments',
                'description' => "Gebuchte Termine - {$branchMetrics['branch_name']}",
                'quantity' => $branchMetrics['appointments']['completed_appointments'],
                'unit_price' => 2.00,
                'total' => $branchMetrics['revenue']['per_appointment_charge'],
                'created_at' => now(),
            ]);
            
            // Base fee
            if ($branchMetrics['revenue']['base_fee'] > 0) {
                DB::table('billing_line_items')->insert([
                    'billing_snapshot_id' => $snapshotId,
                    'branch_id' => $branchMetrics['branch_id'],
                    'item_type' => 'base_fee',
                    'description' => "Grundgebühr - {$branchMetrics['branch_name']}",
                    'quantity' => 1,
                    'unit_price' => $branchMetrics['revenue']['base_fee'],
                    'total' => $branchMetrics['revenue']['base_fee'],
                    'created_at' => now(),
                ]);
            }
        }
    }
    
    /**
     * Finalize snapshot - make it immutable
     */
    public function finalizeSnapshot($snapshotId)
    {
        $snapshot = DB::table('billing_snapshots')->find($snapshotId);
        
        if ($snapshot->is_finalized) {
            throw new \Exception('Snapshot already finalized');
        }
        
        // Create backup before finalizing
        DB::table('billing_snapshots_archive')->insert([
            'original_id' => $snapshotId,
            'snapshot_data' => $snapshot->snapshot_data,
            'checksum' => $snapshot->checksum,
            'archived_at' => now(),
        ]);
        
        // Mark as finalized
        DB::table('billing_snapshots')
            ->where('id', $snapshotId)
            ->update([
                'is_finalized' => true,
                'finalized_at' => now(),
            ]);
            
        Log::info('Billing snapshot finalized', ['snapshot_id' => $snapshotId]);
    }
    
    /**
     * Verify snapshot integrity
     */
    public function verifySnapshotIntegrity($snapshotId)
    {
        $snapshot = DB::table('billing_snapshots')->find($snapshotId);
        
        $calculatedChecksum = hash('sha256', $snapshot->snapshot_data);
        
        if ($calculatedChecksum !== $snapshot->checksum) {
            Log::critical('Billing snapshot integrity check failed', [
                'snapshot_id' => $snapshotId,
                'expected' => $snapshot->checksum,
                'calculated' => $calculatedChecksum,
            ]);
            
            throw new \Exception('Snapshot integrity check failed!');
        }
        
        return true;
    }
}