<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            if (! Schema::hasColumn('calls', 'customer_id')) {
                $t->foreignId('customer_id')->nullable()
                    ->constrained()->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            if (Schema::hasColumn('calls', 'customer_id')) {
                $t->dropForeign(['customer_id']);
                $t->dropColumn('customer_id');
            }
        });
    }
};
