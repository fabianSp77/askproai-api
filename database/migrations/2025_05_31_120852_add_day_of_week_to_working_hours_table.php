<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_hours', function (Blueprint $table) {
            if (!Schema::hasColumn('working_hours', 'day_of_week')) {
                $table->unsignedTinyInteger('day_of_week')
                    ->after('staff_id')
                    ->default(1)
                    ->comment('1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday, 7=Sunday');
                
                $table->index('day_of_week');
            }
        });
    }

    public function down(): void
    {
        Schema::table('working_hours', function (Blueprint $table) {
            $table->dropIndex(['day_of_week']);
            $table->dropColumn('day_of_week');
        });
    }
};
