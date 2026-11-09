@extends('layouts.app')

@section('page_title', 'Ringkasan Kos')
@section('page_subtitle', 'Kondisi pendapatan, hunian, pesanan, dan stok hari ini')
@section('no_page_band', '1')

@section('content')
@php
    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'pagi' : ($hour < 15 ? 'siang' : ($hour < 18 ? 'sore' : 'malam'));
    // Lewati gelar/singkatan berakhiran titik ("H.", "Dr.") saat menyapa
    $nameParts = array_values(array_filter(explode(' ', trim(Auth::user()->name)), fn ($w) => $w !== '' && !str_ends_with($w, '.')));
    $firstName = $nameParts[0] ?? 'Pengelola';
    $attention = [];
    if ($overdueBills->isNotEmpty()) { $attention[] = $overdueBills->count() . ' tagihan lewat jatuh tempo'; }
    if ($stockAlerts->isNotEmpty()) { $attention[] = $stockAlerts->count() . ' stok menipis'; }
    if ($activeOrdersCount > 0) { $attention[] = $activeOrdersCount . ' pesanan sedang berjalan'; }
    $occ = round(($occupiedRoomsCount / max($roomsCount, 1)) * 100);
@endphp
<div class="space-y-6">

    <!-- Sapaan + tindakan cepat -->
    <section aria-labelledby="greet" class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand to-brand-deep text-surface p-5 sm:p-7 shadow-md">
        <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-ochre/25 blur-2xl" aria-hidden="true"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
            <div>
                <p class="text-sm font-medium text-surface/90">{{ now()->translatedFormat('l, d F Y') }}</p>
                <h1 id="greet" class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight">Selamat {{ $greeting }}, {{ $firstName }}</h1>
                <p class="mt-2 text-sm sm:text-base text-surface/90 max-w-xl">
                    @if(count($attention))
                        Hari ini perlu perhatian: {{ implode(', ', $attention) }}.
                    @else
                        Semua beres. Tidak ada tagihan tertunda maupun stok yang menipis.
                    @endif
                </p>
            </div>
            <div class="grid grid-cols-1 sm:flex sm:flex-wrap gap-2.5">
                <a href="{{ route('pengelola.tagihan.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-surface text-brand-deep text-sm font-semibold shadow-sm hover:bg-brand-wash transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Cek tagihan
                </a>
                <a href="{{ route('pengelola.penghuni.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-surface/15 text-surface text-sm font-semibold ring-1 ring-inset ring-surface/40 hover:bg-surface/25 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Daftarkan penghuni
                </a>
                <a href="{{ route('pengelola.prediksi.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-surface/15 text-surface text-sm font-semibold ring-1 ring-inset ring-surface/40 hover:bg-surface/25 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Rencana belanja
                </a>
            </div>
        </div>
    </section>

    <!-- KPI: 2 kolom di ponsel, 4 kolom di layar lebar -->
    <section aria-label="Ringkasan angka penting" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="stat-card p-4 sm:p-5" data-tone="brand">
            <div class="flex items-start justify-between gap-2">
                <span class="text-2xs sm:text-xs font-bold text-ink-soft uppercase tracking-wide">Pendapatan bulan ini</span>
                <span class="hidden sm:flex w-9 h-9 rounded-xl bg-brand-wash text-brand items-center justify-center shrink-0" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-xl sm:text-3xl font-extrabold text-ink tabular-nums">Rp {{ number_format($totalIncomeThisMonth, 0, ',', '.') }}</p>
            <dl class="mt-3 pt-3 border-t border-line text-xs text-ink-soft space-y-1">
                <div class="flex justify-between gap-2"><dt>Sewa</dt><dd class="font-semibold text-ink tabular-nums">Rp {{ number_format($rentIncomeThisMonth, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between gap-2"><dt>Warkop &amp; galon</dt><dd class="font-semibold text-ochre-ink tabular-nums">Rp {{ number_format($serviceSalesThisMonth, 0, ',', '.') }}</dd></div>
            </dl>
        </div>

        <div class="stat-card p-4 sm:p-5" data-tone="process">
            <div class="flex items-start justify-between gap-2">
                <span class="text-2xs sm:text-xs font-bold text-ink-soft uppercase tracking-wide">Okupansi kamar</span>
                <span class="hidden sm:flex w-9 h-9 rounded-xl bg-process-wash text-process items-center justify-center shrink-0" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <p class="mt-2 text-xl sm:text-3xl font-extrabold text-ink tabular-nums">{{ $occupiedRoomsCount }}<span class="text-ink-faint font-bold"> / {{ $roomsCount }}</span></p>
            <div class="mt-2 h-2 rounded-full bg-process-wash overflow-hidden" role="progressbar" aria-valuenow="{{ $occ }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kamar terisi">
                <div class="h-full rounded-full bg-process" style="width: {{ $occ }}%"></div>
            </div>
            <dl class="mt-3 pt-3 border-t border-line text-xs text-ink-soft space-y-1">
                <div class="flex justify-between gap-2"><dt>Terisi</dt><dd class="font-semibold text-ink tabular-nums">{{ $occ }}%</dd></div>
                <div class="flex justify-between gap-2"><dt>Kosong / perbaikan</dt><dd class="font-semibold text-ink tabular-nums">{{ $availableRoomsCount }} / {{ $repairRoomsCount }}</dd></div>
            </dl>
        </div>

        <div class="stat-card p-4 sm:p-5" data-tone="ochre">
            <div class="flex items-start justify-between gap-2">
                <span class="text-2xs sm:text-xs font-bold text-ink-soft uppercase tracking-wide">Penghuni aktif</span>
                <span class="hidden sm:flex w-9 h-9 rounded-xl bg-ochre-wash text-ochre-ink items-center justify-center shrink-0" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <p class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xl sm:text-3xl font-extrabold text-ink tabular-nums">{{ $tenantsCount }}</span>
                <span class="text-sm font-medium text-ink-soft">orang</span>
            </p>
            <div class="mt-3 pt-3 border-t border-line text-xs">
                <a href="{{ route('pengelola.penghuni.index') }}" class="inline-flex items-center gap-1 font-semibold text-brand hover:text-brand-deep underline-offset-2 hover:underline py-1">Kelola penghuni <span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>

        <div class="stat-card p-4 sm:p-5" data-tone="{{ $pendingRentThisMonth > 0 ? 'alert' : 'done' }}">
            <div class="flex items-start justify-between gap-2">
                <span class="text-2xs sm:text-xs font-bold text-ink-soft uppercase tracking-wide">Tagihan tertunda</span>
                <span class="hidden sm:flex w-9 h-9 rounded-xl {{ $pendingRentThisMonth > 0 ? 'bg-alert-wash text-alert' : 'bg-done-wash text-done' }} items-center justify-center shrink-0" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-xl sm:text-3xl font-extrabold tabular-nums {{ $pendingRentThisMonth > 0 ? 'text-alert' : 'text-done' }}">Rp {{ number_format($pendingRentThisMonth, 0, ',', '.') }}</p>
            <div class="mt-3 pt-3 border-t border-line text-xs flex flex-wrap items-center justify-between gap-x-2">
                <span class="font-semibold {{ $overdueBills->count() ? 'text-alert' : 'text-done' }}">{{ $overdueBills->count() ? $overdueBills->count() . ' perlu ditagih' : 'Semua lancar' }}</span>
                <a href="{{ route('pengelola.tagihan.index') }}" class="font-semibold text-brand hover:text-brand-deep underline-offset-2 hover:underline py-1">Lihat <span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>
    </section>

    <!-- Peringatan tagihan menunggak -->
    @if($overdueBills->isNotEmpty())
        <div role="alert" class="p-4 sm:p-5 bg-alert-wash border border-alert/30 rounded-2xl flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
            <div class="flex items-start gap-3 flex-1 min-w-0">
                <svg class="w-6 h-6 text-alert shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-ink">{{ $overdueBills->count() }} tagihan sewa melewati jatuh tempo</h3>
                    <p class="text-sm text-ink-soft mt-0.5">
                        @foreach($overdueBills as $ob)
                            <span class="font-semibold text-ink whitespace-nowrap">{{ $ob->room->room_number }} ({{ $ob->tenantProfile->user->name ?? 'Penghuni' }})</span>{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </p>
                </div>
            </div>
            <a href="{{ route('pengelola.tagihan.index') }}" class="inline-flex justify-center items-center text-sm font-bold text-surface bg-alert hover:bg-alert/90 px-4 py-2.5 rounded-xl transition-colors shrink-0">
                Tinjau tagihan
            </a>
        </div>
    @endif

    <!-- Grafik Perbandingan Finansial 6 Bulan (Chart.js) -->
    <div class="bg-surface rounded-2xl border border-line shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-line gap-2">
            <div>
                <h3 class="font-bold text-sm text-ink">Tren Pendapatan 6 Bulan Terakhir</h3>
                <p class="text-xs text-ink-soft">Perbandingan perolehan uang sewa kamar vs omzet penjualan warkop & galon</p>
            </div>
            <div class="flex items-center space-x-4 text-xs font-medium">
                <div class="flex items-center space-x-1.5">
                    <span class="w-3 h-3 rounded-md bg-brand inline-block"></span>
                    <span class="text-ink-soft">Sewa Kamar</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="w-3 h-3 rounded-md bg-ochre inline-block"></span>
                    <span class="text-ink-soft">Warkop & Galon</span>
                </div>
            </div>
        </div>

        <div class="mt-4 relative h-72 w-full">
            <canvas id="revenueTrendChart" role="img" aria-label="Grafik batang pendapatan sewa kamar dan penjualan warkop selama enam bulan terakhir"></canvas>
        </div>
    </div>

    <!-- 2 Column Section: Pesanan Terbaru & Ringkasan Hunian/Stok -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Pesanan Terbaru (2 Cols) -->
        <div class="lg:col-span-2 bg-surface rounded-2xl border border-line shadow-xs overflow-hidden">
            <div class="p-5 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-ink">Pesanan Layanan Kamar Terbaru</h3>
                    <p class="text-xs text-ink-soft">Aktivitas pesanan warkop & galon terkini dari penghuni</p>
                </div>
                <span class="text-xs font-semibold text-brand bg-brand-wash px-2.5 py-1 rounded-lg border border-brand/20">
                    {{ $activeOrdersCount }} Aktif Diproses
                </span>
            </div>

            <div class="divide-y divide-line">
                @forelse($recentOrders as $order)
                    @php
                        $badge = $order->status_badge;
                    @endphp
                    <div class="p-4 sm:p-5 hover:bg-canvas transition-colors flex items-center justify-between">
                        <div class="flex items-start space-x-3.5">
                            <div class="w-10 h-10 rounded-xl bg-canvas text-ink flex items-center justify-center flex-shrink-0 border border-line">
                                @if($order->order_type === 'quick_galon')
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-sm text-ink">{{ $order->room_number }}</span>
                                    <span class="text-xs text-ink-faint font-mono">&bull; {{ $order->order_code }}</span>
                                </div>
                                <p class="text-xs text-ink-soft mt-0.5">
                                    {{ $order->items->pluck('item_name')->join(', ') }}
                                </p>
                                <span class="text-2xs text-ink-faint mt-1 block">
                                    {{ $order->created_at->diffForHumans() }} &bull; {{ $order->payment_method }}
                                </span>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0 ml-4">
                            <x-status-badge :status="$order->status" :label="$badge['label']" />
                            <p class="text-sm font-extrabold text-ink tabular-nums mt-1.5 whitespace-nowrap">
                                {{ $order->formatted_total }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-ink-faint text-xs">
                        Belum ada pesanan layanan yang tercatat.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right Column: Status Hunian & Prediksi Stok (1 Col) -->
        <div class="space-y-6">
            <!-- Kapasitas Hunian -->
            <div class="bg-surface rounded-2xl border border-line shadow-xs p-5">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold text-sm text-ink">Status Hunian Kos</h3>
                    <a href="{{ route('pengelola.kamar.index') }}" class="text-xs text-brand font-semibold hover:text-brand-deep">Detail &rarr;</a>
                </div>
                <p class="text-xs text-ink-soft mb-4">Total 10 Kamar di Kos Tiana Putra 1</p>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center text-ink-soft">
                            <span class="w-2.5 h-2.5 rounded-full bg-done mr-2"></span>
                            Kamar Terisi
                        </span>
                        <span class="font-bold text-ink tabular-nums">{{ $occupiedRoomsCount }} Kamar</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center text-ink-soft">
                            <span class="w-2.5 h-2.5 rounded-full bg-brand mr-2"></span>
                            Kamar Tersedia
                        </span>
                        <span class="font-bold text-ink tabular-nums">{{ $availableRoomsCount }} Kamar</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center text-ink-soft">
                            <span class="w-2.5 h-2.5 rounded-full bg-wait mr-2"></span>
                            Dalam Perbaikan
                        </span>
                        <span class="font-bold text-ink tabular-nums">{{ $repairRoomsCount }} Kamar</span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-line">
                    @php
                        $terisiPct = round(($occupiedRoomsCount / max($roomsCount, 1)) * 100);
                        $tersediaPct = round(($availableRoomsCount / max($roomsCount, 1)) * 100);
                        $perbaikanPct = 100 - $terisiPct - $tersediaPct;
                    @endphp
                    <div class="w-full bg-canvas rounded-full h-2.5 flex overflow-hidden border border-line">
                        <div class="bg-done h-2.5" style="width: {{ $terisiPct }}%"></div>
                        <div class="bg-brand h-2.5" style="width: {{ $tersediaPct }}%"></div>
                        <div class="bg-wait h-2.5" style="width: {{ max($perbaikanPct, 0) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Rekomendasi Pengadaan & Belanja Stok -->
            <div class="bg-surface rounded-2xl border border-line shadow-xs p-5">
                <div class="flex items-center justify-between mb-1.5">
                    <h3 class="font-bold text-sm text-ink">Pengingat Belanja Stok</h3>
                    <a href="{{ route('pengelola.prediksi.index') }}" class="text-xs text-brand font-semibold hover:text-brand-deep">
                        Buka Rincian &rarr;
                    </a>
                </div>
                <p class="text-xs text-ink-soft mb-3.5">Barang yang perlu disiapkan untuk 7 hari ke depan:</p>

                <div class="space-y-2.5">
                    @php
                        $restockItems = $predictions->filter(fn($p) => $p->recommended_restock_qty > 0)->take(4);
                    @endphp
                    @forelse($restockItems as $pred)
                        <div class="p-3 rounded-xl bg-canvas border border-line">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-ink truncate">{{ $pred->catalogItem->name ?? 'Item' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold shrink-0 {{ $pred->status === 'kritis' ? 'bg-alert-wash text-alert border border-alert/20' : 'bg-wait-wash text-wait border border-wait/20' }}">
                                    {{ $pred->status === 'kritis' ? 'Segera Beli' : 'Perlu Ditambah' }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-ink-soft">
                                <span>Sisa di kos: <strong class="text-ink tabular-nums">{{ $pred->current_stock }}</strong> {{ $pred->catalogItem->unit ?? '' }}</span>
                                <span>Cukup: <strong class="text-ink tabular-nums">~{{ $pred->days_of_supply }} hari</strong></span>
                            </div>
                            <div class="mt-2 pt-2 border-t border-line/60 flex items-center justify-between text-xs">
                                <span class="text-ink-soft">Saran beli:</span>
                                <span class="font-bold text-brand tabular-nums">
                                    +{{ $pred->recommended_restock_qty }} {{ $pred->catalogItem->unit ?? '' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-ink-faint space-y-1">
                            <p class="font-bold text-done">Persediaan Kos Masih Aman</p>
                            <p class="text-2xs">Seluruh stok warkop dan galon mencukupi untuk 7 hari ke depan.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Peringatan Stok Menipis -->
            @if($stockAlerts->isNotEmpty())
                <div class="bg-surface border border-line border-l-4 border-l-alert rounded-2xl p-5 shadow-xs">
                    <h4 class="text-xs font-bold text-ink flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-alert" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Peringatan Stok di Bawah Minimum
                    </h4>
                    <p class="text-xs text-alert mt-1 font-medium">Item berikut mendekati atau sudah habis:</p>
                    <div class="mt-3 space-y-1.5">
                        @foreach($stockAlerts as $item)
                            <div class="flex items-center justify-between text-xs bg-canvas p-2.5 rounded-lg border border-line">
                                <span class="font-semibold text-ink">{{ $item->name }}</span>
                                <span class="text-alert font-bold tabular-nums">Sisa {{ $item->stock }} {{ $item->unit }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>

</div>
@endsection

@push('scripts')
@include('layouts.partials.charts-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('revenueTrendChart');
    if (!ctx) return;

    const labels = {!! json_encode($chartLabels) !!};
    const rentData = {!! json_encode($rentSeries) !!};
    const salesData = {!! json_encode($salesSeries) !!};

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Sewa Kamar',
                    data: rentData,
                    backgroundColor: '#006059',
                    borderRadius: 4,
                    maxBarThickness: 28,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                },
                {
                    label: 'Warkop & Galon',
                    data: salesData,
                    backgroundColor: '#c98a2b',
                    borderRadius: 4,
                    maxBarThickness: 28,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                }
            ]
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
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                            }
                            return label;
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
                            size: 12
                        },
                        color: '#46544f',
                        maxRotation: 0,
                        autoSkip: true,
                        callback: function (value) {
                            const label = this.getLabelForValue(value);
                            return window.innerWidth < 640 ? String(label).split(' ')[0] : label;
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#dedbd0',
                        lineWidth: 1
                    },
                    ticks: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11
                        },
                        color: '#46544f',
                        maxTicksLimit: 5,
                        callback: function (value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(value % 1000000 ? 1 : 0) + ' jt';
                            }
                            return (value / 1000).toFixed(0) + ' rb';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
