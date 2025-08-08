<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add optimal composite index for company_pricing_tiers
        Schema::table('company_pricing_tiers', function (Blueprint $table) {
            // Main lookup index for pricing queries
            $table->index(
                ['company_id', 'child_company_id', 'pricing_type', 'is_active'],
                'idx_company_pricing_optimal'
            );
            
            // Index for child company lookups
            $table->index(
                ['child_company_id', 'pricing_type', 'is_active'],
                'idx_child_pricing_lookup'
            );
            
            // Index for date-based margin calculations
            $table->index(['created_at', 'company_id'], 'idx_pricing_date_company');
        });

        // Add indexes for pricing_margins table
        Schema::table('pricing_margins', function (Blueprint $table) {
            // Index for date-based reporting
            $table->index(
                ['calculated_date', 'company_pricing_tier_id'],
                'idx_margin_date_tier'
            );
        });

        // Optimize calls table for new queries (if indexes don't exist)
        $this->addCallsIndexesIfNotExists();

        // Add indexes for campaign_targets
        if (Schema::hasTable('campaign_targets')) {
            Schema::table('campaign_targets', function (Blueprint $table) {
                $table->index(
                    ['campaign_id', 'status', 'created_at'],
                    'idx_campaign_status_date'
                );
                
                $table->index(
                    ['phone_number', 'campaign_id'],
                    'idx_target_phone_campaign'
                );
            });
        }

        // Add indexes for outbound_call_templates
        if (Schema::hasTable('outbound_call_templates')) {
            Schema::table('outbound_call_templates', function (Blueprint $table) {
                $table->index(
                    ['company_id', 'template_type', 'is_active'],
                    'idx_template_company_type'
                );
            });
        }
    }

    /**
     * Add calls table indexes if they don't exist
     */
    private function addCallsIndexesIfNotExists(): void
    {
        // Check current index count
        $indexCount = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'calls'
        ")[0]->count;

        // Only add if we have room (MySQL limit is 64)
        if ($indexCount < 60) {
            Schema::table('calls', function (Blueprint $table) {
                // For margin report queries
                try {
                    $table->index(
                        ['company_id', 'created_at', 'duration_minutes'],
                        'idx_calls_company_date_duration'
                    );
                } catch (\Exception $e) {
                    // Index might already exist
                }

                // For direction-based aggregations
                try {
                    $table->index(
                        ['company_id', 'direction', 'created_at'],
                        'idx_calls_company_direction_date'
                    );
                } catch (\Exception $e) {
                    // Index might already exist
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_pricing_tiers', function (Blueprint $table) {
            $table->dropIndex('idx_company_pricing_optimal');
            $table->dropIndex('idx_child_pricing_lookup');
            $table->dropIndex('idx_pricing_date_company');
        });

        Schema::table('pricing_margins', function (Blueprint $table) {
            $table->dropIndex('idx_margin_date_tier');
        });

        if (Schema::hasTable('campaign_targets')) {
            Schema::table('campaign_targets', function (Blueprint $table) {
                $table->dropIndex('idx_campaign_status_date');
                $table->dropIndex('idx_target_phone_campaign');
            });
        }

        if (Schema::hasTable('outbound_call_templates')) {
            Schema::table('outbound_call_templates', function (Blueprint $table) {
                $table->dropIndex('idx_template_company_type');
            });
        }

        // Note: We don't drop calls indexes as we're not sure which ones we added
    }
};