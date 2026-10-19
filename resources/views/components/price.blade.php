@props([
    'amount' => 0,
    'prefix' => 'Rp',
])

@php
    $formatted = number_format((float) $amount, 0, ',', '.');
@endphp

<span {{ $attributes->merge(['class' => 'whitespace-nowrap tabular-nums']) }}>
    @if($prefix)<span class="text-[0.85em] font-medium mr-0.5">{{ $prefix }}</span>@endif{{ $formatted }}
</span>
