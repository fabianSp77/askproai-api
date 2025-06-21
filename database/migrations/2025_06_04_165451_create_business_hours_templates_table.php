<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_hours_templates')) {
            $this->createTableIfNotExists('business_hours_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $this->addJsonColumn($table, 'hours', true);
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $this->dropTableIfExists('business_hours_templates');
    }
};
