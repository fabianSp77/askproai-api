<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        $this->createTableIfNotExists('dashboard_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $this->addJsonColumn($table, 'widget_settings', true);
            $this->addJsonColumn($table, 'layout_settings', true);
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        $this->dropTableIfExists('dashboard_configurations');
    }
};