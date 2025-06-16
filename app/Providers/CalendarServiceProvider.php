<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Calendar\CalendarInterface;
use App\Services\Calendar\CalendarFactory;

class CalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('calendar.factory', function () {
            return new CalendarFactory();
        });
    }

    public function boot(): void
    {
        //
    }
}
