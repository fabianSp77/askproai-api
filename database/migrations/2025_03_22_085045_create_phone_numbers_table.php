<?php
use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void {
        $this->createTableIfNotExists('phone_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->index();
            $table->string('number')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { $this->dropTableIfExists('phone_numbers'); }
};
