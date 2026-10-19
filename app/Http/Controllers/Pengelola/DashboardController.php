<?php

namespace App\Http\Controllers\Pengelola;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\RentBill;
use App\Models\Room;
use App\Models\StockPrediction;
use App\Models\TenantProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan Executive Dashboard untuk Pengelola Kos Tiana Putra 1 (FR-11).
     */
    public function index()
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // 1. Metrik Okupansi & Penghuni
        $roomsCount = Room::count();
        $occupiedRoomsCount = Room::where('status', 'terisi')->count();
        $availableRoomsCount = Room::where('status', 'tersedia')->count();
        $repairRoomsCount = Room::where('status', 'perbaikan')->count();
        $tenantsCount = TenantProfile::where('is_active', true)->count();

        // 2. Metrik Keuangan Bulan Berjalan
        $rentIncomeThisMonth = RentBill::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->where('status', 'lunas')
            ->sum('amount');

        $pendingRentThisMonth = RentBill::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->whereIn('status', ['menunggu', 'menunggak'])
            ->sum('amount');

        $serviceSalesThisMonth = Order::where('status', 'selesai')
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->sum('total_amount');

        $totalIncomeThisMonth = $rentIncomeThisMonth + $serviceSalesThisMonth;

        // 3. Metrik Operasional Harian
        $activeOrdersCount = Order::whereIn('status', ['menunggu', 'diproses', 'diantar'])->count();
        $stockAlerts = CatalogItem::whereColumn('stock', '<=', 'minimum_stock')->get();
        $overdueBills = RentBill::with(['tenantProfile.user', 'room'])
            ->whereIn('status', ['menunggu', 'menunggak'])
            ->where('due_date', '<', $now->toDateString())
            ->get();

        // 4. Data Visualisasi Grafik Chart.js (6 Bulan Terakhir)
        $chartLabels = [];
        $rentSeries = [];
        $salesSeries = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $m = $monthDate->month;
            $y = $monthDate->year;

            $chartLabels[] = $monthDate->translatedFormat('M Y');

            $mRent = RentBill::where('year', $y)
                ->where('month', $m)
                ->where('status', 'lunas')
                ->sum('amount');
            $rentSeries[] = (int) $mRent;

            $mSales = Order::where('status', 'selesai')
                ->whereYear('created_at', $y)
                ->whereMonth('created_at', $m)
                ->sum('total_amount');
            $salesSeries[] = (int) $mSales;
        }

        // 5. Pesanan Layanan Terbaru
        $recentOrders = Order::with(['items', 'user'])
            ->latest('created_at')
            ->take(6)
            ->get();

        // 6. Prediksi Kebutuhan Stok AI Terkini
        $predictions = StockPrediction::with('catalogItem')
            ->orderByRaw("CASE WHEN status = 'kritis' THEN 1 WHEN status = 'waspada' THEN 2 ELSE 3 END")
            ->take(4)
            ->get();

        return view('pengelola.dashboard', compact(
            'roomsCount',
            'occupiedRoomsCount',
            'availableRoomsCount',
            'repairRoomsCount',
            'tenantsCount',
            'rentIncomeThisMonth',
            'pendingRentThisMonth',
            'serviceSalesThisMonth',
            'totalIncomeThisMonth',
            'activeOrdersCount',
            'stockAlerts',
            'overdueBills',
            'chartLabels',
            'rentSeries',
            'salesSeries',
            'recentOrders',
            'predictions'
        ));
    }
}
