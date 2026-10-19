@extends('layouts.app')

@section('page_title', 'Rekomendasi Belanja & Prediksi Stok')
@section('page_subtitle', 'Perkiraan kebutuhan galon dan bahan warkop untuk 7 hari ke depan berdasarkan kebiasaan pesanan penghuni kos')

@section('content')
<div class="space-y-6" x-data="{
    isRunning: false,
    guideModalOpen: false,
    guideTab: 'owner',
    restockModalOpen: false,
    selectedItem: null,
    restockQty: 1,
    restockNotes: '',
    restockActionUrl: '',
    activeFilter: 'semua',
    searchQuery: '',
    copiedWa: false,

    openRestock(item) {
        this.selectedItem = item;
        this.restockQty = item.restock_qty > 0 ? item.restock_qty : 10;
        this.restockNotes = 'Belanja pasokan kos';
        this.restockActionUrl = '{{ url('/pengelola/prediksi-stok/restock') }}/' + item.prediction_id;
        this.restockModalOpen = true;
    },

    copyWaList() {
        const text = document.getElementById('waShoppingText').value;
        navigator.clipboard.writeText(text);
        this.copiedWa = true;
        setTimeout(() => this.copiedWa = false, 2500);
    },

    matchesFilter(item) {
        if (this.searchQuery && !item.name.toLowerCase().includes(this.searchQuery.toLowerCase())) {
            return false;
        }
        if (this.activeFilter === 'semua') return true;
        if (this.activeFilter === 'restock') return item.restock_qty > 0;
        if (this.activeFilter === 'kritis') return item.status === 'kritis';
        if (this.activeFilter === 'waspada') return item.status === 'waspada';
        if (this.activeFilter === 'galon') return item.category === 'galon';
        if (this.activeFilter === 'makanan') return item.category === 'makanan';
        if (this.activeFilter === 'minuman') return item.category === 'minuman';
        return true;
    }
}">

    <!-- Hidden textarea for WhatsApp Text Copying -->
    <textarea id="waShoppingText" class="sr-only" readonly>{{ $waShoppingText }}</textarea>

    <!-- ============================================================== -->
    <!-- 1. HEADER BAR & PENGATURAN KEBUTUHAN -->
    <!-- ============================================================== -->
    <div class="bg-surface p-5 sm:p-6 rounded-2xl border border-line shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-brand-wash text-brand border border-brand/20">
                    Analisis Otomatis 30 Hari Terakhir
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-canvas text-ink-soft border border-line">
                    Kebutuhan 7 Hari ke Depan
                </span>
            </div>

            <h2 class="text-lg sm:text-xl font-extrabold text-ink mt-2">
                Rencana Belanja & Persediaan Kos
            </h2>

            <p class="text-xs text-ink-soft mt-1 flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-ink-faint flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    @if($lastCalculatedAt)
                        Terakhir dianalisis: <strong class="text-ink font-bold">{{ \Carbon\Carbon::parse($lastCalculatedAt)->translatedFormat('d F Y, H:i') }} WIB</strong> 
                        <span class="text-brand font-semibold">({{ \Carbon\Carbon::parse($lastCalculatedAt)->diffForHumans() }})</span>
                    @else
                        Belum pernah dianalisis
                    @endif
                </span>
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
            <!-- Tombol Modal Panduan (Sangat ramah pengguna) -->
            <button type="button" 
                    @click="guideModalOpen = true"
                    class="h-10 px-3.5 rounded-xl border border-line bg-canvas hover:bg-line/50 text-ink text-xs font-bold transition-colors flex items-center space-x-1.5 shadow-2xs">
                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Panduan Membaca Prediksi</span>
            </button>

            <!-- Tombol Hitung Ulang Kebutuhan -->
            <form action="{{ route('pengelola.prediksi.calculate') }}" method="POST" @submit="isRunning = true">
                @csrf
                <button type="submit" 
                        :disabled="isRunning"
                        class="h-10 px-4 rounded-xl bg-brand hover:bg-brand-deep disabled:opacity-50 text-surface text-xs font-bold shadow-xs transition-all flex items-center space-x-2">
                    <template x-if="!isRunning">
                        <div class="flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Perbarui Hitungan Kebutuhan</span>
                        </div>
                    </template>
                    <template x-if="isRunning">
                        <div class="flex items-center space-x-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Menganalisis pesanan kos...</span>
                        </div>
                    </template>
                </button>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2. 4 KARTU RINGKASAN EKSEKUTIF -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- KPI 1: Perlu Belanja Segera -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-ink-soft uppercase tracking-wider">Perlu Belanja</span>
                    @if($stats['need_restock'] > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-alert-wash text-alert border border-alert/20">
                            Perlu Restock
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-done-wash text-done border border-done/20">
                            Stok Aman
                        </span>
                    @endif
                </div>

                <div class="mt-3 flex items-baseline space-x-2">
                    <span class="text-3xl font-extrabold {{ $stats['need_restock'] > 0 ? 'text-alert' : 'text-ink' }} tabular-nums">
                        {{ $stats['need_restock'] }}
                    </span>
                    <span class="text-xs text-ink-soft font-medium">Barang</span>
                </div>

                <div class="mt-1 flex items-center space-x-2 text-xs">
                    <span class="text-alert font-bold tabular-nums">{{ $stats['kritis'] }} Mendesak</span>
                    <span class="text-ink-faint">&bull;</span>
                    <span class="text-wait font-semibold tabular-nums">{{ $stats['waspada'] }} Perlu Ditambah</span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-line flex items-center justify-between">
                <button type="button" 
                        @click="activeFilter = 'restock'"
                        class="text-[11px] font-bold text-brand hover:text-brand-deep transition-colors flex items-center space-x-1">
                    <span>Lihat {{ $stats['need_restock'] }} barang ini &rarr;</span>
                </button>
            </div>
        </div>

        <!-- KPI 2: Estimasi Modal Belanja Minggu Ini -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-ink-soft uppercase tracking-wider">Estimasi Uang Belanja</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-wash text-brand border border-brand/20">
                        7 Hari
                    </span>
                </div>

                <div class="mt-3">
                    <span class="text-2xl sm:text-[26px] font-extrabold text-brand tabular-nums tracking-tight block">
                        Rp {{ number_format($stats['estimated_budget'], 0, ',', '.') }}
                    </span>
                    <p class="text-[11px] text-ink-faint mt-1 font-medium">Perkiraan dana belanja untuk membeli seluruh saran restock</p>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-line flex items-center justify-between text-[11px] text-ink-soft">
                <span>Rata-rata modal per barang:</span>
                <span class="font-bold text-ink tabular-nums">
                    Rp {{ $stats['need_restock'] > 0 ? number_format($stats['estimated_budget'] / $stats['need_restock'], 0, ',', '.') : '0' }}
                </span>
            </div>
        </div>

        <!-- KPI 3: Kebutuhan Galon 7 Hari ke Depan -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-ink-soft uppercase tracking-wider">Kebutuhan Galon (7 Hari)</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-wash text-brand border border-brand/20">
                        Aqua &bull; Le Min &bull; RO
                    </span>
                </div>

                <div class="mt-3 flex items-baseline space-x-2">
                    <span class="text-3xl font-extrabold text-ink tabular-nums">
                        {{ $stats['galon_demand'] }}
                    </span>
                    <span class="text-xs text-ink-soft font-medium">Galon Air</span>
                </div>

                <p class="text-[11px] text-ink-soft mt-1">
                    Sisa di kos: <strong class="text-ink tabular-nums">{{ $stats['galon_stock'] }}</strong> &bull; 
                    Saran pesan: <strong class="text-alert tabular-nums">+{{ $stats['galon_restock'] }} galon</strong>
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-line flex items-center justify-between">
                <button type="button" 
                        @click="activeFilter = 'galon'"
                        class="text-[11px] font-bold text-brand hover:text-brand-deep transition-colors">
                    Lihat khusus galon &rarr;
                </button>
            </div>
        </div>

        <!-- KPI 4: Status Stok Aman -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-ink-soft uppercase tracking-wider">Persediaan Masih Cukup</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-done-wash text-done border border-done/20">
                        Aman
                    </span>
                </div>

                <div class="mt-3 flex items-baseline space-x-2">
                    <span class="text-3xl font-extrabold text-done tabular-nums">
                        {{ $stats['aman'] }}
                    </span>
                    <span class="text-xs text-ink-soft font-medium">dari {{ $stats['total'] }} Barang</span>
                </div>

                <p class="text-[11px] text-ink-faint mt-1">
                    Stok mencukupi untuk 1&ndash;2 minggu ke depan
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-line flex items-center justify-between text-[11px]">
                <span class="text-ink-soft">Kesiapan persediaan:</span>
                <span class="font-extrabold text-done tabular-nums">
                    {{ round(($stats['aman'] / max(1, $stats['total'])) * 100) }}% Cukup
                </span>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- 3. SECTION VISUAL: GRAFIK KOMPARASI & DAFTAR BELANJA SIAP PAKAI -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- KOLOM KIRI (lg:col-span-7): GRAFIK KOMPARASI RAMAH -->
        <div class="lg:col-span-7 bg-surface p-5 sm:p-6 rounded-2xl border border-line shadow-xs space-y-4">
            <div class="border-b border-line pb-3">
                <h3 class="text-sm font-bold text-ink">
                    Bandingkan: Stok yang Ada vs Kebutuhan 7 Hari
                </h3>
                <p class="text-xs text-ink-soft mt-1 leading-relaxed">
                    <strong class="text-ink">Cara membaca:</strong> Batang abu-abu adalah stok barang di kos saat ini. Batang berwarna adalah target kebutuhan 7 hari ke depan. Jika batang abu-abu lebih pendek dari target warna, barang tersebut perlu segera dibeli.
                </p>
            </div>

            <!-- Legend Info yang Ramah -->
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <div class="flex items-center space-x-1.5">
                    <span class="w-3 h-3 rounded-xs bg-[#8a9892] inline-block"></span>
                    <span class="text-ink-soft font-medium">Stok Ada di Kos</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="w-3 h-3 rounded-xs bg-alert inline-block"></span>
                    <span class="text-ink-soft font-medium">Mendesak (Habis dlm 1-2 hari)</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="w-3 h-3 rounded-xs bg-wait inline-block"></span>
                    <span class="text-ink-soft font-medium">Perlu Ditambah (Untuk 7 hari)</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="w-3 h-3 rounded-xs bg-done inline-block"></span>
                    <span class="text-ink-soft font-medium">Stok Cukup (Aman)</span>
                </div>
            </div>

            <!-- Canvas Chart.js -->
            <div class="relative h-96 w-full pt-2">
                <canvas id="stockComparisonChart"></canvas>
            </div>
        </div>

        <!-- KOLOM KANAN (lg:col-span-5): DAFTAR BELANJA SIAP PAKAI -->
        <div class="lg:col-span-5 bg-surface p-5 sm:p-6 rounded-2xl border border-line shadow-xs space-y-4">
            <div class="flex items-start justify-between border-b border-line pb-3">
                <div>
                    <h3 class="text-sm font-bold text-ink">
                        Daftar Belanja Siap Pakai
                    </h3>
                    <p class="text-xs text-ink-soft mt-0.5">
                        Daftar barang yang perlu dibeli beserta perkiraan biayanya.
                    </p>
                </div>

                <!-- Tombol Salin Format WhatsApp -->
                <button type="button" 
                        @click="copyWaList()"
                        class="px-3 py-1.5 rounded-xl border border-brand bg-brand-wash hover:bg-brand-wash text-brand text-xs font-bold transition-all flex items-center space-x-1.5 shadow-2xs shrink-0">
                    <span x-text="copiedWa ? '✓ Berhasil Disalin' : 'Salin ke WhatsApp'"></span>
                </button>
            </div>

            <!-- Daftar Item Belanja -->
            <div class="divide-y divide-line max-h-[410px] overflow-y-auto pr-1 space-y-2.5">
                @forelse($shoppingChecklist as $item)
                    @php
                        $catItem = $item->catalogItem;
                        $daysSupply = $item->days_of_supply;
                    @endphp
                    <div class="pt-2.5 first:pt-0 pb-1 flex items-start justify-between gap-3 text-xs">
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center space-x-1.5 flex-wrap">
                                @if($item->status === 'kritis')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-alert-wash text-alert border border-alert/20">
                                        Mendesak
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-wait-wash text-wait border border-wait/20">
                                        Perlu Ditambah
                                    </span>
                                @endif
                                <span class="font-bold text-ink truncate">{{ $catItem?->name }}</span>
                            </div>

                            <p class="text-[11px] text-ink-soft">
                                Sisa di kos: <strong class="text-ink tabular-nums">{{ $item->current_stock }} {{ $catItem?->unit }}</strong> 
                                <span class="text-ink-faint">({{ $daysSupply <= 2.5 ? 'habis dlm ~' . $daysSupply . ' hari' : 'cukup ~' . $daysSupply . ' hari' }})</span>
                            </p>

                            <p class="text-[11px] font-semibold text-brand tabular-nums">
                                Estimasi biaya: {{ $item->formatted_estimated_cost }}
                            </p>
                        </div>

                        <div class="text-right flex flex-col items-end space-y-1.5 shrink-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-extrabold tabular-nums {{ $item->status === 'kritis' ? 'bg-alert-wash text-alert border border-alert/20' : 'bg-wait-wash text-wait border border-wait/20' }}">
                                Beli +{{ $item->recommended_restock_qty }} {{ $catItem?->unit }}
                            </span>

                            <button type="button" 
                                    @click="openRestock({
                                        prediction_id: {{ $item->id }},
                                        name: '{{ addslashes($catItem?->name) }}',
                                        unit: '{{ $catItem?->unit }}',
                                        current_stock: {{ $item->current_stock }},
                                        target_stock: {{ $item->target_stock }},
                                        restock_qty: {{ $item->recommended_restock_qty }},
                                        price: {{ $catItem?->price ?? 0 }}
                                    })"
                                    class="px-2.5 py-1 rounded-lg bg-brand hover:bg-brand-deep text-surface font-bold text-[11px] transition-colors shadow-2xs flex items-center space-x-1">
                                <span>+ Catat Dibeli</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-ink-faint space-y-2">
                        <p class="font-bold text-done text-xs">Persediaan Kos Masih Aman!</p>
                        <p class="text-[11px] text-ink-soft">Tidak ada barang yang perlu dibeli untuk 7 hari ke depan.</p>
                    </div>
                @endforelse
            </div>

            <!-- Total Estimasi Footer -->
            <div class="pt-3 border-t border-line flex items-center justify-between text-xs">
                <span class="text-ink-soft font-semibold">Total Estimasi Uang Belanja:</span>
                <span class="font-extrabold text-brand tabular-nums text-sm">
                    Rp {{ number_format($stats['estimated_budget'], 0, ',', '.') }}
                </span>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- 4. TABEL SELURUH PERSEDIAAN BARANG (13 ITEM) -->
    <!-- ============================================================== -->
    <div class="bg-surface rounded-2xl border border-line shadow-xs overflow-hidden space-y-4 p-5 sm:p-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4">
            <div>
                <h3 class="text-sm font-bold text-ink">
                    Tabel Seluruh Persediaan Barang (13 Item)
                </h3>
                <p class="text-xs text-ink-soft mt-0.5">
                    Pantau kebiasaan konsumsi penghuni, sisa stok di kos, perkiraan hari habis, dan saran pembelian.
                </p>
            </div>

            <!-- Input Pencarian Cepat -->
            <div class="w-full sm:w-64">
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari nama barang..." 
                       class="w-full text-xs px-3.5 py-2 border border-line rounded-xl text-ink placeholder-ink-faint focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface">
            </div>
        </div>

        <!-- Filter Tab Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
            <button type="button" 
                    @click="activeFilter = 'semua'"
                    :class="activeFilter === 'semua' ? 'bg-brand text-surface font-bold shadow-2xs' : 'bg-canvas text-ink-soft hover:bg-line font-medium'"
                    class="px-3 py-1.5 rounded-full transition-all flex items-center space-x-1.5 whitespace-nowrap">
                <span>Semua Barang</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                      :class="activeFilter === 'semua' ? 'bg-brand-deep text-surface' : 'bg-line text-ink-soft'">
                    {{ $stats['total'] }}
                </span>
            </button>

            <button type="button" 
                    @click="activeFilter = 'restock'"
                    :class="activeFilter === 'restock' ? 'bg-brand text-surface font-bold shadow-2xs' : 'bg-canvas text-ink-soft hover:bg-line font-medium'"
                    class="px-3 py-1.5 rounded-full transition-all flex items-center space-x-1.5 whitespace-nowrap">
                <span>Perlu Belanja Saja</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                      :class="activeFilter === 'restock' ? 'bg-brand-deep text-surface' : 'bg-line text-ink-soft'">
                    {{ $stats['need_restock'] }}
                </span>
            </button>

            <button type="button" 
                    @click="activeFilter = 'kritis'"
                    :class="activeFilter === 'kritis' ? 'bg-alert text-surface font-bold shadow-2xs' : 'bg-canvas text-ink-soft hover:bg-line font-medium'"
                    class="px-3 py-1.5 rounded-full transition-all flex items-center space-x-1.5 whitespace-nowrap">
                <span>Mendesak</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                      :class="activeFilter === 'kritis' ? 'bg-alert text-surface' : 'bg-line text-ink-soft'">
                    {{ $stats['kritis'] }}
                </span>
            </button>

            <button type="button" 
                    @click="activeFilter = 'galon'"
                    :class="activeFilter === 'galon' ? 'bg-brand text-surface font-bold shadow-2xs' : 'bg-canvas text-ink-soft hover:bg-line font-medium'"
                    class="px-3 py-1.5 rounded-full transition-all flex items-center space-x-1.5 whitespace-nowrap">
                <span>Khusus Galon</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                      :class="activeFilter === 'galon' ? 'bg-brand-deep text-surface' : 'bg-line text-ink-soft'">
                    {{ $predictions->where('catalogItem.category', 'galon')->count() }}
                </span>
            </button>

            <button type="button" 
                    @click="activeFilter = 'makanan'"
                    :class="activeFilter === 'makanan' ? 'bg-brand text-surface font-bold shadow-2xs' : 'bg-canvas text-ink-soft hover:bg-line font-medium'"
                    class="px-3 py-1.5 rounded-full transition-all flex items-center space-x-1.5 whitespace-nowrap">
                <span>Makanan</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                      :class="activeFilter === 'makanan' ? 'bg-brand-deep text-surface' : 'bg-line text-ink-soft'">
                    {{ $predictions->where('catalogItem.category', 'makanan')->count() }}
                </span>
            </button>

            <button type="button" 
                    @click="activeFilter = 'minuman'"
                    :class="activeFilter === 'minuman' ? 'bg-brand text-surface font-bold shadow-2xs' : 'bg-canvas text-ink-soft hover:bg-line font-medium'"
                    class="px-3 py-1.5 rounded-full transition-all flex items-center space-x-1.5 whitespace-nowrap">
                <span>Minuman</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                      :class="activeFilter === 'minuman' ? 'bg-brand-deep text-surface' : 'bg-line text-ink-soft'">
                    {{ $predictions->where('catalogItem.category', 'minuman')->count() }}
                </span>
            </button>
        </div>

        <!-- Tabel Render -->
        <div class="overflow-x-auto -mx-5 sm:-mx-6">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-canvas border-y border-line text-ink-soft uppercase tracking-wider font-semibold">
                        <th class="py-3.5 px-5 sm:px-6">Nama Barang</th>
                        <th class="py-3.5 px-4">Konsumsi Penghuni</th>
                        <th class="py-3.5 px-4">Stok di Kos & Ketahanan</th>
                        <th class="py-3.5 px-4">Kebutuhan 7 Hari</th>
                        <th class="py-3.5 px-4">Saran Belanja</th>
                        <th class="py-3.5 px-4">Kondisi</th>
                        <th class="py-3.5 px-5 sm:px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($predictions as $pred)
                        @php
                            $item = $pred->catalogItem;
                            $formula = $pred->formula_detail;
                            $daysSupply = $pred->days_of_supply;
                            $pctStock = min(100, round(($pred->current_stock / max(1, $pred->target_stock)) * 100));
                        @endphp
                        <tr x-show="matchesFilter({
                                name: '{{ addslashes($item?->name) }}',
                                category: '{{ $item?->category }}',
                                status: '{{ $pred->status }}',
                                restock_qty: {{ $pred->recommended_restock_qty }}
                            })"
                            class="hover:bg-canvas/60 transition-colors">
                            
                            <!-- 1. Nama Barang & Kategori -->
                            <td class="py-4 px-5 sm:px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-canvas border border-line flex items-center justify-center text-base flex-shrink-0">
                                        @if($item?->category === 'galon')
                                            💧
                                        @elseif($item?->category === 'makanan')
                                            🍳
                                        @else
                                            ☕
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-ink leading-tight text-xs sm:text-sm">
                                            {{ $item?->name ?? 'Produk' }}
                                        </p>
                                        <div class="flex items-center space-x-2 text-[10px] text-ink-soft uppercase font-medium mt-0.5">
                                            <span>{{ $item?->category }}</span>
                                            <span>&bull;</span>
                                            <span class="text-ink-soft">Cadangan Minimal: <strong class="text-ink tabular-nums">{{ $formula['safety'] }} {{ $item?->unit }}</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- 2. Konsumsi Penghuni (Rata-rata & Tren) -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div class="font-bold text-ink tabular-nums text-xs sm:text-sm">
                                        {{ number_format($pred->daily_avg_sales, 1) }}
                                        <span class="text-xs font-normal text-ink-soft">{{ $item?->unit }}/hari</span>
                                    </div>
                                    @if($pred->sales_trend === 'naik')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-done-wash text-done border border-done/20">
                                            ↗ Makin Ramai
                                        </span>
                                    @elseif($pred->sales_trend === 'turun')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-canvas text-ink-soft border border-line">
                                            ↘ Menurun
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-canvas text-ink-soft border border-line">
                                            → Normal
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 3. Stok di Kos & Ketahanan Waktu -->
                            <td class="py-4 px-4 whitespace-nowrap min-w-[170px]">
                                <div class="space-y-1.5">
                                    <div class="flex items-baseline justify-between text-xs tabular-nums">
                                        <span class="font-bold text-ink">
                                            Sisa: <span class="{{ $pred->current_stock <= $formula['safety'] ? 'text-alert font-extrabold' : 'text-ink' }}">{{ $pred->current_stock }} {{ $item?->unit }}</span>
                                        </span>
                                        <span class="text-[10px] font-semibold {{ $daysSupply <= 2.5 ? 'text-alert font-bold' : ($daysSupply <= 5 ? 'text-wait' : 'text-done') }}">
                                            {{ $daysSupply <= 2.5 ? 'Habis ~' . $daysSupply . ' hari' : 'Cukup ~' . $daysSupply . ' hari' }}
                                        </span>
                                    </div>

                                    <!-- Visual Progress Bar -->
                                    <div class="w-full bg-canvas rounded-full h-1.5 overflow-hidden border border-line/40">
                                        <div class="h-1.5 rounded-full transition-all duration-500 {{ $pred->status === 'kritis' ? 'bg-alert' : ($pred->status === 'waspada' ? 'bg-wait' : 'bg-done') }}"
                                             style="width: {{ $pctStock }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- 4. Kebutuhan 7 Hari -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="space-y-0.5 tabular-nums">
                                    <span class="font-bold text-ink text-xs">
                                        {{ $pred->predicted_demand_qty }} {{ $item?->unit }}
                                    </span>
                                    <span class="text-[10px] text-ink-faint block">
                                        Target total: {{ $pred->target_stock }}
                                    </span>
                                </div>
                            </td>

                            <!-- 5. Saran Belanja -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($pred->recommended_restock_qty > 0)
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-extrabold tabular-nums {{ $pred->status === 'kritis' ? 'bg-alert-wash text-alert border border-alert/20' : 'bg-wait-wash text-wait border border-wait/20' }}">
                                            + {{ $pred->recommended_restock_qty }} {{ $item?->unit }}
                                        </span>
                                        <p class="text-[10px] font-semibold text-brand tabular-nums">
                                            Est. {{ $pred->formatted_estimated_cost }}
                                        </p>
                                    </div>
                                @else
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-done-wash text-done border border-done/20">
                                            ✓ Cukup
                                        </span>
                                        <span class="text-[10px] text-ink-faint block">Tidak perlu belanja</span>
                                    </div>
                                @endif
                            </td>

                            <!-- 6. Kondisi -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($pred->status === 'kritis')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-alert-wash text-alert border border-alert/20">
                                        Mendesak
                                    </span>
                                @elseif($pred->status === 'waspada')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-wait-wash text-wait border border-wait/20">
                                        Perlu Ditambah
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-done-wash text-done border border-done/20">
                                        Aman
                                    </span>
                                @endif
                            </td>

                            <!-- 7. Aksi Cepat (1-Klik Restock) -->
                            <td class="py-4 px-5 sm:px-6 text-right whitespace-nowrap">
                                <button type="button" 
                                        @click="openRestock({
                                            prediction_id: {{ $pred->id }},
                                            name: '{{ addslashes($item?->name) }}',
                                            unit: '{{ $item?->unit }}',
                                            current_stock: {{ $pred->current_stock }},
                                            target_stock: {{ $pred->target_stock }},
                                            restock_qty: {{ $pred->recommended_restock_qty }},
                                            price: {{ $item?->price ?? 0 }}
                                        })"
                                        class="h-8 px-3 rounded-xl {{ $pred->recommended_restock_qty > 0 ? 'bg-brand hover:bg-brand-deep text-surface' : 'bg-canvas hover:bg-line text-ink' }} text-xs font-bold transition-all shadow-2xs inline-flex items-center space-x-1">
                                    <span>{{ $pred->recommended_restock_qty > 0 ? 'Catat Belanja' : '+ Stok' }}</span>
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-ink-faint text-xs">
                                Belum ada data analisis kebutuhan stok. Klik tombol <strong>Perbarui Hitungan Kebutuhan</strong> di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- MODAL 1: PANDUAN MEMBACA PREDIKSI (RAMAH PEMILIK KOS + TAB TEKNIS) -->
    <!-- ============================================================== -->
    <div x-show="guideModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        
        <div x-show="guideModalOpen" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="guideModalOpen = false" 
             class="fixed inset-0 bg-ink/50 backdrop-blur-xs"></div>

        <div class="min-h-full flex items-center justify-center p-4 text-center">
            <div x-show="guideModalOpen" 
                 x-transition:enter="ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-surface w-full max-w-2xl rounded-3xl p-6 sm:p-7 text-left shadow-2xl border border-line overflow-hidden space-y-5">
                
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-brand-wash text-brand flex items-center justify-center text-lg">
                            💡
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-ink">Panduan Membaca Prediksi Stok</h3>
                            <p class="text-xs text-ink-soft">Penjelasan mudah cara sistem membantu pengadaan barang kos</p>
                        </div>
                    </div>
                    <button @click="guideModalOpen = false" class="p-1 text-ink-faint hover:text-ink-soft rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Tab Switcher: Pemilik Kos vs Developer/Sidang -->
                <div class="flex items-center border border-line rounded-xl p-1 bg-canvas">
                    <button type="button" 
                            @click="guideTab = 'owner'" 
                            :class="guideTab === 'owner' ? 'bg-surface text-ink font-bold shadow-2xs' : 'text-ink-soft hover:text-ink font-medium'"
                            class="flex-1 py-1.5 text-xs rounded-lg transition-all text-center">
                        Untuk Pemilik Kos (Bahasa Sehari-hari)
                    </button>
                    <button type="button" 
                            @click="guideTab = 'tech'" 
                            :class="guideTab === 'tech' ? 'bg-surface text-ink font-bold shadow-2xs' : 'text-ink-soft hover:text-ink font-medium'"
                            class="flex-1 py-1.5 text-xs rounded-lg transition-all text-center">
                        Rincian Teknis & Rumus (Untuk Sidang / Dev)
                    </button>
                </div>

                <!-- TAB 1: RAMAH PEMILIK KOS -->
                <div x-show="guideTab === 'owner'" class="space-y-4 text-xs">
                    
                    <div class="p-3.5 bg-canvas rounded-2xl border border-line space-y-1">
                        <h4 class="font-bold text-ink">1. Bagaimana cara sistem tahu berapa barang yang harus dibeli?</h4>
                        <p class="text-ink-soft leading-relaxed">
                            Sistem otomatis membaca riwayat pesanan seluruh penghuni kamar dalam 30 hari terakhir. Jika suatu barang sering dipesan (misalnya kopi dan mie saat nugas malam, atau galon air di akhir pekan), sistem akan menghitung perkiraan kebutuhan untuk 7 hari ke depan agar stok tidak kehabisan.
                        </p>
                    </div>

                    <div class="p-3.5 bg-canvas rounded-2xl border border-line space-y-1.5">
                        <h4 class="font-bold text-ink">2. Apa arti warna status barang?</h4>
                        <ul class="space-y-1 text-ink-soft leading-relaxed">
                            <li class="flex items-start space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-alert mt-1 shrink-0"></span>
                                <div><strong class="text-alert">Mendesak (Merah):</strong> Stok di kos sudah hampir habis (di bawah cadangan minimal) atau diperkirakan habis dalam 1&ndash;2 hari. Harus segera dibeli hari ini.</div>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-wait mt-1 shrink-0"></span>
                                <div><strong class="text-wait">Perlu Ditambah (Kuning):</strong> Stok saat ini masih ada, namun diperkirakan tidak cukup untuk memenuhi pesanan seminggu ke depan.</div>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-done mt-1 shrink-0"></span>
                                <div><strong class="text-done">Aman (Hijau):</strong> Persediaan di kos masih sangat cukup untuk 1&ndash;2 minggu ke depan. Tidak perlu keluar modal belanja.</div>
                            </li>
                        </ul>
                    </div>

                    <div class="p-3.5 bg-canvas rounded-2xl border border-line space-y-1">
                        <h4 class="font-bold text-ink">3. Apa itu "Cadangan Minimal"?</h4>
                        <p class="text-ink-soft leading-relaxed">
                            Cadangan minimal adalah jumlah barang yang sebaiknya selalu tersisa di gudang kos. Tujuannya sebagai pegangan darurat jika ada lonjakan pesanan mendadak atau jika agen galon/toko langganan sedang tutup di hari libur.
                        </p>
                    </div>

                    <div class="p-3.5 bg-canvas rounded-2xl border border-line space-y-1">
                        <h4 class="font-bold text-ink">4. Bagaimana cara praktis menggunakan fitur ini?</h4>
                        <p class="text-ink-soft leading-relaxed">
                            Cukup buka bagian <strong>"Daftar Belanja Siap Pakai"</strong> di sisi kanan, klik <strong>"Salin ke WhatsApp"</strong>, lalu kirim ke petugas kos atau toko agen. Begitu barang belanjaan tiba di kos, klik tombol <strong>"+ Catat Dibeli"</strong> agar jumlah stok di aplikasi langsung bertambah.
                        </p>
                    </div>

                </div>

                <!-- TAB 2: TEKNIS & SIDANG SKRIPSI/TUGAS AKHIR -->
                <div x-show="guideTab === 'tech'" class="space-y-4 text-xs">
                    
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Arsitektur Pipeline (Decoupled Architecture)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                            <div class="p-3 bg-canvas rounded-xl border border-line">
                                <span class="font-bold text-brand block mb-1">1. Laravel Framework</span>
                                Menyimpan transaksi pesanan dan memanggil worker eksternal.
                            </div>
                            <div class="p-3 bg-canvas rounded-xl border border-line">
                                <span class="font-bold text-brand block mb-1">2. Artisan CLI</span>
                                Command <code class="font-mono text-[10px] bg-line px-1 rounded">stok:prediksi</code> dijalankan terjadwal / on-demand.
                            </div>
                            <div class="p-3 bg-canvas rounded-xl border border-line">
                                <span class="font-bold text-brand block mb-1">3. Python Regression</span>
                                Melatih model Scikit-Learn dan menyimpan proyeksi ke database.
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Rumus Matematika Proyeksi</h4>
                        <div class="bg-ink text-brand-wash p-3.5 rounded-xl space-y-1 font-mono text-[11px]">
                            <p class="text-white font-bold">1. Garis Regresi Linier:</p>
                            <p class="pl-2 text-ink-faint">y = (m &times; Hari) + c  (m = Slope koefisien tren, c = Intersep)</p>
                            <p class="pl-2 text-brand-wash">Prediksi_7H = (0.5 &times; Regresi_7H) + (0.5 &times; Moving_Avg_7H)</p>
                            
                            <p class="text-white font-bold pt-1">2. Target Stok & Saran Belanja:</p>
                            <p class="pl-2 text-brand-wash">Target = Prediksi_7H + Safety_Stock</p>
                            <p class="pl-2 text-brand-wash">Saran_Restock = max(0, Target - Stok_Saat_Ini)</p>

                            <p class="text-white font-bold pt-1">3. Ketahanan Stok (Days of Supply):</p>
                            <p class="pl-2 text-brand-wash">Days_of_Supply = Stok_Saat_Ini / max(Rata_Rata_Harian, 0.5)</p>
                        </div>
                    </div>

                </div>

                <div class="pt-3 border-t border-line text-right">
                    <button type="button" 
                            @click="guideModalOpen = false"
                            class="px-5 py-2.5 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-bold transition-colors">
                        Tutup Panduan
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: KONFIRMASI CATAT BARANG MASUK -->
    <!-- ============================================================== -->
    <div x-show="restockModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        
        <div x-show="restockModalOpen" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="restockModalOpen = false" 
             class="fixed inset-0 bg-ink/50 backdrop-blur-xs"></div>

        <div class="min-h-full flex items-center justify-center p-4 text-center">
            <div x-show="restockModalOpen" 
                 x-transition:enter="ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-surface w-full max-w-md rounded-3xl p-6 text-left shadow-2xl border border-line overflow-hidden space-y-4">
                
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-ink">Catat Barang Masuk ke Kos</h3>
                        <p class="text-xs text-ink-soft">Menambahkan stok fisik dan mencatat riwayat secara otomatis</p>
                    </div>
                    <button @click="restockModalOpen = false" class="p-1 text-ink-faint hover:text-ink-soft rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <template x-if="selectedItem">
                    <form :action="restockActionUrl" method="POST" class="space-y-4">
                        @csrf

                        <!-- Ringkasan Barang -->
                        <div class="p-3.5 bg-canvas rounded-2xl border border-line space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-ink-soft">Nama Barang:</span>
                                <span class="font-bold text-ink" x-text="selectedItem.name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-ink-soft">Stok Saat Ini:</span>
                                <span class="font-bold text-ink" x-text="selectedItem.current_stock + ' ' + selectedItem.unit"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-ink-soft">Target Kebutuhan 7 Hari:</span>
                                <span class="font-bold text-brand" x-text="selectedItem.target_stock + ' ' + selectedItem.unit"></span>
                            </div>
                        </div>

                        <!-- Input Jumlah Masuk -->
                        <div>
                            <label class="block text-xs font-bold text-ink mb-1.5">
                                Jumlah Barang yang Dibeli Masuk (<span x-text="selectedItem.unit"></span>)
                            </label>
                            <input type="number" 
                                   name="quantity" 
                                   x-model="restockQty" 
                                   min="1" 
                                   max="1000" 
                                   required 
                                   class="w-full text-sm font-extrabold px-3.5 py-2.5 border border-line rounded-xl text-ink focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface tabular-nums">
                            <span class="text-[11px] text-ink-faint mt-1 block">
                                Stok setelah restock menjadi: <strong class="text-ink tabular-nums" x-text="(parseInt(selectedItem.current_stock) + parseInt(restockQty || 0)) + ' ' + selectedItem.unit"></strong>
                            </span>
                        </div>

                        <!-- Catatan Opsional -->
                        <div>
                            <label class="block text-xs font-bold text-ink mb-1">Catatan Pengadaan (Opsional)</label>
                            <input type="text" 
                                   name="notes" 
                                   x-model="restockNotes" 
                                   placeholder="Contoh: Kiriman agen galon langganan" 
                                   class="w-full text-xs px-3.5 py-2.5 border border-line rounded-xl text-ink placeholder-ink-faint focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface">
                        </div>

                        <div class="pt-3 border-t border-line flex items-center justify-end space-x-2">
                            <button type="button" @click="restockModalOpen = false" class="px-4 py-2.5 rounded-xl border border-line text-xs font-bold text-ink-soft hover:bg-canvas">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs transition-colors">
                                Simpan & Tambah ke Gudang
                            </button>
                        </div>
                    </form>
                </template>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('stockComparisonChart');
    if (!ctx) return;

    const payload = {!! json_encode($chartPayload) !!};

    const targetColors = payload.statuses.map(function(status) {
        if (status === 'kritis') return '#b03a22';
        if (status === 'waspada') return '#a86a0c';
        return '#4b7d57';
    });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: payload.labels,
            datasets: [
                {
                    label: 'Stok Ada di Kos',
                    data: payload.currentStock,
                    backgroundColor: '#8a9892',
                    borderRadius: 6,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                },
                {
                    label: 'Target Kebutuhan (7 Hari)',
                    data: payload.targetStock,
                    backgroundColor: targetColors,
                    borderRadius: 6,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }
            ]
        },
        options: {
            indexAxis: 'y', // Horizontal Bar Chart
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#16201e',
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 'bold' },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const unit = payload.units[context.dataIndex] || '';
                            const val = context.parsed.x || 0;
                            return context.dataset.label + ': ' + val + ' ' + unit;
                        },
                        afterBody: function(contexts) {
                            const idx = contexts[0].dataIndex;
                            const restock = payload.restockQty[idx];
                            const days = payload.daysOfSupply ? payload.daysOfSupply[idx] : null;
                            const unit = payload.units[idx] || '';
                            let lines = [];
                            if (days !== null) {
                                lines.push('Perkiraan Cukup: ~' + days + ' hari');
                            }
                            if (restock > 0) {
                                lines.push('Saran Beli: +' + restock + ' ' + unit);
                            } else {
                                lines.push('Kondisi: Persediaan Cukup (Aman)');
                            }
                            return lines;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#dbe1dc' },
                    ticks: {
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 10 },
                        color: '#55645f'
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' },
                        color: '#16201e'
                    }
                }
            }
        }
    });
});
</script>
@endpush
