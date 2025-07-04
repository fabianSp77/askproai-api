<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixPhoneNumberResolution extends Command
{
    protected $signature = 'fix:phone-resolution';
    protected $description = 'Fix phone number resolution issues';

    public function handle()
    {
        $this->info('🔧 Fixing Phone Number Resolution Issues');
        $this->info('=======================================');
        
        // 1. Check actual column names
        $this->checkDatabaseSchema();
        
        // 2. Fix PhoneNumberResolver
        $this->fixPhoneNumberResolver();
        
        // 3. Test the resolution
        $this->testResolution();
        
        $this->info('');
        $this->info('✅ Phone number resolution fixes applied!');
    }
    
    private function checkDatabaseSchema()
    {
        $this->info('');
        $this->info('1. Checking Database Schema:');
        
        // Check branches table
        if (Schema::hasTable('branches')) {
            $columns = Schema::getColumnListing('branches');
            $phoneColumn = in_array('phone', $columns) ? 'phone' : 
                          (in_array('phone_number', $columns) ? 'phone_number' : null);
            
            if ($phoneColumn) {
                $this->info("   ✅ Branches table has phone column: '$phoneColumn'");
                
                // Check sample data
                $branch = DB::table('branches')
                    ->whereNotNull($phoneColumn)
                    ->where($phoneColumn, '!=', '')
                    ->first();
                    
                if ($branch) {
                    $this->info("   Sample phone: {$branch->$phoneColumn}");
                }
            } else {
                $this->error("   ❌ Branches table missing phone column");
            }
        }
        
        // Check phone_numbers table
        if (Schema::hasTable('phone_numbers')) {
            $columns = Schema::getColumnListing('phone_numbers');
            $numberColumn = in_array('number', $columns) ? 'number' : 
                           (in_array('phone_number', $columns) ? 'phone_number' : null);
            
            if ($numberColumn) {
                $this->info("   ✅ Phone_numbers table has column: '$numberColumn'");
            }
        }
    }
    
    private function fixPhoneNumberResolver()
    {
        $this->info('');
        $this->info('2. Fixing PhoneNumberResolver.php:');
        
        $filePath = app_path('Services/PhoneNumberResolver.php');
        if (!File::exists($filePath)) {
            $this->error('   ❌ PhoneNumberResolver.php not found!');
            return;
        }
        
        $content = File::get($filePath);
        $originalContent = $content;
        
        // Fix 1: Update branches table column reference
        // Find the actual column name
        $branchPhoneColumn = 'phone'; // Based on our earlier checks
        if (Schema::hasTable('branches')) {
            $columns = Schema::getColumnListing('branches');
            if (in_array('phone', $columns)) {
                $branchPhoneColumn = 'phone';
            } elseif (in_array('phone_number', $columns)) {
                $branchPhoneColumn = 'phone_number';
            }
        }
        
        // Replace the branch phone lookup
        $content = preg_replace(
            "/->where\('phone_number',/",
            "->where('{$branchPhoneColumn}',",
            $content
        );
        
        // Fix 2: Add normalized phone number comparison for branches
        $content = str_replace(
            "->where('{$branchPhoneColumn}', \$phoneNumber)",
            "->where(function(\$query) use (\$phoneNumber, \$normalized) {
                \$query->where('{$branchPhoneColumn}', \$phoneNumber)
                      ->orWhere('{$branchPhoneColumn}', \$normalized);
            })",
            $content
        );
        
        // Fix 3: Ensure normalizePhoneNumber handles spaces properly
        // The current implementation already removes non-numeric chars, so it should work
        
        // Fix 4: Add better logging
        $content = str_replace(
            "Log::info('Phone number resolved to branch',",
            "Log::info('[PhoneResolver] Phone number resolved to branch',",
            $content
        );
        
        // Fix 5: Handle both 'number' and 'phone_number' columns in phone_numbers table
        $content = preg_replace(
            '/->where\(\'number\', \$normalized\)\s*->orWhere\(\'number\', \$phoneNumber\)/',
            '->where(function($q) use ($normalized, $phoneNumber) {
                    $numberColumn = Schema::hasColumn(\'phone_numbers\', \'number\') ? \'number\' : \'phone_number\';
                    $q->where($numberColumn, $normalized)
                      ->orWhere($numberColumn, $phoneNumber);
                })',
            $content
        );
        
        if ($content !== $originalContent) {
            File::put($filePath, $content);
            $this->info('   ✅ Fixed PhoneNumberResolver.php');
        } else {
            $this->info('   ℹ️ No changes needed in PhoneNumberResolver.php');
        }
    }
    
    private function testResolution()
    {
        $this->info('');
        $this->info('3. Testing Phone Resolution:');
        
        // Test with a known phone number
        $testNumber = '+493083793369';
        
        try {
            $resolver = app(\App\Services\PhoneNumberResolver::class);
            
            // Create test webhook data
            $webhookData = [
                'to' => $testNumber,
                'to_number' => $testNumber,
                'from' => '+491234567890'
            ];
            
            $result = $resolver->resolveFromWebhook($webhookData);
            
            if ($result && $result['branch_id']) {
                $this->info("   ✅ Successfully resolved phone {$testNumber}");
                $this->info("      Branch ID: {$result['branch_id']}");
                $this->info("      Company ID: {$result['company_id']}");
                $this->info("      Method: {$result['resolution_method']}");
            } else {
                $this->warn("   ⚠️ Could not resolve phone {$testNumber}");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error testing resolution: " . $e->getMessage());
        }
    }
}