<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staff;
use App\Models\Company;
use App\Models\Branch;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncCalcomUsers extends Command
{
    protected $signature = 'calcom:sync-users 
                            {--company= : Company ID to sync for}
                            {--branch= : Specific branch ID to assign users to}
                            {--dry-run : Show what would be imported without making changes}
                            {--update-existing : Update existing staff records}
                            {--interactive : Interactive mode to select users}';
                            
    protected $description = 'Synchronize Cal.com users as staff members with improved mapping';

    public function handle()
    {
        $this->info('🔄 Starting Cal.com User Synchronization...');

        // Get company
        $companyId = $this->option('company');
        $company = $companyId ? Company::find($companyId) : Company::first();
        
        if (!$company) {
            $this->error('No company found! Please specify --company=ID or create a company first.');
            return 1;
        }

        // Initialize Cal.com service with company's API key
        $apiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : null;
        if (!$apiKey) {
            $this->error('Company has no Cal.com API key configured!');
            return 1;
        }

        $calcomService = new CalcomV2Service($apiKey);

        // Get branch
        $branchId = $this->option('branch');
        $branch = $branchId ? Branch::find($branchId) : $company->branches()->first();
        
        if (!$branch) {
            $this->error('No branch found! Please specify --branch=ID or create a branch first.');
            return 1;
        }

        $this->info("Syncing for Company: {$company->name}, Branch: {$branch->name}");

        // Fetch Cal.com users
        $response = $calcomService->getUsers();
        
        if (!$response || !isset($response['users'])) {
            $this->error('Failed to fetch users from Cal.com!');
            return 1;
        }

        $users = $response['users'];
        $this->info("Found " . count($users) . " Cal.com users");

        // Display users
        $this->table(
            ['ID', 'Name', 'Email', 'Username', 'Status'],
            collect($users)->map(function ($user) {
                $existingStaff = Staff::where('calcom_user_id', $user['id'])
                    ->orWhere('email', $user['email'])
                    ->first();
                
                return [
                    $user['id'] ?? 'N/A',
                    $user['name'] ?? 'N/A',
                    $user['email'] ?? 'N/A',
                    $user['username'] ?? 'N/A',
                    $existingStaff ? '✅ Exists' : '➕ New'
                ];
            })
        );

        // Interactive selection if requested
        $selectedUsers = $users;
        if ($this->option('interactive')) {
            $selectedUsers = $this->selectUsersInteractively($users);
        }

        // Confirm import
        if (!$this->option('dry-run')) {
            $count = count($selectedUsers);
            if (!$this->confirm("Import/update {$count} users?")) {
                return 0;
            }
        }

        // Import users
        $imported = 0;
        $updated = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($selectedUsers as $user) {
                if ($this->option('dry-run')) {
                    $this->info("[DRY-RUN] Would import: {$user['name']} (ID: {$user['id']})");
                    continue;
                }

                try {
                    $result = $this->importUser($user, $branch);
                    if ($result === 'created') {
                        $imported++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Failed to import {$user['name']}: " . $e->getMessage());
                }
            }

            DB::commit();
            
            $this->info("✨ Synchronization complete!");
            $this->info("   Created: {$imported} staff members");
            $this->info("   Updated: {$updated} staff members");
            if ($errors > 0) {
                $this->warn("   Errors: {$errors}");
            }

            // Trigger event type sync if requested
            if (!$this->option('dry-run') && $this->confirm('Sync event type assignments now?')) {
                $this->call('calcom:sync-event-type-users', ['--company' => $company->id]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Synchronization failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function selectUsersInteractively(array $users): array
    {
        $choices = [];
        foreach ($users as $index => $user) {
            $choices[$index] = "{$user['name']} ({$user['email']})";
        }

        $selected = $this->choice(
            'Select users to import (comma-separated numbers)',
            $choices,
            null,
            null,
            true
        );

        $selectedUsers = [];
        foreach ($selected as $choice) {
            $index = array_search($choice, $choices);
            if ($index !== false) {
                $selectedUsers[] = $users[$index];
            }
        }

        return $selectedUsers;
    }

    protected function importUser(array $userData, Branch $branch): string
    {
        // Check for existing staff
        $existingStaff = Staff::where('calcom_user_id', $userData['id'])->first();
        
        if (!$existingStaff) {
            // Try to find by email
            $existingStaff = Staff::where('email', $userData['email'])->first();
        }

        // Extract name parts
        $nameParts = explode(' ', $userData['name'] ?? '', 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Prepare staff data
        $staffData = [
            'name' => $userData['name'] ?? 'Unknown User',
            'email' => $userData['email'] ?? null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'calcom_user_id' => $userData['id'],
            'calcom_calendar_link' => $userData['username'] ?? null,
            'is_active' => true,
            'is_bookable' => true, // Cal.com users are bookable by default
        ];

        if ($existingStaff) {
            if ($this->option('update-existing')) {
                // Update existing staff
                $existingStaff->update($staffData);
                $this->info("✅ Updated: {$existingStaff->name}");
                return 'updated';
            } else {
                // Just ensure Cal.com ID is set
                if (!$existingStaff->calcom_user_id) {
                    $existingStaff->calcom_user_id = $userData['id'];
                    $existingStaff->save();
                    $this->info("✅ Linked: {$existingStaff->name} to Cal.com ID {$userData['id']}");
                    return 'updated';
                }
                $this->info("⏭️  Skipped: {$existingStaff->name} (already exists)");
                return 'skipped';
            }
        } else {
            // Create new staff
            $staffData['branch_id'] = $branch->id;
            $staff = Staff::create($staffData);
            $this->info("✅ Created: {$staff->name}");
            return 'created';
        }
    }
}