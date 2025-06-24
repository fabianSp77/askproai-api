<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\CalcomEventType::class => \App\Policies\CalcomEventTypePolicy::class,
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
        \App\Models\Branch::class => \App\Policies\BranchPolicy::class,
        \App\Models\Staff::class => \App\Policies\StaffPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * -----------------------------------------------------------------
         *  Gate::before() für Super Admin - erlaubt alle Aktionen
         * -----------------------------------------------------------------
         */
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
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
