<?php

namespace App\Providers;

use App\Models\Task;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::bind('task', function (string $value) {
            return Task::query()
                ->whereKey($value)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        });
    }
}
