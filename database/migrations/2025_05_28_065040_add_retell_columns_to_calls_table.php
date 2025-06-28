<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRetellColumnsToCallsTable extends CompatibleMigration
{
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->string('audio_url')->nullable();
            $table->string('disconnection_reason')->nullable();
            $table->text('summary')->nullable();
            $table->string('sentiment')->nullable();
            $table->string('public_log_url')->nullable();
        });
    }

    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('audio_url');
            $table->dropColumn('disconnection_reason');
            $table->dropColumn('summary');
            $table->dropColumn('sentiment');
            $table->dropColumn('public_log_url');
        });
    }
}
