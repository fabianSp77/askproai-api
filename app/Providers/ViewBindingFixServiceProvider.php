<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\ViewServiceProvider;

class ViewBindingFixServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->app->bound('view')) {
            $this->app->register(ViewServiceProvider::class);
        }
    }
}
