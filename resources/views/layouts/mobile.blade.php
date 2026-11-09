<!DOCTYPE html>
<html lang="id" class="h-full bg-canvas" data-role="{{ Auth::user()->role ?? 'penghuni' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Layanan Kos' }} — Kos Tiana Putra 1</title>


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
        html { scroll-behavior: smooth; -webkit-tap-highlight-color: transparent; }
    </style>
    @stack('styles')
</head>
<body class="h-full text-ink antialiased">
    <a href="#main" class="skip-link">Lewati ke konten utama</a>

    <!-- Toast Component -->
    @include('components.toast')

    <!-- Full-Width Adaptive Layout Container -->
    <div class="min-h-screen flex flex-col relative">
        
        <!-- ============================================================== -->
        <!-- TOP APP BAR (Sticky, Max-w-7xl, Full Width Adaptive) -->
        <!-- ============================================================== -->
        <header class="app-bar sticky top-0 z-40 rounded-b-[1.75rem] shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 w-full flex items-center justify-between">
                <!-- Brand & Room Pill -->
                <div class="flex items-center space-x-3">
                    <a href="{{ Auth::user()->isPetugas() ? route('petugas.antrean') : route('penghuni.beranda') }}" 
                       class="w-10 h-10 rounded-xl bg-white text-brand-deep flex items-center justify-center font-bold text-sm shadow-md flex-shrink-0 transition-transform hover:scale-105" aria-label="Kos Tiana Putra, ke beranda">
                        KT
                    </a>
                    <div class="min-w-0">
                        <span class="block font-display font-bold text-white text-base leading-tight whitespace-nowrap">Kos Tiana Putra 1</span>
                        <p class="mt-0.5 flex items-center gap-1.5 text-xs text-white/90 font-medium whitespace-nowrap">
                            @if(Auth::user() && Auth::user()->isPenghuni() && Auth::user()->tenantProfile && Auth::user()->tenantProfile->room)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-white text-brand-deep tabular-nums">{{ Auth::user()->tenantProfile->room->room_number }}</span>
                            @elseif(Auth::user() && Auth::user()->isPetugas())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-white/20 text-white">Petugas</span>
                            @endif
                            <span class="truncate hidden min-[400px]:inline">@yield('header_subtitle', 'Layanan Kos')</span>
                        </p>
                    </div>
                </div>

                @php
                    $petugasActiveCount = (Auth::user() && Auth::user()->isPetugas()) 
                        ? \App\Models\Order::whereIn('status', ['menunggu', 'diproses', 'diantar'])->count() 
                        : 0;
                @endphp

                <!-- Desktop Horizontal Navigation Links (Only on >= md, strictly whitespace-nowrap) -->
                @if(Auth::user() && Auth::user()->isPenghuni())
                    <nav class="hidden md:flex items-center gap-1.5 whitespace-nowrap">
                        <a href="{{ route('penghuni.beranda') }}" 
                           @if(request()->routeIs('penghuni.beranda')) aria-current="page" @endif
                           class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('penghuni.beranda') ? 'bg-white text-brand-deep font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/15 font-medium' }}">
                            Beranda
                        </a>
                        <a href="{{ route('penghuni.katalog') }}" 
                           @if(request()->routeIs('penghuni.katalog*') || request()->routeIs('penghuni.menu*')) aria-current="page" @endif
                           class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('penghuni.katalog*') || request()->routeIs('penghuni.menu*') ? 'bg-white text-brand-deep font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/15 font-medium' }}">
                            Pesan Menu
                        </a>
                        <a href="{{ route('penghuni.pesanan') }}" 
                           @if(request()->routeIs('penghuni.pesanan*')) aria-current="page" @endif
                           class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('penghuni.pesanan*') ? 'bg-white text-brand-deep font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/15 font-medium' }}">
                            Pesanan Saya
                        </a>
                        <a href="{{ route('penghuni.tagihan') }}" 
                           @if(request()->routeIs('penghuni.tagihan*')) aria-current="page" @endif
                           class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('penghuni.tagihan*') ? 'bg-white text-brand-deep font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/15 font-medium' }}">
                            Tagihan Sewa
                        </a>
                    </nav>
                @elseif(Auth::user() && Auth::user()->isPetugas())
                    <nav class="hidden md:flex items-center gap-1.5 whitespace-nowrap">
                        <a href="{{ route('petugas.dashboard') }}" 
                           @if(request()->routeIs('petugas.dashboard') || request()->routeIs('petugas.antrean')) aria-current="page" @endif
                           class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5 {{ request()->routeIs('petugas.dashboard') || request()->routeIs('petugas.antrean') ? 'bg-white text-brand-deep font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/15 font-medium' }}">
                            <span>Antrean</span>
                            @if($petugasActiveCount > 0)
                                <span class="px-1.5 py-0.2 rounded-full text-2xs font-bold tabular-nums {{ request()->routeIs('petugas.dashboard') || request()->routeIs('petugas.antrean') ? 'bg-brand-deep text-white' : 'bg-ochre text-ink' }}">
                                    {{ $petugasActiveCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('petugas.stocks.index') }}" 
                           @if(request()->routeIs('petugas.stocks*') || request()->routeIs('petugas.stok')) aria-current="page" @endif
                           class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('petugas.stocks*') || request()->routeIs('petugas.stok') ? 'bg-white text-brand-deep font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/15 font-medium' }}">
                            Stok
                        </a>
                        <a href="{{ route('petugas.history') }}" 
                           @if(request()->routeIs('petugas.history*') || request()->routeIs('petugas.riwayat')) aria-current="page" @endif
                           class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('petugas.history*') || request()->routeIs('petugas.riwayat') ? 'bg-white text-brand-deep font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/15 font-medium' }}">
                            Riwayat
                        </a>
                    </nav>
                @endif

                <!-- Header Right (Notifications & Profile Switcher) -->
                <div class="flex items-center space-x-2" x-data="{ userMenu: false }">
                    <!-- Notifications Dropdown -->
                    @php
                        $unreadCount = Auth::user() ? Auth::user()->unreadNotificationsCount() : 0;
                        // Hanya 8 terbaru dari database (sebelumnya seluruh riwayat notifikasi dimuat lalu dipotong di PHP)
                        $recentNotifications = Auth::user() ? Auth::user()->appNotifications()->limit(8)->get() : collect();
                    @endphp
                    <div class="relative" x-data="{ 
                        notifOpen: false, 
                        unreadCount: {{ $unreadCount }}
                    }">
                        <button type="button" @click="notifOpen = !notifOpen" 
                                class="relative p-2.5 text-white hover:bg-white/15 rounded-xl transition-colors"
                                title="Notifikasi" aria-label="Notifikasi" aria-haspopup="true" :aria-expanded="notifOpen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span x-show="unreadCount > 0" 
                                  x-cloak
                                  class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-ochre rounded-full ring-2 ring-brand animate-pulse"></span>
                        </button>

                        <!-- Notification Menu -->
                        <div x-show="notifOpen" 
                             @click.away="notifOpen = false" 
                             x-cloak
                             class="absolute right-0 mt-2 w-80 sm:w-96 bg-surface rounded-2xl shadow-xl border border-line py-2 z-50">
                            <div class="px-4 py-2.5 border-b border-line flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-xs text-ink uppercase tracking-wider">Notifikasi</span>
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
                                @forelse($recentNotifications as $n)
                                    <div x-data="{ isRead: {{ $n->is_read ? 'true' : 'false' }} }"
                                         @all-notifications-read.window="isRead = true"
                                         @click="
                                             if (!isRead) {
                                                 isRead = true;
                                                 unreadCount = Math.max(0, unreadCount - 1);
                                                 fetch('{{ url('/notifications') }}/{{ $n->id }}/read', {
                                                     method: 'POST',
                                                     headers: {
                                                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                         'Accept': 'application/json'
                                                     }
                                                 });
                                             }
                                             @if($n->action_url)
                                                 window.location.href = '{{ $n->action_url }}';
                                             @endif
                                         "
                                         class="p-3.5 transition-colors cursor-pointer group flex items-start space-x-2.5"
                                         :class="!isRead ? 'bg-brand-wash/40 hover:bg-brand-wash/70' : 'hover:bg-canvas opacity-80 hover:opacity-100'">
                                        <span class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0 transition-colors"
                                              :class="!isRead ? 'bg-brand ring-2 ring-brand-wash' : 'bg-line'"></span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <p class="text-xs font-bold leading-snug truncate" 
                                                   :class="!isRead ? 'text-ink font-bold' : 'text-ink-soft'">
                                                    {{ $n->title }}
                                                </p>
                                                <span x-show="!isRead" class="inline-block flex-shrink-0 text-2xs font-bold text-brand bg-brand-wash px-1.5 py-0.2 rounded">
                                                    Baru
                                                </span>
                                            </div>
                                            <p class="text-xs text-ink-soft mt-0.5 leading-relaxed line-clamp-2">{{ $n->message }}</p>
                                            <div class="flex items-center justify-between mt-1.5">
                                                <span class="text-2xs text-ink-faint font-medium">{{ $n->created_at->diffForHumans() }}</span>
                                                @if($n->action_url)
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
                                        Belum ada notifikasi saat ini.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Profile & Role Switcher Dropdown -->
                    <div class="relative">
                        <button type="button" @click="userMenu = !userMenu" aria-label="Menu akun" aria-haspopup="true" :aria-expanded="userMenu"
                                class="w-10 h-10 rounded-full bg-white text-brand-deep font-bold flex items-center justify-center text-sm hover:ring-4 hover:ring-white/30 transition-all shadow-sm">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </button>

                        <div x-show="userMenu" 
                             @click.away="userMenu = false" 
                             x-cloak
                             class="absolute right-0 mt-2 w-60 bg-surface rounded-2xl shadow-xl border border-line py-2 z-50">
                            <div class="px-4 py-2.5 border-b border-line">
                                <p class="text-xs font-bold text-ink truncate">{{ Auth::user()->name ?? 'Pengguna' }}</p>
                                <p class="text-2xs text-ink-soft truncate">{{ Auth::user()->email ?? '' }}</p>
                                <div class="mt-1">
                                    <span class="inline-block text-2xs font-bold text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-line">
                                        Peran: {{ ucfirst(Auth::user()->role) }}
                                    </span>
                                </div>
                            </div>

                            <a href="{{ Auth::user()->isPetugas() ? route('petugas.profil') : route('penghuni.profil') }}" class="block px-4 py-2 text-xs font-semibold text-ink hover:bg-canvas">Profil &amp; Ganti Kata Sandi</a>

                            <!-- Fast Demo Role Switcher (khusus development: config kos.demo_login) -->
                            @if(config('kos.demo_login'))
                            <div class="px-3 py-2 border-b border-line bg-canvas">
                                <p class="text-2xs font-semibold text-ink-faint uppercase tracking-wider mb-1.5">Ganti Demo Role</p>
                                <div class="grid grid-cols-3 gap-1">
                                    <a href="{{ route('demo.login', 'penghuni') }}" class="text-2xs text-center py-1 rounded {{ Auth::user()->isPenghuni() ? 'bg-brand-wash text-brand font-semibold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas' }}">Penghuni</a>
                                    <a href="{{ route('demo.login', 'petugas') }}" class="text-2xs text-center py-1 rounded {{ Auth::user()->isPetugas() ? 'bg-brand-wash text-brand font-semibold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas' }}">Petugas</a>
                                    <a href="{{ route('demo.login', 'pengelola') }}" class="text-2xs text-center py-1 rounded {{ Auth::user()->isPengelola() ? 'bg-brand-wash text-brand font-semibold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas' }}">Pengelola</a>
                                </div>
                            </div>
                            @endif

                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-xs font-semibold text-alert hover:bg-alert-wash flex items-center space-x-2 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span>Keluar Akun</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ============================================================== -->
        <!-- MAIN CONTENT AREA (Adaptive max-w-7xl, pb-28 for mobile, pb-12 for desktop) -->
        <!-- ============================================================== -->
        <main id="main" tabindex="-1" class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 md:pb-12 focus:outline-none">
            @yield('content')
        </main>

        <!-- ============================================================== -->
        <!-- FIXED BOTTOM NAVIGATION BAR (Mobile Only: STRICTLY md:hidden) -->
        <!-- ============================================================== -->
        <nav aria-label="Navigasi utama" class="fixed bottom-0 inset-x-0 z-40 bg-surface/95 backdrop-blur-md border-t border-line shadow-[0_-4px_16px_rgb(23_33_31/0.08)] md:hidden pb-[env(safe-area-inset-bottom,0px)]">
            <div class="max-w-lg mx-auto grid {{ (Auth::user() && Auth::user()->isPetugas()) ? 'grid-cols-3' : 'grid-cols-4' }} items-stretch px-2">
                
                @if(Auth::user() && Auth::user()->isPetugas())
                    <!-- ================= PETUGAS NAVIGATION (3 Menu Utama) ================= -->
                    <!-- 1. Antrean Tugas -->
                    <a href="{{ route('petugas.dashboard') }}" 
                       class="bn-link" @if(request()->routeIs('petugas.dashboard*') || request()->routeIs('petugas.antrean*')) aria-current="page" @endif>
                        <div class="bn-icon relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            @if($petugasActiveCount > 0)
                                <span class="absolute -top-1.5 -right-2.5 min-w-[18px] h-[18px] px-1 bg-wait text-surface text-2xs font-bold rounded-full flex items-center justify-center tabular-nums shadow-xs">
                                    {{ $petugasActiveCount }}
                                </span>
                            @endif
                        </div>
                        <span class="whitespace-nowrap">Antrean</span>
                    </a>

                    <!-- 2. Stok Galon & Menu -->
                    <a href="{{ route('petugas.stocks.index') }}" 
                       class="bn-link" @if(request()->routeIs('petugas.stocks*') || request()->routeIs('petugas.stok*')) aria-current="page" @endif>
                        <div class="bn-icon relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span class="whitespace-nowrap">Stok</span>
                    </a>

                    <!-- 3. Riwayat Selesai -->
                    <a href="{{ route('petugas.history') }}" 
                       class="bn-link" @if(request()->routeIs('petugas.history*') || request()->routeIs('petugas.riwayat*')) aria-current="page" @endif>
                        <div class="bn-icon relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="whitespace-nowrap">Riwayat</span>
                    </a>

                @else
                    <!-- ================= PENGHUNI NAVIGATION (4 Menu Utama) ================= -->
                    <!-- 1. Beranda -->
                    <a href="{{ route('penghuni.beranda') }}" 
                       class="bn-link" @if(request()->routeIs('penghuni.beranda')) aria-current="page" @endif>
                        <div class="bn-icon relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <span class="whitespace-nowrap">Beranda</span>
                    </a>

                    <!-- 2. Pesan Menu -->
                    <a href="{{ route('penghuni.katalog') }}" 
                       class="bn-link" @if(request()->routeIs('penghuni.katalog*') || request()->routeIs('penghuni.menu*')) aria-current="page" @endif>
                        <div class="bn-icon relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <span class="whitespace-nowrap">Pesan Menu</span>
                    </a>

                    <!-- 3. Pesanan Saya -->
                    <a href="{{ route('penghuni.pesanan') }}" 
                       class="bn-link" @if(request()->routeIs('penghuni.pesanan*')) aria-current="page" @endif>
                        <div class="bn-icon relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <span class="whitespace-nowrap">Pesanan Saya</span>
                    </a>

                    <!-- 4. Tagihan Sewa -->
                    <a href="{{ route('penghuni.tagihan') }}" 
                       class="bn-link" @if(request()->routeIs('penghuni.tagihan*')) aria-current="page" @endif>
                        <div class="bn-icon relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="whitespace-nowrap">Tagihan Sewa</span>
                    </a>
                @endif

            </div>
        </nav>
    </div>

    @if(session('cart_clear'))
        <script>try { localStorage.removeItem('kos-cart-{{ Auth::id() }}'); } catch (e) {}</script>
    @endif

    @stack('scripts')
</body>
</html>
