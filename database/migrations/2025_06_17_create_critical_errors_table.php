<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('critical_errors', function (Blueprint $table) {
            $table->id();
            $table->uuid('trace_id')->index();
            $table->string('error_class');
            $table->text('error_message');
            $table->integer('error_code')->nullable();
            $table->string('file');
            $table->integer('line');
            $table->json('context');
            $table->timestamp('created_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            
            // Indexes for monitoring
            $table->index(['error_class', 'created_at']);
            $table->index(['error_code', 'created_at']);
            $table->index(['created_at', 'resolved_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('critical_errors');
    }
};