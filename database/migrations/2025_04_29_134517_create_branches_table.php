<?php
use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void {
        $this->createTableIfNotExists('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('city')->nullable();
            $table->string('phone_number')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { $this->dropTableIfExists('branches'); }
};
