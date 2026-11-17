@php
    // Bilah bawah HP: 4 tujuan utama + tombol "Baru" di tengah (terjangkau jempol).
    // Menu lain ada di lembar "Lainnya" yang naik dari bawah, bukan laci dari samping.
    $b = $navBadges ?? [];
    $billTodo = ($b['tagihan_overdue'] ?? 0) + ($b['tagihan'] ?? 0);
    $stok = $b['stok'] ?? 0;
    $icons = [
        'home'   => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'kamar'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'bill'   => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'people' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'box'    => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
        'log'    => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'cart'   => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z',
        'chart'  => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'web'    => 'M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25',
        'user'   => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
        'addp'   => 'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z',
    ];
    $left = [
        ['route' => 'pengelola.dashboard', 'match' => 'pengelola.dashboard', 'label' => 'Ringkasan', 'icon' => 'home'],
        ['route' => 'pengelola.kamar.index', 'match' => 'pengelola.kamar.*', 'label' => 'Kamar', 'icon' => 'kamar'],
    ];
    $right = [
        ['route' => 'pengelola.tagihan.index', 'match' => 'pengelola.tagihan.*', 'label' => 'Tagihan', 'icon' => 'bill', 'badge' => $billTodo, 'tone' => 'bg-alert text-white'],
    ];
    $more = [
        ['route' => 'pengelola.penghuni.index', 'match' => 'pengelola.penghuni.*', 'label' => 'Penghuni', 'icon' => 'people'],
        ['route' => 'pengelola.katalog.index', 'match' => 'pengelola.katalog.*', 'label' => 'Menu & Galon', 'icon' => 'box', 'badge' => $stok],
        ['route' => 'pengelola.prediksi.index', 'match' => 'pengelola.prediksi.*', 'label' => 'Rencana Belanja', 'icon' => 'cart'],
        ['route' => 'pengelola.stok.index', 'match' => 'pengelola.stok.*', 'label' => 'Riwayat Stok', 'icon' => 'log'],
        ['route' => 'pengelola.laporan.index', 'match' => 'pengelola.laporan.*', 'label' => 'Laporan', 'icon' => 'chart'],
        ['route' => 'pengelola.profil', 'match' => 'pengelola.profil', 'label' => 'Profil', 'icon' => 'user'],
    ];
    $moreActive = collect($more)->contains(fn ($m) => request()->routeIs($m['match']));
    $quick = [
        ['url' => route('pengelola.penghuni.index', ['baru' => 1]), 'label' => 'Daftarkan penghuni', 'hint' => 'Isi kamar kosong', 'icon' => 'addp'],
        ['url' => route('pengelola.tagihan.index', ['baru' => 1]), 'label' => 'Terbitkan tagihan', 'hint' => 'Sekaligus untuk semua penghuni', 'icon' => 'bill'],
        ['url' => route('pengelola.katalog.index', ['baru' => 1]), 'label' => 'Tambah menu / galon', 'hint' => 'Jual di kios kos', 'icon' => 'box'],
    ];
@endphp

<div class="lg:hidden" x-data="{ quickOpen: false, moreOpen: false }" @keydown.escape.window="quickOpen = false; moreOpen = false">

    <!-- Bilah bawah -->
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 backdrop-blur-sm pb-[env(safe-area-inset-bottom)]" aria-label="Menu utama">
        <ul class="grid grid-cols-5 items-end" role="list">
            @foreach($left as $t)
                @php $on = request()->routeIs($t['match']); @endphp
                <li>
                    <a href="{{ route($t['route']) }}" @if($on) aria-current="page" @endif class="tab-item {{ $on ? 'is-on' : '' }}">
                        <span class="tab-icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$t['icon']] }}"/></svg></span>
                        {{ $t['label'] }}
                    </a>
                </li>
            @endforeach
            <li class="flex justify-center">
                <button type="button" @click="quickOpen = true; moreOpen = false" :aria-expanded="quickOpen" aria-haspopup="dialog"
                        class="tab-fab" aria-label="Buat baru">
                    <svg class="w-6 h-6 transition-transform duration-200" :class="quickOpen && 'rotate-45'" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
            </li>
            @foreach($right as $t)
                @php $on = request()->routeIs($t['match']); @endphp
                <li>
                    <a href="{{ route($t['route']) }}" @if($on) aria-current="page" @endif class="tab-item {{ $on ? 'is-on' : '' }}">
                        <span class="tab-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$t['icon']] }}"/></svg>
                            @if(!empty($t['badge']))
                                <span class="tab-badge {{ $t['tone'] }}">{{ $t['badge'] }}<span class="sr-only"> perlu ditindak</span></span>
                            @endif
                        </span>
                        {{ $t['label'] }}
                    </a>
                </li>
            @endforeach
            <li>
                <button type="button" @click="moreOpen = true; quickOpen = false" :aria-expanded="moreOpen" aria-haspopup="dialog"
                        class="tab-item w-full {{ $moreActive ? 'is-on' : '' }}">
                    <span class="tab-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        @if($stok)
                            <span class="tab-badge bg-ochre text-ink">{{ $stok }}<span class="sr-only"> stok menipis</span></span>
                        @endif
                    </span>
                    Lainnya
                </button>
            </li>
        </ul>
    </nav>

    <!-- Lembar "Baru" -->
    <x-bottom-sheet show="quickOpen" title="Mau buat apa?">
        <div class="-mx-2 -my-2 space-y-1">
            @foreach($quick as $q)
                <a href="{{ $q['url'] }}" class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-canvas active:bg-canvas">
                    <span class="w-11 h-11 rounded-xl bg-brand-wash text-brand flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$q['icon']] }}"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-ink">{{ $q['label'] }}</span>
                        <span class="block text-xs text-ink-soft">{{ $q['hint'] }}</span>
                    </span>
                    <svg class="w-4 h-4 text-ink-faint" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endforeach
        </div>
    </x-bottom-sheet>

    <!-- Lembar "Lainnya": semua menu dalam satu genggaman -->
    <x-bottom-sheet show="moreOpen" title="Menu lainnya">
        <div class="grid grid-cols-3 gap-2">
            @foreach($more as $m)
                @php $on = request()->routeIs($m['match']); @endphp
                <a href="{{ route($m['route']) }}" @if($on) aria-current="page" @endif
                   class="relative flex flex-col items-center justify-center gap-1.5 rounded-2xl border px-1 py-3.5 text-center text-xs font-semibold {{ $on ? 'border-brand/40 bg-brand-wash text-brand-deep' : 'border-line bg-canvas/60 text-ink' }}">
                    <svg class="w-6 h-6 {{ $on ? 'text-brand' : 'text-ink-soft' }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$m['icon']] }}"/></svg>
                    {{ $m['label'] }}
                    @if(!empty($m['badge']))
                        <span class="absolute top-1.5 right-1.5 rounded-md bg-ochre px-1.5 font-mono text-[11px] font-bold leading-5 text-ink">{{ $m['badge'] }}<span class="sr-only"> stok menipis</span></span>
                    @endif
                </a>
            @endforeach
            <a href="{{ route('penghuni.beranda') }}" target="_blank" rel="noopener"
               class="col-span-3 flex items-center justify-center gap-2 rounded-2xl border border-dashed border-line px-3 py-3 text-xs font-semibold text-ink-soft">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['web'] }}"/></svg>
                Lihat web penghuni
            </a>
        </div>

        <div class="mt-4 pt-4 border-t border-line flex items-center gap-3">
            <span class="w-10 h-10 rounded-full bg-ochre text-ink font-bold flex items-center justify-center text-sm shrink-0">{{ mb_substr(Auth::user()->name ?? 'P', 0, 1) }}</span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink truncate">{{ Auth::user()->name ?? 'Pengelola' }}</p>
                <p class="text-2xs text-ink-soft truncate">{{ Auth::user()->email ?? '' }}</p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="h-10 px-3.5 rounded-xl border border-alert/30 text-alert text-xs font-semibold hover:bg-alert-wash">Keluar</button>
            </form>
        </div>
        @if(config('kos.demo_login'))
            <div class="mt-3 flex items-center gap-2 text-2xs text-ink-faint">
                Coba peran lain:
                <a href="{{ route('demo.login', 'petugas') }}" class="font-semibold text-brand underline-offset-2 hover:underline">Petugas</a>
                <a href="{{ route('demo.login', 'penghuni') }}" class="font-semibold text-brand underline-offset-2 hover:underline">Penghuni</a>
            </div>
        @endif
    </x-bottom-sheet>
</div>
