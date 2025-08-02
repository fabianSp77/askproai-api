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
        \App\Models\Appointment::class => \App\Policies\AppointmentPolicy::class,
        \App\Models\BillingPeriod::class => \App\Policies\BillingPeriodPolicy::class,
        \App\Models\Branch::class => \App\Policies\BranchPolicy::class,
        \App\Models\CalcomEventType::class => \App\Policies\CalcomEventTypePolicy::class,
        \App\Models\Call::class => \App\Policies\CallPolicy::class,
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\EventType::class => \App\Policies\EventTypePolicy::class,
        \App\Models\Integration::class => \App\Policies\IntegrationPolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\Service::class => \App\Policies\ServicePolicy::class,
        \App\Models\Staff::class => \App\Policies\StaffPolicy::class,
        \App\Models\Subscription::class => \App\Policies\SubscriptionPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\WorkingHour::class => \App\Policies\WorkingHourPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        
        // Register custom user provider for portal users that bypasses CompanyScope
        $this->app['auth']->provider('portal_eloquent', function ($app, array $config) {
            return new \App\Auth\PortalUserProvider($app['hash'], $config['model']);
        });
        
        // SECURITY FIX: Re-enabled CustomSessionGuard with session fixation protection
        // Override the default session guard with our custom implementation
        $this->app['auth']->extend('session', function ($app, $name, array $config) {
            $provider = $app['auth']->createUserProvider($config['provider'] ?? null);
            
            $guard = new \App\Auth\CustomSessionGuard(
                $name,
                $provider,
                $app['session.store']
            );
            
            // When using the "remember me" functionality of the authentication services we
            // will need to be set the encryption instance used by the guard, which allows
            // secure, encrypted cookie values to get generated for those cookies.
            if (method_exists($guard, 'setCookieJar')) {
                $guard->setCookieJar($app['cookie']);
            }
            
            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($app['events']);
            }
            
            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            }
            
            if (isset($config['remember'])) {
                $guard->setRememberDuration($config['remember']);
            }
            
            return $guard;
        });

        /**
         * -----------------------------------------------------------------
         *  Gate::before() für Super Admin - erlaubt alle Aktionen
         * -----------------------------------------------------------------
         */
        Gate::before(function ($user, $ability) {
            // Allow various admin role variations to bypass all checks
            $adminRoles = ['super_admin', 'super-admin', 'Super Admin', 'admin', 'Admin'];
            
            foreach ($adminRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
            
            // Also check if user has role field set to admin
            if (isset($user->role) && in_array($user->role, ['admin', 'super_admin'])) {
                return true;
            }
            
            // Admin API routes should follow normal authorization
            
            return null;
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
