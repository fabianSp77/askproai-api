<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_portal_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->enum('status', [
                'new',
                'in_progress',
                'callback_scheduled',
                'not_reached_1',
                'not_reached_2',
                'not_reached_3',
                'completed',
                'abandoned',
                'requires_action'
            ])->default('new');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->json('tags')->nullable();
            $table->datetime('next_action_date')->nullable();
            $table->text('internal_notes')->nullable();
            $table->integer('follow_up_count')->default(0);
            $table->text('resolution_notes')->nullable();
            $table->datetime('callback_scheduled_at')->nullable();
            $table->unsignedBigInteger('callback_scheduled_by')->nullable();
            $table->text('callback_notes')->nullable();
            $table->json('status_history')->nullable();
            $table->datetime('assigned_at')->nullable();
            $table->timestamps();
            
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('portal_users')->onDelete('set null');
            $table->foreign('callback_scheduled_by')->references('id')->on('portal_users')->onDelete('set null');
            $table->index(['status', 'assigned_to']);
            $table->index('next_action_date');
            $table->index('callback_scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_portal_data');
    }
};