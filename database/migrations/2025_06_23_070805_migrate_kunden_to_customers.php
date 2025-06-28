<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if kunden table exists and has data
        if (Schema::hasTable('kunden')) {
            $kundenCount = DB::table('kunden')->count();
            
            if ($kundenCount > 0) {
                // Migrate data from kunden to customers
                $kunden = DB::table('kunden')->get();
                
                foreach ($kunden as $kunde) {
                    // Check if customer already exists with same email or phone
                    $existingCustomer = DB::table('customers')
                        ->where(function($query) use ($kunde) {
                            if (!empty($kunde->email)) {
                                $query->where('email', $kunde->email);
                            }
                            if (!empty($kunde->telefonnummer)) {
                                $query->orWhere('phone', $kunde->telefonnummer);
                            }
                        })
                        ->first();
                    
                    if (!$existingCustomer) {
                        // Map kunden fields to customers fields
                        DB::table('customers')->insert([
                            'name' => $kunde->name,
                            'email' => $kunde->email,
                            'phone' => $kunde->telefonnummer,
                            'notes' => $kunde->notizen,
                            'company_id' => 1, // Default company ID - adjust as needed
                            'created_at' => $kunde->created_at ?? now(),
                            'updated_at' => $kunde->updated_at ?? now(),
                        ]);
                        
                        echo "Migrated kunde: {$kunde->name}\n";
                    } else {
                        echo "Skipped duplicate kunde: {$kunde->name} (already exists)\n";
                    }
                }
                
                echo "Migration completed. Migrated $kundenCount records.\n";
            }
            
            // Update foreign key references before dropping kunden table
            
            // Drop foreign key constraints
            if (Schema::hasColumn('calls', 'kunde_id')) {
                Schema::table('calls', function (Blueprint $table) {
                    $table->dropForeign(['kunde_id']);
                });
            }
            
            if (Schema::hasColumn('users', 'kunde_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['kunde_id']);
                });
            }
            
            // Drop the kunden table after successful migration
            Schema::dropIfExists('kunden');
            echo "Dropped kunden table.\n";
            
            // Drop the now-unused kunde_id columns
            if (Schema::hasColumn('calls', 'kunde_id')) {
                Schema::table('calls', function (Blueprint $table) {
                    $table->dropColumn('kunde_id');
                });
            }
            
            if (Schema::hasColumn('users', 'kunde_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('kunde_id');
                });
            }
        } else {
            echo "Kunden table does not exist. Nothing to migrate.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        // Recreate kunden table if needed
        if (!Schema::hasTable('kunden')) {
            Schema::create('kunden', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('telefonnummer')->nullable();
                $table->text('notizen')->nullable();
                $table->timestamps();
            });
            
            echo "Recreated kunden table. Data restoration requires backup.\n";
        }
    }
};