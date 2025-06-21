<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->uuid('uuid')->nullable();
            $table->index('uuid');
        });
        
        // Bestehende Einträge mit UUIDs versehen
        $branches = DB::table('branches')->get();
        foreach ($branches as $branch) {
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['uuid' => Str::uuid()]);
        }
        
        // UUID-Feld auf NOT NULL setzen
        Schema::table('branches', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
