<!DOCTYPE html>
<html lang="id" class="h-full bg-canvas">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — Kos Tiana Putra 1</title>

    <!-- Google Fonts: Instrument Sans & Plus Jakarta Sans Fallback -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Instrument Sans"', '"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
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
    @endif

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Instrument Sans', 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-full bg-canvas text-ink antialiased selection:bg-brand-wash selection:text-brand-deep">

    @include('components.toast')

    <div x-data="{ 
            showPassword: false, 
            loading: false, 
            remember: {{ old('remember') ? 'true' : 'false' }}, 
            checkboxFocused: false, 
            forgotModal: false, 
            loadingDemo: null 
         }" 
         class="min-h-screen flex flex-col lg:flex-row">

        <!-- ============================================================== -->
        <!-- 1. HEADER MOBILE (< lg, tinggi <= 220px di 390px)              -->
        <!-- ============================================================== -->
        <header class="lg:hidden relative bg-brand text-white px-5 pt-4 pb-2 overflow-hidden flex flex-col justify-between shrink-0 shadow-xs select-none max-h-[220px]">
            <!-- Baris Nama Kos & Status Warkop -->
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base font-bold tracking-tight text-white">Kos Tiana Putra</span>
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-surface/15 text-white text-[11px] font-mono font-bold border border-surface/25 shadow-xs">1</span>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] text-white/80">
                    <span class="w-1.5 h-1.5 rounded-full bg-ochre"></span>
                    <span>Warkop buka s.d. 23.00</span>
                </div>
            </div>

            <!-- Komponen Ilustrasi Kompak -->
            <x-auth.kos-scene compact class="mt-2 px-2" />
        </header>

        <!-- ============================================================== -->
        <!-- 2. PANEL KIRI DESKTOP (>= lg)                                  -->
        <!-- ============================================================== -->
        <aside class="relative hidden min-h-dvh lg:w-[46%] xl:w-[44%] flex-col justify-between overflow-hidden bg-brand text-white lg:flex shrink-0 select-none">
            <div class="flex items-center gap-3 px-12 pt-12">
                <div class="w-10 h-10 rounded-xl bg-surface/10 border border-surface/20 text-surface flex items-center justify-center font-bold text-sm tracking-wide shadow-xs">
                    KT
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-semibold tracking-tight text-white">Kos Tiana Putra</span>
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-surface/15 text-white text-xs font-mono font-bold border border-surface/25 shadow-xs" title="Cabang 1">1</span>
                </div>
            </div>

            <div class="px-12 py-4">
                <h1 class="max-w-[15ch] text-balance text-[clamp(2.5rem,4.2vw,4rem)] font-semibold leading-[1.05] tracking-tight">
                    Kopi hangat dan galon sampai depan pintu.
                </h1>
                <p class="mt-5 flex items-center gap-3 text-[15px] text-white/80">
                    <span class="h-px w-8 bg-ochre"></span>
                    Dikelola langsung oleh Pak H. Tiana
                </p>
            </div>

            <div>
                <x-auth.kos-scene class="px-4" />
                <div class="flex items-center gap-2 px-12 pb-8 pt-4 text-sm text-white/80">
                    <span class="h-2 w-2 rounded-full bg-ochre"></span>
                    Warkop buka s.d. 23.00
                </div>
            </div>
        </aside>

        <!-- ============================================================== -->
        <!-- 3. AREA FORM LOGIN & AKSES DEMO (Kolom Kanan)                  -->
        <!-- ============================================================== -->
        <main class="flex-1 flex flex-col justify-between p-5 sm:p-8 lg:p-12 min-h-screen overflow-y-auto">
            
            <div class="max-w-[400px] w-full mx-auto my-auto space-y-6 pt-4 pb-2">

                <!-- Header Form (Sapaan Akrab & Natural) -->
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-ink tracking-tight">Masuk</h1>
                    <p class="text-xs sm:text-sm text-ink-soft mt-1 leading-relaxed">
                        Tagihan, pesanan, dan antrean menunggu di balik pintu ini.
                    </p>
                </div>

                <!-- Form Login Utama -->
                <form action="{{ route('login.post') }}" 
                      method="POST" 
                      x-on:submit="loading = true" 
                      class="space-y-4">
                    @csrf

                    <!-- Kolom 1: Alamat Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-ink-soft mb-1.5">
                            Alamat Email
                        </label>
                        <div class="relative">
                            <input id="email" 
                                   name="email" 
                                   type="email" 
                                   value="{{ old('email', '') }}" 
                                   required 
                                   autocomplete="email"
                                   placeholder="nama@email.com"
                                   x-init="if (window.innerWidth >= 1024) { @if($errors->has('email') || !$errors->any()) $el.focus(); @endif }"
                                   class="w-full h-12 min-h-[48px] px-3.5 text-sm rounded-xl border bg-surface text-ink placeholder-ink-faint focus:outline-none focus:ring-2 transition-all duration-150 {{ $errors->has('email') ? 'border-alert focus:ring-alert focus:border-alert' : 'border-line focus:ring-brand focus:border-brand' }}"
                                   aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}">
                        </div>
                        @error('email')
                            <p id="email-error" class="mt-1.5 text-xs text-alert flex items-start gap-1.5" aria-live="polite">
                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>{{ $message == 'Email atau kata sandi yang Anda masukkan tidak sesuai.' ? 'Email atau kata sandi tidak cocok. Coba lagi.' : $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <!-- Kolom 2: Kata Sandi -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-ink-soft">
                                Kata Sandi
                            </label>
                            <!-- Tautan Lupa Sandi Membuka Petunjuk Ringkas -->
                            <button type="button" 
                                    x-on:click="forgotModal = true" 
                                    class="text-xs text-brand hover:text-brand-deep font-semibold transition-colors focus:outline-none focus:underline min-h-[44px] flex items-center">
                                Lupa sandi?
                            </button>
                        </div>

                        <div class="relative">
                            <input id="password" 
                                   name="password" 
                                   x-bind:type="showPassword ? 'text' : 'password'" 
                                   value="" 
                                   required 
                                   autocomplete="current-password"
                                   placeholder="Masukkan kata sandi"
                                   x-init="if (window.innerWidth >= 1024 && @json($errors->has('password') && !$errors->has('email'))) { $el.focus(); }"
                                   class="w-full h-12 min-h-[48px] pl-3.5 pr-12 text-sm rounded-xl border bg-surface text-ink placeholder-ink-faint focus:outline-none focus:ring-2 transition-all duration-150 {{ $errors->has('password') ? 'border-alert focus:ring-alert focus:border-alert' : 'border-line focus:ring-brand focus:border-brand' }}"
                                   aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}">
                            
                            <!-- Tombol Tampil/Sembunyikan Sandi -->
                            <button type="button" 
                                    x-on:click="showPassword = !showPassword" 
                                    x-bind:aria-pressed="showPassword"
                                    x-bind:aria-label="showPassword ? 'Sembunyikan sandi' : 'Tampilkan sandi'"
                                    class="absolute right-1 top-1 bottom-1 w-11 min-h-[44px] flex items-center justify-center text-ink-faint hover:text-ink transition-colors rounded-lg focus:outline-none focus:ring-2 focus:ring-brand">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>

                        @error('password')
                            <p id="password-error" class="mt-1.5 text-xs text-alert flex items-start gap-1.5" aria-live="polite">
                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <!-- Kotak Centang Ingat Saya -->
                    <div class="pt-1">
                        <label for="remember" class="inline-flex items-center gap-2.5 cursor-pointer select-none group min-h-[44px]">
                            <div class="relative flex items-center">
                                <input id="remember" 
                                       name="remember" 
                                       type="checkbox" 
                                       x-model="remember"
                                       x-on:focus="checkboxFocused = true"
                                       x-on:blur="checkboxFocused = false"
                                       class="opacity-0 absolute w-4 h-4 cursor-pointer focus:outline-none">
                                <div class="w-4 h-4 rounded-md border flex items-center justify-center transition-all duration-150"
                                     x-bind:class="[
                                         remember ? 'bg-brand border-brand' : 'bg-surface border-line',
                                         checkboxFocused ? 'ring-2 ring-brand ring-offset-2 ring-offset-canvas' : ''
                                     ]">
                                    <svg x-show="remember" x-cloak class="w-3 h-3 text-surface" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>
                            <span class="text-xs text-ink-soft group-hover:text-ink transition-colors font-medium">
                                Ingat saya di perangkat ini
                            </span>
                        </label>
                    </div>

                    <!-- Tombol Masuk Utama -->
                    <div class="pt-1">
                        <button type="submit" 
                                x-bind:disabled="loading"
                                class="w-full h-12 min-h-[48px] rounded-xl font-bold text-sm text-surface bg-brand hover:bg-brand-deep active:bg-brand-deep disabled:opacity-65 disabled:cursor-not-allowed transition-all duration-150 flex items-center justify-center gap-2 shadow-xs focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-surface" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="loading ? 'Memeriksa...' : 'Masuk'">Masuk</span>
                        </button>
                    </div>
                </form>

                <!-- ============================================================== -->
                <!-- 4. AKSES DEMO (Terkoneksi ke Titik Ilustrasi Lorong Kos)       -->
                <!-- ============================================================== -->
                @php
                    $showDemo = config('app.demo', true);
                @endphp

                @if($showDemo)
                    <div class="pt-2">
                        <!-- Pembatas Tipis & Judul Netral -->
                        <div class="relative my-4">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-line"></div>
                            </div>
                            <div class="relative flex justify-center text-xs">
                                <span class="bg-canvas px-3 text-ink-faint font-medium">Coba tanpa akun</span>
                            </div>
                        </div>

                        <!-- 3 Pilihan Peran Demo (Dengan Event & Ukuran Tetap 76px) -->
                        <div class="space-y-2">
                            <!-- 1. Demo Pengelola (Terkoneksi ke Ruang Pemilik) -->
                            <a href="{{ route('demo.login', 'pengelola') }}" 
                               x-on:click="loadingDemo = 'pengelola'"
                               onmouseenter="window.dispatchEvent(new CustomEvent('kos-role',{detail:'pengelola'}))"
                               onfocus="window.dispatchEvent(new CustomEvent('kos-role',{detail:'pengelola'}))"
                               onmouseleave="window.dispatchEvent(new CustomEvent('kos-role',{detail:null}))"
                               onblur="window.dispatchEvent(new CustomEvent('kos-role',{detail:null}))"
                               class="min-h-[56px] p-3 rounded-xl bg-surface border border-line hover:border-brand/40 hover:bg-brand-wash/40 transition-all duration-150 flex items-center justify-between gap-3 text-left group focus:outline-none focus:ring-2 focus:ring-brand"
                               x-bind:class="loadingDemo === 'pengelola' ? 'opacity-70 pointer-events-none' : ''">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-[76px] shrink-0 text-center text-xs px-1.5 h-9 rounded-lg bg-canvas border border-line flex items-center justify-center font-mono font-bold text-ink-soft group-hover:text-brand group-hover:border-brand/40 group-hover:bg-brand-wash transition-colors">
                                        Pemilik
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-semibold text-ink group-hover:text-brand transition-colors">Pengelola</span>
                                            <span class="text-[11px] text-ink-faint">&bull; Pak H. Tiana</span>
                                        </div>
                                        <p class="text-[13px] text-ink-soft leading-tight truncate">
                                            Kelola kamar, tagihan sewa bulanan, dan laporan
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center">
                                    <svg x-show="loadingDemo === 'pengelola'" x-cloak class="w-4 h-4 animate-spin text-brand" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="loadingDemo !== 'pengelola'" class="w-4 h-4 text-ink-faint group-hover:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </a>

                            <!-- 2. Demo Petugas (Terkoneksi ke Pos Warkop) -->
                            <a href="{{ route('demo.login', 'petugas') }}" 
                               x-on:click="loadingDemo = 'petugas'"
                               onmouseenter="window.dispatchEvent(new CustomEvent('kos-role',{detail:'petugas'}))"
                               onfocus="window.dispatchEvent(new CustomEvent('kos-role',{detail:'petugas'}))"
                               onmouseleave="window.dispatchEvent(new CustomEvent('kos-role',{detail:null}))"
                               onblur="window.dispatchEvent(new CustomEvent('kos-role',{detail:null}))"
                               class="min-h-[56px] p-3 rounded-xl bg-surface border border-line hover:border-brand/40 hover:bg-brand-wash/40 transition-all duration-150 flex items-center justify-between gap-3 text-left group focus:outline-none focus:ring-2 focus:ring-brand"
                               x-bind:class="loadingDemo === 'petugas' ? 'opacity-70 pointer-events-none' : ''">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-[76px] shrink-0 text-center text-xs px-1.5 h-9 rounded-lg bg-canvas border border-line flex items-center justify-center font-mono font-bold text-ink-soft group-hover:text-brand group-hover:border-brand/40 group-hover:bg-brand-wash transition-colors">
                                        Warkop
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-semibold text-ink group-hover:text-brand transition-colors">Petugas</span>
                                            <span class="text-[11px] text-ink-faint">&bull; Mas Joko</span>
                                        </div>
                                        <p class="text-[13px] text-ink-soft leading-tight truncate">
                                            Antrean tugas, antar galon, dan periksa stok
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center">
                                    <svg x-show="loadingDemo === 'petugas'" x-cloak class="w-4 h-4 animate-spin text-brand" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="loadingDemo !== 'petugas'" class="w-4 h-4 text-ink-faint group-hover:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </a>

                            <!-- 3. Demo Penghuni (Terkoneksi ke Kamar A-04) -->
                            <a href="{{ route('demo.login', 'penghuni') }}" 
                               x-on:click="loadingDemo = 'penghuni'"
                               onmouseenter="window.dispatchEvent(new CustomEvent('kos-role',{detail:'penghuni'}))"
                               onfocus="window.dispatchEvent(new CustomEvent('kos-role',{detail:'penghuni'}))"
                               onmouseleave="window.dispatchEvent(new CustomEvent('kos-role',{detail:null}))"
                               onblur="window.dispatchEvent(new CustomEvent('kos-role',{detail:null}))"
                               class="min-h-[56px] p-3 rounded-xl bg-surface border border-line hover:border-brand/40 hover:bg-brand-wash/40 transition-all duration-150 flex items-center justify-between gap-3 text-left group focus:outline-none focus:ring-2 focus:ring-brand"
                               x-bind:class="loadingDemo === 'penghuni' ? 'opacity-70 pointer-events-none' : ''">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-[76px] shrink-0 text-center text-xs px-1.5 h-9 rounded-lg bg-canvas border border-line flex items-center justify-center font-mono font-bold text-ink-soft group-hover:text-brand group-hover:border-brand/40 group-hover:bg-brand-wash transition-colors">
                                        A-04
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-semibold text-ink group-hover:text-brand transition-colors">Penghuni</span>
                                            <span class="text-[11px] text-ink-faint">&bull; Dimas (A-04)</span>
                                        </div>
                                        <p class="text-[13px] text-ink-soft leading-tight truncate">
                                            Pesan menu & galon, cek tagihan sewa bulanan
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center">
                                    <svg x-show="loadingDemo === 'penghuni'" x-cloak class="w-4 h-4 animate-spin text-brand" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="loadingDemo !== 'penghuni'" class="w-4 h-4 text-ink-faint group-hover:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif

            </div>

            <!-- Footer Ringkas Satu Baris -->
            <footer class="mt-8 pb-3 text-center text-xs text-ink-faint">
                &copy; {{ date('Y') }} Kos Tiana Putra 1
            </footer>

        </main>

        <!-- ============================================================== -->
        <!-- 5. MODAL / BOTTOM SHEET PETUNJUK LUPA SANDI                     -->
        <!-- ============================================================== -->
        <x-bottom-sheet show="forgotModal" title="Lupa Kata Sandi">
            <div class="space-y-4 text-xs text-ink-soft">
                <p class="leading-relaxed">
                    Demi keamanan privasi dan transaksi sewa seluruh penghuni, pengaturan ulang kata sandi dilakukan secara terpusat oleh pengelola kos.
                </p>

                <div class="p-3.5 bg-canvas rounded-xl border border-line space-y-2">
                    <p class="font-bold text-ink">Langkah Pengaturan Ulang:</p>
                    <ol class="space-y-1.5 list-decimal list-inside text-ink-soft">
                        <li>Hubungi <strong class="text-ink">Pengelola Kos (Pak H. Tiana)</strong> secara langsung atau via WhatsApp.</li>
                        <li>Sebutkan nama lengkap Anda dan <strong class="text-ink">nomor kamar</strong> yang ditempati (contoh: A-04).</li>
                        <li>Pengelola akan mereset kata sandi Anda ke sandi sementara agar Anda dapat masuk kembali.</li>
                    </ol>
                </div>

                <div class="pt-1">
                    <button type="button" 
                            x-on:click="forgotModal = false" 
                            class="w-full h-11 min-h-[44px] rounded-xl bg-brand hover:bg-brand-deep text-surface font-semibold text-xs transition-colors flex items-center justify-center">
                        Mengerti
                    </button>
                </div>
            </div>
        </x-bottom-sheet>

    </div>

</body>
</html>
