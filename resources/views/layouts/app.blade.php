<!DOCTYPE html>
<html lang="id" class="h-full bg-canvas" data-role="pengelola">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard Pengelola' }} — Kos Tiana Putra 1</title>


    <!-- Tailwind CSS (Vite with CDN fallback) -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fonts
    @else
        @include('layouts.partials.tailwind-fallback', ['chart' => true])
    @endif

    <style>
        [x-cloak] { display: none !important; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
    </style>
    @stack('styles')
</head>
<body class="h-full text-ink antialiased">
    <a href="#main" class="skip-link">Lewati ke konten utama</a>

    @include('components.toast')

    <div class="min-h-full flex flex-col lg:flex-row">

        <!-- ============================================================== -->
        <!-- SIDEBAR DESKTOP                                                -->
        <!-- ============================================================== -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 nav-dark nav-tiles z-20">
            <a href="{{ route('pengelola.dashboard') }}" class="block px-5 pt-6 pb-5">
                <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-surface text-brand-deep flex items-center justify-center font-display font-bold text-sm shadow-sm">KT</div>
                        <div class="min-w-0">
                            <p class="flex items-center gap-1.5 font-semibold text-white text-[15px] leading-tight whitespace-nowrap">
                                Kos Tiana Putra
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-ochre text-ink text-[11px] font-mono font-bold">1</span>
                            </p>
                            <p class="text-2xs text-white/70 mt-0.5">Panel Pengelola</p>
                        </div>
                    </div>
            </a>
            <!-- pr-0: menu aktif menyambung ke area konten seperti tab map -->
            <nav class="flex-1 pl-3 pr-0 pb-4 overflow-y-auto overflow-x-hidden" aria-label="Menu pengelola">
                @include('layouts.partials.sidebar-links')
            </nav>
            <div class="p-3 border-t border-white/10">
                    <div class="flex items-center gap-2.5 rounded-xl p-2 hover:bg-white/5 transition-colors">
                        <a href="{{ route('pengelola.profil') }}" title="Profil & ganti kata sandi" class="flex items-center gap-2.5 min-w-0 flex-1">
                            <span class="w-9 h-9 rounded-full bg-ochre text-ink font-bold flex items-center justify-center text-xs shrink-0">{{ mb_substr(Auth::user()->name ?? 'P', 0, 1) }}</span>
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-white truncate">{{ Auth::user()->name ?? 'Pengelola' }}</span>
                                <span class="block text-2xs text-white/65 truncate">Profil &amp; kata sandi</span>
                            </span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-9 h-9 flex items-center justify-center text-white/70 hover:text-white rounded-lg hover:bg-alert transition-colors" title="Keluar" aria-label="Keluar dari akun">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                    @if(config('kos.demo_login'))
                        <details class="group mt-1 px-2">
                            <summary class="cursor-pointer list-none text-2xs text-white/55 hover:text-white/85 py-1 flex items-center gap-1">
                                <svg class="w-3 h-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                Coba peran lain (demo)
                            </summary>
                            <div class="grid grid-cols-2 gap-1 pt-1 pb-1">
                                <a href="{{ route('demo.login', 'petugas') }}" class="text-2xs text-center py-1.5 rounded-md bg-white/10 hover:bg-white/20 text-white/85">Petugas</a>
                                <a href="{{ route('demo.login', 'penghuni') }}" class="text-2xs text-center py-1.5 rounded-md bg-white/10 hover:bg-white/20 text-white/85">Penghuni</a>
                            </div>
                        </details>
                    @endif
                </div>
        </aside>

        <!-- ============================================================== -->
        <!-- AREA KONTEN                                                    -->
        <!-- ============================================================== -->
        <div class="lg:pl-64 flex flex-col flex-1 min-h-screen min-w-0">

            <!-- Bilah atas: tanggal + aksi cepat + notifikasi -->
            <header x-data="{ scrolled: false }" @scroll.window.passive="scrolled = window.scrollY > 72"
                    class="h-14 lg:h-16 bg-canvas/85 backdrop-blur-sm border-b sticky top-0 z-10 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-3 transition-colors"
                    :class="scrolled ? 'border-line/70' : 'border-transparent lg:border-line/70'">
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="lg:hidden w-8 h-8 rounded-lg bg-brand text-surface flex items-center justify-center font-display font-bold text-[11px] shrink-0">KT</div>
                    <div class="min-w-0 flex-1">
                        <div class="lg:hidden relative h-6 overflow-hidden">
                            <p class="absolute inset-0 flex items-center gap-1.5 font-semibold text-ink text-[15px] transition duration-200" :class="scrolled && '-translate-y-full opacity-0'">
                                Kos Tiana Putra <span class="inline-flex items-center justify-center w-4 h-4 rounded bg-ochre text-ink text-[10px] font-mono font-bold">1</span>
                            </p>
                            <p class="absolute inset-0 font-display font-bold text-ink truncate transition duration-200 translate-y-full opacity-0" :class="scrolled && '!translate-y-0 !opacity-100'">@yield('page_title', 'Dashboard')</p>
                        </div>
                        <p class="hidden lg:flex items-center gap-2 text-sm text-ink-soft">
                            <svg class="w-4 h-4 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <!-- Aksi cepat: pekerjaan yang paling sering dimulai -->
                    <div class="relative hidden lg:block" x-data="{ open: false }" @keydown.escape.stop="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                                class="h-10 inline-flex items-center gap-1.5 px-3 sm:px-3.5 rounded-xl bg-brand text-surface text-sm font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <span class="hidden sm:inline">Baru</span>
                            <span class="sr-only sm:hidden">Buat baru</span>
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute right-0 mt-2 w-64 bg-surface rounded-2xl shadow-xl border border-line p-1.5 z-50">
                            @foreach([
                                ['url' => route('pengelola.penghuni.index', ['baru' => 1]), 'label' => 'Daftarkan penghuni', 'hint' => 'Isi kamar kosong', 'icon' => 'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z'],
                                ['url' => route('pengelola.tagihan.index', ['baru' => 1]), 'label' => 'Terbitkan tagihan', 'hint' => 'Sekaligus untuk semua penghuni', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                ['url' => route('pengelola.katalog.index', ['baru' => 1]), 'label' => 'Tambah menu / galon', 'hint' => 'Jual di kios kos', 'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
                            ] as $qa)
                                <a href="{{ $qa['url'] }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-canvas">
                                    <span class="w-9 h-9 rounded-lg bg-brand-wash text-brand flex items-center justify-center shrink-0">
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qa['icon'] }}"/></svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-ink">{{ $qa['label'] }}</span>
                                        <span class="block text-2xs text-ink-soft">{{ $qa['hint'] }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Notifications Dropdown -->
                    @php
                        $unreadCount = Auth::user() ? Auth::user()->unreadNotificationsCount() : 0;
                        // Hanya 8 terbaru dari database (sebelumnya seluruh riwayat notifikasi dimuat lalu dipotong di PHP)
                        $recentNotifications = Auth::user() ? Auth::user()->appNotifications()->limit(8)->get() : collect();
                    @endphp
                    <div class="relative" x-data="{ 
                        open: false, 
                        unreadCount: {{ $unreadCount }}
                    }">
                        <button type="button" @click="open = !open" 
                                class="relative p-2 text-ink-soft hover:text-ink hover:bg-canvas rounded-xl transition-colors"
                                title="Notifikasi" aria-label="Notifikasi" aria-haspopup="true" :aria-expanded="open">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span x-show="unreadCount > 0" 
                                  x-cloak
                                  class="absolute top-1 right-1 w-2.5 h-2.5 bg-alert rounded-full ring-2 ring-surface"></span>
                        </button>

                        <!-- Notification Menu -->
                        <div x-show="open" 
                             @click.away="open = false" 
                             x-cloak
                             class="absolute right-0 mt-2 w-80 sm:w-96 bg-surface rounded-2xl shadow-xl border border-line py-2 z-50">
                            <div class="px-4 py-2.5 border-b border-line flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-bold text-xs text-ink uppercase tracking-wider">Notifikasi</h4>
                                    <span x-show="unreadCount > 0" 
                                          x-text="unreadCount + ' Baru'"
                                          class="text-2xs font-semibold text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-line">
                                    </span>
                                    <span x-show="unreadCount === 0" 
                                          class="text-2xs font-semibold text-ink-soft bg-canvas px-2 py-0.5 rounded-full border border-line">
                                        Semua Terbaca
                                    </span>
                                </div>
                                <button type="button" 
                                        x-show="unreadCount > 0"
                                        @click="
                                            fetch('{{ route('notifications.read_all') }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                }
                                            }).then(() => {
                                                unreadCount = 0;
                                                $dispatch('all-notifications-read');
                                            });
                                        "
                                        class="text-2xs text-brand hover:text-brand-deep font-semibold transition-colors">
                                    Tandai Semua Dibaca
                                </button>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-line">
                                @forelse($recentNotifications as $notif)
                                    <div x-data="{ isRead: {{ $notif->is_read ? 'true' : 'false' }} }"
                                         @all-notifications-read.window="isRead = true"
                                         @click="
                                             if (!isRead) {
                                                 isRead = true;
                                                 unreadCount = Math.max(0, unreadCount - 1);
                                                 fetch('{{ url('/notifications') }}/{{ $notif->id }}/read', {
                                                     method: 'POST',
                                                     headers: {
                                                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                         'Accept': 'application/json'
                                                     }
                                                 });
                                             }
                                             @if($notif->action_url)
                                                 window.location.href = '{{ $notif->action_url }}';
                                             @endif
                                         "
                                         class="p-3.5 transition-colors cursor-pointer group flex items-start space-x-2.5"
                                         :class="!isRead ? 'bg-brand-wash/40 hover:bg-brand-wash/70' : 'hover:bg-canvas opacity-80 hover:opacity-100'">
                                        <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0 transition-colors"
                                              :class="!isRead ? 'bg-brand ring-2 ring-brand-wash' : 'bg-line'"></span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <p class="text-xs font-semibold leading-snug truncate" 
                                                   :class="!isRead ? 'text-ink font-bold' : 'text-ink-soft'">
                                                    {{ $notif->title }}
                                                </p>
                                                <span x-show="!isRead" class="inline-block flex-shrink-0 text-2xs font-bold text-brand bg-brand-wash px-1.5 py-0.2 rounded">
                                                    Baru
                                                </span>
                                            </div>
                                            <p class="text-xs text-ink-soft mt-0.5 leading-relaxed line-clamp-2">{{ $notif->message }}</p>
                                            <div class="flex items-center justify-between mt-1.5">
                                                <span class="text-2xs text-ink-faint font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                                @if($notif->action_url)
                                                    <span class="text-2xs font-semibold text-brand group-hover:underline flex items-center space-x-0.5">
                                                        <span>Buka</span>
                                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    </span>
                                                @else
                                                    <span x-show="!isRead" class="text-2xs text-ink-faint italic">Ketuk untuk tandai dibaca</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 text-center text-xs text-ink-faint">
                                        Tidak ada notifikasi saat ini.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>


                    <a href="{{ route('penghuni.beranda') }}" target="_blank" rel="noopener"
                       class="hidden lg:inline-flex w-10 h-10 items-center justify-center text-ink-soft hover:text-ink hover:bg-surface rounded-xl transition-colors"
                       title="Lihat web penghuni" aria-label="Lihat web penghuni (tab baru)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    </a>
                </div>
            </header>

            <main id="main" tabindex="-1" class="flex-1 px-4 pt-2 pb-28 sm:p-6 sm:pb-28 lg:p-8 max-w-[96rem] w-full mx-auto focus:outline-none">
                @hasSection('no_page_band')
                @else
                    <header class="page-band mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="text-[1.375rem] leading-tight sm:text-3xl text-brand-deep">@yield('page_title', 'Dashboard')</h1>
                            @hasSection('page_subtitle')
                                <p class="mt-1 text-xs sm:text-base text-ink-soft max-w-3xl line-clamp-2 sm:line-clamp-none">@yield('page_subtitle')</p>
                            @endif
                        </div>
                        @hasSection('page_actions')
                            <div class="flex flex-wrap items-center gap-2.5 shrink-0">@yield('page_actions')</div>
                        @endif
                    </header>
                @endif

                @yield('content')
            </main>

            <footer class="hidden lg:block px-6 py-5 text-center text-xs text-ink-soft">
                <p>&copy; {{ date('Y') }} {{ config('kos.name') }}</p>
            </footer>
        </div>
    </div>

    @include('layouts.partials.pengelola-tabbar')

    @if($errors->any() && old('_restore'))
        <script>window.__kosOld = {{ Illuminate\Support\Js::from(['old' => old()]) }};</script>
    @endif

    @stack('scripts')
</body>
</html>
