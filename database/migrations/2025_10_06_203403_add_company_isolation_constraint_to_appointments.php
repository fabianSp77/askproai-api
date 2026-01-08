<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add validation to ensure appointments.company_id always matches customer.company_id
     * Prevents multi-tenant isolation breaches
     */
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support triggers)
        if (app()->environment('testing')) {
            return;
        }

        // Add database triggers to enforce multi-tenant isolation
        // Ensures appointments.company_id always matches customer.company_id
        DB::unprepared('
            CREATE TRIGGER before_appointment_insert_company_check
            BEFORE INSERT ON appointments
            FOR EACH ROW
            BEGIN
                DECLARE customer_company_id BIGINT;

                SELECT company_id INTO customer_company_id
                FROM customers
                WHERE id = NEW.customer_id;

                IF NEW.company_id != customer_company_id THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Appointment company_id must match customer company_id (multi-tenant isolation)";
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER before_appointment_update_company_check
            BEFORE UPDATE ON appointments
            FOR EACH ROW
            BEGIN
                DECLARE customer_company_id BIGINT;

                IF NEW.customer_id != OLD.customer_id OR NEW.company_id != OLD.company_id THEN
                    SELECT company_id INTO customer_company_id
                    FROM customers
                    WHERE id = NEW.customer_id;

                    IF NEW.company_id != customer_company_id THEN
                        SIGNAL SQLSTATE "45000"
                        SET MESSAGE_TEXT = "Appointment company_id must match customer company_id (multi-tenant isolation)";
                    END IF;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS before_appointment_insert_company_check');
        DB::unprepared('DROP TRIGGER IF EXISTS before_appointment_update_company_check');
    }
};
