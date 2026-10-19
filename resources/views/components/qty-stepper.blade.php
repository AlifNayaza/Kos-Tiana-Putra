@props([
    'model' => 'quantity',
    'min' => 1,
    'max' => 99,
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center border border-line rounded-xl bg-surface overflow-hidden']) }}>
    <button type="button" 
            @click="{{ $model }} = Math.max({{ $min }}, {{ $model }} - 1)" 
            :disabled="{{ $model }} <= {{ $min }}"
            class="w-11 h-11 flex items-center justify-center text-ink-soft hover:text-ink hover:bg-canvas disabled:opacity-30 disabled:pointer-events-none transition-colors"
            aria-label="Kurangi jumlah">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
        </svg>
    </button>

    <span class="w-10 text-center font-bold text-xs sm:text-sm text-ink tabular-nums select-none" 
          x-text="{{ $model }}">
    </span>

    <button type="button" 
            @click="{{ $model }} = Math.min({{ $max }}, {{ $model }} + 1)"
            :disabled="{{ $model }} >= {{ $max }}"
            class="w-11 h-11 flex items-center justify-center text-ink-soft hover:text-ink hover:bg-canvas disabled:opacity-30 disabled:pointer-events-none transition-colors"
            aria-label="Tambah jumlah">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
    </button>
</div>
