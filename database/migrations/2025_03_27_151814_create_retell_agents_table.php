<?php
use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        // Tabelle existiert bereits – nur falls sie fehlt neu anlegen
        if (!Schema::hasTable('retell_agents')) {
            $this->createTableIfNotExists('retell_agents', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        $this->dropTableIfExists('retell_agents');
    }
};
