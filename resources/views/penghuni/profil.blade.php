@extends('layouts.mobile')

@section('header_subtitle', 'Data Penghuni & Fasilitas Kamar')

@section('content')
<div class="bg-surface rounded-2xl p-6 border border-line shadow-xs space-y-4">
    <div class="flex items-center space-x-3">
        <div class="w-12 h-12 rounded-full bg-brand text-surface font-bold flex items-center justify-center text-base">
            {{ substr(Auth::user()->name, 0, 1) }}
        </div>
        <div>
            <h3 class="font-bold text-sm text-ink">{{ Auth::user()->name }}</h3>
            <p class="text-xs text-ink-soft">{{ Auth::user()->email }} &bull; {{ Auth::user()->phone }}</p>
            <span class="inline-block mt-1 text-[10px] font-bold text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-line">
                {{ Auth::user()->tenantProfile?->room?->room_number ?? 'Penghuni' }}
            </span>
        </div>
    </div>
</div>
@endsection
