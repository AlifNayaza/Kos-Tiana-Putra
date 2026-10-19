@extends('layouts.app')

@section('page_title', 'Audit Log Mutasi Stok')
@section('page_subtitle', 'Catatan riwayat pergerakan stok masuk, penjualan pesanan, dan penyesuaian manual galon & warkop')

@section('content')
<div class="space-y-6">

    <!-- Filter Bar -->
    <div class="bg-surface p-4 sm:p-5 rounded-2xl border border-line shadow-xs">
        <form method="GET" action="{{ route('pengelola.stok.index') }}" class="flex flex-wrap items-center gap-3">
            <!-- Tipe Mutasi -->
            <div>
                <label for="type" class="sr-only">Tipe Mutasi</label>
                <select name="type" id="type" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    <option value="semua" {{ !$typeFilter || $typeFilter === 'semua' ? 'selected' : '' }}>Semua Tipe (Masuk & Keluar)</option>
                    <option value="in" {{ $typeFilter === 'in' ? 'selected' : '' }}>Stok Masuk (Restock / Awal)</option>
                    <option value="out" {{ $typeFilter === 'out' ? 'selected' : '' }}>Stok Keluar (Terjual / Rusak)</option>
                </select>
            </div>

            <!-- Pilih Item Menu/Galon -->
            <div>
                <label for="item_id" class="sr-only">Pilih Item</label>
                <select name="item_id" id="item_id" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    <option value="semua" {{ !$itemFilter || $itemFilter === 'semua' ? 'selected' : '' }}>Semua Item Produk</option>
                    @foreach($catalogItems as $cItem)
                        <option value="{{ $cItem->id }}" {{ $itemFilter == $cItem->id ? 'selected' : '' }}>
                            {{ $cItem->name }} (Sisa: {{ $cItem->stock }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Notes -->
            <div class="relative">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari keterangan / catatan..." 
                       class="text-xs rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none pl-8 pr-4 py-2 bg-canvas w-48 sm:w-64">
                <svg class="w-4 h-4 text-ink-faint absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            @if($typeFilter || $itemFilter || $search)
                <a href="{{ route('pengelola.stok.index') }}" class="text-xs text-alert hover:text-alert font-semibold px-2 py-1">
                    Reset Filter
                </a>
            @endif
        </form>
    </div>

    <!-- Stock Logs Table -->
    <div class="bg-surface rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-canvas border-b border-line text-ink-soft uppercase tracking-wider font-semibold">
                        <th class="py-3.5 px-4 sm:px-6">Waktu Mutasi</th>
                        <th class="py-3.5 px-4">Item Produk</th>
                        <th class="py-3.5 px-4">Tipe Mutasi</th>
                        <th class="py-3.5 px-4">Perubahan Stok</th>
                        <th class="py-3.5 px-4">Pencatat / Aktor</th>
                        <th class="py-3.5 px-4 sm:px-6">Catatan & Referensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($logs as $log)
                        <tr class="hover:bg-canvas transition-colors">
                            <!-- Waktu -->
                            <td class="py-4 px-4 sm:px-6 whitespace-nowrap">
                                <p class="font-bold text-ink leading-tight">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </p>
                                <span class="text-[10px] text-ink-faint block mt-0.5">
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </td>

                            <!-- Item -->
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-2">
                                    <span class="w-6 h-6 rounded-md bg-canvas flex items-center justify-center text-brand border border-line flex-shrink-0">
                                        @if($log->catalogItem?->category === 'galon')
                                            <svg class="w-3.5 h-3.5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                            </svg>
                                        @elseif($log->catalogItem?->category === 'makanan')
                                            <svg class="w-3.5 h-3.5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                            </svg>
                                        @else
                                            <svg class="w-3.5 h-3.5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/>
                                            </svg>
                                        @endif
                                    </span>
                                    <div>
                                        <p class="font-bold text-ink leading-tight">{{ $log->catalogItem->name ?? 'Item Dihapus' }}</p>
                                        <span class="text-[10px] text-ink-soft uppercase font-medium">{{ $log->catalogItem->category ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Tipe Mutasi -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($log->type === 'in')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-done-wash text-done border border-done/20">
                                        + Masuk ({{ $log->quantity }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-alert-wash text-alert border border-alert/20">
                                        - Keluar ({{ $log->quantity }})
                                    </span>
                                @endif
                            </td>

                            <!-- Perubahan Stok -->
                            <td class="py-4 px-4 whitespace-nowrap tabular-nums">
                                <div class="flex items-center space-x-2">
                                    <span class="text-ink-soft">{{ $log->before_stock }}</span>
                                    <svg class="w-3.5 h-3.5 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                    <span class="font-bold text-ink">{{ $log->after_stock }}</span>
                                    <span class="text-[10px] text-ink-faint font-normal">{{ $log->catalogItem->unit ?? '' }}</span>
                                </div>
                            </td>

                            <!-- Pencatat -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-canvas text-ink font-bold flex items-center justify-center text-[10px]">
                                        {{ substr($log->user->name ?? 'S', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-ink leading-tight">{{ $log->user->name ?? 'Sistem' }}</p>
                                        <span class="text-[10px] text-ink-faint">{{ ucfirst($log->user->role ?? 'Sistem') }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Catatan & Referensi -->
                            <td class="py-4 px-4 sm:px-6">
                                <p class="text-ink leading-relaxed">{{ $log->notes ?? '-' }}</p>
                                @if($log->reference_type)
                                    <span class="inline-block mt-1 text-[10px] text-ink-faint font-mono bg-canvas px-1.5 py-0.5 rounded">
                                        Ref: {{ $log->reference_type }} {{ $log->reference_id ? '#' . $log->reference_id : '' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-ink-faint text-xs">
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
