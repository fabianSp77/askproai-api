<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database triggers for automatic data consistency enforcement.
     */
    public function up(): void
    {
        // Trigger 1: Auto-set direction to 'inbound' if NULL
        DB::unprepared("
            CREATE OR REPLACE FUNCTION set_default_call_direction()
            RETURNS TRIGGER AS $$
            BEGIN
                -- If direction is NULL, default to 'inbound'
                IF NEW.direction IS NULL THEN
                    NEW.direction := 'inbound';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            CREATE TRIGGER before_insert_call_set_direction
                BEFORE INSERT ON calls
                FOR EACH ROW
                EXECUTE FUNCTION set_default_call_direction();
        ");

        // Trigger 2: Auto-sync customer link status when customer_id is set
        DB::unprepared("
            CREATE OR REPLACE FUNCTION sync_customer_link_status()
            RETURNS TRIGGER AS $$
            BEGIN
                -- If customer_id is set, mark as linked
                IF NEW.customer_id IS NOT NULL AND
                   (OLD.customer_id IS NULL OR OLD.customer_id IS DISTINCT FROM NEW.customer_id) THEN

                    NEW.customer_link_status := 'linked';
                    NEW.customer_linked_at := NOW();

                    -- Set method if not already set
                    IF NEW.customer_link_method IS NULL THEN
                        NEW.customer_link_method := 'phone_match';
                    END IF;

                    -- Set confidence if not already set
                    IF NEW.customer_link_confidence IS NULL THEN
                        NEW.customer_link_confidence := 100.00;
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            CREATE TRIGGER before_update_call_sync_customer_link
                BEFORE UPDATE ON calls
                FOR EACH ROW
                EXECUTE FUNCTION sync_customer_link_status();
        ");

        // Trigger 3: Validate session outcome consistency
        DB::unprepared("
            CREATE OR REPLACE FUNCTION validate_session_outcome_consistency()
            RETURNS TRIGGER AS $$
            BEGIN
                -- If session_outcome is 'appointment_booked', ensure appointment_made is true
                IF NEW.session_outcome = 'appointment_booked' AND NEW.appointment_made = FALSE THEN
                    RAISE WARNING 'Inconsistency detected: session_outcome=appointment_booked but appointment_made=FALSE for call_id=%', NEW.id;

                    -- Auto-correct: set appointment_made to TRUE
                    NEW.appointment_made := TRUE;

                    -- Log to alerts table
                    INSERT INTO data_consistency_alerts (
                        alert_type,
                        severity,
                        entity_type,
                        entity_id,
                        description,
                        detected_at,
                        auto_corrected,
                        corrected_at,
                        created_at,
                        updated_at
                    ) VALUES (
                        'session_outcome_mismatch',
                        'warning',
                        'call',
                        NEW.id,
                        format('Auto-corrected appointment_made to TRUE for call %s (session_outcome was appointment_booked)', NEW.retell_call_id),
                        NOW(),
                        TRUE,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;

                -- If appointment_made is TRUE but session_outcome is not appointment_booked
                IF NEW.appointment_made = TRUE AND
                   NEW.session_outcome IS DISTINCT FROM 'appointment_booked' AND
                   NEW.session_outcome IS NOT NULL THEN

                    RAISE WARNING 'Inconsistency detected: appointment_made=TRUE but session_outcome!=appointment_booked for call_id=%', NEW.id;

                    -- Auto-correct: set session_outcome
                    NEW.session_outcome := 'appointment_booked';

                    INSERT INTO data_consistency_alerts (
                        alert_type,
                        severity,
                        entity_type,
                        entity_id,
                        description,
                        detected_at,
                        auto_corrected,
                        corrected_at,
                        created_at,
                        updated_at
                    ) VALUES (
                        'appointment_made_mismatch',
                        'warning',
                        'call',
                        NEW.id,
                        format('Auto-corrected session_outcome to appointment_booked for call %s (appointment_made was TRUE)', NEW.retell_call_id),
                        NOW(),
                        TRUE,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            CREATE TRIGGER before_insert_or_update_call_validate_outcome
                BEFORE INSERT OR UPDATE ON calls
                FOR EACH ROW
                EXECUTE FUNCTION validate_session_outcome_consistency();
        ");

        // Trigger 4: Sync appointment link status when appointment is created/deleted
        DB::unprepared("
            CREATE OR REPLACE FUNCTION sync_appointment_link_status()
            RETURNS TRIGGER AS $$
            BEGIN
                -- When appointment is created with call_id, update call's appointment_link_status
                IF TG_OP = 'INSERT' AND NEW.call_id IS NOT NULL THEN
                    UPDATE calls
                    SET
                        appointment_link_status = 'linked',
                        appointment_linked_at = NOW(),
                        appointment_made = TRUE,
                        session_outcome = COALESCE(session_outcome, 'appointment_booked')
                    WHERE id = NEW.call_id;

                    -- Log successful linking
                    INSERT INTO data_consistency_alerts (
                        alert_type,
                        severity,
                        entity_type,
                        entity_id,
                        description,
                        detected_at,
                        auto_corrected,
                        corrected_at,
                        created_at,
                        updated_at
                    ) VALUES (
                        'appointment_linked',
                        'info',
                        'appointment',
                        NEW.id,
                        format('Appointment %s automatically linked to call %s via trigger', NEW.id, NEW.call_id),
                        NOW(),
                        TRUE,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;

                -- When appointment is deleted, update call's status
                IF TG_OP = 'DELETE' AND OLD.call_id IS NOT NULL THEN
                    UPDATE calls
                    SET
                        appointment_link_status = 'unlinked',
                        appointment_made = FALSE
                    WHERE id = OLD.call_id;

                    -- Log unlinking
                    INSERT INTO data_consistency_alerts (
                        alert_type,
                        severity,
                        entity_type,
                        entity_id,
                        description,
                        detected_at,
                        auto_corrected,
                        corrected_at,
                        created_at,
                        updated_at
                    ) VALUES (
                        'appointment_unlinked',
                        'warning',
                        'appointment',
                        OLD.id,
                        format('Appointment %s deleted - call %s flags updated via trigger', OLD.id, OLD.call_id),
                        NOW(),
                        TRUE,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;

                RETURN COALESCE(NEW, OLD);
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            CREATE TRIGGER after_appointment_change_sync_call
                AFTER INSERT OR DELETE ON appointments
                FOR EACH ROW
                EXECUTE FUNCTION sync_appointment_link_status();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers
        DB::unprepared("DROP TRIGGER IF EXISTS before_insert_call_set_direction ON calls;");
        DB::unprepared("DROP TRIGGER IF EXISTS before_update_call_sync_customer_link ON calls;");
        DB::unprepared("DROP TRIGGER IF EXISTS before_insert_or_update_call_validate_outcome ON calls;");
        DB::unprepared("DROP TRIGGER IF EXISTS after_appointment_change_sync_call ON appointments;");

        // Drop functions
        DB::unprepared("DROP FUNCTION IF EXISTS set_default_call_direction();");
        DB::unprepared("DROP FUNCTION IF EXISTS sync_customer_link_status();");
        DB::unprepared("DROP FUNCTION IF EXISTS validate_session_outcome_consistency();");
        DB::unprepared("DROP FUNCTION IF EXISTS sync_appointment_link_status();");
    }
};
