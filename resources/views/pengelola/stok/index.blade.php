@extends('layouts.app')

@section('page_title', 'Audit Log Mutasi Stok')
@section('page_subtitle', 'Catatan riwayat pergerakan stok masuk, penjualan pesanan, dan penyesuaian manual galon & warkop')

@section('content')
@php
    // Kelompokkan per tanggal agar riwayat mudah dipindai seperti mutasi rekening.
    $logsByDay = $logs->getCollection()->groupBy(fn ($log) => $log->created_at->toDateString());

    $dayLabel = function (string $date): string {
        $day = \Carbon\Carbon::parse($date);

        return match (true) {
            $day->isToday()     => 'Hari ini',
            $day->isYesterday() => 'Kemarin',
            default             => $day->translatedFormat('l, d F Y'),
        };
    };
@endphp

<div class="space-y-6">

    <!-- Filter Bar -->
    <div class="bg-surface p-4 sm:p-5 rounded-2xl border border-line shadow-xs">
        <form method="GET" action="{{ route('pengelola.stok.index') }}" class="grid grid-cols-2 sm:flex sm:flex-wrap sm:items-center gap-3">
            <!-- Tipe Mutasi -->
            <div>
                <label for="type" class="sr-only">Tipe Mutasi</label>
                <select name="type" id="type" onchange="this.form.submit()"
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2.5 pl-3 pr-2 sm:pr-8 bg-canvas w-full sm:w-auto max-w-full">
                    <option value="semua" {{ !$typeFilter || $typeFilter === 'semua' ? 'selected' : '' }}>Semua tipe</option>
                    <option value="in" {{ $typeFilter === 'in' ? 'selected' : '' }}>Stok masuk</option>
                    <option value="out" {{ $typeFilter === 'out' ? 'selected' : '' }}>Stok keluar</option>
                </select>
            </div>

            <!-- Pilih Item Menu/Galon -->
            <div>
                <label for="item_id" class="sr-only">Pilih Item</label>
                <select name="item_id" id="item_id" onchange="this.form.submit()"
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2.5 pl-3 pr-2 sm:pr-8 bg-canvas w-full sm:w-auto max-w-full">
                    <option value="semua" {{ !$itemFilter || $itemFilter === 'semua' ? 'selected' : '' }}>Semua item</option>
                    @foreach($catalogItems as $cItem)
                        <option value="{{ $cItem->id }}" {{ $itemFilter == $cItem->id ? 'selected' : '' }}>
                            {{ $cItem->name }} (Sisa: {{ $cItem->stock }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Notes -->
            <div class="relative col-span-2 sm:col-span-1 w-full sm:w-auto">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama item / catatan..."
                       class="text-xs rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none pl-8 pr-4 py-2.5 bg-canvas w-full sm:w-64">
                <svg class="w-4 h-4 text-ink-faint absolute left-2.5 top-3.5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            @if($typeFilter || $itemFilter || $search)
                <a href="{{ route('pengelola.stok.index') }}" class="col-span-2 sm:col-span-1 text-xs text-alert hover:text-alert font-semibold px-2 py-1">
                    Reset Filter
                </a>
            @endif
        </form>
    </div>

    <!-- ============================================================== -->
    <!-- MOBILE: Daftar ringkas per tanggal                             -->
    <!-- ============================================================== -->
    <div class="md:hidden space-y-5">
        @forelse($logsByDay as $date => $dayLogs)
            <section>
                <h2 class="px-1 mb-2 flex items-center justify-between text-2xs font-bold uppercase tracking-wider text-ink-faint">
                    <span>{{ $dayLabel($date) }}</span>
                    <span class="font-semibold normal-case tracking-normal">{{ $dayLogs->count() }} catatan</span>
                </h2>

                <ul class="bg-surface rounded-2xl border border-line shadow-xs divide-y divide-line overflow-hidden">
                    @foreach($dayLogs as $log)
                        @php
                            $isIn = $log->type === 'in';
                            $item = $log->catalogItem;
                        @endphp
                        <li class="p-3 flex gap-3">
                            <img src="{{ $item?->image_url ?? asset('images/placeholders/makanan.svg') }}" alt="" loading="lazy"
                                 class="w-12 h-12 rounded-xl object-cover border border-line bg-canvas flex-shrink-0">

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-bold text-ink leading-snug line-clamp-2">{{ $item->name ?? 'Item Dihapus' }}</p>
                                    <span class="text-base font-extrabold tabular-nums whitespace-nowrap leading-snug {{ $isIn ? 'text-done' : 'text-alert' }}">
                                        {{ $isIn ? '+' : '−' }}{{ $log->quantity }}
                                    </span>
                                </div>

                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-2xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold {{ $isIn ? 'bg-done-wash text-done' : 'bg-alert-wash text-alert' }}">
                                        {{ $log->reference_label }}
                                    </span>
                                    <span class="text-ink-soft tabular-nums">
                                        Stok {{ $log->before_stock }} → <span class="font-bold text-ink">{{ $log->after_stock }}</span> {{ $item->unit ?? '' }}
                                    </span>
                                </div>

                                @if($log->display_notes)
                                    <p class="mt-1.5 text-xs text-ink-soft leading-relaxed line-clamp-2">{{ $log->display_notes }}</p>
                                @endif

                                <p class="mt-1.5 text-2xs text-ink-faint">
                                    {{ $log->created_at->format('H:i') }}
                                    &middot; {{ $log->user->name ?? 'Sistem' }}
                                    @if($log->user)
                                        ({{ ucfirst($log->user->role) }})
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="bg-surface rounded-2xl border border-line p-8 text-center text-ink-faint text-xs">
                Belum ada riwayat mutasi stok yang tercatat pada filter ini.
            </div>
        @endforelse

        @if($logs->hasPages())
            <div class="bg-surface rounded-2xl border border-line p-3">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- DESKTOP: Tabel lengkap                                         -->
    <!-- ============================================================== -->
    <div class="hidden md:block bg-surface rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-canvas border-b border-line text-ink-soft uppercase tracking-wider font-semibold">
                        <th class="py-3.5 px-4 lg:px-6">Jam</th>
                        <th class="py-3.5 px-4">Item</th>
                        <th class="py-3.5 px-4">Jenis Mutasi</th>
                        <th class="py-3.5 px-4 text-right">Jumlah</th>
                        <th class="py-3.5 px-4">Stok</th>
                        <th class="py-3.5 px-4">Dicatat oleh</th>
                        <th class="py-3.5 px-4 lg:px-6">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($logsByDay as $date => $dayLogs)
                        <tr class="bg-canvas/60">
                            <td colspan="7" class="py-2 px-4 lg:px-6 text-2xs font-bold uppercase tracking-wider text-ink-faint">
                                {{ $dayLabel($date) }}
                            </td>
                        </tr>
                        @foreach($dayLogs as $log)
                            @php
                                $isIn = $log->type === 'in';
                                $item = $log->catalogItem;
                            @endphp
                            <tr class="hover:bg-canvas transition-colors align-top">
                                <!-- Waktu -->
                                <td class="py-3.5 px-4 lg:px-6 whitespace-nowrap">
                                    <p class="font-bold text-ink tabular-nums" title="{{ $log->created_at->diffForHumans() }}">{{ $log->created_at->format('H:i') }}</p>
                                </td>

                                <!-- Item -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5 min-w-[11rem]">
                                        <img src="{{ $item?->image_url ?? asset('images/placeholders/makanan.svg') }}" alt="" loading="lazy"
                                             class="w-9 h-9 rounded-lg object-cover border border-line bg-canvas flex-shrink-0">
                                        <div class="min-w-0">
                                            <p class="font-bold text-ink leading-tight">{{ $item->name ?? 'Item Dihapus' }}</p>
                                            <span class="text-2xs text-ink-soft uppercase font-medium">{{ $item->category ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Jenis Mutasi -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-2xs font-bold leading-snug {{ $isIn ? 'bg-done-wash text-done' : 'bg-alert-wash text-alert' }}">
                                        {{ $log->reference_label }}
                                    </span>
                                </td>

                                <!-- Jumlah -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-right tabular-nums">
                                    <span class="text-sm font-extrabold {{ $isIn ? 'text-done' : 'text-alert' }}">
                                        {{ $isIn ? '+' : '−' }}{{ $log->quantity }}
                                    </span>
                                </td>

                                <!-- Stok sebelum → sesudah -->
                                <td class="py-3.5 px-4 whitespace-nowrap tabular-nums text-ink-soft">
                                    {{ $log->before_stock }} → <span class="font-bold text-ink">{{ $log->after_stock }}</span>
                                    <span class="text-2xs text-ink-faint">{{ $item->unit ?? '' }}</span>
                                </td>

                                <!-- Pencatat -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="font-semibold text-ink leading-tight">{{ $log->user->name ?? 'Sistem' }}</p>
                                    <span class="text-2xs text-ink-faint">{{ ucfirst($log->user->role ?? 'Otomatis') }}</span>
                                </td>

                                <!-- Catatan -->
                                <td class="py-3.5 px-4 lg:px-6 text-ink-soft leading-relaxed min-w-[12rem]">
                                    {{ $log->display_notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-ink-faint text-xs">
                                Belum ada riwayat mutasi stok yang tercatat pada filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-line bg-canvas">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
