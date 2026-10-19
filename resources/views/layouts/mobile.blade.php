<!DOCTYPE html>
<html lang="id" class="h-full bg-canvas">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Layanan Kos' }} — Kos Tiana Putra 1</title>

    <!-- Google Fonts: Instrument Sans & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Vite with CDN fallback) -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        },
                        colors: {
                            canvas: '#f2f4f1',
                            surface: '#fafbf9',
                            line: '#dbe1dc',
                            ink: '#16201e',
                            'ink-soft': '#55645f',
                            'ink-faint': '#8a9892',
                            brand: '#006059',
                            'brand-deep': '#004540',
                            'brand-wash': '#e1eeeb',
                            ochre: '#c98a2b',
                            'ochre-wash': '#f6ead4',
                            wait: '#a86a0c',
                            'wait-wash': '#f8f1e5',
                            process: '#3b5a8c',
                            'process-wash': '#edf2f8',
                            done: '#4b7d57',
                            'done-wash': '#edf5f0',
                            alert: '#b03a22',
                            'alert-wash': '#faece9',
                        }
                    }
                }
            }
        </script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
        html { scroll-behavior: smooth; -webkit-tap-highlight-color: transparent; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-canvas text-ink antialiased selection:bg-brand-wash selection:text-brand-deep">

    <!-- Toast Component -->
    @include('components.toast')

    <!-- Full-Width Adaptive Layout Container -->
    <div class="min-h-screen bg-canvas flex flex-col relative">
        
        <!-- ============================================================== -->
        <!-- TOP APP BAR (Sticky, Max-w-7xl, Full Width Adaptive) -->
        <!-- ============================================================== -->
        <header class="sticky top-0 z-40 bg-surface/95 backdrop-blur-md border-b border-line">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 w-full flex items-center justify-between">
                <!-- Brand & Room Pill -->
                <div class="flex items-center space-x-3">
                    <a href="{{ Auth::user()->isPetugas() ? route('petugas.antrean') : route('penghuni.beranda') }}" 
                       class="w-9 h-9 rounded-xl bg-brand text-surface flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0 transition-transform hover:scale-105">
                        KT
                    </a>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-ink text-sm leading-tight">Kos Tiana Putra 1</span>
                            @if(Auth::user() && Auth::user()->isPenghuni() && Auth::user()->tenantProfile && Auth::user()->tenantProfile->room)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-brand-wash text-brand border border-line tabular-nums">
                                    {{ Auth::user()->tenantProfile->room->room_number }}
                                </span>
                            @elseif(Auth::user() && Auth::user()->isPetugas())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-canvas text-ink-soft border border-line">
                                    Petugas
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] text-ink-soft font-medium">
                            @yield('header_subtitle', 'Layanan Kos')
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
                           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('penghuni.beranda') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
                            Beranda
                        </a>
                        <a href="{{ route('penghuni.katalog') }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('penghuni.katalog*') || request()->routeIs('penghuni.menu*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
                            Pesan Menu
                        </a>
                        <a href="{{ route('penghuni.pesanan') }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('penghuni.pesanan*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
                            Pesanan Saya
                        </a>
                        <a href="{{ route('penghuni.tagihan') }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('penghuni.tagihan*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
                            Tagihan Sewa
                        </a>
                    </nav>
                @elseif(Auth::user() && Auth::user()->isPetugas())
                    <nav class="hidden md:flex items-center gap-1.5 whitespace-nowrap">
                        <a href="{{ route('petugas.dashboard') }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5 {{ request()->routeIs('petugas.dashboard') || request()->routeIs('petugas.antrean') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
                            <span>Antrean</span>
                            @if($petugasActiveCount > 0)
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold tabular-nums {{ request()->routeIs('petugas.dashboard') || request()->routeIs('petugas.antrean') ? 'bg-wait text-surface' : 'bg-wait-wash text-wait' }}">
                                    {{ $petugasActiveCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('petugas.stocks.index') }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('petugas.stocks*') || request()->routeIs('petugas.stok') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
                            Stok
                        </a>
                        <a href="{{ route('petugas.history') }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request()->routeIs('petugas.history*') || request()->routeIs('petugas.riwayat') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
                            Riwayat
                        </a>
                    </nav>
                @endif

                <!-- Header Right (Notifications & Profile Switcher) -->
                <div class="flex items-center space-x-2" x-data="{ userMenu: false }">
                    <!-- Notifications Dropdown -->
                    @php
                        $unreadCount = Auth::user() ? Auth::user()->unreadNotificationsCount() : 0;
                    @endphp
                    <div class="relative" x-data="{ 
                        notifOpen: false, 
                        unreadCount: {{ $unreadCount }}
                    }">
                        <button @click="notifOpen = !notifOpen" 
                                class="relative p-2 text-ink-soft hover:text-ink hover:bg-canvas rounded-xl transition-colors"
                                title="Notifikasi">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span x-show="unreadCount > 0" 
                                  x-cloak
                                  class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-alert rounded-full ring-2 ring-surface animate-pulse"></span>
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
                                          class="text-[10px] font-semibold text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-line">
                                    </span>
                                    <span x-show="unreadCount === 0" 
                                          class="text-[10px] font-semibold text-ink-soft bg-canvas px-2 py-0.5 rounded-full border border-line">
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
                                        class="text-[11px] text-brand hover:text-brand-deep font-semibold transition-colors">
                                    Tandai Semua Dibaca
                                </button>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-line">
                                @forelse(Auth::user()->appNotifications->take(8) ?? [] as $n)
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
                                                <span x-show="!isRead" class="inline-block flex-shrink-0 text-[9px] font-bold text-brand bg-brand-wash px-1.5 py-0.2 rounded">
                                                    Baru
                                                </span>
                                            </div>
                                            <p class="text-xs text-ink-soft mt-0.5 leading-relaxed line-clamp-2">{{ $n->message }}</p>
                                            <div class="flex items-center justify-between mt-1.5">
                                                <span class="text-[10px] text-ink-faint font-medium">{{ $n->created_at->diffForHumans() }}</span>
                                                @if($n->action_url)
                                                    <span class="text-[10px] font-semibold text-brand group-hover:underline flex items-center space-x-0.5">
                                                        <span>Buka</span>
                                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    </span>
                                                @else
                                                    <span x-show="!isRead" class="text-[9px] text-ink-faint italic">Ketuk untuk tandai dibaca</span>
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
                        <button @click="userMenu = !userMenu" 
                                class="w-8 h-8 rounded-full bg-brand text-surface font-bold flex items-center justify-center text-xs hover:ring-2 hover:ring-brand-wash transition-all shadow-xs">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </button>

                        <div x-show="userMenu" 
                             @click.away="userMenu = false" 
                             x-cloak
                             class="absolute right-0 mt-2 w-60 bg-surface rounded-2xl shadow-xl border border-line py-2 z-50">
                            <div class="px-4 py-2.5 border-b border-line">
                                <p class="text-xs font-bold text-ink truncate">{{ Auth::user()->name ?? 'Pengguna' }}</p>
                                <p class="text-[11px] text-ink-soft truncate">{{ Auth::user()->email ?? '' }}</p>
                                <div class="mt-1">
                                    <span class="inline-block text-[10px] font-bold text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-line">
                                        Peran: {{ ucfirst(Auth::user()->role) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Fast Demo Role Switcher -->
                            <div class="px-3 py-2 border-b border-line bg-canvas">
                                <p class="text-[10px] font-semibold text-ink-faint uppercase tracking-wider mb-1.5">Ganti Demo Role</p>
                                <div class="grid grid-cols-3 gap-1">
                                    <a href="{{ route('demo.login', 'penghuni') }}" class="text-[10px] text-center py-1 rounded {{ Auth::user()->isPenghuni() ? 'bg-brand-wash text-brand font-semibold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas' }}">Penghuni</a>
                                    <a href="{{ route('demo.login', 'petugas') }}" class="text-[10px] text-center py-1 rounded {{ Auth::user()->isPetugas() ? 'bg-brand-wash text-brand font-semibold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas' }}">Petugas</a>
                                    <a href="{{ route('demo.login', 'pengelola') }}" class="text-[10px] text-center py-1 rounded {{ Auth::user()->isPengelola() ? 'bg-brand-wash text-brand font-semibold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas' }}">Pengelola</a>
                                </div>
                            </div>

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
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 md:pb-12">
            @yield('content')
        </main>

        <!-- ============================================================== -->
        <!-- FIXED BOTTOM NAVIGATION BAR (Mobile Only: STRICTLY md:hidden) -->
        <!-- ============================================================== -->
        <nav class="fixed bottom-0 inset-x-0 z-40 bg-surface/95 backdrop-blur-md border-t border-line shadow-lg md:hidden pb-[env(safe-area-inset-bottom,0px)]">
            <div class="max-w-lg mx-auto grid {{ (Auth::user() && Auth::user()->isPetugas()) ? 'grid-cols-3' : 'grid-cols-4' }} h-16 items-center px-2">
                
                @if(Auth::user() && Auth::user()->isPetugas())
                    <!-- ================= PETUGAS NAVIGATION (3 Menu Utama) ================= -->
                    <!-- 1. Antrean Tugas -->
                    <a href="{{ route('petugas.dashboard') }}" 
                       class="flex flex-col items-center justify-center py-1 transition-colors {{ request()->routeIs('petugas.dashboard*') || request()->routeIs('petugas.antrean*') ? 'text-brand font-bold' : 'text-ink-faint hover:text-ink' }}">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            @if($petugasActiveCount > 0)
                                <span class="absolute -top-1.5 -right-2.5 min-w-[18px] h-[18px] px-1 bg-wait text-surface text-[10px] font-bold rounded-full flex items-center justify-center tabular-nums shadow-xs">
                                    {{ $petugasActiveCount }}
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] mt-1 font-semibold whitespace-nowrap">Antrean</span>
                    </a>

                    <!-- 2. Stok Galon & Menu -->
                    <a href="{{ route('petugas.stocks.index') }}" 
                       class="flex flex-col items-center justify-center py-1 transition-colors {{ request()->routeIs('petugas.stocks*') || request()->routeIs('petugas.stok*') ? 'text-brand font-bold' : 'text-ink-faint hover:text-ink' }}">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span class="text-[11px] mt-1 font-semibold whitespace-nowrap">Stok</span>
                    </a>

                    <!-- 3. Riwayat Selesai -->
                    <a href="{{ route('petugas.history') }}" 
                       class="flex flex-col items-center justify-center py-1 transition-colors {{ request()->routeIs('petugas.history*') || request()->routeIs('petugas.riwayat*') ? 'text-brand font-bold' : 'text-ink-faint hover:text-ink' }}">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] mt-1 font-semibold whitespace-nowrap">Riwayat</span>
                    </a>

                @else
                    <!-- ================= PENGHUNI NAVIGATION (4 Menu Utama) ================= -->
                    <!-- 1. Beranda -->
                    <a href="{{ route('penghuni.beranda') }}" 
                       class="flex flex-col items-center justify-center py-1 transition-colors {{ request()->routeIs('penghuni.beranda') ? 'text-brand font-bold' : 'text-ink-faint hover:text-ink' }}">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <span class="text-[11px] mt-1 whitespace-nowrap">Beranda</span>
                    </a>

                    <!-- 2. Pesan Menu -->
                    <a href="{{ route('penghuni.katalog') }}" 
                       class="flex flex-col items-center justify-center py-1 transition-colors {{ request()->routeIs('penghuni.katalog*') || request()->routeIs('penghuni.menu*') ? 'text-brand font-bold' : 'text-ink-faint hover:text-ink' }}">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <span class="text-[11px] mt-1 whitespace-nowrap">Pesan Menu</span>
                    </a>

                    <!-- 3. Pesanan Saya -->
                    <a href="{{ route('penghuni.pesanan') }}" 
                       class="flex flex-col items-center justify-center py-1 transition-colors {{ request()->routeIs('penghuni.pesanan*') ? 'text-brand font-bold' : 'text-ink-faint hover:text-ink' }}">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] mt-1 whitespace-nowrap">Pesanan Saya</span>
                    </a>

                    <!-- 4. Tagihan Sewa -->
                    <a href="{{ route('penghuni.tagihan') }}" 
                       class="flex flex-col items-center justify-center py-1 transition-colors {{ request()->routeIs('penghuni.tagihan*') ? 'text-brand font-bold' : 'text-ink-faint hover:text-ink' }}">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] mt-1 whitespace-nowrap">Tagihan Sewa</span>
                    </a>
                @endif

            </div>
        </nav>
    </div>

    @stack('scripts')
</body>
</html>
