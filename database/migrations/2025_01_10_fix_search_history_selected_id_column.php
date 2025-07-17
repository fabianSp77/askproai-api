<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, clear any existing selected_id values to avoid truncation errors
        DB::table('search_history')->update(['selected_id' => null]);
        
        Schema::table('search_history', function (Blueprint $table) {
            // Change selected_id from bigint to varchar to support UUIDs
            $table->string('selected_id', 255)->nullable()->change();
        });
    }

    public function down()
    {
        // Clear any UUID values that won't fit in bigint
        DB::table('search_history')->whereRaw('LENGTH(selected_id) > 10')->update(['selected_id' => null]);
        
        Schema::table('search_history', function (Blueprint $table) {
            $table->unsignedBigInteger('selected_id')->nullable()->change();
        });
    }
};