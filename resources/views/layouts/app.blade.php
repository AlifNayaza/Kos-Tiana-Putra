<!DOCTYPE html>
<html lang="id" class="h-full bg-canvas">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard Pengelola' }} — Kos Tiana Putra 1</title>

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
    </style>
    @stack('styles')
</head>
<body class="h-full text-ink antialiased selection:bg-brand-wash selection:text-brand-deep" x-data="{ sidebarOpen: false, profileDropdown: false, notifDropdown: false }">
    
    <!-- Toast Component -->
    @include('components.toast')

    <div class="min-h-full flex flex-col lg:flex-row">
        
        <!-- ============================================================== -->
        <!-- MOBILE SIDEBAR BACKDROP & DRAWER -->
        <!-- ============================================================== -->
        <div x-show="sidebarOpen" 
             x-cloak
             class="fixed inset-0 z-40 lg:hidden" 
             role="dialog" 
             aria-modal="true">
            <div x-show="sidebarOpen" 
                 x-transition:enter="transition-opacity ease-linear duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="transition-opacity ease-linear duration-300" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 @click="sidebarOpen = false" 
                 class="fixed inset-0 bg-ink/60 backdrop-blur-xs"></div>

            <div x-show="sidebarOpen" 
                 x-transition:enter="transition ease-in-out duration-300 transform" 
                 x-transition:enter-start="-translate-x-full" 
                 x-transition:enter-end="translate-x-0" 
                 x-transition:leave="transition ease-in-out duration-300 transform" 
                 x-transition:leave-start="translate-x-0" 
                 x-transition:leave-end="-translate-x-full" 
                 class="relative flex-1 flex flex-col max-w-xs w-full bg-surface h-full shadow-2xl border-r border-line">
                <div class="p-5 flex items-center justify-between border-b border-line">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-brand text-surface flex items-center justify-center font-bold text-lg shadow-xs">
                            KT
                        </div>
                        <div>
                            <h2 class="font-bold text-ink text-sm leading-tight">Kos Tiana Putra 1</h2>
                            <p class="text-xs text-ink-soft">Panel Pengelola</p>
                        </div>
                    </div>
                    <button @click="sidebarOpen = false" class="text-ink-faint hover:text-ink p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Navigation List Mobile -->
                <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto">
                    @include('layouts.partials.sidebar-links')
                </nav>

                <!-- User Footer Mobile -->
                <div class="p-4 border-t border-line bg-canvas">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-full bg-brand-wash text-brand font-semibold flex items-center justify-center text-xs">
                                {{ substr(Auth::user()->name ?? 'P', 0, 2) }}
                            </div>
                            <div class="truncate">
                                <p class="text-xs font-semibold text-ink truncate">{{ Auth::user()->name ?? 'Pengelola' }}</p>
                                <span class="inline-flex items-center text-[10px] font-medium text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-line">Pengelola</span>
                            </div>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-ink-faint hover:text-alert p-1.5 rounded-lg transition-colors" title="Keluar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- DESKTOP SIDEBAR -->
        <!-- ============================================================== -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-surface border-r border-line z-20">
            <!-- Brand Header -->
            <div class="h-16 flex items-center px-6 border-b border-line justify-between">
                <a href="{{ route('pengelola.dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-9 h-9 rounded-xl bg-brand text-surface flex items-center justify-center font-bold text-sm shadow-xs transition-transform group-hover:scale-105">
                        KT
                    </div>
                    <div>
                        <h1 class="font-bold text-ink text-sm tracking-tight leading-none">Kos Tiana Putra 1</h1>
                        <p class="text-[11px] text-ink-soft mt-1 font-medium">Panel Pengelola</p>
                    </div>
                </a>
            </div>

            <!-- Role Badge / Quick Indicator -->
            <div class="px-6 py-3 bg-canvas border-b border-line flex items-center justify-between">
                <span class="text-[11px] font-semibold text-ink-soft uppercase tracking-wider">Peran Aktif</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-brand-wash text-brand border border-line">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand mr-1.5"></span>
                    Pengelola
                </span>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto">
                @include('layouts.partials.sidebar-links')
            </nav>

            <!-- Bottom User & Role Quick Switch -->
            <div class="p-4 border-t border-line bg-canvas">
                <!-- Role Switcher Shortcuts for effortless demo testing -->
                <div class="mb-3 p-2 bg-surface rounded-lg border border-line shadow-xs">
                    <p class="text-[10px] font-semibold text-ink-faint uppercase tracking-wider mb-1.5 px-1">Ganti Demo Role</p>
                    <div class="grid grid-cols-3 gap-1">
                        <a href="{{ route('demo.login', 'pengelola') }}" class="text-[10px] text-center py-1 rounded bg-brand-wash font-semibold text-brand border border-line">Pengelola</a>
                        <a href="{{ route('demo.login', 'petugas') }}" class="text-[10px] text-center py-1 rounded bg-surface hover:bg-canvas font-medium text-ink-soft border border-line">Petugas</a>
                        <a href="{{ route('demo.login', 'penghuni') }}" class="text-[10px] text-center py-1 rounded bg-surface hover:bg-canvas font-medium text-ink-soft border border-line">Penghuni</a>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-brand text-surface font-bold flex items-center justify-center text-xs flex-shrink-0">
                            {{ substr(Auth::user()->name ?? 'P', 0, 1) }}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-semibold text-ink truncate">{{ Auth::user()->name ?? 'Pengelola' }}</p>
                            <p class="text-[11px] text-ink-soft truncate">{{ Auth::user()->email ?? '' }}</p>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-ink-faint hover:text-alert p-1.5 rounded-lg hover:bg-alert-wash transition-colors" title="Keluar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- ============================================================== -->
        <!-- MAIN CONTENT AREA -->
        <!-- ============================================================== -->
        <div class="lg:pl-64 flex flex-col flex-1 min-h-screen">
            
            <!-- Top App Bar -->
            <header class="h-16 bg-surface border-b border-line sticky top-0 z-10 px-4 sm:px-6 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 text-ink-soft hover:text-ink rounded-lg hover:bg-canvas">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-base font-bold text-ink leading-tight">
                            @yield('page_title', 'Dashboard')
                        </h2>
                        <p class="text-xs text-ink-soft hidden sm:block">
                            @yield('page_subtitle', 'Sistem Pengelolaan Kos & Layanan Terpadu')
                        </p>
                    </div>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center space-x-3">
                    <!-- Notifications Dropdown -->
                    @php
                        $unreadCount = Auth::user() ? Auth::user()->unreadNotificationsCount() : 0;
                    @endphp
                    <div class="relative" x-data="{ 
                        open: false, 
                        unreadCount: {{ $unreadCount }}
                    }">
                        <button @click="open = !open" 
                                class="relative p-2 text-ink-soft hover:text-ink hover:bg-canvas rounded-xl transition-colors"
                                title="Notifikasi">
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
                                          class="text-[11px] font-semibold text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-line">
                                    </span>
                                    <span x-show="unreadCount === 0" 
                                          class="text-[11px] font-semibold text-ink-soft bg-canvas px-2 py-0.5 rounded-full border border-line">
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
                                @forelse(Auth::user()->appNotifications->take(8) ?? [] as $notif)
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
                                                <span x-show="!isRead" class="inline-block flex-shrink-0 text-[9px] font-bold text-brand bg-brand-wash px-1.5 py-0.2 rounded">
                                                    Baru
                                                </span>
                                            </div>
                                            <p class="text-xs text-ink-soft mt-0.5 leading-relaxed line-clamp-2">{{ $notif->message }}</p>
                                            <div class="flex items-center justify-between mt-1.5">
                                                <span class="text-[10px] text-ink-faint font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                                @if($notif->action_url)
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
                                        Tidak ada notifikasi saat ini.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Direct View Site / Tenant Preview Link -->
                    <a href="{{ route('penghuni.beranda') }}" 
                       target="_blank" 
                       class="hidden sm:inline-flex items-center space-x-1.5 text-xs font-semibold text-brand bg-brand-wash hover:bg-brand-wash/80 px-3 py-2 rounded-xl border border-line transition-colors">
                        <span>Lihat Web Penghuni</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                </div>
            </header>

            <!-- Main Page Content Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-surface border-t border-line px-6 py-4 text-center sm:flex sm:justify-between sm:items-center text-xs text-ink-soft">
                <p>&copy; {{ date('Y') }} Kos Tiana Putra 1 &bull; Sistem Layanan Kos Terpadu</p>
                <p class="mt-1 sm:mt-0 font-medium text-ink-faint">Laravel 11 &bull; Tailwind CSS &bull; Alpine.js</p>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
