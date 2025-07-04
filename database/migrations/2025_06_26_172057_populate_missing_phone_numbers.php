<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create phone_numbers records from branches that have phone numbers
        $branches = DB::table('branches')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->where('is_active', true)
            ->get();
            
        foreach ($branches as $branch) {
            // Check if phone number already exists
            $exists = DB::table('phone_numbers')
                ->where('number', $branch->phone_number)
                ->exists();
                
            if (!$exists) {
                try {
                    DB::table('phone_numbers')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'branch_id' => $branch->id,
                        'company_id' => $branch->company_id,
                        'number' => $branch->phone_number,
                        'is_active' => true,
                        'is_primary' => true,
                        'type' => 'main',
                        'retell_agent_id' => $branch->retell_agent_id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info('Created phone_numbers record for branch', [
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name,
                        'phone_number' => $branch->phone_number
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create phone_numbers record', [
                        'branch_id' => $branch->id,
                        'phone_number' => $branch->phone_number,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // 2. Update phone_numbers that have branch_id but missing company_id
        DB::table('phone_numbers')
            ->whereNotNull('branch_id')
            ->whereNull('company_id')
            ->update([
                'company_id' => DB::raw('(SELECT company_id FROM branches WHERE branches.id = phone_numbers.branch_id)'),
                'updated_at' => now()
            ]);
            
        // 3. Copy retell_agent_id from branches to phone_numbers where missing
        if (Schema::hasColumn('branches', 'retell_agent_id') && Schema::hasColumn('phone_numbers', 'retell_agent_id')) {
            if ($this->isSQLite()) {
                // SQLite doesn't support UPDATE with JOIN, use subquery
                DB::statement('
                    UPDATE phone_numbers 
                    SET retell_agent_id = (
                        SELECT retell_agent_id 
                        FROM branches 
                        WHERE branches.id = phone_numbers.branch_id
                        AND branches.retell_agent_id IS NOT NULL
                    ),
                    updated_at = datetime("now")
                    WHERE retell_agent_id IS NULL
                    AND EXISTS (
                        SELECT 1 
                        FROM branches 
                        WHERE branches.id = phone_numbers.branch_id
                        AND branches.retell_agent_id IS NOT NULL
                    )
                ');
            } else {
                DB::table('phone_numbers as pn')
                    ->join('branches as b', 'pn.branch_id', '=', 'b.id')
                    ->whereNull('pn.retell_agent_id')
                    ->whereNotNull('b.retell_agent_id')
                    ->update([
                        'pn.retell_agent_id' => DB::raw('b.retell_agent_id'),
                        'pn.updated_at' => now()
                    ]);
            }
        }
            
        // 4. Ensure at least one test phone number exists
        $testCompany = DB::table('companies')
            ->where('slug', 'test-company')
            ->orWhere('name', 'like', '%Test%')
            ->first();
            
        if ($testCompany) {
            $testBranch = DB::table('branches')
                ->where('company_id', $testCompany->id)
                ->first();
                
            if ($testBranch) {
                $testPhoneExists = DB::table('phone_numbers')
                    ->where('number', '+4915551234567')
                    ->exists();
                    
                if (!$testPhoneExists) {
                    DB::table('phone_numbers')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'branch_id' => $testBranch->id,
                        'company_id' => $testCompany->id,
                        'number' => '+4915551234567',
                        'is_active' => true,
                        'is_primary' => false,
                        'type' => 'test',
                        'retell_agent_id' => 'test_agent',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info('Created test phone number for development');
                }
            }
        }
        
        // 5. Log summary
        $totalPhoneNumbers = DB::table('phone_numbers')->count();
        $activePhoneNumbers = DB::table('phone_numbers')->where('is_active', true)->count();
        $phoneNumbersWithBranch = DB::table('phone_numbers')->whereNotNull('branch_id')->count();
        $phoneNumbersWithAgent = DB::table('phone_numbers')->whereNotNull('retell_agent_id')->count();
        
        Log::info('Phone numbers population complete', [
            'total' => $totalPhoneNumbers,
            'active' => $activePhoneNumbers,
            'with_branch' => $phoneNumbersWithBranch,
            'with_agent' => $phoneNumbersWithAgent
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is mostly additive, so we don't delete the created records
        // Only remove the test phone number if it exists
        DB::table('phone_numbers')
            ->where('number', '+4915551234567')
            ->where('type', 'test')
            ->delete();
    }
};