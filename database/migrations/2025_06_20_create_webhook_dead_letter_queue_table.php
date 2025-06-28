<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        $this->createTableIfNotExists('webhook_dead_letter_queue', function (Blueprint $table) {
            $table->id();
            $table->string('correlation_id')->index();
            $table->string('event_type');
            $this->addJsonColumn($table, 'payload', false);
            $this->addJsonColumn($table, 'headers', false);
            $table->text('error');
            $table->integer('retry_count')->default(0);
            $table->timestamp('failed_at');
            $table->timestamp('last_retry_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            // Indexes for monitoring
            $table->index(['event_type', 'failed_at']);
            $table->index(['resolved', 'failed_at']);
        });
    }

    public function down()
    {
        $this->dropTableIfExists('webhook_dead_letter_queue');
    }
};