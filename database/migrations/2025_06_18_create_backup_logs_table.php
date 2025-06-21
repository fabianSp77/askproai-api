<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // full, incremental, critical
            $table->string('filepath');
            $table->bigInteger('size')->default(0);
            $table->string('status', 20); // success, failed
            $table->text('error')->nullable();
            $table->timestamp('created_at');
            $table->index(['type', 'status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('backup_logs');
    }
};