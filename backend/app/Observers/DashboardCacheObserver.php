<?php

namespace App\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DashboardCacheObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Model $model): void
    {
        Cache::increment('dashboard:version');
    }

    public function deleted(Model $model): void
    {
        Cache::increment('dashboard:version');
    }
}