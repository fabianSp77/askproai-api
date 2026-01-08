<?php

use App\Models\Company;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Archives existing service case categories and seeds Thomas's new
     * incident categorization system.
     *
     * Strategy:
     * 1. Create fallback category for each company
     * 2. Reassign all existing ServiceCases to fallback
     * 3. Archive existing categories (mark inactive, rename)
     * 4. Run ThomasIncidentCategoriesSeeder
     */
    public function up(): void
    {
        Log::info('Migration: Starting migration to Thomas categories');

        DB::transaction(function () {
            $companies = Company::where('is_active', true)->get();

            foreach ($companies as $company) {
                Log::info("Processing company: {$company->name} (ID: {$company->id})");

                // Step 1: Create fallback category
                $fallback = ServiceCaseCategory::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'slug' => 'general-archived-fallback'
                    ],
                    [
                        'name' => 'General (Archived Fallback)',
                        'default_case_type' => ServiceCase::TYPE_INQUIRY,
                        'default_priority' => ServiceCase::PRIORITY_NORMAL,
                        'is_active' => false, // Inactive - just for historical data
                        'sort_order' => 9999,
                        'confidence_threshold' => 0.50,
                        'intent_keywords' => ['archived', 'fallback'],
                    ]
                );

                Log::info("Created fallback category (ID: {$fallback->id}) for company {$company->id}");

                // Step 2: Reassign all existing ServiceCases to fallback
                $casesUpdated = ServiceCase::where('company_id', $company->id)
                    ->whereNotNull('category_id')
                    ->where('category_id', '!=', $fallback->id)
                    ->update(['category_id' => $fallback->id]);

                Log::info("Reassigned {$casesUpdated} service cases to fallback category for company {$company->id}");

                // Step 3: Archive existing categories
                $timestamp = now()->format('YmdHis');
                $categoriesArchived = ServiceCaseCategory::where('company_id', $company->id)
                    ->where('id', '!=', $fallback->id)
                    ->update([
                        'is_active' => false,
                        'slug' => DB::raw("CONCAT(slug, '_archived_{$timestamp}')"),
                        'name' => DB::raw("CONCAT(name, ' [ARCHIVED]')"),
                    ]);

                Log::info("Archived {$categoriesArchived} categories for company {$company->id}");
            }
        });

        // Step 4: Run the new seeder
        Log::info('Running ThomasIncidentCategoriesSeeder...');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ThomasIncidentCategoriesSeeder']);
        Log::info('ThomasIncidentCategoriesSeeder completed');

        Log::info('Migration: Completed successfully');
    }

    /**
     * Reverse the migrations.
     *
     * Restores archived categories and deletes Thomas's categories.
     */
    public function down(): void
    {
        Log::info('Migration: Rolling back to pre-Thomas categories');

        DB::transaction(function () {
            // Step 1: Restore archived categories
            $restored = ServiceCaseCategory::where('name', 'LIKE', '%[ARCHIVED]%')
                ->update([
                    'is_active' => true,
                    'name' => DB::raw("REPLACE(name, ' [ARCHIVED]', '')"),
                    'slug' => DB::raw("SUBSTRING_INDEX(slug, '_archived_', 1)"),
                ]);

            Log::info("Restored {$restored} archived categories");

            // Step 2: Delete Thomas categories by slug patterns
            $deleted = ServiceCaseCategory::where(function ($query) {
                $query->where('slug', 'LIKE', 'n1-%')
                    ->orWhere('slug', 'LIKE', 'n2-%')
                    ->orWhere('slug', 'LIKE', 'v1-%')
                    ->orWhere('slug', 'LIKE', 'srv1-%')
                    ->orWhere('slug', 'LIKE', 'm365-1-%')
                    ->orWhere('slug', 'LIKE', 'sec-1-%')
                    ->orWhere('slug', 'LIKE', 'uc-1-%')
                    ->orWhere('slug', '=', 'network-connectivity')
                    ->orWhere('slug', '=', 'wan-internet')
                    ->orWhere('slug', '=', 'remote-access-vpn')
                    ->orWhere('slug', '=', 'server-virtualization-vdi')
                    ->orWhere('slug', '=', 'fileshares-rds')
                    ->orWhere('slug', '=', 'microsoft-365-collaboration')
                    ->orWhere('slug', '=', 'onedrive')
                    ->orWhere('slug', '=', 'security-email-security')
                    ->orWhere('slug', '=', 'phishing-spoofing')
                    ->orWhere('slug', '=', 'unified-communications-voip')
                    ->orWhere('slug', '=', 'endgeraete-rufprofile')
                    ->orWhere('slug', '=', 'general')
                    ->orWhere('slug', '=', 'allgemeine-anfrage');
            })->delete();

            Log::info("Deleted {$deleted} Thomas categories");

            // Step 3: Delete fallback categories
            $fallbackDeleted = ServiceCaseCategory::where('slug', 'general-archived-fallback')->delete();
            Log::info("Deleted {$fallbackDeleted} fallback categories");
        });

        Log::info('Migration: Rollback completed');
    }
};
