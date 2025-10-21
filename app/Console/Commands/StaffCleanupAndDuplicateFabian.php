<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StaffCleanupAndDuplicateFabian extends Command
{
    protected $signature = 'staff:cleanup-and-duplicate-fabian';
    protected $description = 'Clean up test staff and duplicate Fabian for Company 15';

    public function handle()
    {
        $this->info('=== STAFF CLEANUP & FABIAN DUPLICATION ===');
        $this->newLine();

        try {
            // Phase 1: Fix Fabian's branch_id
            $this->info('Phase 1: Fixing Fabian branch_id...');
            $fabian = Staff::find('9f47fda1-977c-47aa-a87a-0e8cbeaeb119');
            if ($fabian) {
                $oldBranchId = $fabian->branch_id;
                $fabian->update(['branch_id' => '34c4d48e-4753-4715-9c30-c55843a943e8']);
                $this->line("  ✅ Fabian (Company 1) branch_id: {$oldBranchId} → 34c4d48e-4753-4715-9c30-c55843a943e8");
            } else {
                $this->error("  ❌ Fabian not found!");
                return 1;
            }
            $this->newLine();

            // Phase 2: Get or duplicate Fabian for Company 15
            $this->info('Phase 2: Getting/Duplicating Fabian for Company 15...');
            $fabianCompany15 = Staff::where('company_id', 15)
                ->where('email', $fabian->email)
                ->first();

            if ($fabianCompany15) {
                $this->line("  ✅ Fabian already exists in Company 15 (ID: {$fabianCompany15->id})");
            } else {
                $fabianCompany15 = Staff::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'company_id' => 15,
                    'branch_id' => '9f4d5e2a-46f7-41b6-b81d-1532725381d4', // AskProAI Zentrale
                    'name' => $fabian->name,
                    'email' => $fabian->email,
                    'phone' => $fabian->phone,
                    'is_active' => true,
                    'active' => true,
                    'is_bookable' => $fabian->is_bookable ?? true,
                ]);
                $this->line("  ✅ New Fabian created for Company 15 (ID: {$fabianCompany15->id})");
            }
            $this->newLine();

            // Phase 3: Update Service 47 assignment
            $this->info('Phase 3: Updating Service 47 staff assignment...');

            // Use raw SQL to update the pivot table
            $updated = DB::table('service_staff')
                ->where('service_id', 47)
                ->where('staff_id', '9f47fda1-977c-47aa-a87a-0e8cbeaeb119')
                ->update(['staff_id' => $fabianCompany15->id]);

            if ($updated > 0) {
                $this->line("  ✅ Service 47: Updated staff assignment to new Fabian ({$fabianCompany15->id})");
            } else {
                $this->error("  ⚠️ No existing assignment found, inserting new one...");
                DB::table('service_staff')->insert([
                    'service_id' => 47,
                    'staff_id' => $fabianCompany15->id,
                    'is_primary' => false,
                    'can_book' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->line("  ✅ Service 47: Inserted new staff assignment");
            }
            $this->newLine();

            // Phase 4: Delete test staff
            $this->info('Phase 4: Deleting test staff...');
            $testStaffIds = [
                '8257bee8-75ee-4beb-a28c-66d0c777416a', // Frank Keller
                'fa61e2bd-f08f-4f49-837c-6b36a9af3096', // Heidrun Schuster
                '010be4a7-3468-4243-bb0a-2223b8e5878c', // Emma Williams
                'c4a19739-4824-46b2-8a50-72b9ca23e013', // David Martinez
                'ce3d932c-52d1-4c15-a7b9-686a29babf0a', // Michael Chen
                'f9d4d054-1ccd-4b60-87b9-c9772d17c892', // Dr. Sarah Johnson
            ];

            // Delete service_staff pivot entries
            DB::table('service_staff')->whereIn('staff_id', $testStaffIds)->delete();
            $this->line("  ✅ Removed service_staff pivot entries");

            // Soft delete staff
            Staff::whereIn('id', $testStaffIds)->delete();
            $testStaff = Staff::whereIn('id', $testStaffIds)->get();
            foreach ($testStaff as $staff) {
                $this->line("  ✅ Deleted: {$staff->name}");
            }
            $this->newLine();

            // Phase 5: Validation
            $this->info('Phase 5: Running validation checks...');

            $activeStaffCount = Staff::where('is_active', true)->count();
            $this->line("  Active Staff count: {$activeStaffCount}");

            $invalidBranchIds = Staff::where(DB::raw('LENGTH(branch_id)'), '<', 30)->count();
            $this->line("  Invalid branch_ids: {$invalidBranchIds} ✅");

            $service47Staff = Service::find(47)->staff()->count();
            $this->line("  Service 47 staff count: {$service47Staff} ✅");

            $orphanedPivots = DB::table('service_staff')
                ->whereNotExists(function ($q) {
                    $q->select('id')->from('staff')->whereRaw('staff.id = service_staff.staff_id');
                })->count();
            $this->line("  Orphaned pivot entries: {$orphanedPivots} ✅");

            $this->newLine();
            $this->info('=== CLEANUP COMPLETE ===');
            $this->line('✅ All operations successful!');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ ERROR: {$e->getMessage()}");
            $this->newLine();
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
