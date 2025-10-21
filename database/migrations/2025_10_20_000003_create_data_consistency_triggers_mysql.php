<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database triggers for automatic data consistency enforcement (MySQL/MariaDB).
     */
    public function up(): void
    {
        // Trigger 1: Auto-set direction to 'inbound' if NULL on INSERT
        DB::unprepared("
            CREATE TRIGGER before_insert_call_set_direction
            BEFORE INSERT ON calls
            FOR EACH ROW
            BEGIN
                -- If direction is NULL, default to 'inbound'
                IF NEW.direction IS NULL OR NEW.direction = '' THEN
                    SET NEW.direction = 'inbound';
                END IF;
            END
        ");

        // Trigger 2: Auto-sync customer link status when customer_id is set on UPDATE
        DB::unprepared("
            CREATE TRIGGER before_update_call_sync_customer_link
            BEFORE UPDATE ON calls
            FOR EACH ROW
            BEGIN
                -- If customer_id is set (newly added or changed), mark as linked
                IF NEW.customer_id IS NOT NULL AND
                   (OLD.customer_id IS NULL OR OLD.customer_id != NEW.customer_id) THEN

                    SET NEW.customer_link_status = 'linked';
                    SET NEW.customer_linked_at = NOW();

                    -- Set method if not already set
                    IF NEW.customer_link_method IS NULL THEN
                        SET NEW.customer_link_method = 'phone_match';
                    END IF;

                    -- Set confidence if not already set
                    IF NEW.customer_link_confidence IS NULL THEN
                        SET NEW.customer_link_confidence = 100.00;
                    END IF;
                END IF;
            END
        ");

        // Trigger 3: Validate session outcome consistency on INSERT
        DB::unprepared("
            CREATE TRIGGER before_insert_call_validate_outcome
            BEFORE INSERT ON calls
            FOR EACH ROW
            BEGIN
                -- If session_outcome is 'appointment_booked', ensure appointment_made is true
                IF NEW.session_outcome = 'appointment_booked' AND NEW.appointment_made = 0 THEN
                    SET NEW.appointment_made = 1;

                    -- Log to alerts table (async via INSERT, won't block trigger)
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
                        CONCAT('Auto-corrected appointment_made to 1 for new call (session_outcome was appointment_booked)'),
                        NOW(),
                        1,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;

                -- If appointment_made is TRUE but session_outcome is not appointment_booked
                IF NEW.appointment_made = 1 AND
                   NEW.session_outcome IS NOT NULL AND
                   NEW.session_outcome != 'appointment_booked' THEN

                    SET NEW.session_outcome = 'appointment_booked';

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
                        CONCAT('Auto-corrected session_outcome to appointment_booked for new call (appointment_made was 1)'),
                        NOW(),
                        1,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ");

        // Trigger 4: Validate session outcome consistency on UPDATE
        DB::unprepared("
            CREATE TRIGGER before_update_call_validate_outcome
            BEFORE UPDATE ON calls
            FOR EACH ROW
            BEGIN
                -- If session_outcome is 'appointment_booked', ensure appointment_made is true
                IF NEW.session_outcome = 'appointment_booked' AND NEW.appointment_made = 0 THEN
                    SET NEW.appointment_made = 1;

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
                        CONCAT('Auto-corrected appointment_made to 1 for call ', COALESCE(NEW.retell_call_id, NEW.id), ' (session_outcome was appointment_booked)'),
                        NOW(),
                        1,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;

                -- If appointment_made is TRUE but session_outcome is not appointment_booked
                IF NEW.appointment_made = 1 AND
                   NEW.session_outcome IS NOT NULL AND
                   NEW.session_outcome != 'appointment_booked' THEN

                    SET NEW.session_outcome = 'appointment_booked';

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
                        CONCAT('Auto-corrected session_outcome to appointment_booked for call ', COALESCE(NEW.retell_call_id, NEW.id), ' (appointment_made was 1)'),
                        NOW(),
                        1,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ");

        // Trigger 5: Sync appointment link status when appointment is created
        DB::unprepared("
            CREATE TRIGGER after_insert_appointment_sync_call
            AFTER INSERT ON appointments
            FOR EACH ROW
            BEGIN
                -- When appointment is created with call_id, update call's flags
                IF NEW.call_id IS NOT NULL THEN
                    UPDATE calls
                    SET
                        appointment_link_status = 'linked',
                        appointment_linked_at = NOW(),
                        appointment_made = 1,
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
                        CONCAT('Appointment ', NEW.id, ' automatically linked to call ', NEW.call_id, ' via trigger'),
                        NOW(),
                        1,
                        NOW(),
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ");

        // Trigger 6: Sync appointment link status when appointment is deleted
        DB::unprepared("
            CREATE TRIGGER after_delete_appointment_sync_call
            AFTER DELETE ON appointments
            FOR EACH ROW
            BEGIN
                -- When appointment is deleted, check if call should be marked as unlinked
                IF OLD.call_id IS NOT NULL THEN
                    -- Only update if there are no other appointments for this call
                    IF (SELECT COUNT(*) FROM appointments WHERE call_id = OLD.call_id AND deleted_at IS NULL) = 0 THEN
                        UPDATE calls
                        SET
                            appointment_link_status = 'unlinked',
                            appointment_made = 0
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
                            CONCAT('Appointment ', OLD.id, ' deleted - call ', OLD.call_id, ' flags updated via trigger'),
                            NOW(),
                            1,
                            NOW(),
                            NOW(),
                            NOW()
                        );
                    END IF;
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers (MySQL syntax)
        DB::unprepared("DROP TRIGGER IF EXISTS before_insert_call_set_direction;");
        DB::unprepared("DROP TRIGGER IF EXISTS before_update_call_sync_customer_link;");
        DB::unprepared("DROP TRIGGER IF EXISTS before_insert_call_validate_outcome;");
        DB::unprepared("DROP TRIGGER IF EXISTS before_update_call_validate_outcome;");
        DB::unprepared("DROP TRIGGER IF EXISTS after_insert_appointment_sync_call;");
        DB::unprepared("DROP TRIGGER IF EXISTS after_delete_appointment_sync_call;");
    }
};
