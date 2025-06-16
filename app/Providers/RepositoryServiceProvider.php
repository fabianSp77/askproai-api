<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\AppointmentRepository;
use App\Repositories\CallRepository;
use App\Repositories\CustomerRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repositories to the container
        $this->app->bind('App\Repositories\AppointmentRepository', function ($app) {
            return new AppointmentRepository();
        });
        
        $this->app->bind('App\Repositories\CallRepository', function ($app) {
            return new CallRepository();
        });
        
        $this->app->bind('App\Repositories\CustomerRepository', function ($app) {
            return new CustomerRepository();
        });
        
        // Register as singletons for better performance
        $this->app->singleton(AppointmentRepository::class);
        $this->app->singleton(CallRepository::class);
        $this->app->singleton(CustomerRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}