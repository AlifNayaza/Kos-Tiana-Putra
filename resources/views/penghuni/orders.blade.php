@extends('layouts.mobile')

@section('content')
<div class="max-w-2xl mx-auto space-y-4" x-data="{
    currentTab: '{{ $tab === 'riwayat' ? 'riwayat' : 'aktif' }}',
    cancelConfirmOpen: false,
    orderToCancelId: null,
    orderToCancelCode: '',

    openCancelConfirm(id, code) {
        this.orderToCancelId = id;
        this.orderToCancelCode = code;
        this.cancelConfirmOpen = true;
    }
}">

    <!-- Header Halaman -->
    <div class="flex items-center justify-between pb-1">
        <div>
            <h1 class="text-base sm:text-lg font-bold text-ink">Pesanan Saya</h1>
            <p class="text-xs text-ink-soft">Pantau status antrean dan riwayat pemesanan kamar</p>
        </div>
    </div>

    <!-- Tab Selector: Berjalan / Riwayat -->
    <div class="grid grid-cols-2 p-1 bg-canvas rounded-xl text-xs border border-line">
        <button type="button" 
                @click="currentTab = 'aktif'"
                :class="currentTab === 'aktif' ? 'bg-surface text-ink font-bold shadow-xs' : 'text-ink-soft hover:text-ink font-medium'"
                class="min-h-[44px] rounded-lg transition-colors flex items-center justify-center gap-2">
            <span>Sedang Berjalan</span>
            @if($activeOrders->count() > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-wash text-brand tabular-nums">
                    {{ $activeOrders->count() }}
                </span>
            @endif
        </button>

        <button type="button" 
                @click="currentTab = 'riwayat'"
                :class="currentTab === 'riwayat' ? 'bg-surface text-ink font-bold shadow-xs' : 'text-ink-soft hover:text-ink font-medium'"
                class="min-h-[44px] rounded-lg transition-colors flex items-center justify-center gap-2">
            <span>Riwayat Selesai</span>
            @if($completedOrders->total() > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-line text-ink-soft tabular-nums">
                    {{ $completedOrders->total() }}
                </span>
            @endif
        </button>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 1: PESANAN SEDANG BERJALAN                                 -->
    <!-- ============================================================== -->
    <div x-show="currentTab === 'aktif'" class="space-y-4">
        @forelse($activeOrders as $order)
            <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs space-y-4">
                
                <!-- Baris Atas: ID & Status -->
                <div class="flex items-start justify-between gap-3 pb-3 border-b border-line">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-ink">{{ $order->order_code }}</span>
                            @if($order->order_type === 'quick_galon')
                                <span class="text-[10px] font-medium text-ink-soft bg-canvas px-2 py-0.5 rounded-full border border-line">
                                    Galon
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] text-ink-faint mt-0.5">
                            {{ $order->created_at->translatedFormat('d M Y, H:i') }} &bull; Diantar ke {{ $order->room_number }}
                        </p>
                    </div>

                    <div class="text-right flex-shrink-0">
                        <x-status-badge :status="$order->status" />
                        <span class="block text-[11px] text-ink-faint mt-1">
                            diperbarui {{ $order->updated_at->locale('id')->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <!-- Komponen Stepper Bersama -->
                <x-order-stepper :status="$order->status" />

                <!-- Rincian Pesanan (Gaya Nota: Garis Putus-Putus) -->
                <div class="bg-canvas rounded-xl p-3.5 border border-line space-y-2 text-xs">
                    <div class="divide-y divide-line">
                        @foreach($order->items as $item)
                            <div class="py-1.5 first:pt-0 last:pb-0 flex items-center justify-between">
                                <span class="text-ink font-medium">
                                    {{ $item->item_name }} <span class="text-ink-faint tabular-nums">× {{ $item->quantity }}</span>
                                </span>
                                <span class="font-semibold text-ink tabular-nums whitespace-nowrap">
                                    <x-price :amount="$item->subtotal" />
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Garis Pemisah Putus-Putus -->
                    <div class="border-t border-dashed border-line pt-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-ink block">Total</span>
                            <span class="text-[11px] text-ink-faint">Metode: {{ $order->payment_method }}</span>
                        </div>
                        <span class="text-base font-bold text-ink tabular-nums whitespace-nowrap">
                            {{ $order->formatted_total }}
                        </span>
                    </div>
                </div>

                <!-- Catatan untuk Petugas (TAMPIL SEKALI) -->
                @if($order->notes)
                    <div class="p-3 bg-canvas rounded-xl border border-line text-xs text-ink-soft">
                        <span class="font-semibold text-ink block text-[11px] mb-0.5">Catatan:</span>
                        <p class="leading-relaxed">{{ $order->notes }}</p>
                    </div>
                @endif

                <!-- Aksi Batalkan Pesanan (Hanya jika status 'menunggu') -->
                @if($order->isCancellable())
                    <div class="pt-2 flex justify-end">
                        <button type="button" 
                                @click="openCancelConfirm({{ $order->id }}, '{{ $order->order_code }}')" 
                                class="min-h-[44px] px-4 py-2 border border-line hover:border-alert text-alert hover:bg-alert-wash rounded-xl text-xs font-semibold transition-colors">
                            Batalkan Pesanan
                        </button>
                    </div>
                @endif

            </div>
        @empty
            <div class="bg-surface rounded-xl border border-line p-8 text-center text-xs text-ink-faint shadow-xs space-y-2">
                <p>Tidak ada pesanan yang sedang berjalan.</p>
                <a href="{{ route('penghuni.katalog') }}" class="inline-block font-semibold text-brand hover:text-brand-deep">
                    Pesan menu sekarang
                </a>
            </div>
        @endforelse
    </div>

    <!-- ============================================================== -->
    <!-- TAB 2: RIWAYAT SELESAI & BATAL                                 -->
    <!-- ============================================================== -->
    <div x-show="currentTab === 'riwayat'" class="space-y-3">
        @forelse($completedOrders as $order)
            <div class="bg-surface rounded-xl border border-line p-4 text-ink shadow-xs space-y-3">
                <div class="flex items-start justify-between gap-3 pb-2.5 border-b border-line">
                    <div>
                        <span class="font-mono text-xs font-bold text-ink">{{ $order->order_code }}</span>
                        <p class="text-[11px] text-ink-faint mt-0.5">
                            {{ $order->created_at->translatedFormat('d M Y, H:i') }}
                        </p>
                    </div>
                    <x-status-badge :status="$order->status" />
                </div>

                <!-- Rincian Item Singkat -->
                <div class="space-y-1.5 text-xs">
                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between text-ink-soft">
                            <span>{{ $item->item_name }} <span class="text-ink-faint tabular-nums">× {{ $item->quantity }}</span></span>
                            <span class="font-medium text-ink tabular-nums whitespace-nowrap"><x-price :amount="$item->subtotal" /></span>
                        </div>
                    @endforeach
                </div>

                <!-- Garis Pemisah Putus-Putus Nota -->
                <div class="border-t border-dashed border-line pt-2 flex items-center justify-between text-xs">
                    <span class="text-ink-soft font-medium">Metode: {{ $order->payment_method }}</span>
                    <span class="font-bold text-ink tabular-nums whitespace-nowrap">{{ $order->formatted_total }}</span>
                </div>

                <!-- Catatan Tampil Sekali jika ada -->
                @if($order->notes)
                    <p class="text-[11px] text-ink-soft bg-canvas rounded-lg p-2 border border-line">
                        Catatan: {{ $order->notes }}
                    </p>
                @endif
            </div>
        @empty
            <div class="bg-surface rounded-xl border border-line p-8 text-center text-xs text-ink-faint shadow-xs">
                Belum ada riwayat pesanan sebelumnya.
            </div>
        @endforelse

        @if($completedOrders->hasPages())
            <div class="pt-2">
                {{ $completedOrders->links() }}
            </div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- DIALOG KONFIRMASI PEMBATALAN PESANAN                           -->
    <!-- ============================================================== -->
    <x-bottom-sheet show="cancelConfirmOpen" title="Batalkan Pesanan">
        <div class="space-y-4">
            <p class="text-xs text-ink-soft leading-relaxed">
                Apakah Anda yakin ingin membatalkan pesanan <span class="font-mono font-bold text-ink" x-text="'#' + orderToCancelCode"></span>? 
                Stok barang akan dikembalikan secara otomatis.
            </p>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                <button type="button" 
                        @click="cancelConfirmOpen = false" 
                        class="min-h-[44px] px-4 py-2 border border-line rounded-xl text-xs font-semibold text-ink-soft hover:bg-canvas transition-colors">
                    Kembali
                </button>
                <form :action="'{{ url('/penghuni/pesanan') }}/' + orderToCancelId + '/batal'" method="POST">
                    @csrf
                    <button type="submit" 
                            class="min-h-[44px] px-4 py-2 bg-alert hover:bg-alert/90 text-surface rounded-xl text-xs font-semibold transition-colors">
                        Ya, Batalkan
                    </button>
                </form>
            </div>
        </div>
    </x-bottom-sheet>

</div>
@endsection
