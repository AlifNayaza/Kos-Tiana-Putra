@props(['order'])

@php
    $isCash = in_array(strtolower($order->payment_method ?? ''), ['tunai', 'cash']);
    $isPaid = ($order->payment_status === 'lunas');
@endphp

<div class="bg-surface rounded-xl border border-line p-4 shadow-xs space-y-3 transition-colors hover:border-ink-faint">
    <!-- Baris 1: Nomor Kamar & Status Badge + Indikator Tertunda -->
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="text-base sm:text-lg font-bold text-ink leading-tight tracking-tight">
                {{ $order->room_number }}
            </span>
            @if($order->is_delayed)
                <span class="text-[11px] font-semibold text-alert">
                    Tertunda ({{ $order->delayed_duration }})
                </span>
            @endif
        </div>

        <div>
            <x-status-badge :status="$order->status" />
        </div>
    </div>

    <!-- Baris 2: Nama Pemesan, ID Mono, Waktu Pembuatan -->
    <div class="text-xs text-ink-soft flex flex-wrap items-center gap-x-2 gap-y-0.5">
        <span class="font-medium text-ink">{{ $order->user->name ?? 'Penghuni' }}</span>
        <span class="text-ink-faint">&bull;</span>
        <span class="font-mono text-ink-soft">#{{ $order->order_code }}</span>
        <span class="text-ink-faint">&bull;</span>
        <span>dibuat {{ $order->created_at ? $order->created_at->diffForHumans() : '-' }}</span>
    </div>

    <!-- Daftar Item -->
    <div class="space-y-1.5 text-xs text-ink pt-1">
        @foreach($order->items as $item)
            <div class="flex justify-between items-start gap-2">
                <div class="min-w-0 flex-1">
                    <span class="font-semibold tabular-nums text-ink">{{ $item->quantity }}x</span>
                    <span class="ml-1 text-ink">{{ $item->item_name }}</span>
                    @if($item->notes && trim($item->notes) !== trim($order->notes ?? ''))
                        <p class="text-[11px] text-ink-faint italic pl-5 mt-0.5">{{ $item->notes }}</p>
                    @endif
                </div>
                <x-price :amount="$item->subtotal" class="font-medium text-ink-soft text-right shrink-0" />
            </div>
        @endforeach
    </div>

    <!-- Catatan Pesanan (Satu baris dengan garis kiri aksen) -->
    @if($order->notes)
        <div class="border-l-2 border-line pl-2.5 py-1 text-xs text-ink-soft flex items-start gap-1.5 bg-canvas rounded-r">
            <svg class="w-3.5 h-3.5 text-ink-faint shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
            <span class="leading-relaxed">Catatan: {{ $order->notes }}</span>
        </div>
    @endif

    <!-- Garis Pemisah Putus-Putus -->
    <div class="border-t border-dashed border-line pt-2.5">
        <!-- Baris Pembayaran -->
        <div class="flex items-center justify-between text-xs mb-3">
            <div class="flex items-center gap-1.5">
                @if($isCash && !$isPaid)
                    <span class="w-1.5 h-1.5 rounded-full bg-wait"></span>
                    <span class="font-medium text-ink-soft">Tagih tunai</span>
                @elseif($isCash && $isPaid)
                    <span class="w-1.5 h-1.5 rounded-full bg-done"></span>
                    <span class="font-medium text-ink-soft">Tunai (Lunas)</span>
                @elseif(strtolower($order->payment_method ?? '') === 'qris')
                    <span class="w-1.5 h-1.5 rounded-full {{ $isPaid ? 'bg-done' : 'bg-wait' }}"></span>
                    <span class="font-medium text-ink-soft">QRIS ({{ $isPaid ? 'Lunas' : 'Menunggu Bayar' }})</span>
                @else
                    <span class="w-1.5 h-1.5 rounded-full bg-ink-faint"></span>
                    <span class="font-medium text-ink-soft">{{ ucfirst($order->payment_method ?? 'Metode') }}</span>
                @endif
            </div>

            <x-price :amount="$order->total_amount" class="text-sm font-bold text-ink" />
        </div>

        <!-- Tombol Aksi Utama Selebar Kartu (Min 48px) Sesuai Status Alur -->
        <form method="POST" action="{{ route('petugas.orders.update-status', $order) }}">
            @csrf
            @method('PATCH')

            @if($order->status === 'menunggu')
                <input type="hidden" name="status" value="diproses">
                <button type="submit" 
                        class="w-full min-h-[48px] px-4 py-3 bg-brand hover:bg-brand-deep active:bg-brand-deep text-surface font-semibold text-xs rounded-xl transition-colors shadow-xs flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Mulai proses</span>
                </button>
            @elseif($order->status === 'diproses')
                <input type="hidden" name="status" value="diantar">
                <button type="submit" 
                        class="w-full min-h-[48px] px-4 py-3 bg-brand hover:bg-brand-deep active:bg-brand-deep text-surface font-semibold text-xs rounded-xl transition-colors shadow-xs flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                    <span>Antar sekarang</span>
                </button>
            @elseif($order->status === 'diantar')
                <input type="hidden" name="status" value="selesai">
                <button type="submit" 
                        class="w-full min-h-[48px] px-4 py-3 bg-brand-deep hover:bg-brand-deep/90 active:bg-brand-deep text-surface font-semibold text-xs rounded-xl transition-colors shadow-xs flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ ($isCash && !$isPaid) ? 'Selesai, uang diterima' : 'Selesai' }}</span>
                </button>
            @endif
        </form>
    </div>
</div>
