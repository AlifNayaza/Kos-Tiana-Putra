@props([
    'code' => null,
    'date' => null,
    'total' => null,
    'totalLabel' => 'Total',
])

<div {{ $attributes->merge(['class' => 'bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink']) }}>
    @if($code || $date)
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-line text-xs">
            @if($code)
                <span class="font-mono font-medium text-ink-soft select-all">{{ $code }}</span>
            @endif
            @if($date)
                <span class="text-ink-faint font-medium">{{ $date }}</span>
            @endif
        </div>
    @endif

    <!-- Rincian Nota Slot -->
    <div class="space-y-2 text-xs">
        {{ $slot }}
    </div>

    @if($total !== null || isset($footer))
        <!-- Garis Pemisah Putus-Putus Khas Nota Bersih -->
        <div class="border-t border-dashed border-line my-3"></div>

        <div class="flex items-center justify-between">
            @if(isset($footer))
                {{ $footer }}
            @else
                <span class="text-xs font-semibold text-ink-soft">{{ $totalLabel }}</span>
                <span class="text-base sm:text-lg font-bold text-ink whitespace-nowrap tabular-nums">
                    @if(is_numeric($total))
                        <x-price :amount="$total" />
                    @else
                        {{ $total }}
                    @endif
                </span>
            @endif
        </div>
    @endif
</div>
