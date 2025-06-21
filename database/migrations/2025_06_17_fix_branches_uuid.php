<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any NULL uuid values with database-specific logic
        if ($this->isSQLite()) {
            // SQLite doesn't have UUID(), so we'll use PHP's Str::uuid()
            $branches = DB::table('branches')
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->get();
                
            foreach ($branches as $branch) {
                DB::table('branches')
                    ->where('id', $branch->id)
                    ->update(['uuid' => Str::uuid()->toString()]);
            }
        } else {
            // MySQL/PostgreSQL can use UUID()
            DB::table('branches')
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->update(['uuid' => DB::raw('UUID()')]);
        }
        
        // For SQLite, we can't set a default UUID function, so we'll handle it in the model
        if (!$this->isSQLite()) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('uuid')->default(DB::raw('(UUID())'))->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('uuid')->nullable()->change();
        });
    }
};