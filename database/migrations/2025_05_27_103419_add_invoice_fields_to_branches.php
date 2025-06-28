<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('invoice_recipient')->default(false);
            $table->string('invoice_name')->nullable();
            $table->string('invoice_email')->nullable();
            $table->string('invoice_address')->nullable();
            $table->string('invoice_phone')->nullable();
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
        
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_recipient',
                'invoice_name',
                'invoice_email',
                'invoice_address',
                'invoice_phone',
            ]);
        });
    }
};
