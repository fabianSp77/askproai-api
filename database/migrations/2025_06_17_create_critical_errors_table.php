<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        $this->createTableIfNotExists('critical_errors', function (Blueprint $table) {
            $table->id();
            $table->uuid('trace_id')->index();
            $table->string('error_class');
            $table->text('error_message');
            $table->integer('error_code')->nullable();
            $table->string('file');
            $table->integer('line');
            $this->addJsonColumn($table, 'context', false);
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
        $this->dropTableIfExists('critical_errors');
    }
};