<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First clean up any remaining duplicates (just in case)
        if (config('database.default') === 'mysql') {
            $duplicates = DB::select("
                SELECT company_id, email, COUNT(*) as count, 
                       GROUP_CONCAT(id ORDER BY created_at DESC) as ids
                FROM staff 
                WHERE email IS NOT NULL AND email != ''
                GROUP BY company_id, email 
                HAVING COUNT(*) > 1
            ");
        } else {
            // SQLite doesn't support ORDER BY in GROUP_CONCAT
            $duplicates = DB::select("
                SELECT company_id, email, COUNT(*) as count, 
                       GROUP_CONCAT(id) as ids
                FROM staff 
                WHERE email IS NOT NULL AND email != ''
                GROUP BY company_id, email 
                HAVING COUNT(*) > 1
            ");
        }
        
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup->ids);
            // Keep the first one (newest), delete the rest
            $keepId = $ids[0];
            $deleteIds = array_slice($ids, 1);
            
            foreach ($deleteIds as $deleteId) {
                // Transfer any appointments to the keeper
                DB::table('appointments')
                    ->where('staff_id', $deleteId)
                    ->update(['staff_id' => $keepId]);
                
                // Transfer any service assignments
                $existingServices = DB::table('staff_services')
                    ->where('staff_id', $keepId)
                    ->pluck('service_id')
                    ->toArray();
                    
                $toTransfer = DB::table('staff_services')
                    ->where('staff_id', $deleteId)
                    ->whereNotIn('service_id', $existingServices)
                    ->get();
                    
                foreach ($toTransfer as $service) {
                    DB::table('staff_services')->insert([
                        'staff_id' => $keepId,
                        'service_id' => $service->service_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                DB::table('staff_services')->where('staff_id', $deleteId)->delete();
                
                // Delete the duplicate
                DB::table('staff')->where('id', $deleteId)->delete();
            }
        }
        
        // Add unique constraint on company_id + email (only where email is not null)
        Schema::table('staff', function (Blueprint $table) {
            // Create a partial unique index that only applies when email is not null
            $table->unique(['company_id', 'email'], 'staff_company_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropUnique('staff_company_email_unique');
        });
    }
};