@props([
    'status' => 'netral',
    'label' => null,
])

@php
    $normalized = strtolower(trim((string) $status));
    
    $config = match ($normalized) {
        'lunas', 'selesai', 'aman' => [
            'container' => 'bg-transparent',
            'text' => 'text-done',
            'dot' => 'bg-done',
            'defaultLabel' => $normalized === 'aman' ? 'Aman' : ($normalized === 'lunas' ? 'Lunas' : 'Selesai'),
        ],
        'menunggu_verifikasi' => [
            'container' => 'bg-transparent',
            'text' => 'text-wait',
            'dot' => 'bg-wait',
            'defaultLabel' => 'Menunggu Verifikasi',
        ],
        'menunggu', 'menunggu_pembayaran' => [
            'container' => 'bg-transparent',
            'text' => 'text-wait',
            'dot' => 'bg-wait',
            'defaultLabel' => 'Menunggu',
        ],
        'menipis' => [
            'container' => 'bg-transparent',
            'text' => 'text-wait',
            'dot' => 'bg-wait',
            'defaultLabel' => 'Menipis',
        ],
        'diproses' => [
            'container' => 'bg-transparent',
            'text' => 'text-process',
            'dot' => 'bg-process',
            'defaultLabel' => 'Diproses',
        ],
        'diantar' => [
            'container' => 'bg-transparent',
            'text' => 'text-brand',
            'dot' => 'bg-brand',
            'defaultLabel' => 'Diantar',
        ],
        'tertunda' => [
            'container' => 'bg-alert-wash px-2 py-0.5 rounded-full',
            'text' => 'text-alert',
            'dot' => 'bg-alert',
            'defaultLabel' => 'Tertunda',
        ],
        'menunggak' => [
            'container' => 'bg-alert-wash px-2 py-0.5 rounded-full',
            'text' => 'text-alert',
            'dot' => 'bg-alert',
            'defaultLabel' => 'Menunggak',
        ],
        'habis' => [
            'container' => 'bg-transparent',
            'text' => 'text-alert',
            'dot' => 'bg-alert',
            'defaultLabel' => 'Habis',
        ],
        'batal', 'dibatalkan' => [
            'container' => 'bg-transparent',
            'text' => 'text-alert',
            'dot' => 'bg-alert',
            'defaultLabel' => 'Dibatalkan',
        ],
        default => [
            'container' => 'bg-transparent',
            'text' => 'text-ink-soft',
            'dot' => 'bg-ink-faint',
            'defaultLabel' => ucfirst($status),
        ],
    };

    $labelText = $label ?? $config['defaultLabel'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 text-xs font-medium {$config['container']} {$config['text']}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $config['dot'] }}"></span>
    <span>{{ $labelText }}</span>
</span>
