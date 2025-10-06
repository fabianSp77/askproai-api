<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Appointment::class => \App\Policies\AppointmentPolicy::class,
        \App\Models\Staff::class => \App\Policies\StaffPolicy::class,
        \App\Models\Branch::class => \App\Policies\BranchPolicy::class,
        \App\Models\Transaction::class => \App\Policies\TransactionPolicy::class,
        \App\Models\Call::class => \App\Policies\CallPolicy::class,
        \App\Models\Service::class => \App\Policies\ServicePolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\PhoneNumber::class => \App\Policies\PhoneNumberPolicy::class,
        // New policies for multi-tenant features
        \App\Models\PolicyConfiguration::class => \App\Policies\PolicyConfigurationPolicy::class,
        \App\Models\NotificationConfiguration::class => \App\Policies\NotificationConfigurationPolicy::class,
        \App\Models\CallbackRequest::class => \App\Policies\CallbackRequestPolicy::class,
        \App\Models\SystemSetting::class => \App\Policies\SystemSettingPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\AppointmentModification::class => \App\Policies\AppointmentModificationPolicy::class,
        \App\Models\NotificationEventMapping::class => \App\Policies\NotificationEventMappingPolicy::class,
        \App\Models\CallbackEscalation::class => \App\Policies\CallbackEscalationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * Super admin bypass - allows super_admin role to bypass all authorization checks.
         * Regular users go through normal policy checks for proper multi-tenant isolation.
         */
        Gate::before(function ($user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
            return null; // Let policies handle authorization
        });

        /*
         |--------------------------------------------------------------
         | Passport deaktiviert
         |--------------------------------------------------------------
         | use Laravel\Passport\Passport;
         | Passport::routes();
         | einfach wieder aktivieren, wenn das Paket installiert ist.
         */
    }
}
