<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add missing columns to existing notification_templates table
        
        if (!Schema::hasTable('notification_templates')) {
            return;
        }

        Schema::table('notification_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_templates', 'name')) {
                $table->string('name');
            }
            if (!Schema::hasColumn('notification_templates', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('notification_templates', 'type')) {
                $table->string('type');
            }
            if (!Schema::hasColumn('notification_templates', 'subject')) {
                $table->json('subject')->nullable();
            }
            if (!Schema::hasColumn('notification_templates', 'content')) {
                $table->json('content')->nullable();
            }
            if (!Schema::hasColumn('notification_templates', 'priority')) {
                $table->integer('priority')->default(5);
            }

            // Add indexes if they don't exist
            $indexes = collect(Schema::getIndexes('notification_templates'))->pluck('name')->toArray();
            if (!in_array('notification_templates_channel_type_index', $indexes)) {
                $table->index(['channel', 'type']);
            }
        });

        // Customer notification preferences
        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                    $table->string('channel');
                    $table->boolean('enabled')->default(true);
                    $table->json('types')->nullable();
                    $table->string('language')->default('de');
                    $table->json('quiet_hours')->nullable();
                    $table->time('preferred_time')->nullable();
                    $table->json('frequency_limits')->nullable();
                    $table->boolean('marketing_consent')->default(false);
                    $table->timestamp('marketing_consent_at')->nullable();
                    $table->string('unsubscribe_token')->nullable();
                    $table->timestamps();

                    $table->unique(['customer_id', 'channel']);
                    $table->index('unsubscribe_token');
                });
        }

        // Enhanced notification queue
        if (!Schema::hasTable('notification_queue')) {
            Schema::create('notification_queue', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->morphs('notifiable');
                $table->string('channel');
                $table->string('template_key')->nullable();
                $table->string('type');
                $table->json('data');
                $table->json('recipient');
                $table->string('language')->default('de');
                $table->integer('priority')->default(5);
                $table->string('status')->default('pending');
                $table->integer('attempts')->default(0);
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->json('metadata')->nullable();
                $table->text('error_message')->nullable();
                $table->string('provider_message_id')->nullable();
                $table->decimal('cost', 8, 4)->nullable();
                $table->timestamps();

                $table->index(['status', 'scheduled_at']);
                $table->index(['channel', 'type']);
                $table->index('notifiable_type');
                $table->index('notifiable_id');
                $table->index('sent_at');
            });
        }

        // Notification delivery logs (detailed tracking)
        if (!Schema::hasTable('notification_deliveries')) {
            Schema::create('notification_deliveries', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('notification_queue_id')->constrained('notification_queue');
                    $table->string('event');
                    $table->json('data')->nullable();
                    $table->string('provider')->nullable();
                    $table->string('provider_status')->nullable();
                    $table->text('provider_response')->nullable();
                    $table->string('ip_address')->nullable();
                    $table->string('user_agent')->nullable();
                    $table->timestamp('occurred_at');
                    $table->timestamps();

                    $table->index(['notification_queue_id', 'event']);
                    $table->index('occurred_at');
                });
        }

        // SMS/WhatsApp provider configurations
        if (!Schema::hasTable('notification_providers')) {
            Schema::create('notification_providers', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('company_id')->nullable()->constrained();
                    $table->string('name');
                    $table->string('type');
                    $table->string('channel');
                    $table->json('credentials');
                    $table->json('config')->nullable();
                    $table->boolean('is_default')->default(false);
                    $table->boolean('is_active')->default(true);
                    $table->integer('priority')->default(1);
                    $table->decimal('balance', 10, 2)->nullable();
                    $table->integer('rate_limit')->nullable();
                    $table->json('allowed_countries')->nullable();
                    $table->json('statistics')->nullable();
                    $table->timestamps();

                    $table->index(['company_id', 'channel', 'is_active']);
                });
        }

        // Notification analytics
        if (!Schema::hasTable('notification_analytics')) {
            Schema::create('notification_analytics', function (Blueprint $table) {
                    $table->id();
                    $table->date('date');
                    $table->foreignId('company_id')->nullable()->constrained();
                    $table->string('channel');
                    $table->string('type');
                    $table->integer('sent_count')->default(0);
                    $table->integer('delivered_count')->default(0);
                    $table->integer('opened_count')->default(0);
                    $table->integer('clicked_count')->default(0);
                    $table->integer('failed_count')->default(0);
                    $table->integer('bounced_count')->default(0);
                    $table->decimal('total_cost', 10, 4)->default(0);
                    $table->float('delivery_rate')->nullable();
                    $table->float('open_rate')->nullable();
                    $table->float('click_rate')->nullable();
                    $table->integer('avg_delivery_time')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamps();

                    $table->unique(['date', 'company_id', 'channel', 'type']);
                    $table->index(['company_id', 'date']);
                });
        }

        // Unsubscribe list
        if (!Schema::hasTable('notification_unsubscribes')) {
            Schema::create('notification_unsubscribes', function (Blueprint $table) {
                    $table->id();
                    $table->string('email')->nullable();
                    $table->string('phone')->nullable();
                    $table->string('channel');
                    $table->string('type')->nullable();
                    $table->foreignId('customer_id')->nullable()->constrained();
                    $table->string('reason')->nullable();
                    $table->text('feedback')->nullable();
                    $table->string('method');
                    $table->string('ip_address')->nullable();
                    $table->timestamps();

                    $table->index(['email', 'channel']);
                    $table->index(['phone', 'channel']);
                    $table->index('customer_id');
                });
        }

        // Add notification fields to customers table
        
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'notification_language')) {
                $table->string('notification_language', 5)->default('de');
            }
            if (!Schema::hasColumn('customers', 'whatsapp_number')) {
                $table->string('whatsapp_number')->nullable();
            }
            if (!Schema::hasColumn('customers', 'push_token')) {
                $table->text('push_token')->nullable();
            }
            if (!Schema::hasColumn('customers', 'push_platform')) {
                $table->string('push_platform')->nullable();
            }
        });

        // Add notification fields to appointments table
        
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'notification_status')) {
                $table->json('notification_status')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'reminder_count')) {
                $table->integer('reminder_count')->default(0);
            }
            if (!Schema::hasColumn('appointments', 'last_reminder_at')) {
                $table->timestamp('last_reminder_at')->nullable();
            }
        });
    }

    public function down()
    {
        // Remove columns from appointments
        Schema::table('appointments', function (Blueprint $table) {
            $columns = ['notification_status', 'reminder_count', 'last_reminder_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Remove columns from customers
        Schema::table('customers', function (Blueprint $table) {
            $columns = ['notification_language', 'whatsapp_number', 'push_token', 'push_platform'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Drop tables
        Schema::dropIfExists('notification_unsubscribes');
        Schema::dropIfExists('notification_analytics');
        Schema::dropIfExists('notification_providers');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('notification_preferences');

        // Remove added columns from notification_templates
        Schema::table('notification_templates', function (Blueprint $table) {
            $columns = ['name', 'description', 'type', 'subject', 'content', 'priority'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('notification_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};