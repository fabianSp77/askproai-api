<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff')) {
            $this->createTableIfNotExists('staff', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('branch_id')->index();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff')) {
            $this->dropTableIfExists('staff');
        }
    }
};
