<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_service_assignments')) {
            $this->createTableIfNotExists('staff_service_assignments', function (Blueprint $table) {
                $table->id();
                $table->char('staff_id', 36);
                $table->bigInteger('calcom_event_type_id')->unsigned();
                $table->timestamps();
                
                $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
                $table->foreign('calcom_event_type_id')->references('id')->on('calcom_event_types')->onDelete('cascade');
                
                $table->unique(['staff_id', 'calcom_event_type_id']);
            });
        }
    }

    public function down(): void
    {
        $this->dropTableIfExists('staff_service_assignments');
    }
};
