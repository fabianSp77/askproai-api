<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        $this->createTableIfNotExists('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunde_id')->constrained('customers')->onDelete('cascade');
            $table->string('system');
            $this->addJsonColumn($table, 'zugangsdaten', false);
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropTableIfExists('integrations');
    }
};
