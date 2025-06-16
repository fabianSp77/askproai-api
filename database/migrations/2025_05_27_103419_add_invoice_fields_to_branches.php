<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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

    public function down(): void
    {
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
