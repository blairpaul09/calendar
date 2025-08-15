<?php

namespace Calendar\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class CalendarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishesMigrations([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ]);

        $this->publishes([
            __DIR__ . '/../../config/calendar.php' => config_path('calendar.php'),
        ]);

        Request::macro('timezone', function () {
            return request()->header('timezone', config('app.timezone'));
        });
    }
}
