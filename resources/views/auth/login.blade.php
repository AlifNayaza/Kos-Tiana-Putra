<!DOCTYPE html>
<html lang="id" class="h-full bg-canvas">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#006059">
    <title>Masuk — Kos Tiana Putra 1</title>

    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fonts
    @else
        @include('layouts.partials.tailwind-fallback', ['chart' => false])
    @endif

    <style>
        [x-cloak] { display: none !important; }
        /* Latar tidak di-"fixed" agar scroll di HP tidak memicu repaint penuh. */
        body.login-page { background-attachment: scroll; }
        /* Pola ubin lantai kos: satu gradient CSS, tanpa gambar tambahan. */
        .login-tiles {
            background-image:
                linear-gradient(rgb(255 255 255 / .06) 1px, transparent 1px),
                linear-gradient(90deg, rgb(255 255 255 / .06) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* --- Animasi masuk (hanya transform/opacity, sekali jalan) --- */
        .rise { animation: login-rise .7s cubic-bezier(.2,.7,.2,1) backwards; animation-delay: calc(var(--i, 0) * 90ms + 120ms); }
        @keyframes login-rise { from { opacity: 0; transform: translateY(16px); } }

        /* Coretan di bawah "depan pintu." digambar sekali */
        .scribble path { stroke-dasharray: 220; stroke-dashoffset: 220; animation: login-draw .9s 1s cubic-bezier(.6,0,.2,1) forwards; }
        @keyframes login-draw { to { stroke-dashoffset: 0; } }

        /* Daun pintu terbuka saat halaman dimuat, dan menutup lagi saat tombol Masuk ditekan */
        .login-door {
            position: absolute; inset: -1px; z-index: 20; pointer-events: none;
            border-radius: 2.25rem 2.25rem 1rem 1rem;
            background:
                radial-gradient(120% 60% at 20% 0%, rgb(255 255 255 / .08), transparent 60%),
                linear-gradient(160deg, var(--color-brand), var(--color-brand-deep));
            transform-origin: 0 50%;
            animation: login-door-open 1s .55s cubic-bezier(.7,0,.25,1) forwards;
        }
        .login-door__panel { position: absolute; left: 13%; right: 13%; border: 1.5px solid rgb(255 255 255 / .16); border-radius: .9rem; }
        .login-door__panel--top { top: 9%; height: 37%; border-radius: 2rem 2rem .9rem .9rem; }
        .login-door__panel--bottom { bottom: 7%; height: 38%; }
        .login-door__plate {
            position: absolute; top: 22%; left: 50%; transform: translateX(-50%);
            padding: .25rem .6rem; border-radius: .35rem; background: var(--color-ochre); color: var(--color-ink);
            font: 700 .75rem/1.1 ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .12em; white-space: nowrap;
        }
        .login-door__knob { position: absolute; right: 8%; top: 52%; width: 14px; height: 14px; border-radius: 50%; background: var(--color-ochre); }
        .login-door__lever {
            position: absolute; right: calc(8% + 7px); top: calc(52% + 4px); width: 24px; height: 6px; border-radius: 3px;
            background: var(--color-ochre); transform-origin: 100% 50%; animation: login-lever .5s .05s ease-in-out;
        }
        .login-door.is-closing { animation: login-door-close .45s cubic-bezier(.4,0,.2,1) forwards; }
        @keyframes login-lever { 50% { transform: rotate(-35deg); } }
        @keyframes login-door-open {
            0% { transform: perspective(1400px) rotateY(0); opacity: 1; }
            12% { transform: perspective(1400px) rotateY(4deg); }
            75% { opacity: 1; }
            100% { transform: perspective(1400px) rotateY(-105deg); opacity: 0; visibility: hidden; }
        }
        @keyframes login-door-close {
            from { transform: perspective(1400px) rotateY(-105deg); opacity: 0; visibility: visible; }
            to { transform: perspective(1400px) rotateY(0); opacity: 1; visibility: visible; }
        }

        /* Keset "Selamat Datang" di depan pintu */
        .login-mat {
            clip-path: polygon(6% 0, 94% 0, 100% 100%, 0 100%);
            background:
                repeating-linear-gradient(90deg, rgb(0 0 0 / .06) 0 2px, transparent 2px 5px),
                var(--color-ochre-wash);
        }

        /* Mode "kurangi gerakan": tanpa geser/putar 3D, diganti pudar lembut. */
        @media (prefers-reduced-motion: reduce) {
            .rise { animation-name: login-fade; }
            .login-door { animation: login-door-fade .7s .5s ease forwards; }
            .login-door.is-closing { animation: login-door-fade .3s ease reverse forwards; }
            .login-door__lever { animation: none; }
            /* app.css memaksa semua animasi 0.01ms di mode ini; pudar lembut dipulihkan di sini. */
            .rise { animation-duration: .7s !important; }
            .scribble path { animation-duration: .9s !important; }
            .login-door { animation-duration: .7s !important; }
            .login-door.is-closing { animation-duration: .3s !important; }
            @keyframes login-fade { from { opacity: 0; } }
            @keyframes login-door-fade { from { opacity: 1; } to { opacity: 0; visibility: hidden; } }
        }
    </style>
</head>
<body class="login-page min-h-full text-ink antialiased">

    @include('components.toast')

    @php
        $showDemo = config('kos.demo_login');
        $demoRoles = [
            [
                'role' => 'pengelola', 'name' => 'Pengelola', 'tag' => 'Pemilik', 'person' => 'Pak H. Tiana',
                'desc' => 'Kelola kamar, tagihan sewa bulanan, dan laporan',
                'tone' => 'bg-brand-wash text-brand',
                'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
            ],
            [
                'role' => 'petugas', 'name' => 'Petugas', 'tag' => 'Kios', 'person' => 'Mas Joko',
                'desc' => 'Antrean tugas, antar galon, dan periksa stok',
                'tone' => 'bg-ochre-wash text-ochre-ink',
                'icon' => 'M4 9h12v4a5 5 0 01-5 5H9a5 5 0 01-5-5V9zm12 1.5h1.25a2.25 2.25 0 010 4.5H16M8 3v3m4-3v3M3 21h14',
            ],
            [
                'role' => 'penghuni', 'name' => 'Penghuni', 'tag' => 'A-04', 'person' => 'Dimas',
                'desc' => 'Pesan menu & galon, cek tagihan sewa bulanan',
                'tone' => 'bg-process-wash text-process',
                'icon' => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
            ],
        ];
    @endphp

    <div x-data="{
            showPassword: false,
            loading: false,
            remember: {{ old('remember') ? 'true' : 'false' }},
            forgotModal: false,
            loadingDemo: null
         }"
         class="min-h-dvh mx-auto max-w-[1440px] lg:grid lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-8 lg:p-6">

        <!-- ============================================================== -->
        <!-- 1. PANEL CERITA: HP = header melengkung, desktop = kartu tinggi -->
        <!-- ============================================================== -->
        <aside class="login-tiles relative overflow-hidden bg-brand text-white select-none
                      rounded-b-[2rem] px-5 pt-5 pb-14
                      lg:sticky lg:top-6 lg:h-[calc(100dvh-3rem)] lg:rounded-[2rem] lg:p-10 xl:p-12 lg:flex lg:flex-col">

            <!-- Cahaya lampu kios: satu radial-gradient, tanpa blur/filter -->
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full lg:h-[28rem] lg:w-[28rem]"
                 style="background: radial-gradient(closest-side, rgb(201 138 43 / .35), transparent)"></div>

            <!-- Logo & status kios -->
            <div class="rise relative flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 lg:w-11 lg:h-11 rounded-xl bg-surface text-brand-deep flex items-center justify-center font-display font-bold text-sm lg:text-base">KT</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[15px] lg:text-xl font-semibold tracking-tight whitespace-nowrap">Kos Tiana Putra</span>
                        <span class="inline-flex items-center justify-center w-5 h-5 lg:w-6 lg:h-6 rounded-md bg-ochre text-ink text-2xs font-mono font-bold" title="Cabang 1">1</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-2xs text-white/90 whitespace-nowrap lg:hidden" title="Kios kos buka s.d. 23.00">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="absolute inset-0 rounded-full bg-ochre motion-safe:animate-ping opacity-60"></span>
                        <span class="relative h-1.5 w-1.5 rounded-full bg-ochre"></span>
                    </span>
                    <span class="hidden min-[400px]:inline">Kios buka s.d. 23.00</span>
                    <span class="min-[400px]:hidden">Kios s.d. 23.00</span>
                </div>
            </div>

            <!-- Judul -->
            <div class="relative mt-5 lg:mt-auto lg:pt-10">
                <h1 class="rise font-display text-balance font-bold tracking-tight leading-[1.08]
                           text-[1.65rem] max-w-[17ch]
                           lg:text-[clamp(2.4rem,3.6vw,3.6rem)] lg:max-w-[14ch]" style="--i:1">
                    Kopi hangat dan galon sampai
                    <span class="relative inline-block whitespace-nowrap text-ochre">depan pintu.<svg class="scribble absolute left-0 -bottom-[0.2em] w-full h-[0.3em]" viewBox="0 0 200 12" preserveAspectRatio="none" aria-hidden="true"><path d="M3 8c40-6 90-7 140-3 20 2 38 2 54-1" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg></span>
                </h1>
                <p style="--i:2" class="rise mt-2 lg:mt-5 flex items-center gap-2.5 text-xs lg:text-[15px] text-white/80">
                    <span class="h-px w-6 lg:w-8 bg-ochre"></span>
                    Dikelola langsung oleh Pak H. Tiana
                </p>

                <!-- Fakta singkat (desktop) -->
                <ul style="--i:3" class="rise hidden lg:flex flex-wrap gap-2 mt-7 text-xs [@media(max-height:760px)]:!hidden">
                    <li class="flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inset-0 rounded-full bg-ochre motion-safe:animate-ping opacity-60"></span>
                            <span class="relative h-2 w-2 rounded-full bg-ochre"></span>
                        </span>
                        Kios kos buka s.d. 23.00
                    </li>
                    <li class="rounded-full bg-white/10 px-3 py-1.5">Makan &amp; minum dari kios kos</li>
                    <li class="rounded-full bg-white/10 px-3 py-1.5">Galon diantar ke kamar</li>
                    <li class="rounded-full bg-white/10 px-3 py-1.5">Tagihan sewa cek dari HP</li>
                </ul>
            </div>

            <!-- Ilustrasi lorong kos: lebar dibatasi tinggi layar agar selalu terlihat utuh -->
            <div style="--i:3" class="rise relative mt-3 -mx-1 sm:mx-auto sm:max-w-md lg:hidden">
                <x-auth.kos-scene compact />
            </div>
            <div style="--i:4" class="rise relative hidden lg:block mt-auto pt-8">
                <div class="mx-auto w-[min(100%,max(16rem,calc((100dvh-31.5rem)*1.9)))]">
                    <x-auth.kos-scene />
                </div>
            </div>
        </aside>

        <!-- ============================================================== -->
        <!-- 2. PINTU MASUK (form) + AKUN DEMO                               -->
        <!-- ============================================================== -->
        <main class="relative z-10 -mt-8 px-4 pb-6 lg:mt-0 lg:px-0 lg:py-6 flex flex-col">
            <div class="w-full max-w-[420px] mx-auto lg:my-auto space-y-6">

                <!-- Kartu berbentuk daun pintu dengan papan nomor; daun pintunya terbuka saat halaman dimuat -->
                <div class="rise" style="--i:1">
                <section class="relative bg-surface border border-line rounded-t-[2.25rem] rounded-b-2xl shadow-md px-5 pt-9 pb-6 sm:px-7">
                    <div class="absolute z-30 -top-3.5 left-1/2 -translate-x-1/2 rounded-md bg-ochre px-3 py-1 font-mono text-2xs font-bold tracking-[0.2em] text-ink shadow-xs">
                        PINTU&nbsp;MASUK
                    </div>

                    <div class="login-door" x-bind:class="loading && 'is-closing'" aria-hidden="true">
                        <div class="login-door__panel login-door__panel--top"></div>
                        <div class="login-door__panel login-door__panel--bottom"></div>
                        <div class="login-door__plate">KOS TIANA</div>
                        <span class="login-door__lever"></span>
                        <span class="login-door__knob"></span>
                    </div>

                    <div class="text-center">
                        <h2 class="font-display text-3xl font-bold text-brand-deep tracking-tight">Masuk</h2>
                        <p class="text-xs text-ink-soft mt-1 leading-relaxed">
                            Tagihan, pesanan, dan antrean menunggu di balik pintu ini.
                        </p>
                    </div>

                    <form action="{{ route('login.post') }}" method="POST" x-on:submit="loading = true" class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="block text-xs font-semibold text-ink-soft mb-1.5">Alamat Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', '') }}" required
                                   autocomplete="email" inputmode="email" placeholder="nama@email.com"
                                   x-init="if (window.innerWidth >= 1024) { @if($errors->has('email') || !$errors->any()) $el.focus(); @endif }"
                                   class="w-full h-12 px-3.5 text-sm rounded-xl border bg-canvas/40 text-ink placeholder-ink-faint focus:bg-surface focus:outline-none focus:ring-2 transition-colors {{ $errors->has('email') ? 'border-alert focus:ring-alert focus:border-alert' : 'border-line focus:ring-brand/30 focus:border-brand' }}"
                                   @error('email') aria-describedby="email-error" aria-invalid="true" @enderror>
                            @error('email')
                                <p id="email-error" class="mt-1.5 text-xs text-alert flex items-start gap-1.5" aria-live="polite">
                                    <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>{{ $message == 'Email atau kata sandi yang Anda masukkan tidak sesuai.' ? 'Email atau kata sandi tidak cocok. Coba lagi.' : $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-xs font-semibold text-ink-soft">Kata Sandi</label>
                                <button type="button" x-on:click="forgotModal = true"
                                        class="text-xs text-brand hover:text-brand-deep font-semibold focus:outline-none focus:underline min-h-[36px] flex items-center">
                                    Lupa sandi?
                                </button>
                            </div>
                            <div class="relative">
                                <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" value="" required
                                       autocomplete="current-password" placeholder="Masukkan kata sandi"
                                       x-init="if (window.innerWidth >= 1024 && @json($errors->has('password') && !$errors->has('email'))) { $el.focus(); }"
                                       class="w-full h-12 pl-3.5 pr-12 text-sm rounded-xl border bg-canvas/40 text-ink placeholder-ink-faint focus:bg-surface focus:outline-none focus:ring-2 transition-colors {{ $errors->has('password') ? 'border-alert focus:ring-alert focus:border-alert' : 'border-line focus:ring-brand/30 focus:border-brand' }}"
                                       @error('password') aria-describedby="password-error" aria-invalid="true" @enderror>
                                <button type="button" x-on:click="showPassword = !showPassword"
                                        x-bind:aria-pressed="showPassword"
                                        x-bind:aria-label="showPassword ? 'Sembunyikan sandi' : 'Tampilkan sandi'"
                                        class="absolute right-1 top-1 bottom-1 w-11 flex items-center justify-center text-ink-faint hover:text-ink rounded-lg focus:outline-none focus:ring-2 focus:ring-brand">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                </button>
                            </div>
                            @error('password')
                                <p id="password-error" class="mt-1.5 text-xs text-alert flex items-start gap-1.5" aria-live="polite">
                                    <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <label for="remember" class="flex items-center gap-2.5 cursor-pointer select-none min-h-[40px]">
                            <input id="remember" name="remember" type="checkbox" x-model="remember"
                                   class="w-[18px] h-[18px] rounded-md accent-[var(--color-brand)] cursor-pointer">
                            <span class="text-xs text-ink-soft font-medium">Ingat saya di perangkat ini</span>
                        </label>

                        <button type="submit" x-bind:disabled="loading"
                                class="group w-full h-12 rounded-xl font-bold text-sm text-surface bg-brand hover:bg-brand-deep disabled:opacity-65 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-surface">
                            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="loading ? 'Memeriksa...' : 'Masuk'">Masuk</span>
                            <svg x-show="!loading" class="w-4 h-4 transition-transform motion-safe:group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                        </button>
                    </form>
                </section>
                <div class="login-mat mx-auto w-[72%] h-7 flex items-center justify-center font-mono text-[10px] font-bold tracking-[0.3em] text-ochre-ink" aria-hidden="true">
                    SELAMAT DATANG
                </div>
                </div>

                <!-- Akun demo: tag sama dengan papan pintu di ilustrasi (hover menyalakan jendelanya) -->
                @if($showDemo)
                    <section class="rise" style="--i:3">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="h-px flex-1 bg-line"></span>
                            <span class="text-xs font-medium text-ink-faint">Coba tanpa akun</span>
                            <span class="h-px flex-1 bg-line"></span>
                        </div>

                        <div class="space-y-2">
                            @foreach($demoRoles as $d)
                                <a href="{{ route('demo.login', $d['role']) }}"
                                   x-on:click="loadingDemo = '{{ $d['role'] }}'"
                                   onmouseenter="window.dispatchEvent(new CustomEvent('kos-role',{detail:'{{ $d['role'] }}'}))"
                                   onfocus="window.dispatchEvent(new CustomEvent('kos-role',{detail:'{{ $d['role'] }}'}))"
                                   onmouseleave="window.dispatchEvent(new CustomEvent('kos-role',{detail:null}))"
                                   onblur="window.dispatchEvent(new CustomEvent('kos-role',{detail:null}))"
                                   x-bind:class="loadingDemo === '{{ $d['role'] }}' ? 'opacity-70 pointer-events-none' : ''"
                                   class="group flex items-center gap-3 p-3 rounded-2xl bg-surface border border-line hover:border-brand/40 transition-colors focus:outline-none focus:ring-2 focus:ring-brand">
                                    <span class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center {{ $d['tone'] }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $d['icon'] }}"/></svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-sm font-semibold text-ink group-hover:text-brand transition-colors">{{ $d['name'] }}</span>
                                            <span class="rounded border border-ink/25 px-1 font-mono text-[11px] font-bold leading-4 text-ink-soft">{{ $d['tag'] }}</span>
                                            <span class="text-2xs text-ink-faint">{{ $d['person'] }}</span>
                                        </span>
                                        <span class="block text-2xs text-ink-soft leading-snug mt-0.5">{!! $d['desc'] !!}</span>
                                    </span>
                                    <svg x-show="loadingDemo === '{{ $d['role'] }}'" x-cloak class="w-4 h-4 shrink-0 animate-spin text-brand" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg x-show="loadingDemo !== '{{ $d['role'] }}'" class="w-4 h-4 shrink-0 text-ink-faint group-hover:text-brand transition-all motion-safe:group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <footer class="pt-2 text-center text-2xs text-ink-faint">
                    &copy; {{ date('Y') }} Kos Tiana Putra 1
                </footer>
            </div>
        </main>

        <!-- ============================================================== -->
        <!-- 3. PETUNJUK LUPA SANDI                                          -->
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
                    <button type="button" x-on:click="forgotModal = false"
                            class="w-full h-11 rounded-xl bg-brand hover:bg-brand-deep text-surface font-semibold text-xs transition-colors flex items-center justify-center">
                        Mengerti
                    </button>
                </div>
            </div>
        </x-bottom-sheet>

    </div>

</body>
</html>
