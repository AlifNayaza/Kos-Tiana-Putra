@php
    $groups = [
        'Utama' => [
            ['route' => 'pengelola.dashboard', 'match' => 'pengelola.dashboard', 'label' => 'Ringkasan', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ],
        'Hunian & Sewa' => [
            ['route' => 'pengelola.kamar.index', 'match' => 'pengelola.kamar.*', 'label' => 'Data Kamar', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['route' => 'pengelola.penghuni.index', 'match' => 'pengelola.penghuni.*', 'label' => 'Data Penghuni', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
            ['route' => 'pengelola.tagihan.index', 'match' => 'pengelola.tagihan.*', 'label' => 'Tagihan & Pembayaran', 'badge' => $navBadges['tagihan'] ?? 0, 'badgeLabel' => 'bukti menunggu verifikasi', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ],
        'Katalog & Stok' => [
            ['route' => 'pengelola.katalog.index', 'match' => 'pengelola.katalog.*', 'label' => 'Menu & Galon', 'badge' => $navBadges['stok'] ?? 0, 'badgeLabel' => 'item stok menipis', 'badgeTone' => 'wait', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
            ['route' => 'pengelola.stok.index', 'match' => 'pengelola.stok.*', 'label' => 'Riwayat Stok', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
        ],
        'Analisis' => [
            ['route' => 'pengelola.laporan.index', 'match' => 'pengelola.laporan.*', 'label' => 'Laporan', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['route' => 'pengelola.prediksi.index', 'match' => 'pengelola.prediksi.*', 'label' => 'Rencana Belanja', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
        ],
    ];
@endphp

@foreach($groups as $groupLabel => $links)
    <p class="px-3 text-2xs font-bold text-white/70 uppercase tracking-wider mb-1 {{ $loop->first ? 'mt-1' : 'mt-5' }}">{{ $groupLabel }}</p>

    <ul class="space-y-0.5" role="list">
        @foreach($links as $link)
            @php $active = request()->routeIs($link['match']); @endphp
            <li>
                <a href="{{ route($link['route']) }}"
                   @if($active) aria-current="page" @endif
                   class="group relative flex items-center gap-3 px-3 py-2.5 text-sm rounded-xl transition-colors {{ $active ? 'bg-white text-brand-deep font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/10 font-medium' }}">
                    <svg class="w-5 h-5 shrink-0 {{ $active ? 'text-brand' : 'text-white/70 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                    </svg>
                    <span class="flex-1 truncate">{{ $link['label'] }}</span>
                    @if(!empty($link['badge']))
                        <span class="shrink-0 min-w-5 h-5 px-1.5 rounded-full text-2xs font-bold flex items-center justify-center {{ $active ? 'bg-brand text-white' : (($link['badgeTone'] ?? '') === 'wait' ? 'bg-ochre text-ink' : 'bg-alert text-white') }}">
                            {{ $link['badge'] }}<span class="sr-only"> {{ $link['badgeLabel'] }}</span>
                        </span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endforeach
