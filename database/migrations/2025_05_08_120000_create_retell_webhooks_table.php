<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retell_webhooks', function (Blueprint $t) {
            $t->id();
            $t->string('event_type')->index();
            $t->string('call_id')->nullable()->index();
            $t->json('payload');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retell_webhooks');
    }
};
