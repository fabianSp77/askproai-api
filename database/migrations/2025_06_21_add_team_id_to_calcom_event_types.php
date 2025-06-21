<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->integer('team_id')->nullable()->after('calcom_numeric_event_type_id')
                ->comment('Cal.com Team ID for team event types');
            $table->boolean('is_team_event')->default(false)->after('team_id')
                ->comment('Whether this is a team event type');
            $table->index(['calcom_numeric_event_type_id', 'team_id']);
        });
        
        // Update existing event type 2563193 with team ID
        DB::table('calcom_event_types')
            ->where('calcom_numeric_event_type_id', 2563193)
            ->update([
                'team_id' => 39203,
                'is_team_event' => true,
                'updated_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->dropIndex(['calcom_numeric_event_type_id', 'team_id']);
            $table->dropColumn(['team_id', 'is_team_event']);
        });
    }
};