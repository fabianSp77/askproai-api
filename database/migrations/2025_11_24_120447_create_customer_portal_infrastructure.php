<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the complete Customer Portal MVP infrastructure:
     * - user_invitations: Token-based user invitation system
     * - appointment_audit_logs: Immutable audit trail for appointments
     * - invitation_email_queue: Email delivery queue with retry mechanism
     * - Modifies appointments: Optimistic locking + Cal.com sync status
     * - Modifies companies: Pilot program flag
     * - Modifies users: Unique staff constraint
     * - Modifies appointment_reservations: Reschedule support
     */
    public function up(): void
    {
        // =====================================================================
        // TABLE 1: user_invitations
        // =====================================================================
        if (!Schema::hasTable('user_invitations')) {
            Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');
            $table->string('email', 255);
            $table->foreignId('role_id')
                ->constrained('roles')
                ->onDelete('cascade');
            $table->foreignId('invited_by')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('token', 64)->unique();  // SHA256 hash
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->json('metadata')->nullable();  // { branch_id, staff_id, custom_fields }
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'accepted_at']);
            $table->index('token');
            $table->index('expires_at');

                // Note: MySQL doesn't support partial unique indexes
                // Uniqueness for pending invitations (email+company where accepted_at IS NULL)
                // will be enforced at the application level via UserInvitationObserver
            });
        }

        // =====================================================================
        // TABLE 2: appointment_audit_logs
        // =====================================================================
        if (!Schema::hasTable('appointment_audit_logs')) {
            Schema::create('appointment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                ->constrained('appointments')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('action', 50);  // created, rescheduled, cancelled, restored
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at');  // NO updated_at - immutable

            // Indexes
            $table->index(['appointment_id', 'created_at']);
                $table->index(['user_id', 'action', 'created_at']);
                $table->index('action');
            });
        }

        // =====================================================================
        // TABLE 3: invitation_email_queue
        // =====================================================================
        if (!Schema::hasTable('invitation_email_queue')) {
            Schema::create('invitation_email_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_invitation_id')
                ->constrained('user_invitations')
                ->onDelete('cascade');
            $table->string('status', 20)->default('pending');  // pending, sent, failed, cancelled
            $table->smallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

                // Indexes
                $table->index(['status', 'next_attempt_at']);
            });
        }

        // =====================================================================
        // MODIFY: appointments table
        // =====================================================================
        Schema::table('appointments', function (Blueprint $table) {
            // Optimistic locking
            if (!Schema::hasColumn('appointments', 'version')) {
                $table->integer('version')->default(1)->after('id');
            }
            if (!Schema::hasColumn('appointments', 'last_modified_at')) {
                $table->timestamp('last_modified_at')->useCurrent()->after('updated_at');
            }
            if (!Schema::hasColumn('appointments', 'last_modified_by')) {
                $table->foreignId('last_modified_by')
                    ->nullable()
                    ->after('last_modified_at')
                    ->constrained('users')
                    ->onDelete('set null');
            }

            // Cal.com sync status tracking
            // Note: calcom_sync_status already exists from 2025_10_11 migration (as ENUM)
            // We'll reuse that column instead of creating a new VARCHAR

            if (!Schema::hasColumn('appointments', 'calcom_last_sync_at')) {
                $table->timestamp('calcom_last_sync_at')->nullable()->after('calcom_sync_status');
            }
            if (!Schema::hasColumn('appointments', 'calcom_sync_error')) {
                $table->text('calcom_sync_error')->nullable()->after('calcom_last_sync_at');
            }
            if (!Schema::hasColumn('appointments', 'calcom_sync_attempts')) {
                $table->smallInteger('calcom_sync_attempts')->default(0)->after('calcom_sync_error');
            }
        });

        // Add indexes separately (check if they don't exist)
        if (!$this->indexExists('appointments', 'appointments_id_version_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['id', 'version'], 'appointments_id_version_index');
            });
        }

        if (!$this->indexExists('appointments', 'appointments_calcom_sync_status_calcom_last_sync_at_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['calcom_sync_status', 'calcom_last_sync_at'], 'appointments_calcom_sync_status_calcom_last_sync_at_index');
            });
        }

        // =====================================================================
        // MODIFY: companies table
        // =====================================================================
        Schema::table('companies', function (Blueprint $table) {
            // Pilot program mechanism
            if (!Schema::hasColumn('companies', 'is_pilot')) {
                $table->boolean('is_pilot')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('companies', 'pilot_enabled_at')) {
                $table->timestamp('pilot_enabled_at')->nullable()->after('is_pilot');
            }
            if (!Schema::hasColumn('companies', 'pilot_enabled_by')) {
                $table->foreignId('pilot_enabled_by')
                    ->nullable()
                    ->after('pilot_enabled_at')
                    ->constrained('users')
                    ->onDelete('set null');
            }
            if (!Schema::hasColumn('companies', 'pilot_notes')) {
                $table->text('pilot_notes')->nullable()->after('pilot_enabled_by');
            }
        });

        // Add index separately
        if (!$this->indexExists('companies', 'companies_is_pilot_id_index')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->index(['is_pilot', 'id'], 'companies_is_pilot_id_index');
            });
        }

        // =====================================================================
        // MODIFY: users table
        // =====================================================================
        // MySQL doesn't support partial indexes with WHERE clause
        // We'll enforce uniqueness at application level via UserObserver
        // Add a regular index for performance
        if (Schema::hasColumn('users', 'staff_id') && !$this->indexExists('users', 'users_staff_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('staff_id');
            });
        }

        // =====================================================================
        // MODIFY: appointment_reservations table (if exists)
        // =====================================================================
        if (Schema::hasTable('appointment_reservations')) {
            Schema::table('appointment_reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('appointment_reservations', 'original_appointment_id')) {
                    $table->foreignId('original_appointment_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('appointments')
                        ->onDelete('cascade');
                }
                if (!Schema::hasColumn('appointment_reservations', 'reservation_type')) {
                    $table->string('reservation_type', 20)->default('new_booking')->after('original_appointment_id');
                    // Values: new_booking, reschedule, cancel_hold
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Modify appointment_reservations
        if (Schema::hasTable('appointment_reservations')) {
            Schema::table('appointment_reservations', function (Blueprint $table) {
                $table->dropForeign(['original_appointment_id']);
                $table->dropColumn(['original_appointment_id', 'reservation_type']);
            });
        }

        // Modify users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['staff_id']);
        });

        // Modify companies
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['pilot_enabled_by']);
            $table->dropIndex(['is_pilot', 'id']);
            $table->dropColumn(['is_pilot', 'pilot_enabled_at', 'pilot_enabled_by', 'pilot_notes']);
        });

        // Modify appointments
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['last_modified_by']);
            $table->dropIndex(['id', 'version']);
            $table->dropIndex(['calcom_sync_status', 'calcom_last_sync_at']);
            $table->dropColumn([
                'version',
                'last_modified_at',
                'last_modified_by',
                'calcom_sync_status',
                'calcom_last_sync_at',
                'calcom_sync_error',
                'calcom_sync_attempts'
            ]);
        });

        // Drop tables
        Schema::dropIfExists('invitation_email_queue');
        Schema::dropIfExists('appointment_audit_logs');
        Schema::dropIfExists('user_invitations');
    }

    /**
     * Check if index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            return collect($indexes)->pluck('name')->contains($index);
        } catch (\Exception $e) {
            return false;
        }
    }
};
