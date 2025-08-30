<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\View::composer('layouts.partials.sidenav', function ($view) {
            $newOrdersCount = 0;
            if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->region_id) {
                $newOrdersCount = \App\Models\Order::where('region_id', \Illuminate\Support\Facades\Auth::user()->region_id)
                    ->where('status', 'pending')->count();
            }
            $view->with('newOrdersCount', $newOrdersCount);
        });
    }
}
