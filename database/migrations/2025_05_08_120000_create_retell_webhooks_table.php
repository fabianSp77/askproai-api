<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        $this->createTableIfNotExists('retell_webhooks', function (Blueprint $t) {
            $t->id();
            $t->string('event_type')->index();
            $t->string('call_id')->nullable()->index();
            $t->json('payload');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTableIfExists('retell_webhooks');
    }
};
