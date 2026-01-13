<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aggregate_invoices', function (Blueprint $table) {
            $table->bigInteger('discount_cents')->default(0)->after('subtotal_cents');
            $table->string('discount_description')->nullable()->after('discount_cents');
        });
    }

    public function down(): void
    {
        Schema::table('aggregate_invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_cents', 'discount_description']);
        });
    }
};
