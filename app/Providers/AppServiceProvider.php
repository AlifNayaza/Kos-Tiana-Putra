<?php

namespace App\Providers;

use App\Models\CatalogItem;
use App\Models\RentBill;
use App\Models\Room;
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
        // Angka hidup di menu pengelola (sidebar desktop, laci, dan bilah bawah HP): pekerjaan yang menunggu tindakan.
        // Menu dirender beberapa kali per halaman; hitung cukup sekali (3 query ringkas).
        View::composer(['layouts.partials.sidebar-links', 'layouts.partials.pengelola-tabbar'], function ($view) {
            // Disimpan di objek request (bukan static) agar tidak bocor antar-request pada proses yang berumur panjang.
            $attributes = request()->attributes;

            if (!$attributes->has('navBadges')) {
                $bills = RentBill::query()
                    ->selectRaw("COALESCE(SUM(CASE WHEN status = 'menunggu' AND payment_proof IS NOT NULL THEN 1 ELSE 0 END), 0) as verify")
                    ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('menunggu','menunggak') AND due_date < ? AND payment_proof IS NULL THEN 1 ELSE 0 END), 0) as overdue", [now()->toDateString()])
                    ->first();
                $rooms = Room::query()
                    ->selectRaw("COUNT(*) as total, COALESCE(SUM(CASE WHEN status = 'terisi' THEN 1 ELSE 0 END), 0) as terisi")
                    ->first();

                $attributes->set('navBadges', [
                    'tagihan'         => (int) $bills->verify,
                    'tagihan_overdue' => (int) $bills->overdue,
                    'stok'            => CatalogItem::whereColumn('stock', '<=', 'minimum_stock')->count(),
                    'kamar_terisi'    => (int) $rooms->terisi,
                    'kamar_total'     => (int) $rooms->total,
                ]);
            }

            $view->with('navBadges', $attributes->get('navBadges'));
        });
    }
}
