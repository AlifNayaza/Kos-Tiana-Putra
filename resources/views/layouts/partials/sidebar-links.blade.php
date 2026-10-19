@php
    $currentRoute = Route::currentRouteName() ?? '';
@endphp

<!-- Section: Operasional Utama -->
<p class="px-3 text-[11px] font-bold text-ink-faint uppercase tracking-wider mb-1 mt-2">Utama</p>

<!-- Dashboard -->
<a href="{{ route('pengelola.dashboard') }}" 
   class="flex items-center px-3 py-2.5 text-xs rounded-xl transition-all {{ request()->routeIs('pengelola.dashboard') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('pengelola.dashboard') ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Ringkasan Dashboard</span>
</a>

<!-- Section: Manajemen Hunian -->
<p class="px-3 text-[11px] font-bold text-ink-faint uppercase tracking-wider mb-1 mt-4">Hunian & Sewa</p>

<!-- Data Kamar -->
<a href="{{ route('pengelola.kamar.index') }}" 
   class="flex items-center px-3 py-2.5 text-xs rounded-xl transition-all {{ request()->routeIs('pengelola.kamar.*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('pengelola.kamar.*') ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
    </svg>
    <span>Data Kamar</span>
</a>

<!-- Data Penghuni -->
<a href="{{ route('pengelola.penghuni.index') }}" 
   class="flex items-center px-3 py-2.5 text-xs rounded-xl transition-all {{ request()->routeIs('pengelola.penghuni.*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('pengelola.penghuni.*') ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <span>Data Penghuni Kos</span>
</a>

<!-- Tagihan Sewa -->
<a href="{{ route('pengelola.tagihan.index') }}" 
   class="flex items-center px-3 py-2.5 text-xs rounded-xl transition-all {{ request()->routeIs('pengelola.tagihan.*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('pengelola.tagihan.*') ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <span>Tagihan & Pembayaran</span>
</a>

<!-- Section: Layanan Kamar & Stok -->
<p class="px-3 text-[11px] font-bold text-ink-faint uppercase tracking-wider mb-1 mt-4">Katalog & Stok</p>

<!-- Katalog Menu & Galon -->
<a href="{{ route('pengelola.katalog.index') }}" 
   class="flex items-center px-3 py-2.5 text-xs rounded-xl transition-all {{ request()->routeIs('pengelola.katalog.*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('pengelola.katalog.*') ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
    </svg>
    <span>Katalog Menu & Galon</span>
</a>

<!-- Log Riwayat Stok -->
<a href="{{ route('pengelola.stok.index') }}" 
   class="flex items-center px-3 py-2.5 text-xs rounded-xl transition-all {{ request()->routeIs('pengelola.stok.*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('pengelola.stok.*') ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
    </svg>
    <span>Audit Log Stok</span>
</a>

<!-- Section: Analisis & Prediksi -->
<p class="px-3 text-[11px] font-bold text-ink-faint uppercase tracking-wider mb-1 mt-4">Analisis & Laporan</p>

<!-- Rekap & Laporan Pendapatan -->
<a href="{{ route('pengelola.laporan.index') }}" 
   class="flex items-center px-3 py-2.5 text-xs rounded-xl transition-all {{ request()->routeIs('pengelola.laporan.*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('pengelola.laporan.*') ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
    <span>Rekap Penjualan & Sewa</span>
</a>

<!-- Prediksi Stok ML -->
<a href="{{ route('pengelola.prediksi.index') }}" 
   class="flex items-center px-3 py-2.5 text-xs rounded-xl transition-all {{ request()->routeIs('pengelola.prediksi.*') ? 'bg-brand-wash text-brand font-bold' : 'text-ink-soft hover:text-ink hover:bg-canvas font-medium' }}">
    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('pengelola.prediksi.*') ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
    </svg>
    <span>Prediksi Kebutuhan Stok</span>
</a>
