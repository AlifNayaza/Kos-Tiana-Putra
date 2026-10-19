@extends('layouts.app')

@section('page_title', 'Rekap Penjualan & Laporan Finansial')
@section('page_subtitle', 'Analisis komprehensif penerimaan uang sewa, omzet warkop harian, dan item terlaris')

@section('content')
<div class="space-y-6">

    <!-- Filter Header (Periode Bulan & Tahun) -->
    <div class="bg-surface p-4 sm:p-5 rounded-2xl border border-line shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-ink">
                Periode Laporan: {{ \Carbon\Carbon::create(null, $selectedMonth)->translatedFormat('F') }} {{ $selectedYear }}
            </h3>
            <p class="text-xs text-ink-soft">Pilih bulan dan tahun pembukuan untuk melihat analisis finansial</p>
        </div>

        <form method="GET" action="{{ route('pengelola.laporan.index') }}" class="flex items-center space-x-3">
            <div>
                <label for="month" class="sr-only">Bulan</label>
                <select name="month" id="month" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <div>
                <label for="year" class="sr-only">Tahun</label>
                <select name="year" id="year" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    @foreach([2025, 2026, 2027] as $y)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <!-- Revenue Summary Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Grand Total Revenue -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex flex-col justify-between">
            <span class="text-xs font-semibold text-ink-soft uppercase tracking-wider">Total Pendapatan Bersih</span>
            <div class="mt-2 text-2xl font-extrabold text-brand tabular-nums">
                Rp {{ number_format($grandTotalRevenue, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-ink-faint mt-1">Gabungan sewa kamar lunas + omzet warkop</p>
        </div>

        <!-- Card 2: Sewa Kamar Terkumpul -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex flex-col justify-between">
            <span class="text-xs font-semibold text-ink-soft uppercase tracking-wider">Pemasukan Sewa Kamar</span>
            <div class="mt-2 text-2xl font-extrabold text-done tabular-nums">
                Rp {{ number_format($totalRentPaid, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-ink-faint mt-1">
                Tertunda: <strong class="text-alert tabular-nums">Rp {{ number_format($totalRentPending, 0, ',', '.') }}</strong>
            </p>
        </div>

        <!-- Card 3: Penjualan Warkop & Galon -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex flex-col justify-between">
            <span class="text-xs font-semibold text-ink-soft uppercase tracking-wider">Omzet Layanan Kamar & Galon</span>
            <div class="mt-2 text-2xl font-extrabold text-ochre tabular-nums">
                Rp {{ number_format($totalServiceSales, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-ink-faint mt-1">
                Dari <strong class="text-ink tabular-nums">{{ $totalOrdersCount }}</strong> pesanan selesai
            </p>
        </div>
    </div>

    <!-- Grafik Tren Penjualan Harian Layanan Kamar (Chart.js) -->
    <div class="bg-surface rounded-2xl border border-line shadow-xs p-6">
        <div class="flex items-center justify-between pb-4 border-b border-line">
            <div>
                <h3 class="font-bold text-sm text-ink">Tren Omzet Harian Layanan Warkop & Galon</h3>
                <p class="text-xs text-ink-soft">Pergerakan transaksi harian bulan {{ \Carbon\Carbon::create(null, $selectedMonth)->translatedFormat('F') }} {{ $selectedYear }}</p>
            </div>
            <span class="text-xs font-bold text-brand bg-brand-wash px-2.5 py-1 rounded-lg border border-brand/20">
                Layanan Antar Kamar
            </span>
        </div>

        <div class="mt-4 relative h-72 w-full">
            <canvas id="dailySalesChart"></canvas>
        </div>
    </div>

    <!-- Rincian Kategori Penjualan & Top 5 Menu Terlaris -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Rincian Omzet per Kategori (1 Col) -->
        <div class="bg-surface rounded-2xl border border-line shadow-xs p-5">
            <h3 class="font-bold text-sm text-ink">Distribusi Kategori Layanan</h3>
            <p class="text-xs text-ink-soft mb-4">Porsi kontribusi omzet warkop & galon</p>

            <div class="space-y-4">
                @php
                    $safeSales = max($totalServiceSales, 1);
                    $galonPct = round(($galonSales / $safeSales) * 100);
                    $foodPct = round(($foodSales / $safeSales) * 100);
                    $drinkPct = 100 - $galonPct - $foodPct;
                @endphp

                <!-- Galon -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="font-bold text-ink flex items-center">
                            <span class="w-2.5 h-2.5 rounded-full bg-brand mr-2"></span>
                            Galon Air
                        </span>
                        <span class="font-extrabold text-ink tabular-nums">
                            Rp {{ number_format($galonSales, 0, ',', '.') }} ({{ $galonPct }}%)
                        </span>
                    </div>
                    <div class="w-full bg-canvas rounded-full h-2">
                        <div class="bg-brand h-2 rounded-full" style="width: {{ $galonPct }}%"></div>
                    </div>
                </div>

                <!-- Makanan -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="font-bold text-ink flex items-center">
                            <span class="w-2.5 h-2.5 rounded-full bg-ochre mr-2"></span>
                            Makanan & Mie
                        </span>
                        <span class="font-extrabold text-ink tabular-nums">
                            Rp {{ number_format($foodSales, 0, ',', '.') }} ({{ $foodPct }}%)
                        </span>
                    </div>
                    <div class="w-full bg-canvas rounded-full h-2">
                        <div class="bg-ochre h-2 rounded-full" style="width: {{ $foodPct }}%"></div>
                    </div>
                </div>

                <!-- Minuman -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="font-bold text-ink flex items-center">
                            <span class="w-2.5 h-2.5 rounded-full bg-brand-deep mr-2"></span>
                            Kopi & Minuman
                        </span>
                        <span class="font-extrabold text-ink tabular-nums">
                            Rp {{ number_format($drinkSales, 0, ',', '.') }} ({{ max($drinkPct, 0) }}%)
                        </span>
                    </div>
                    <div class="w-full bg-canvas rounded-full h-2">
                        <div class="bg-brand-deep h-2 rounded-full" style="width: {{ max($drinkPct, 0) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: 5 Item Terlaris (2 Cols) -->
        <div class="lg:col-span-2 bg-surface rounded-2xl border border-line shadow-xs overflow-hidden">
            <div class="p-5 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-ink">5 Item Menu Paling Laris</h3>
                    <p class="text-xs text-ink-soft">Berdasarkan total kuantitas porsi/unit yang terjual</p>
                </div>
                <span class="text-xs font-semibold text-brand bg-brand-wash px-2 py-0.5 rounded-md border border-brand/20">
                    Top Sellers
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-canvas border-b border-line text-ink-soft uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4 sm:px-6">Peringkat</th>
                            <th class="py-3 px-4">Nama Item</th>
                            <th class="py-3 px-4 text-center">Terjual</th>
                            <th class="py-3 px-4 sm:px-6 text-right">Total Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($topItems as $index => $item)
                            <tr class="hover:bg-canvas transition-colors">
                                <td class="py-3 px-4 sm:px-6">
                                    <span class="w-6 h-6 rounded-full inline-flex items-center justify-center font-bold text-xs {{ $index === 0 ? 'bg-ochre-wash text-ochre' : 'bg-canvas text-ink-soft border border-line' }}">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-bold text-ink">
                                    {{ $item->item_name }}
                                </td>
                                <td class="py-3 px-4 text-center font-extrabold text-ink tabular-nums">
                                    {{ $item->total_sold }}
                                </td>
                                <td class="py-3 px-4 sm:px-6 text-right font-extrabold text-brand tabular-nums">
                                    Rp {{ number_format($item->total_omset, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-ink-faint text-xs">
                                    Belum ada data penjualan pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('dailySalesChart');
    if (!ctx) return;

    const labels = {!! json_encode($dailyLabels) !!};
    const data = {!! json_encode($dailyServiceSales) !!};

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Omzet Harian (Rp)',
                data: data,
                borderColor: '#006059',
                backgroundColor: 'rgba(0, 96, 89, 0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#006059'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(items) {
                            return 'Tanggal ' + items[0].label + ' ' + '{{ \Carbon\Carbon::create(null, $selectedMonth)->translatedFormat("F") }}';
                        },
                        label: function (context) {
                            return 'Omzet: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11
                        },
                        color: '#55645f'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#dbe1dc'
                    },
                    ticks: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11
                        },
                        color: '#55645f',
                        callback: function (value) {
                            if (value >= 1000) {
                                return 'Rp ' + (value / 1000).toFixed(0) + ' rb';
                            }
                            return 'Rp ' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
