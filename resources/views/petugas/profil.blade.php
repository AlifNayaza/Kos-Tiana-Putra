@extends('layouts.mobile')

@section('header_subtitle', 'Profil Akun Petugas')

@section('content')
<div class="space-y-4">
    <div class="bg-surface rounded-2xl p-6 border border-line shadow-xs space-y-4">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full bg-brand text-surface font-bold flex items-center justify-center text-base">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div>
                <h3 class="font-bold text-sm text-ink">{{ $user->name }}</h3>
                <p class="text-xs text-ink-soft">{{ $user->email }} &bull; {{ $user->phone }}</p>
                <span class="inline-block mt-1 text-2xs font-bold text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-line">Petugas Layanan</span>
            </div>
        </div>
    </div>

    <div class="bg-surface rounded-2xl p-6 border border-line shadow-xs space-y-3">
        <h3 class="font-bold text-sm text-ink">Ganti Kata Sandi</h3>
        <x-password-form />
    </div>

    <div class="bg-surface rounded-2xl p-4 border border-line shadow-xs space-y-3">
        @if(config('kos.demo_login'))
            <div class="flex items-center gap-2 text-xs text-ink-soft">
                <span>Coba peran lain:</span>
                <a href="{{ route('demo.login', 'pengelola') }}" class="font-semibold text-brand hover:underline underline-offset-2">Pengelola</a>
                <a href="{{ route('demo.login', 'penghuni') }}" class="font-semibold text-brand hover:underline underline-offset-2">Penghuni</a>
            </div>
        @endif
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full min-h-[48px] rounded-xl border border-alert/30 text-alert text-sm font-semibold hover:bg-alert-wash transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar dari akun
            </button>
        </form>
    </div>
</div>
@endsection
