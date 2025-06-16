<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('calls', 'phone_number_id')) {
                $table->unsignedBigInteger('phone_number_id')->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('calls', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->after('phone_number_id');
            }
            if (!Schema::hasColumn('calls', 'cost_cents')) {
                $table->unsignedInteger('cost_cents')->nullable()->after('duration_sec');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'phone_number_id', 'agent_id', 'cost_cents']);
        });
    }
};
