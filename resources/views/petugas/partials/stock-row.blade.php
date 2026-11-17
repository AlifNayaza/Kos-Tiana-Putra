@props([
    'item',
    'lastLog' => null,
])

@php
    $status = $item->stock_status; // 'habis', 'menipis', 'aman'

    $statusPill = match($status) {
        'habis'   => ['label' => 'Habis', 'class' => 'bg-alert-wash text-alert'],
        'menipis' => ['label' => 'Menipis', 'class' => 'bg-wait-wash text-wait'],
        default   => ['label' => 'Aman', 'class' => 'bg-done-wash text-done'],
    };

    $accent = match($status) {
        'habis'   => 'border-l-alert',
        'menipis' => 'border-l-wait',
        default   => 'border-l-transparent',
    };

    $itemJson = [
        'id' => $item->id,
        'name' => $item->name,
        'category' => $item->category,
        'stock' => $item->stock,
        'minimum_stock' => $item->minimum_stock,
        'unit' => $item->unit,
        'image_url' => $item->image_url,
    ];
@endphp

<div x-show="matches(@js($item->category), {{ $item->stock }}, {{ $item->minimum_stock }}, @js($item->name))"
     class="p-3 sm:p-4 border-l-2 {{ $accent }} space-y-3 lg:bg-surface lg:rounded-2xl lg:border-y lg:border-r lg:border-y-line lg:border-r-line lg:shadow-xs lg:flex lg:flex-col lg:justify-between">

    <!-- Info item -->
    <div class="flex items-start gap-3">
        <img src="{{ $item->image_url }}" alt="" loading="lazy"
             class="w-12 h-12 rounded-lg object-cover bg-canvas border border-line shrink-0">

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5 flex-wrap">
                <h3 class="text-sm font-semibold text-ink leading-tight">{{ $item->name }}</h3>
                <span class="px-1.5 py-0.5 rounded text-2xs font-semibold {{ $statusPill['class'] }}">{{ $statusPill['label'] }}</span>
            </div>
            <p class="text-2xs text-ink-soft mt-0.5">
                Batas minimum {{ $item->minimum_stock }} {{ $item->unit }}
                @if($status !== 'aman')
                    <span class="text-ink-faint">&middot; segera belanja</span>
                @endif
            </p>
            @if($lastLog)
                <p class="text-2xs text-ink-faint mt-0.5 truncate">
                    Terakhir: {{ $lastLog->reference_label }}
                    <span class="{{ $lastLog->type === 'in' ? 'text-done' : 'text-alert' }} font-semibold tabular-nums">{{ $lastLog->type === 'in' ? '+' : '−' }}{{ $lastLog->quantity }}</span>
                    &middot; {{ $lastLog->created_at?->diffForHumans() }}
                </p>
            @endif
        </div>

        <div class="text-right shrink-0">
            <div class="text-2xl font-bold font-mono tabular-nums leading-none {{ $status === 'habis' ? 'text-alert' : 'text-ink' }}">{{ $item->stock }}</div>
            <div class="text-2xs text-ink-soft mt-1">{{ $item->unit }} tersedia</div>
        </div>
    </div>

    <!-- Tiga aksi dengan nama yang menjelaskan kejadiannya -->
    <div class="grid grid-cols-3 gap-2">
        <button type="button"
                @click="openModal(@js($itemJson), 'in')"
                class="min-h-[52px] px-1 py-1.5 rounded-xl text-xs font-semibold flex flex-col items-center justify-center gap-0.5 transition-colors {{ $status !== 'aman' ? 'bg-brand hover:bg-brand-deep text-surface' : 'bg-brand-wash hover:bg-brand/15 text-brand border border-brand/20' }}"
                title="Catat barang yang baru datang">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            <span>Tambah</span>
        </button>
        <button type="button"
                @click="openModal(@js($itemJson), 'out')"
                class="min-h-[52px] px-1 py-1.5 rounded-xl text-xs font-semibold flex flex-col items-center justify-center gap-0.5 bg-surface border border-line hover:bg-alert-wash hover:border-alert/30 text-alert transition-colors"
                title="Catat barang rusak, bocor, kedaluwarsa, atau hilang">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
            <span>Kurangi</span>
        </button>
        <button type="button"
                @click="openModal(@js($itemJson), 'recount')"
                class="min-h-[52px] px-1 py-1.5 rounded-xl text-xs font-semibold flex flex-col items-center justify-center gap-0.5 bg-surface border border-line hover:bg-canvas text-ink-soft hover:text-ink transition-colors"
                title="Samakan stok aplikasi dengan jumlah barang di rak">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Hitung ulang</span>
        </button>
    </div>
</div>
