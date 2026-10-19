@extends('layouts.app')

@section('page_title', 'Manajemen Tagihan & Pembayaran Sewa')
@section('page_subtitle', 'Verifikasi bukti transfer, pemantauan jatuh tempo, dan generate tagihan sewa bulanan')

@section('content')
<div class="space-y-6" x-data="{ 
    generateModalOpen: false, 
    proofModalOpen: false, 
    proofImageUrl: '',
    proofTitle: '',
    viewProof(url, title) {
        this.proofImageUrl = url;
        this.proofTitle = title;
        this.proofModalOpen = true;
    }
}">

    <!-- Stats Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Lunas -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-ink-soft uppercase tracking-wider">Total Lunas Terkumpul</p>
                <div class="mt-2 text-2xl font-extrabold text-done tabular-nums">
                    Rp {{ number_format($stats['lunas_sum'], 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-ink-faint mt-1">Periode {{ \Carbon\Carbon::create(null, $monthFilter)->translatedFormat('F') }} {{ $yearFilter }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-done-wash border border-done/20 text-done flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- Tertunda / Menunggak -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-ink-soft uppercase tracking-wider">Tertunda / Menunggak</p>
                <div class="mt-2 text-2xl font-extrabold text-alert tabular-nums">
                    Rp {{ number_format($stats['menunggu_sum'], 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-ink-faint mt-1">Belum terlunasi</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-alert-wash border border-alert/20 text-alert flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- Butuh Verifikasi Bukti -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-ink-soft uppercase tracking-wider">Verifikasi Bukti</p>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-extrabold text-ink tabular-nums">{{ $stats['need_verify'] }}</span>
                    <span class="text-xs font-semibold text-brand bg-brand-wash px-2 py-0.5 rounded-full border border-brand/20">
                        Bukti Masuk
                    </span>
                </div>
                <p class="text-[11px] text-ink-faint mt-1">Menunggu validasi pengelola</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-brand-wash border border-brand/20 text-brand flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>

        <!-- Total Lembar Tagihan -->
        <div class="bg-surface p-5 rounded-2xl border border-line shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-ink-soft uppercase tracking-wider">Total Lembar Tagihan</p>
                <div class="mt-2 text-2xl font-extrabold text-ink tabular-nums">
                    {{ $stats['total_bills'] }}
                </div>
                <p class="text-[11px] text-ink-faint mt-1">Seluruh kamar aktif</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-canvas border border-line text-ink flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filter & Action Header -->
    <div class="bg-surface p-4 sm:p-5 rounded-2xl border border-line shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <!-- Form Filter Periode, Status & Pencarian -->
        <form method="GET" action="{{ route('pengelola.tagihan.index') }}" class="flex flex-wrap items-center gap-3">
            <!-- Bulan -->
            <div>
                <label for="month" class="sr-only">Bulan</label>
                <select name="month" id="month" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $monthFilter == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <!-- Tahun -->
            <div>
                <label for="year" class="sr-only">Tahun</label>
                <select name="year" id="year" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    @foreach([2025, 2026, 2027] as $y)
                        <option value="{{ $y }}" {{ $yearFilter == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="sr-only">Status</label>
                <select name="status" id="status" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    <option value="semua" {{ $statusFilter === 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="menunggu" {{ $statusFilter === 'menunggu' ? 'selected' : '' }}>Menunggu Bayar</option>
                    <option value="lunas" {{ $statusFilter === 'lunas' ? 'selected' : '' }}>Lunas Terverifikasi</option>
                    <option value="menunggak" {{ $statusFilter === 'menunggak' ? 'selected' : '' }}>Menunggak</option>
                </select>
            </div>

            <!-- Search -->
            <div class="relative">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari kode/kamar/nama..." 
                       class="text-xs rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none pl-8 pr-4 py-2 bg-canvas w-48 sm:w-56">
                <svg class="w-4 h-4 text-ink-faint absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            @if($statusFilter !== 'semua' || $search)
                <a href="{{ route('pengelola.tagihan.index', ['month' => $monthFilter, 'year' => $yearFilter]) }}" class="text-xs text-alert hover:text-alert font-semibold px-2 py-1">
                    Reset
                </a>
            @endif
        </form>

        <!-- Button: Terbitkan Tagihan Massal -->
        <button type="button" 
                @click="generateModalOpen = true" 
                class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Terbitkan Tagihan Massal</span>
        </button>
    </div>

    <!-- Bills Table -->
    <div class="bg-surface rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-canvas border-b border-line text-ink-soft uppercase tracking-wider font-semibold">
                        <th class="py-3.5 px-4 sm:px-6">Tagihan & Kamar</th>
                        <th class="py-3.5 px-4">Penghuni</th>
                        <th class="py-3.5 px-4">Nominal</th>
                        <th class="py-3.5 px-4">Jatuh Tempo</th>
                        <th class="py-3.5 px-4">Status & Bukti Transfer</th>
                        <th class="py-3.5 px-4 sm:px-6 text-right">Aksi Konfirmasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($bills as $bill)
                        @php
                            $badge = $bill->status_badge;
                        @endphp
                        <tr class="hover:bg-canvas transition-colors">
                            <!-- Tagihan & Kamar -->
                            <td class="py-4 px-4 sm:px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-canvas text-ink font-bold flex items-center justify-center text-xs flex-shrink-0">
                                        {{ $bill->room ? str_replace('Kamar ', '', $bill->room->room_number) : '??' }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-ink leading-tight">{{ $bill->room->room_number ?? '-' }}</p>
                                        <p class="text-ink-soft font-mono text-[11px] mt-0.5">{{ $bill->bill_code }}</p>
                                        <span class="text-[10px] text-ink-faint block mt-0.5">Bulan {{ $bill->period_label }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Penghuni -->
                            <td class="py-4 px-4">
                                @if($bill->tenantProfile && $bill->tenantProfile->user)
                                    <p class="font-bold text-ink">{{ $bill->tenantProfile->user->name }}</p>
                                    <p class="text-ink-soft text-[11px]">{{ $bill->tenantProfile->user->phone ?? '-' }}</p>
                                @else
                                    <span class="text-ink-faint italic">Penghuni telah checkout</span>
                                @endif
                            </td>

                            <!-- Nominal -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="text-sm font-extrabold text-ink tabular-nums">
                                    {{ $bill->formatted_amount }}
                                </div>
                                <span class="text-[10px] text-ink-faint">Sewa 1 Bulan Penuh</span>
                            </td>

                            <!-- Jatuh Tempo -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @php
                                    $isPastDue = \Carbon\Carbon::parse($bill->due_date)->isPast() && $bill->status !== 'lunas';
                                @endphp
                                <div class="font-medium {{ $isPastDue ? 'text-alert font-bold' : 'text-ink' }}">
                                    {{ \Carbon\Carbon::parse($bill->due_date)->format('d M Y') }}
                                </div>
                                @if($isPastDue)
                                    <span class="text-[10px] text-alert font-semibold">Lewat Jatuh Tempo</span>
                                @endif
                            </td>

                            <!-- Status & Bukti Transfer -->
                            <td class="py-4 px-4">
                                <div class="space-y-1.5">
                                    <x-status-badge :status="$bill->status" :label="$badge['label']" />

                                    <!-- Bukti Upload -->
                                    @if($bill->payment_proof)
                                        <button type="button" 
                                                @click="viewProof('{{ asset('storage/' . $bill->payment_proof) }}', 'Bukti Pembayaran {{ $bill->bill_code }}')" 
                                                class="inline-flex items-center space-x-1 text-[11px] font-bold text-brand bg-brand-wash hover:bg-brand-wash/80 px-2 py-0.5 rounded border border-brand/20 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <span>Lihat Bukti Struk</span>
                                        </button>
                                    @elseif($bill->status === 'lunas')
                                        <span class="text-[10px] text-ink-faint block">Diverifikasi Tunai / Bank</span>
                                    @else
                                        <span class="text-[10px] text-ink-faint italic block">Belum ada struk</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Aksi Konfirmasi -->
                            <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                @if($bill->status !== 'lunas')
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Tombol Konfirmasi Lunas -->
                                        <form action="{{ route('pengelola.tagihan.confirm', $bill->id) }}" method="POST" 
                                              onsubmit="return confirm('Konfirmasi penerimaan pembayaran sewa sebesar {{ $bill->formatted_amount }} untuk {{ $bill->room->room_number }}?');">
                                            @csrf
                                            <button type="submit" 
                                                    class="inline-flex items-center space-x-1 text-xs font-bold text-surface bg-brand hover:bg-brand-deep px-3 py-1.5 rounded-lg shadow-xs transition-colors text-surface">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Verifikasi Lunas</span>
                                            </button>
                                        </form>

                                        @if($bill->status !== 'menunggak')
                                            <!-- Tombol Tandai Menunggak -->
                                            <form action="{{ route('pengelola.tagihan.overdue', $bill->id) }}" method="POST" 
                                                  onsubmit="return confirm('Tandai tagihan {{ $bill->bill_code }} sebagai Menunggak?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="text-xs font-semibold text-alert hover:text-alert bg-alert-wash hover:bg-alert-wash/80 px-2.5 py-1.5 rounded-lg border border-alert/20 transition-colors" title="Tandai Menunggak">
                                                    Menunggak
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-[11px] text-ink-soft">
                                        <span class="text-done font-bold flex items-center justify-end">
                                            <svg class="w-3.5 h-3.5 mr-1 text-done" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Terverifikasi Lunas
                                        </span>
                                        <span class="text-[10px] text-ink-faint block mt-0.5">
                                            {{ $bill->confirmer->name ?? 'Pengelola' }} ({{ $bill->payment_date ? \Carbon\Carbon::parse($bill->payment_date)->format('d/m/Y') : '' }})
                                        </span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-ink-faint text-xs">
                                Tidak ada tagihan sewa pada periode dan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bills->hasPages())
            <div class="p-4 border-t border-line bg-canvas">
                {{ $bills->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form: Generate Tagihan Massal 1-Klik (FR-04) -->
    <div x-show="generateModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" @click="generateModalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div x-show="generateModalOpen" 
                 x-transition
                 class="relative bg-surface rounded-2xl max-w-md w-full p-6 shadow-2xl border border-line text-left">
                
                <div class="flex items-center justify-between pb-4 border-b border-line">
                    <div>
                        <h3 class="text-sm font-bold text-ink">Terbitkan Tagihan Sewa Massal</h3>
                        <p class="text-xs text-ink-soft">Generate tagihan otomatis untuk seluruh penghuni aktif</p>
                    </div>
                    <button @click="generateModalOpen = false" class="text-ink-faint hover:text-ink-soft p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('pengelola.tagihan.generate') }}" method="POST" class="mt-4 space-y-4">
                    @csrf

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Target Bulan <span class="text-alert">*</span></label>
                            <select name="month" required class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Tahun <span class="text-alert">*</span></label>
                            <select name="year" required class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                                @foreach([2025, 2026, 2027] as $y)
                                    <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Batas Waktu Jatuh Tempo <span class="text-alert">*</span></label>
                        <input type="date" name="due_date" value="{{ date('Y-m-10', strtotime('+1 month')) }}" required 
                               class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        <p class="text-[11px] text-ink-faint mt-1">Standar jatuh tempo Kos Tiana Putra 1: Tanggal 10 setiap bulan.</p>
                    </div>

                    <div class="p-3 bg-brand-wash rounded-xl border border-brand/20 text-xs text-brand">
                        <p class="font-semibold">Informasi Sistem:</p>
                        <p class="text-[11px] mt-0.5">Sistem akan otomatis men-generate tagihan sebesar harga sewa masing-masing kamar dan mengirimkan notifikasi ke aplikasi seluruh penghuni aktif.</p>
                    </div>

                    <div class="pt-4 border-t border-line flex items-center justify-end space-x-3">
                        <button type="button" @click="generateModalOpen = false" 
                                class="px-4 py-2 rounded-xl border border-line text-xs font-semibold text-ink-soft hover:bg-canvas">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-5 py-2 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs">
                            Terbitkan Sekarang
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Modal View Bukti Struk Transfer -->
    <div x-show="proofModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" @click="proofModalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div x-show="proofModalOpen" 
                 x-transition
                 class="relative bg-surface rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-line text-left">
                
                <div class="flex items-center justify-between pb-3 border-b border-line">
                    <h3 class="text-sm font-bold text-ink" x-text="proofTitle"></h3>
                    <button @click="proofModalOpen = false" class="text-ink-faint hover:text-ink-soft p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-4 flex items-center justify-center bg-canvas rounded-xl overflow-hidden max-h-96">
                    <img :src="proofImageUrl" alt="Bukti Transfer" class="object-contain max-h-96 w-full">
                </div>

                <div class="mt-4 pt-3 border-t border-line flex justify-end">
                    <button type="button" @click="proofModalOpen = false" 
                            class="px-4 py-2 rounded-xl bg-canvas text-xs font-bold text-ink hover:bg-line/60">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
