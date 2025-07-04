<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

trait SimplifiedMigrations
{
    /**
     * Determine if simplified migrations should be used
     */
    protected function shouldUseSimplifiedMigrations(): bool
    {
        // Can be overridden in test classes
        return property_exists($this, 'useSimplifiedMigrations') ? $this->useSimplifiedMigrations : false;
    }
    
    /**
     * Run simplified migrations for faster test execution
     */
    protected function runSimplifiedMigrations(): void
    {
        // Core tables needed for most tests
        $this->createUsersTable();
        $this->createCompaniesTable();
        $this->createBranchesTable();
        $this->createCustomersTable();
        $this->createStaffTable();
        $this->createServicesTable();
        $this->createAppointmentsTable();
        $this->createCallsTable();
        $this->createPhoneNumbersTable();
        
        // Authentication tables
        $this->createPasswordResetTable();
        $this->createPersonalAccessTokensTable();
        
        // System tables
        $this->createFailedJobsTable();
        $this->createCacheTable();
        $this->createJobsTable();
        
        // Billing tables
        $this->createBillingPeriodsTable();
        $this->createSubscriptionsTable();
        $this->createInvoicesTable();
        $this->createBillingAlertConfigsTable();
        $this->createBillingAlertsTable();
    }
    
    /**
     * Drop all simplified tables
     */
    protected function dropSimplifiedTables(): void
    {
        $tables = [
            'billing_alerts', 'billing_alert_configs', 'invoices', 'subscriptions', 'billing_periods',
            'appointments', 'calls', 'phone_numbers', 'services', 'staff', 
            'customers', 'branches', 'companies', 'users',
            'password_reset_tokens', 'personal_access_tokens',
            'failed_jobs', 'cache', 'jobs'
        ];
        
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
    
    private function createUsersTable(): void
    {
        if (!Schema::hasTable('users')) {
            DB::statement('
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    email_verified_at TIMESTAMP NULL,
                    password VARCHAR(255) NOT NULL,
                    two_factor_secret TEXT NULL,
                    two_factor_recovery_codes TEXT NULL,
                    two_factor_confirmed_at TIMESTAMP NULL,
                    remember_token VARCHAR(100) NULL,
                    current_team_id INTEGER NULL,
                    profile_photo_path VARCHAR(2048) NULL,
                    company_id INTEGER NULL,
                    tenant_id INTEGER NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createCompaniesTable(): void
    {
        if (!Schema::hasTable('companies')) {
            DB::statement('
                CREATE TABLE companies (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NULL,
                    is_active BOOLEAN DEFAULT 1,
                    alerts_enabled BOOLEAN DEFAULT 1,
                    retell_api_key TEXT NULL,
                    retell_agent_id VARCHAR(255) NULL,
                    calcom_api_key TEXT NULL,
                    calcom_team_slug VARCHAR(255) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createBranchesTable(): void
    {
        if (!Schema::hasTable('branches')) {
            DB::statement('
                CREATE TABLE branches (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    is_active BOOLEAN DEFAULT 1,
                    calcom_event_type_id INTEGER NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createCustomersTable(): void
    {
        if (!Schema::hasTable('customers')) {
            DB::statement('
                CREATE TABLE customers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    first_name VARCHAR(255) NULL,
                    last_name VARCHAR(255) NULL,
                    email VARCHAR(255) NULL,
                    phone VARCHAR(255) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createStaffTable(): void
    {
        if (!Schema::hasTable('staff')) {
            DB::statement('
                CREATE TABLE staff (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    branch_id INTEGER NULL,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NULL,
                    is_active BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createServicesTable(): void
    {
        if (!Schema::hasTable('services')) {
            DB::statement('
                CREATE TABLE services (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    duration INTEGER DEFAULT 30,
                    price DECIMAL(10,2) NULL,
                    is_active BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createAppointmentsTable(): void
    {
        if (!Schema::hasTable('appointments')) {
            DB::statement('
                CREATE TABLE appointments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    branch_id INTEGER NOT NULL,
                    customer_id INTEGER NOT NULL,
                    staff_id INTEGER NULL,
                    service_id INTEGER NULL,
                    starts_at TIMESTAMP NOT NULL,
                    ends_at TIMESTAMP NOT NULL,
                    status VARCHAR(50) DEFAULT "scheduled",
                    notes TEXT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createCallsTable(): void
    {
        if (!Schema::hasTable('calls')) {
            DB::statement('
                CREATE TABLE calls (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NULL,
                    call_id VARCHAR(255) NULL,
                    retell_call_id VARCHAR(255) NULL,
                    from_number VARCHAR(255) NULL,
                    to_number VARCHAR(255) NULL,
                    status VARCHAR(50) NULL,
                    duration INTEGER NULL,
                    transcript TEXT NULL,
                    recording_url TEXT NULL,
                    appointment_id INTEGER NULL,
                    customer_id INTEGER NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createPhoneNumbersTable(): void
    {
        if (!Schema::hasTable('phone_numbers')) {
            DB::statement('
                CREATE TABLE phone_numbers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    branch_id INTEGER NULL,
                    number VARCHAR(255) NOT NULL,
                    type VARCHAR(50) DEFAULT "main",
                    is_active BOOLEAN DEFAULT 1,
                    retell_phone_number_id VARCHAR(255) NULL,
                    retell_agent_id VARCHAR(255) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createPasswordResetTable(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            DB::statement('
                CREATE TABLE password_reset_tokens (
                    email VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createPersonalAccessTokensTable(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            DB::statement('
                CREATE TABLE personal_access_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tokenable_type VARCHAR(255) NOT NULL,
                    tokenable_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    abilities TEXT NULL,
                    last_used_at TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createFailedJobsTable(): void
    {
        if (!Schema::hasTable('failed_jobs')) {
            DB::statement('
                CREATE TABLE failed_jobs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uuid VARCHAR(255) NOT NULL UNIQUE,
                    connection TEXT NOT NULL,
                    queue TEXT NOT NULL,
                    payload TEXT NOT NULL,
                    exception TEXT NOT NULL,
                    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');
        }
    }
    
    private function createCacheTable(): void
    {
        if (!Schema::hasTable('cache')) {
            DB::statement('
                CREATE TABLE cache (
                    key VARCHAR(255) NOT NULL PRIMARY KEY,
                    value TEXT NOT NULL,
                    expiration INTEGER NOT NULL
                )
            ');
        }
    }
    
    private function createJobsTable(): void
    {
        if (!Schema::hasTable('jobs')) {
            DB::statement('
                CREATE TABLE jobs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    queue VARCHAR(255) NOT NULL,
                    payload TEXT NOT NULL,
                    attempts INTEGER NOT NULL,
                    reserved_at INTEGER NULL,
                    available_at INTEGER NOT NULL,
                    created_at INTEGER NOT NULL
                )
            ');
        }
    }
    
    private function createBillingPeriodsTable(): void
    {
        if (!Schema::hasTable('billing_periods')) {
            DB::statement('
                CREATE TABLE billing_periods (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    branch_id INTEGER NULL,
                    subscription_id INTEGER NULL,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    status VARCHAR(50) DEFAULT "pending",
                    included_minutes INTEGER DEFAULT 0,
                    used_minutes INTEGER DEFAULT 0,
                    total_minutes INTEGER DEFAULT 0,
                    overage_minutes INTEGER DEFAULT 0,
                    base_fee DECIMAL(10,2) DEFAULT 0,
                    price_per_minute DECIMAL(10,4) DEFAULT 0,
                    overage_cost DECIMAL(10,2) DEFAULT 0,
                    total_cost DECIMAL(10,2) DEFAULT 0,
                    total_revenue DECIMAL(10,2) DEFAULT 0,
                    margin DECIMAL(10,2) DEFAULT 0,
                    margin_percentage DECIMAL(5,2) DEFAULT 0,
                    currency VARCHAR(3) DEFAULT "EUR",
                    is_invoiced BOOLEAN DEFAULT 0,
                    invoiced_at TIMESTAMP NULL,
                    invoice_id INTEGER NULL,
                    stripe_invoice_id VARCHAR(255) NULL,
                    stripe_invoice_created_at TIMESTAMP NULL,
                    is_prorated BOOLEAN DEFAULT 0,
                    proration_factor DECIMAL(5,4) DEFAULT 1.0,
                    metadata JSON NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createSubscriptionsTable(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            DB::statement('
                CREATE TABLE subscriptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    stripe_subscription_id VARCHAR(255) NULL,
                    status VARCHAR(50) DEFAULT "active",
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createInvoicesTable(): void
    {
        if (!Schema::hasTable('invoices')) {
            DB::statement('
                CREATE TABLE invoices (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    number VARCHAR(255) NOT NULL,
                    status VARCHAR(50) DEFAULT "draft",
                    total DECIMAL(10,2) DEFAULT 0,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createBillingAlertConfigsTable(): void
    {
        if (!Schema::hasTable('billing_alert_configs')) {
            DB::statement('
                CREATE TABLE billing_alert_configs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    alert_type VARCHAR(50) NOT NULL,
                    is_enabled BOOLEAN DEFAULT 1,
                    notification_channels JSON NULL,
                    notify_primary_contact BOOLEAN DEFAULT 1,
                    notify_billing_contact BOOLEAN DEFAULT 1,
                    thresholds JSON NULL,
                    advance_days INTEGER NULL,
                    metadata JSON NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
    
    private function createBillingAlertsTable(): void
    {
        if (!Schema::hasTable('billing_alerts')) {
            DB::statement('
                CREATE TABLE billing_alerts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    config_id INTEGER NULL,
                    severity VARCHAR(50) DEFAULT "info",
                    alert_type VARCHAR(50) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NULL,
                    status VARCHAR(50) DEFAULT "pending",
                    sent_at TIMESTAMP NULL,
                    acknowledged_at TIMESTAMP NULL,
                    acknowledged_by INTEGER NULL,
                    data JSON NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }
}