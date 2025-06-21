<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->boolean('necessary_cookies')->default(true);
            $table->boolean('functional_cookies')->default(false);
            $table->boolean('analytics_cookies')->default(false);
            $table->boolean('marketing_cookies')->default(false);
            $this->addJsonColumn($table, 'consent_details', true);
            $table->timestamp('consented_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id', 'consented_at']);
            $table->index(['session_id', 'consented_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
    }
};
