@php
    // Tiga kelompok tanpa judul (dipisah garis tipis). Setiap menu yang punya pekerjaan
    // menampilkan angka hidup di "papan nomor" kecil, gaya papan kamar kos.
    $b = $navBadges ?? [];
    $billTodo = ($b['tagihan_overdue'] ?? 0) + ($b['tagihan'] ?? 0);
    $groups = [
        [
            ['route' => 'pengelola.dashboard', 'match' => 'pengelola.dashboard', 'label' => 'Ringkasan', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ],
        [
            ['route' => 'pengelola.kamar.index', 'match' => 'pengelola.kamar.*', 'label' => 'Kamar',
             'tag' => isset($b['kamar_total']) ? ($b['kamar_terisi'] . '/' . $b['kamar_total']) : null, 'tagLabel' => 'kamar terisi',
             'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['route' => 'pengelola.penghuni.index', 'match' => 'pengelola.penghuni.*', 'label' => 'Penghuni', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
            ['route' => 'pengelola.tagihan.index', 'match' => 'pengelola.tagihan.*', 'label' => 'Tagihan',
             'tag' => $billTodo ?: null, 'tone' => 'alert', 'tagLabel' => 'tagihan perlu ditindak (lewat jatuh tempo atau bukti menunggu dicek)',
             'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ],
        [
            ['route' => 'pengelola.katalog.index', 'match' => 'pengelola.katalog.*', 'label' => 'Menu & Galon',
             'tag' => ($b['stok'] ?? 0) ?: null, 'tone' => 'wait', 'tagLabel' => 'item stok menipis',
             'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
            ['route' => 'pengelola.stok.index', 'match' => 'pengelola.stok.*', 'label' => 'Riwayat Stok', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ['route' => 'pengelola.prediksi.index', 'match' => 'pengelola.prediksi.*', 'label' => 'Rencana Belanja', 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z'],
            ['route' => 'pengelola.laporan.index', 'match' => 'pengelola.laporan.*', 'label' => 'Laporan', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ],
    ];
@endphp

@foreach($groups as $links)
    @if(!$loop->first)
        <div class="my-3 mx-3 border-t border-dashed border-white/15" role="separator"></div>
    @endif
    <ul class="space-y-0.5" role="list">
        @foreach($links as $link)
            @php
                $active = request()->routeIs($link['match']);
                $tag = $link['tag'] ?? null;
                $tone = $link['tone'] ?? null;
                $tagClass = match (true) {
                    $active && $tone === 'alert' => 'bg-alert text-white border-alert',
                    $active && $tone === 'wait'  => 'bg-ochre text-ink border-ochre',
                    $active                      => 'border-brand/30 text-brand',
                    $tone === 'alert'            => 'bg-alert text-white border-alert',
                    $tone === 'wait'             => 'bg-ochre text-ink border-ochre',
                    default                      => 'border-white/25 text-white/75',
                };
            @endphp
            <li>
                <a href="{{ route($link['route']) }}"
                   @if($active) aria-current="page" @endif
                   class="nav-item group {{ $active ? 'is-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                    </svg>
                    <span class="flex-1 truncate">{{ $link['label'] }}</span>
                    @if($tag)
                        <span class="shrink-0 rounded-md border px-1.5 font-mono text-[11px] font-bold leading-5 tabular-nums {{ $tagClass }}">
                            {{ $tag }}<span class="sr-only"> {{ $link['tagLabel'] ?? '' }}</span>
                        </span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endforeach
