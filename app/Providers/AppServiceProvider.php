<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::whenQueryingForLongerThan(1, function (Connection $connection, QueryExecuted $event) {
            Log::warning(__CLASS__,[$event->time, $event->sql]);
        });

        DB::listen(function($query) {
            if($query->time > 500) Log::warning(__CLASS__, [$query->sql,$query->bindings,$query->time]);
        });
    }
}
