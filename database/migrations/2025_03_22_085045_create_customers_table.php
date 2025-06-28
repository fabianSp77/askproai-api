<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        $this->createTableIfNotExists('customers', function (Blueprint $table) {
            $table->id();                       // BIGINT UNSIGNED AUTO_INCREMENT
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTableIfExists('customers');
    }
};
