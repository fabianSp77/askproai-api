<?php
use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->date('datum_termin')->nullable();
            $table->time('uhrzeit_termin')->nullable();
            $table->string('dienstleistung')->nullable();
        });
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
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['name', 'email', 'datum_termin', 'uhrzeit_termin', 'dienstleistung']);
        });
    }
};
