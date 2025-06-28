<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        // Create datev_configurations table
        if (!Schema::hasTable('datev_configurations')) {
            $this->createTableIfNotExists('datev_configurations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->string('consultant_number', 7)->nullable();
                $table->string('client_number', 5)->nullable();
                $table->string('export_format', 10)->default('EXTF');
                $this->addJsonColumn($table, 'account_mapping', true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->unique('company_id');
            });
        }
        
        // Create tax_threshold_monitoring table
        if (!Schema::hasTable('tax_threshold_monitoring')) {
            $this->createTableIfNotExists('tax_threshold_monitoring', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->year('year');
                $table->decimal('annual_revenue', 12, 2)->default(0);
                $table->boolean('threshold_exceeded')->default(false);
                $table->date('notification_sent_at')->nullable();
                $table->timestamps();
                
                $table->unique(['company_id', 'year']);
            });
        }
    }
    
    public function down()
    {
        $this->dropTableIfExists('tax_threshold_monitoring');
        $this->dropTableIfExists('datev_configurations');
    }
};