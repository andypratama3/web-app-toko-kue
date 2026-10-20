<?php

namespace App\Providers;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Observers\DeliveryZoneObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
        DeliveryZone::observe(DeliveryZoneObserver::class);
        View::composer('layouts.partials.sidenav', function ($view) {
            if (! Auth::check()) {
                return; // Keluar jika pengguna tidak login
            }

            $user = Auth::user();

            $newOrdersCount = Order::where('region_id', $user->region_id)
                ->where('status', 'baru')
                ->count();

            $verifiedOrdersCount = Order::where('region_id', $user->region_id)
                ->where('status', 'menunggu_verifikasi')
                ->count();

            $view->with([
                'newOrdersCount' => $newOrdersCount,
                'verifiedOrdersCount' => $verifiedOrdersCount,
            ]);
        });
    }
}
