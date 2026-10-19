@extends('layouts.mobile')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start" x-data="{
    changeGalonSheet: false,
    selectedGalonId: {{ $lastGalonItem->id ?? 0 }},
    selectedGalonName: '{{ addslashes($lastGalonItem->name ?? 'Galon') }}',
    selectedGalonPrice: {{ $lastGalonItem->price ?? 0 }},
    galonQty: {{ $lastGalonQty ?? 1 }},
    roomFacilitiesOpen: false,
    get galonSubtotalFormatted() {
        return 'Rp ' + (this.selectedGalonPrice * this.galonQty).toLocaleString('id-ID');
    }
}">

    <!-- ============================================================== -->
    <!-- ELEMEN A: STATUS TAGIHAN BULAN BERJALAN (DOM 1, Desktop Kanan) -->
    <!-- ============================================================== -->
    <div class="lg:col-start-2 lg:row-start-1 space-y-3">
        @if($activeBill)
            @php
                $statusType = $activeBill->effective_status;
            @endphp

            @if($statusType === 'menunggak')
                <!-- Status Menunggak (Aksen Merah Fungsional) -->
                <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs relative overflow-hidden">
                    <div class="absolute top-0 left-0 bottom-0 w-1.5 bg-alert"></div>
                    <div class="flex items-start justify-between gap-3 pl-1">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-ink-soft font-semibold">{{ $activeBill->bill_code }}</span>
                                <x-status-badge status="menunggak" label="Menunggak" />
                            </div>
                            <h2 class="text-sm sm:text-base font-bold text-ink mt-1.5">
                                Tagihan {{ $activeBill->month_name }} menunggak {{ $activeBill->days_overdue > 0 ? $activeBill->days_overdue . ' hari' : '' }}
                            </h2>
                            <p class="text-xs text-ink-soft mt-0.5">
                                Jatuh tempo {{ $activeBill->due_date ? $activeBill->due_date->translatedFormat('d F Y') : '-' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-ink-faint block font-medium">Total</span>
                            <span class="text-base sm:text-lg font-bold text-alert whitespace-nowrap tabular-nums">
                                {{ $activeBill->formatted_amount }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-line flex items-center justify-between pl-1">
                        <span class="text-xs text-ink-soft">Sewa kamar {{ $room ? $room->room_number : '' }}</span>
                        <a href="{{ route('penghuni.tagihan') }}" 
                           class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors">
                            Bayar sekarang
                        </a>
                    </div>
                </div>

            @elseif($statusType === 'menunggu_verifikasi')
                <!-- Status Menunggu Verifikasi (Netral / Kuning Fungsional) -->
                <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-ink-soft">{{ $activeBill->bill_code }}</span>
                                <x-status-badge status="menunggu_verifikasi" label="Menunggu Verifikasi" />
                            </div>
                            <h2 class="text-sm sm:text-base font-bold text-ink mt-1.5">
                                Bukti bayar sedang dicek pengelola
                            </h2>
                            <p class="text-xs text-ink-soft mt-0.5">
                                Tagihan {{ $activeBill->period_label }} &bull; Verifikasi maksimal 1x24 jam
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-ink-faint block font-medium">Nominal</span>
                            <span class="text-base sm:text-lg font-bold text-ink whitespace-nowrap tabular-nums">
                                {{ $activeBill->formatted_amount }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-line flex items-center justify-between text-xs">
                        <span class="text-ink-soft">Metode: {{ $activeBill->payment_method ?? 'Transfer Bank' }}</span>
                        <a href="{{ route('penghuni.tagihan') }}" class="font-semibold text-brand hover:text-brand-deep">
                            Lihat rincian
                        </a>
                    </div>
                </div>

            @elseif($statusType === 'lunas')
                <!-- Status Lunas (Satu Baris Tenang) -->
                <div class="bg-surface rounded-xl border border-line p-4 flex items-center justify-between gap-3 text-xs shadow-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <x-status-badge status="lunas" label="Lunas" />
                        <span class="text-ink-soft font-medium truncate">
                            Tagihan {{ $activeBill->period_label }} lunas
                        </span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="font-semibold text-ink whitespace-nowrap tabular-nums">
                            {{ $activeBill->formatted_amount }}
                        </span>
                        <a href="{{ route('penghuni.tagihan') }}" class="font-semibold text-brand hover:text-brand-deep">
                            Nota
                        </a>
                    </div>
                </div>

            @else
                <!-- Menunggu Pembayaran (Belum Jatuh Tempo) -->
                <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-ink-soft">{{ $activeBill->bill_code }}</span>
                                <x-status-badge status="menunggu" label="Menunggu Pembayaran" />
                            </div>
                            <h2 class="text-sm sm:text-base font-bold text-ink mt-1.5">
                                Tagihan {{ $activeBill->period_label }}
                            </h2>
                            <p class="text-xs text-ink-soft mt-0.5">
                                Jatuh tempo {{ $activeBill->due_date ? $activeBill->due_date->translatedFormat('d F Y') : '-' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-ink-faint block font-medium">Total</span>
                            <span class="text-base sm:text-lg font-bold text-ink whitespace-nowrap tabular-nums">
                                {{ $activeBill->formatted_amount }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-line flex items-center justify-between">
                        <span class="text-xs text-ink-soft">Kamar {{ $room ? $room->room_number : '' }}</span>
                        <a href="{{ route('penghuni.tagihan') }}" 
                           class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors">
                            Bayar sekarang
                        </a>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- ELEMEN B: PESANAN AKTIF (DOM 2, Desktop Kiri)                 -->
    <!-- ============================================================== -->
    <div class="lg:col-start-1 lg:row-start-1 space-y-3">
        @if($activeOrders->isNotEmpty())
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-ink-faint uppercase tracking-wider">Pesanan Berjalan</span>
                    <a href="{{ route('penghuni.pesanan') }}" class="text-xs font-semibold text-brand hover:text-brand-deep">
                        Lihat semua ({{ $activeOrders->count() }})
                    </a>
                </div>

                @foreach($activeOrders->take(2) as $ord)
                    <a href="{{ route('penghuni.pesanan') }}" 
                       class="block bg-surface rounded-xl border border-line hover:border-ink-faint p-4 transition-colors shadow-xs group">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-ink">{{ $ord->order_code }}</span>
                                    @if($ord->order_type === 'quick_galon')
                                        <span class="text-[10px] font-medium text-ink-soft bg-canvas px-2 py-0.5 rounded-full border border-line">Galon</span>
                                    @endif
                                </div>
                                <p class="text-xs text-ink-soft font-medium mt-1 truncate max-w-[240px] sm:max-w-xs">
                                    {{ $ord->items->pluck('item_name')->join(', ') }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <x-status-badge :status="$ord->status" />
                                <span class="block text-[11px] text-ink-faint mt-1">
                                    diperbarui {{ $ord->updated_at->locale('id')->diffForHumans() }}
                                </span>
                            </div>
                        </div>

                        <!-- Stepper Ringkas -->
                        <div class="mt-3 pt-3 border-t border-line">
                            <x-order-stepper :status="$ord->status" :compact="true" />
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- ELEMEN C: PESAN ULANG GALON TERAKHIR (DOM 3, Desktop Kiri)     -->
    <!-- ============================================================== -->
    <div class="lg:col-start-1 lg:row-start-2">
        <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-ink">Pesan ulang galon terakhir</h2>
                    <p class="text-xs text-ink-soft mt-0.5">Diantar dan dipasangkan langsung ke dispenser kamar</p>
                </div>
                <button type="button" 
                        @click="changeGalonSheet = true" 
                        class="text-xs font-semibold text-brand hover:text-brand-deep underline underline-offset-2 transition-colors">
                    Ganti
                </button>
            </div>

            <form action="{{ route('penghuni.quick_galon') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="catalog_item_id" :value="selectedGalonId">
                <input type="hidden" name="quantity" :value="galonQty">

                <!-- Baris Merek & Harga -->
                <div class="flex items-center justify-between bg-canvas rounded-xl p-3 border border-line">
                    <div>
                        <span class="text-xs font-bold text-ink block" x-text="selectedGalonName"></span>
                        <span class="text-xs text-ink-soft tabular-nums" 
                              x-text="'Rp ' + selectedGalonPrice.toLocaleString('id-ID') + ' per galon'"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[11px] text-ink-faint block font-medium">Subtotal</span>
                        <span class="text-sm font-bold text-ink tabular-nums whitespace-nowrap" 
                              x-text="galonSubtotalFormatted"></span>
                    </div>
                </div>

                <!-- Kontrol Jumlah & Tombol Pesan -->
                <div class="flex items-center justify-between gap-3 pt-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-ink-soft font-medium">Jumlah:</span>
                        <x-qty-stepper model="galonQty" :min="1" :max="5" />
                    </div>

                    <button type="submit" 
                            class="min-h-[44px] px-5 py-2.5 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors flex items-center justify-center">
                        Pesan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- ELEMEN D: INFO KAMAR & FASILITAS (DOM 4, Desktop Kanan)       -->
    <!-- ============================================================== -->
    <div class="lg:col-start-2 lg:row-start-2">
        <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs space-y-3">
            <span class="text-[11px] font-bold text-ink-faint uppercase tracking-wider">Informasi Hunian</span>
            
            <!-- Satu Baris Info Kamar -->
            <div class="text-xs text-ink font-medium leading-relaxed">
                <span class="font-bold text-ink">{{ $room ? $room->room_number : 'Kamar' }}</span>
                <span class="text-ink-faint mx-1.5">&bull;</span>
                <span>{{ $room ? $room->type : 'Standar' }}</span>
                @if($tenantProfile && $tenantProfile->start_date)
                    <span class="text-ink-faint mx-1.5">&bull;</span>
                    <span>Menghuni sejak {{ \Carbon\Carbon::parse($tenantProfile->start_date)->translatedFormat('d M Y') }}</span>
                @endif
            </div>

            <!-- Bagian Lipat Fasilitas Kamar -->
            @if($room && $room->facilities)
                <div class="pt-2 border-t border-line">
                    <button type="button" 
                            @click="roomFacilitiesOpen = !roomFacilitiesOpen" 
                            class="min-h-[44px] w-full flex items-center justify-between text-xs font-semibold text-ink-soft hover:text-ink transition-colors">
                        <span>Lihat fasilitas kamar</span>
                        <svg class="w-4 h-4 text-ink-faint transition-transform duration-200 motion-reduce:transition-none" 
                             :class="roomFacilitiesOpen ? 'rotate-180' : ''" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="roomFacilitiesOpen" 
                         x-cloak 
                         x-collapse
                         class="pt-2">
                        <ul class="divide-y divide-line text-xs text-ink-soft">
                            @foreach(explode(',', $room->facilities) as $facility)
                                <li class="py-1.5 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-ink-faint"></span>
                                    <span>{{ trim($facility) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- BOTTOM SHEET: PILIH MEREK GALON LAIN                          -->
    <!-- ============================================================== -->
    <x-bottom-sheet show="changeGalonSheet" title="Pilih Merek Galon">
        <div class="space-y-2">
            @foreach($galonItems as $galon)
                <button type="button" 
                        @click="
                            selectedGalonId = {{ $galon->id }};
                            selectedGalonName = '{{ addslashes($galon->name) }}';
                            selectedGalonPrice = {{ $galon->price }};
                            changeGalonSheet = false;
                        "
                        :class="selectedGalonId === {{ $galon->id }} ? 'border-brand bg-brand-wash' : 'border-line hover:border-ink-faint bg-surface'"
                        class="w-full min-h-[44px] p-3 rounded-xl border text-left flex items-center justify-between transition-colors">
                    <div>
                        <p class="text-xs font-bold text-ink">{{ $galon->name }}</p>
                        <p class="text-[11px] text-ink-soft tabular-nums">{{ $galon->formatted_price }}</p>
                    </div>
                    <div class="text-right">
                        @if($galon->stock <= 0)
                            <span class="text-[10px] font-semibold text-alert">Habis</span>
                        @elseif($galon->stock <= $galon->minimum_stock)
                            <span class="text-[10px] font-semibold text-wait">Sisa sedikit</span>
                        @else
                            <span class="text-[10px] text-ink-faint">Tersedia</span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </x-bottom-sheet>

</div>
@endsection
