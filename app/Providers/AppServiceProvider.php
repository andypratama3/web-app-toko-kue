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

            $newOrdersCount = 0;
            $rejectedOrdersCount = 0;
            $verifiedOrdersCount = 0;

            if ($user->hasRole('admin') && $user->region_id) {
                $newOrdersCount = Order::where('region_id', $user->region_id)
                    ->where('status', 'baru')
                    ->count();

                $verifiedOrdersCount = Order::where('region_id', $user->region_id)
                    ->where('status', 'menunggu_verifikasi_admin')
                    ->count();
            }

            if ($user->hasRole('kurir')) {
                $rejectedOrdersCount = Order::where('created_by_user_id', $user->id)
                    ->whereNotNull('rejection_note')
                    ->whereNotIn('status', ['selesai', 'diverifikasi_admin'])
                    ->count();
            }

            $view->with([
                'newOrdersCount' => $newOrdersCount,
                'rejectedOrdersCount' => $rejectedOrdersCount,
                'verifiedOrdersCount' => $verifiedOrdersCount,
            ]);
        });
    }
}
