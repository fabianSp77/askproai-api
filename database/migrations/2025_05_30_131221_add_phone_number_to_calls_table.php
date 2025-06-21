<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            // Felder die der Controller erwartet, aber möglicherweise fehlen
            if (!Schema::hasColumn('calls', 'phone_number')) {
                $table->string('phone_number')->nullable();
            }
            if (!Schema::hasColumn('calls', 'call_time')) {
                $table->timestamp('call_time')->nullable();
            }
            if (!Schema::hasColumn('calls', 'call_duration')) {
                $table->integer('call_duration')->nullable();
            }
            if (!Schema::hasColumn('calls', 'disconnect_reason')) {
                $table->string('disconnect_reason')->nullable();
            }
            if (!Schema::hasColumn('calls', 'type')) {
                $table->string('type')->nullable()->default('inbound');
            }
            if (!Schema::hasColumn('calls', 'cost')) {
                $table->decimal('cost', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('calls', 'successful')) {
                $table->boolean('successful')->default(true);
            }
            if (!Schema::hasColumn('calls', 'user_sentiment')) {
                $table->string('user_sentiment')->nullable();
            }
            if (!Schema::hasColumn('calls', 'summary')) {
                $table->text('summary')->nullable();
            }
            if (!Schema::hasColumn('calls', 'transcript')) {
                $table->text('transcript')->nullable();
            }
            if (!Schema::hasColumn('calls', 'raw_data')) {
                $this->addJsonColumn($table, 'raw_data', true);
            }
            
            // Kundenbezogene Felder
            if (!Schema::hasColumn('calls', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('calls', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('calls', 'telefonnummer')) {
                $table->string('telefonnummer')->nullable();
            }
            
            // Terminbezogene Felder
            if (!Schema::hasColumn('calls', 'dienstleistung')) {
                $table->string('dienstleistung')->nullable();
            }
            if (!Schema::hasColumn('calls', 'datum_termin')) {
                $table->string('datum_termin')->nullable();
            }
            if (!Schema::hasColumn('calls', 'uhrzeit_termin')) {
                $table->string('uhrzeit_termin')->nullable();
            }
            if (!Schema::hasColumn('calls', 'grund')) {
                $table->text('grund')->nullable();
            }
            if (!Schema::hasColumn('calls', 'behandlung_dauer')) {
                $table->string('behandlung_dauer')->nullable();
            }
            if (!Schema::hasColumn('calls', 'rezeptstatus')) {
                $table->string('rezeptstatus')->nullable();
            }
            if (!Schema::hasColumn('calls', 'versicherungsstatus')) {
                $table->string('versicherungsstatus')->nullable();
            }
            if (!Schema::hasColumn('calls', 'haustiere_name')) {
                $table->string('haustiere_name')->nullable();
            }
            if (!Schema::hasColumn('calls', 'notiz')) {
                $table->text('notiz')->nullable();
            }
            
            // Branch/Company Zuordnung
            if (!Schema::hasColumn('calls', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable();
            }
            if (!Schema::hasColumn('calls', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
            }
            if (!Schema::hasColumn('calls', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('calls', function (Blueprint $table) {
            $columns = [
                'phone_number', 'call_time', 'call_duration', 'disconnect_reason',
                'type', 'cost', 'successful', 'user_sentiment', 'summary', 
                'transcript', 'raw_data', 'name', 'email', 'telefonnummer',
                'dienstleistung', 'datum_termin', 'uhrzeit_termin', 'grund',
                'behandlung_dauer', 'rezeptstatus', 'versicherungsstatus',
                'haustiere_name', 'notiz', 'branch_id', 'company_id', 'customer_id'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('calls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
