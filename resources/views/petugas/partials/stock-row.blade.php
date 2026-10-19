@props([
    'item',
    'isNeedRestock' => false,
    'historyText' => null,
])

@php
    $minStock = max((int) $item->minimum_stock, 1);
    $scaleMax = $minStock * 3;
    $fillPercent = min(100, max(0, round(($item->stock / $scaleMax) * 100)));
    $thresholdPercent = round(($minStock / $scaleMax) * 100);

    $status = $item->stock_status; // 'habis', 'menipis', 'aman'

    // Left accent border: 2px
    $borderLeftClass = match($status) {
        'habis' => 'border-l-2 border-l-alert',
        'menipis' => 'border-l-2 border-l-wait',
        default => 'border-l-2 border-l-transparent',
    };

    // Level bar color
    $barColor = match($status) {
        'habis' => 'bg-alert',
        'menipis' => 'bg-wait',
        default => 'bg-brand/40',
    };
@endphp

<div x-show="matches('{{ $item->category }}', {{ $item->stock }}, {{ $item->minimum_stock }}, '{{ addslashes($item->name) }}')"
     class="p-3 sm:px-4 sm:py-3.5 {{ $borderLeftClass }} transition-colors hover:bg-canvas/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    
    <!-- Informasi Item & Level Stok -->
    <div class="flex items-center justify-between sm:justify-start sm:gap-6 flex-1 min-w-0">
        <!-- Kolom Kiri: Nama Item + Satuan + Status/Riwayat -->
        <div class="min-w-0 flex-1 sm:max-w-xs">
            <div class="flex items-center gap-1.5 flex-wrap">
                <h3 class="text-sm font-semibold text-ink leading-tight truncate">{{ $item->name }}</h3>
                @if($status === 'habis')
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-alert shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-alert"></span>
                        <span>Habis</span>
                    </span>
                @elseif($status === 'menipis')
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-wait shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-wait"></span>
                        <span>Menipis</span>
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-ink-faint">
                <span class="capitalize">{{ $item->unit }}</span>
                @if($historyText)
                    <span>&bull;</span>
                    <span class="truncate">{{ $historyText }}</span>
                @endif
            </div>
        </div>

        <!-- Kolom Tengah: Indikator Level & Angka Stok -->
        <div class="flex items-center gap-3 sm:gap-4 shrink-0">
            <!-- Level Bar dengan Penanda Batas Minimum -->
            <div class="flex flex-col items-end gap-1">
                <div class="relative w-16 sm:w-24 h-1 bg-canvas border border-line/70 rounded-full overflow-visible">
                    <!-- Progress Bar -->
                    <div class="h-1 rounded-full transition-all duration-300 {{ $barColor }}"
                         style="width: {{ $fillPercent }}%"></div>
                    <!-- Penanda Batas Minimum -->
                    <div class="absolute top-1/2 -translate-y-1/2 w-0.5 h-2 bg-ink-faint rounded-full pointer-events-none"
                         style="left: {{ $thresholdPercent }}%"
                         title="Batas minimum: {{ $item->minimum_stock }}"></div>
                </div>
                <span class="text-[10px] text-ink-faint tabular-nums leading-none">batas {{ $item->minimum_stock }}</span>
            </div>

            <!-- Angka Stok Utama (Paling Menonjol) -->
            <div class="flex items-baseline justify-end gap-1 min-w-[54px] text-right">
                <span class="text-xl sm:text-2xl font-bold font-mono text-ink tabular-nums leading-none">{{ $item->stock }}</span>
                <span class="text-xs text-ink-soft leading-none font-normal">{{ $item->unit }}</span>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi: Di Mobile di bawah baris informasi, di Desktop sejajar di kanan -->
    <div class="flex items-center justify-end gap-1.5 sm:gap-2 shrink-0 pt-2 sm:pt-0 border-t border-line/40 sm:border-t-0">
        <!-- Aksi 1: Masuk -->
        <button type="button" 
                @click="openModal({{ json_encode($item) }}, 'in')" 
                class="min-h-[44px] flex-1 sm:flex-initial px-3.5 py-2 text-xs font-semibold rounded-xl transition-colors flex items-center justify-center gap-1.5 {{ $isNeedRestock ? 'bg-brand hover:bg-brand-deep text-surface shadow-2xs' : 'bg-surface border border-line hover:border-brand/40 hover:bg-brand-wash text-brand' }}"
                title="Catat barang masuk untuk {{ $item->name }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Masuk</span>
        </button>

        <!-- Aksi 2: Hitung Fisik -->
        <button type="button" 
                @click="openModal({{ json_encode($item) }}, 'recount')" 
                class="min-h-[44px] flex-1 sm:flex-initial px-3.5 py-2 text-xs font-semibold bg-surface border border-line hover:bg-canvas active:bg-line text-ink-soft hover:text-ink rounded-xl transition-colors flex items-center justify-center gap-1.5"
                title="Cocokkan hitung fisik untuk {{ $item->name }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="whitespace-nowrap">Hitung fisik</span>
        </button>

        <!-- Aksi 3: Titik Tiga (Menu Catat Rusak) -->
        <div class="relative" x-data="{ menuOpen: false }">
            <button type="button" 
                    @click="menuOpen = !menuOpen" 
                    class="min-h-[44px] min-w-[44px] p-2 text-ink-soft hover:text-ink bg-surface border border-line hover:bg-canvas rounded-xl transition-colors flex items-center justify-center"
                    aria-label="Aksi tambahan untuk {{ $item->name }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                </svg>
            </button>

            <!-- Dropdown Menu Kecil -->
            <div x-show="menuOpen" 
                 x-cloak
                 @click.outside="menuOpen = false"
                 class="absolute right-0 top-full mt-1 z-30 bg-surface border border-line rounded-xl shadow-lg p-1 min-w-[140px]">
                <button type="button" 
                        @click="openModal({{ json_encode($item) }}, 'out'); menuOpen = false" 
                        class="w-full min-h-[40px] px-3 py-2 text-left text-xs font-semibold text-alert hover:bg-alert-wash rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-alert shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <span class="whitespace-nowrap">Catat rusak</span>
                </button>
            </div>
        </div>
    </div>
</div>
