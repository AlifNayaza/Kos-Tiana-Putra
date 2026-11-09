<?php

namespace App\Providers;

use App\Models\CatalogItem;
use App\Models\RentBill;
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
        // Lencana angka di menu samping pengelola: pekerjaan yang menunggu tindakan.
        // Menu dirender dua kali per halaman (laci mobile + sidebar desktop); hitung cukup sekali.
        View::composer('layouts.partials.sidebar-links', function ($view) {
            // Disimpan di objek request (bukan static) agar tidak bocor antar-request pada proses yang berumur panjang.
            $attributes = request()->attributes;

            if (!$attributes->has('navBadges')) {
                $attributes->set('navBadges', [
                    'tagihan' => RentBill::where('status', 'menunggu')->whereNotNull('payment_proof')->count(),
                    'stok'    => CatalogItem::whereColumn('stock', '<=', 'minimum_stock')->count(),
                ]);
            }

            $view->with('navBadges', $attributes->get('navBadges'));
        });
    }
}
