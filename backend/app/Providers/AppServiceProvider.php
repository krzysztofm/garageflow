<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\BusinessTrip;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Observers\DashboardCacheObserver;

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
        foreach ([
            Employee::class,
            LeaveRequest::class,
            BusinessTrip::class,
            User::class,
        ] as $model) {
            $model::observe(DashboardCacheObserver::class);
        }
    }
}
