@extends('layouts.mobile')

@section('content')
@php
    $isCash = function ($method) {
        if (!$method) return true;
        $m = strtolower($method);
        return str_contains($m, 'tunai') || str_contains($m, 'cash');
    };

    $formatMethod = function ($method) use ($isCash) {
        if ($isCash($method)) return 'Tunai';
        return $method;
    };

    $groupedByDate = $orders->getCollection()->groupBy(function($order) {
        $date = $order->completed_at ?? $order->updated_at;
        if (!$date) return 'Lainnya';
        if ($date->isToday()) return 'Hari ini';
        if ($date->isYesterday()) return 'Kemarin';
        return $date->translatedFormat('d M Y');
    });
@endphp

<div class="space-y-4 pb-[calc(4.5rem+env(safe-area-inset-bottom,0px))] md:pb-6">

    <!-- ============================================================== -->
    <!-- 1. HEADER: FOKUS REKAPITULASI UANG TUNAI & QRIS HARI INI       -->
    <!-- ============================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-0.5">
        <div>
            <h1 class="text-base sm:text-lg font-bold text-ink leading-tight">Riwayat</h1>
            <p class="text-xs text-ink-soft mt-0.5">
                @if($todayCompletedCount > 0)
                    Tunai hari ini <strong class="font-bold text-ink"><x-price :amount="$todayCash" /></strong> &middot; <span class="text-ink-soft">QRIS <x-price :amount="$todayNonCash" /></span>
                @else
                    Belum ada pesanan selesai hari ini
                @endif
            </p>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2. AREA FILTER DIPADATKAN (Pencarian Penuh + Chip Scrollable)  -->
    <!-- ============================================================== -->
    <div class="space-y-2">
        <form action="{{ route('petugas.history') }}" method="GET" class="space-y-2">
            <!-- Baris 1: Pencarian Satu Baris Penuh -->
            <div class="relative">
                <input type="text" 
                       name="q" 
                       value="{{ $search ?? '' }}" 
                       placeholder="Cari kode pesanan (#ORD-...) atau nomor kamar..." 
                       class="w-full h-10 min-h-[40px] text-xs px-3 pl-8 pr-8 border border-line rounded-xl text-ink placeholder-ink-faint focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand bg-surface shadow-xs">
                <svg class="w-4 h-4 text-ink-faint absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                @if($search)
                    <a href="{{ route('petugas.history', array_filter(['date' => $dateFilter])) }}" 
                       title="Hapus kata kunci"
                       class="absolute right-2.5 top-2.5 p-0.5 text-ink-faint hover:text-ink transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
                @if($dateFilter)
                    <input type="hidden" name="date" value="{{ $dateFilter }}">
                @endif
            </div>

            <!-- Baris 2: Chip Filter Tanggal Satu Baris (Horizontal Scrollable) -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 text-xs whitespace-nowrap">
                @php
                    $todayDate = \Carbon\Carbon::today()->toDateString();
                    $yesterdayDate = \Carbon\Carbon::yesterday()->toDateString();
                    $isCustomDate = $dateFilter && $dateFilter !== $todayDate && $dateFilter !== $yesterdayDate;
                @endphp

                <!-- Chip Semua -->
                <a href="{{ route('petugas.history', array_filter(['q' => $search])) }}" 
                   class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors shrink-0 {{ !$dateFilter ? 'bg-brand-wash text-brand border-brand/20' : 'bg-surface text-ink-soft hover:text-ink border-line' }}">
                    Semua
                </a>

                <!-- Chip Hari Ini -->
                <a href="{{ route('petugas.history', array_filter(['date' => $todayDate, 'q' => $search])) }}" 
                   class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors shrink-0 {{ $dateFilter === $todayDate ? 'bg-brand-wash text-brand border-brand/20' : 'bg-surface text-ink-soft hover:text-ink border-line' }}">
                    Hari Ini
                </a>

                <!-- Chip Kemarin -->
                <a href="{{ route('petugas.history', array_filter(['date' => $yesterdayDate, 'q' => $search])) }}" 
                   class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors shrink-0 {{ $dateFilter === $yesterdayDate ? 'bg-brand-wash text-brand border-brand/20' : 'bg-surface text-ink-soft hover:text-ink border-line' }}">
                    Kemarin
                </a>

                <!-- Chip Pemilih Tanggal Kalender -->
                <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold cursor-pointer transition-colors shrink-0 {{ $isCustomDate ? 'bg-brand-wash text-brand border-brand/20' : 'bg-surface text-ink-soft hover:text-ink border-line' }}">
                    <svg class="w-3.5 h-3.5 {{ $isCustomDate ? 'text-brand' : 'text-ink-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ $isCustomDate ? \Carbon\Carbon::parse($dateFilter)->translatedFormat('d M Y') : 'Pilih Tanggal' }}</span>
                    <input type="date" 
                           name="date" 
                           value="{{ $dateFilter ?? '' }}" 
                           onchange="this.form.submit()" 
                           class="sr-only">
                </label>

                @if($search || $dateFilter)
                    <a href="{{ route('petugas.history') }}" 
                       class="px-2.5 py-1.5 text-xs text-ink-faint hover:text-alert font-medium transition-colors shrink-0">
                        Reset Filter
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- ============================================================== -->
    <!-- 3. DAFTAR PESANAN SELESAI DIKELOMPOKKAN PER TANGGAL (ACCORDION)-->
    <!-- ============================================================== -->
    <div class="space-y-5">
        @forelse($groupedByDate as $dateLabel => $ordersInGroup)
            @php
                $groupCash = $ordersInGroup->filter(fn($o) => $isCash($o->payment_method))->sum('total_amount');
                $groupNonCash = $ordersInGroup->reject(fn($o) => $isCash($o->payment_method))->sum('total_amount');
            @endphp

            <div class="space-y-2">
                <!-- Header Grup Tanggal: Judul & Subtotal Tunai (+ QRIS jika ada) -->
                <div class="flex items-center justify-between gap-2 px-1">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <h2 class="text-xs font-bold text-ink-soft tracking-wider uppercase truncate">{{ $dateLabel }}</h2>
                        <span class="text-[11px] font-semibold text-ink-faint tabular-nums shrink-0">({{ $ordersInGroup->count() }})</span>
                    </div>

                    <div class="flex items-center gap-1.5 text-xs text-right shrink-0">
                        <span class="font-bold text-ink whitespace-nowrap">
                            <x-price :amount="$groupCash" /> <span class="text-[11px] font-normal text-ink-soft">tunai</span>
                        </span>
                        @if($groupNonCash > 0)
                            <span class="text-[11px] text-ink-faint whitespace-nowrap">
                                + <x-price :amount="$groupNonCash" /> QRIS
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Daftar Tiket Pesanan Berbentuk Accordion -->
                <div class="space-y-2">
                    @foreach($ordersInGroup as $order)
                        @php
                            $itemSummary = $order->items->map(fn($i) => "{$i->quantity}x {$i->item_name}")->join(', ');
                            $isCashOrder = $isCash($order->payment_method);
                            $methodLabel = $formatMethod($order->payment_method);

                            // Hitung lama pengantaran ("selesai dalam 18 mnt") dari created_at ke completed_at
                            $deliveryDuration = null;
                            if ($order->created_at && $order->completed_at) {
                                $mins = max(1, (int) $order->created_at->diffInMinutes($order->completed_at));
                                if ($mins < 60) {
                                    $deliveryDuration = "selesai dalam {$mins} mnt";
                                } else {
                                    $hrs = floor($mins / 60);
                                    $rem = $mins % 60;
                                    $deliveryDuration = $rem > 0 ? "selesai dalam {$hrs}j {$rem}m" : "selesai dalam {$hrs} jam";
                                }
                            }
                        @endphp

                        <div x-data="{ open: false }" 
                             class="bg-surface rounded-xl border border-line shadow-xs overflow-hidden transition-all duration-150"
                             :class="open ? 'border-l-2 !border-l-brand' : 'hover:border-ink-faint'">
                            
                            <!-- ============================================================== -->
                            <!-- BARIS TERTUTUP (Selalu Terlihat, Dapat Diklik)                 -->
                            <!-- ============================================================== -->
                            <button type="button" 
                                    @click="open = !open" 
                                    class="w-full text-left p-3 sm:p-3.5 flex items-center justify-between gap-2.5 transition-colors select-none focus:outline-none">
                                
                                <!-- Sisi Kiri: 2 Tingkat Informasi -->
                                <div class="min-w-0 flex-1 space-y-1">
                                    <!-- Tingkat Atas: Jam Mono, Nomor Kamar, (Desktop: Nama Penghuni) -->
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="font-mono text-ink-soft tabular-nums shrink-0 font-medium">
                                            {{ ($order->completed_at ?? $order->updated_at)->format('H:i') }}
                                        </span>
                                        <span class="font-bold text-ink shrink-0">
                                            {{ $order->room_number }}
                                        </span>
                                        <span class="text-ink-soft hidden sm:inline truncate">
                                            &bull; {{ $order->user->name ?? 'Penghuni' }}
                                        </span>
                                    </div>

                                    <!-- Tingkat Bawah: Nama Penghuni (Mobile), Ringkasan Item, Catatan, Penanda QRIS -->
                                    <div class="flex items-center gap-1.5 text-xs text-ink-soft min-w-0 overflow-hidden">
                                        <!-- Nama penghuni di mobile -->
                                        <span class="font-semibold text-ink sm:hidden shrink-0 truncate max-w-[100px]">
                                            {{ $order->user->name ?? 'Penghuni' }}
                                        </span>
                                        <span class="text-ink-faint sm:hidden shrink-0">&bull;</span>

                                        <!-- Ringkasan item (dipotong ellipsis) -->
                                        <span class="text-ink-soft truncate min-w-0 flex-1">
                                            {{ $itemSummary }}
                                        </span>

                                        <!-- Ikon catatan & potongan teks pendek bila ada catatan -->
                                        @if($order->notes)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-ink-faint shrink-0 max-w-[110px] sm:max-w-[150px]" title="{{ $order->notes }}">
                                                <svg class="w-3 h-3 text-ink-faint shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                </svg>
                                                <span class="truncate">{{ Str::limit($order->notes, 18) }}</span>
                                            </span>
                                        @endif

                                        <!-- Penanda metode bayar netral HANYA untuk non-tunai (misal QRIS) -->
                                        @if(!$isCashOrder)
                                            <span class="px-1.5 py-0.2 rounded bg-canvas border border-line text-[10px] font-bold text-ink-soft uppercase tracking-wider shrink-0">
                                                {{ $methodLabel }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Sisi Kanan: Harga & Chevron Kecil Saja (Titik hijau & kata Selesai dihapus) -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-price :amount="$order->total_amount" class="text-xs sm:text-sm font-bold text-ink" />
                                    <svg class="w-4 h-4 text-ink-faint transition-transform duration-200" 
                                         :class="open ? 'rotate-180' : ''" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <!-- ============================================================== -->
                            <!-- BARIS TERBUKA (Nota Rincian Lengkap)                          -->
                            <!-- ============================================================== -->
                            <div x-show="open" 
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="border-t border-dashed border-line p-3.5 sm:p-4 bg-surface space-y-3 text-xs">
                                
                                <!-- Meta Info Nota: ID Order & Lama Pengantaran (Tanggal/Jam Dihapus Sesuai Instruksi) -->
                                <div class="flex items-center justify-between text-ink-soft text-[11px] pb-0.5">
                                    <div>
                                        <span class="text-ink-faint">ID: </span>
                                        <span class="font-mono text-ink font-semibold">#{{ $order->order_code }}</span>
                                    </div>
                                    @if($deliveryDuration)
                                        <div class="text-ink-soft font-medium text-[11px] tabular-nums">
                                            {{ $deliveryDuration }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Rincian Per Item -->
                                <div class="space-y-1.5 pt-0.5">
                                    @foreach($order->items as $item)
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <span class="font-semibold tabular-nums text-ink">{{ $item->quantity }}x</span>
                                                <span class="ml-1 text-ink">{{ $item->item_name }}</span>
                                                @if($item->notes)
                                                    <p class="text-[11px] text-ink-faint italic pl-5 mt-0.5">{{ $item->notes }}</p>
                                                @endif
                                            </div>
                                            <x-price :amount="$item->subtotal" class="font-medium text-ink-soft tabular-nums shrink-0" />
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Catatan Pesanan Sekali Saja (Latar Canvas, Garis Kiri 2px Token Line) -->
                                @if($order->notes)
                                    <div class="border-l-2 border-line pl-3 py-1.5 text-xs text-ink-soft flex items-start gap-2 bg-canvas rounded-r-lg">
                                        <svg class="w-3.5 h-3.5 text-ink-faint shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                        <div class="min-w-0 flex-1">
                                            <span class="font-medium text-ink-soft text-[11px] block">Catatan:</span>
                                            <span class="text-ink leading-relaxed">{{ $order->notes }}</span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Garis Putus-Putus & Total Bersama Metode -->
                                <div class="border-t border-dashed border-line pt-2.5 flex items-center justify-between text-xs">
                                    <!-- Nama Petugas: HANYA Ditampilkan Bila Berbeda dari Petugas yang Login -->
                                    <div class="text-[11px] text-ink-soft">
                                        @if($order->processed_by && $order->processor && $order->processed_by !== Auth::id())
                                            <span>Petugas: <strong class="text-ink">{{ $order->processor->name }}</strong></span>
                                        @endif
                                    </div>

                                    <!-- Total dan Metode Pembayaran: "Total Rp X · QRIS" / "Total Rp X · Tunai" -->
                                    <div class="flex items-center gap-1.5 ml-auto">
                                        <span class="text-ink-soft font-medium">Total</span>
                                        <x-price :amount="$order->total_amount" class="text-sm font-bold text-ink" />
                                        <span class="text-ink-faint">&middot;</span>
                                        <span class="font-semibold {{ $isCashOrder ? 'text-ink-soft' : 'text-ink uppercase tracking-wide' }}">
                                            {{ $methodLabel }}
                                        </span>
                                    </div>
                                </div>

                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-surface rounded-xl border border-line p-8 text-center text-xs text-ink-faint shadow-xs space-y-1">
                <p class="font-medium text-ink-soft">Tidak ada riwayat pesanan yang sesuai.</p>
                @if($search || $dateFilter)
                    <p>Coba sesuaikan kata kunci pencarian atau tanggal filter.</p>
                @else
                    <p>Pesanan yang telah diantar dan diselesaikan akan tercatat di sini.</p>
                @endif
            </div>
        @endforelse
    </div>

    <!-- ============================================================== -->
    <!-- 4. PAGINASI STANDAR                                            -->
    <!-- ============================================================== -->
    <div class="pt-2">
        {{ $orders->withQueryString()->links() }}
    </div>

</div>
@endsection
