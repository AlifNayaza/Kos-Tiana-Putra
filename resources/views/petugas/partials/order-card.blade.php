@props(['order'])

@php
    $isCash = \App\Models\Order::isCashMethod($order->payment_method);
    $isPaid = ($order->payment_status === 'lunas');
@endphp

@php
    $statusBorder = match ($order->status) {
        'menunggu' => 'border-l-wait',
        'diproses' => 'border-l-process',
        'diantar' => 'border-l-brand',
        'selesai' => 'border-l-done',
        default => 'border-l-line',
    };
@endphp
<div data-order-id="{{ $order->id }}" data-order-status="{{ $order->status }}" class="bg-surface rounded-2xl border border-line border-l-[6px] {{ $statusBorder }} p-4 shadow-sm space-y-3 transition-shadow hover:shadow-md">
    <!-- Baris 1: Nomor Kamar & Status Badge + Indikator Tertunda -->
    <div class="flex items-start justify-between gap-2">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 min-w-0">
            <span class="whitespace-nowrap text-lg sm:text-xl font-bold text-ink leading-tight tracking-tight">
                {{ $order->room_number }}
            </span>
            @if($order->is_delayed)
                <span class="text-2xs font-semibold text-alert">
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

    @php
        $contactPhone = $order->user->phone ?? null;
        $telUrl = \App\Support\Phone::telUrl($contactPhone);
        $waUrl = \App\Support\Phone::whatsappUrl($contactPhone, "Halo {$order->user->name}, ini petugas " . config('kos.name') . ". Pesanan #{$order->order_code} ke {$order->room_number} sedang kami proses ya.");
    @endphp
    @if($telUrl)
        <div class="flex gap-2">
            <a href="{{ $telUrl }}" class="inline-flex items-center gap-1.5 min-h-[44px] px-3.5 rounded-xl border border-line bg-surface text-xs font-semibold text-ink hover:bg-brand-wash transition-colors">
                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                Telepon<span class="sr-only"> {{ $order->user->name }}</span>
            </a>
            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 min-h-[44px] px-3.5 rounded-xl border border-line bg-surface text-xs font-semibold text-ink hover:bg-brand-wash transition-colors">
                <svg class="w-4 h-4 text-done" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-9 6l1.6-4.2A8 8 0 1112 20a8 8 0 01-3.6-.9L4 20z"/></svg>
                WhatsApp<span class="sr-only"> {{ $order->user->name }}</span>
            </a>
        </div>
    @endif

    <!-- Daftar Item -->
    <div class="space-y-1.5 text-xs text-ink pt-1">
        @foreach($order->items as $item)
            <div class="flex justify-between items-start gap-2">
                <div class="min-w-0 flex-1">
                    <span class="font-semibold tabular-nums text-ink">{{ $item->quantity }}x</span>
                    <span class="ml-1 text-ink">{{ $item->item_name }}</span>
                    @if($item->notes && trim($item->notes) !== trim($order->notes ?? ''))
                        <p class="text-2xs text-ink-faint italic pl-5 mt-0.5">{{ $item->notes }}</p>
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
                    <span>{{ ($isCash && !$isPaid) ? 'Selesai, terima tunai Rp ' . number_format($order->total_amount, 0, ',', '.') : 'Selesai' }}</span>
                </button>
            @endif
        </form>
    </div>
</div>
