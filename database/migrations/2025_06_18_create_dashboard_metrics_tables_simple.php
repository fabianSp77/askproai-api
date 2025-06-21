<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        // Dashboard widget configurations per user
        if (!Schema::hasTable('dashboard_widget_settings')) {
            $this->createTableIfNotExists('dashboard_widget_settings', function (Blueprint $table) {
                $table->id();
                $table->string('user_id', 36); // UUID as string for now
                $table->string('dashboard_type', 50); // executive, operational, analytics
                $this->addJsonColumn($table, 'widget_order', true);
                $this->addJsonColumn($table, 'widget_visibility', true);
                $this->addJsonColumn($table, 'preferences', true);
                $table->timestamps();
                
                $table->index(['user_id', 'dashboard_type']);
            });
        }
    }

    public function down(): void
    {
        $this->dropTableIfExists('dashboard_widget_settings');
    }
};