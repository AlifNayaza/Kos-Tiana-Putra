@extends('layouts.app')

@section('page_title', 'Ringkasan Kos')
@section('page_subtitle', 'Kondisi pendapatan, hunian, pesanan, dan stok hari ini')
@section('no_page_band', '1')

@section('content')
@php
    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'pagi' : ($hour < 15 ? 'siang' : ($hour < 18 ? 'sore' : 'malam'));
    // Lewati gelar/singkatan berakhiran titik ("H.", "Dr.") saat menyapa
    $nameParts = array_values(array_filter(explode(' ', trim(Auth::user()->name)), fn ($w) => $w !== '' && !str_ends_with($w, '.')));
    $firstName = $nameParts[0] ?? 'Pengelola';
    $monthName = now()->translatedFormat('F');
    $rp = fn ($v) => 'Rp ' . number_format((int) $v, 0, ',', '.');
    $short = fn ($roomNumber) => trim(str_replace('Kamar', '', $roomNumber));

    $overdueOther = $overdueBills->reject(fn ($b) => $b->month === now()->month && $b->year === now()->year);
    $unpaidTotal = $pendingRentThisMonth + $overdueOther->sum('amount');
    $overdueRoomIds = $overdueBills->pluck('room_id')->flip();
    $unbilled = $rooms->filter(fn ($r) => $r->activeTenantProfile && !$currentBills->has($r->id))->count();
    $occ = round(($occupiedRoomsCount / max($roomsCount, 1)) * 100);

    // Daftar "Perlu ditindak": setiap baris punya satu tombol yang langsung membawa ke tempat kerjanya.
    $agenda = [];
    if ($overdueBills->isNotEmpty()) {
        $agenda[] = [
            'tone' => 'alert', 'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
            'title' => $overdueBills->count() . ' tagihan lewat jatuh tempo',
            'detail' => $overdueBills->take(3)->map(fn ($b) => $short($b->room->room_number) . ' ' . (Str::of($b->tenantProfile->user->name ?? '')->before(' ')))->join(', ')
                . ($overdueBills->count() > 3 ? ' +' . ($overdueBills->count() - 3) : '') . ' · ' . $rp($overdueBills->sum('amount')),
            'action' => 'Tagih', 'url' => route('pengelola.tagihan.index', ['month' => 'semua', 'year' => 'semua', 'status' => 'jatuh_tempo']),
        ];
    }
    if ($verifyCount > 0) {
        $agenda[] = [
            'tone' => 'process', 'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
            'title' => $verifyCount . ' bukti transfer menunggu dicek',
            'detail' => 'Cocokkan dengan mutasi rekening, lalu tandai lunas.',
            'action' => 'Cek bukti', 'url' => route('pengelola.tagihan.index', ['month' => 'semua', 'year' => 'semua', 'status' => 'menunggu']),
        ];
    }
    if ($unbilled > 0) {
        $agenda[] = [
            'tone' => 'wait', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'title' => "Tagihan {$monthName} belum terbit untuk {$unbilled} kamar",
            'detail' => 'Terbitkan sekaligus untuk semua penghuni aktif.',
            'action' => 'Terbitkan', 'url' => route('pengelola.tagihan.index', ['baru' => 1]),
        ];
    }
    if ($stockAlerts->isNotEmpty()) {
        $agenda[] = [
            'tone' => 'wait', 'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
            'title' => $stockAlerts->count() . ' barang kios hampir habis',
            'detail' => $stockAlerts->take(3)->map(fn ($i) => "{$i->name} ({$i->stock})")->join(', ') . ($stockAlerts->count() > 3 ? ' +' . ($stockAlerts->count() - 3) : ''),
            'action' => 'Rencana belanja', 'url' => route('pengelola.prediksi.index'),
        ];
    }
    $urgent = count($agenda);
    if ($availableRoomsCount > 0) {
        $agenda[] = [
            'tone' => 'brand', 'icon' => 'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z',
            'title' => $availableRoomsCount . ' kamar kosong siap diisi',
            'detail' => $rooms->where('status', 'tersedia')->map(fn ($r) => $short($r->room_number))->join(', '),
            'action' => 'Daftarkan', 'url' => route('pengelola.penghuni.index', ['baru' => 1]),
        ];
    }

    $toneClass = [
        'alert'   => ['chip' => 'bg-alert-wash text-alert',     'btn' => 'bg-alert text-white hover:bg-alert/90'],
        'process' => ['chip' => 'bg-process-wash text-process', 'btn' => 'bg-process text-white hover:bg-process/90'],
        'wait'    => ['chip' => 'bg-wait-wash text-wait',       'btn' => 'bg-surface text-ink border border-line hover:bg-canvas'],
        'brand'   => ['chip' => 'bg-brand-wash text-brand',     'btn' => 'bg-surface text-ink border border-line hover:bg-canvas'],
    ];

    // Status pintu di denah
    $roomState = function ($room) use ($currentBills, $overdueRoomIds) {
        if ($room->status === 'perbaikan') return 'perbaikan';
        if (!$room->activeTenantProfile) return 'kosong';
        if ($overdueRoomIds->has($room->id)) return 'menunggak';
        $bill = $currentBills->get($room->id);
        if (!$bill) return 'belum';
        if ($bill->status === 'lunas') return 'lunas';
        return $bill->payment_proof ? 'cek' : 'menunggu';
    };
    $stateStyle = [
        'menunggak' => ['label' => 'Menunggak',     'door' => 'border-alert/60 bg-alert-wash',            'pill' => 'bg-alert text-white',        'dot' => 'bg-alert'],
        'cek'       => ['label' => 'Cek bukti',     'door' => 'border-process/40 bg-process-wash/60',     'pill' => 'bg-process text-white',      'dot' => 'bg-process'],
        'menunggu'  => ['label' => 'Belum bayar',   'door' => 'border-wait/40 bg-wait-wash/60',           'pill' => 'bg-wait-wash text-wait',     'dot' => 'bg-wait'],
        'belum'     => ['label' => 'Belum ditagih', 'door' => 'border-line bg-surface',                   'pill' => 'bg-canvas text-ink-soft',    'dot' => 'bg-ink-faint/50'],
        'lunas'     => ['label' => 'Lunas',         'door' => 'border-done/40 bg-done-wash/50',           'pill' => 'bg-done text-white',         'dot' => 'bg-done'],
        'kosong'    => ['label' => 'Kosong',        'door' => 'border-dashed border-ink-faint/50 bg-transparent', 'pill' => 'bg-brand-wash text-brand', 'dot' => 'bg-brand/60'],
        'perbaikan' => ['label' => 'Perbaikan',     'door' => 'border-line room-hatch',                   'pill' => 'bg-ink/80 text-white',       'dot' => 'bg-ink/70'],
    ];
    $stateCounts = $rooms->map($roomState)->countBy();
    $blocks = $rooms->groupBy(fn ($r) => Str::of($short($r->room_number))->substr(0, 1)->upper()->value())->sortKeysDesc();
@endphp

<div class="space-y-6">

    <!-- 1. Sapaan singkat: jumlah hal yang perlu ditindak -->
    <section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <div>
            <h1 class="font-display text-[1.75rem] sm:text-4xl font-bold text-brand-deep tracking-tight leading-tight">Selamat {{ $greeting }}, {{ $firstName }}</h1>
            <p class="mt-1 text-sm text-ink-soft">
                @if($urgent > 0)
                    Ada <b class="text-ink">{{ $urgent }} hal</b> yang perlu ditindak hari ini.
                @else
                    Semua beres hari ini. Tidak ada tagihan tertunggak maupun stok yang menipis.
                @endif
            </p>
        </div>
        <p class="text-xs text-ink-faint lg:hidden">{{ now()->translatedFormat('l, d F Y') }}</p>
    </section>

    <!-- 2. Perlu ditindak + Buku kas -->
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] gap-6 items-start">

        <section aria-labelledby="todo-title" class="bg-surface rounded-3xl border border-line shadow-xs">
            <div class="px-5 pt-5 pb-3 flex items-center justify-between gap-2">
                <h2 id="todo-title" class="text-base font-bold text-ink">Perlu ditindak</h2>
                @if($urgent > 0)
                    <span class="rounded-md border border-alert/30 bg-alert-wash px-1.5 font-mono text-xs font-bold leading-5 text-alert tabular-nums">{{ $urgent }}</span>
                @endif
            </div>
            <ul class="px-2 pb-2" role="list">
                @forelse($agenda as $item)
                    @php $t = $toneClass[$item['tone']]; @endphp
                    <li class="{{ $loop->index === $urgent && $urgent > 0 ? 'mt-1 pt-1 border-t border-dashed border-line' : '' }}">
                      <a href="{{ $item['url'] }}" class="group flex items-center gap-3 rounded-2xl px-3 py-3 hover:bg-canvas/70 transition-colors">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $t['chip'] }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink leading-snug">{{ $item['title'] }}</p>
                            <p class="text-xs text-ink-soft line-clamp-2 sm:line-clamp-1">{{ $item['detail'] }}</p>
                        </div>
                        <span class="hidden sm:inline-flex shrink-0 items-center gap-1 h-9 px-3 rounded-xl text-xs font-semibold transition-colors {{ $t['btn'] }}">
                            {{ $item['action'] }}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </span>
                        <span class="sm:hidden shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $t['btn'] }}" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </span>
                        <span class="sr-only">{{ $item['action'] }}</span>
                      </a>
                    </li>
                @empty
                    <li class="flex items-center gap-3 rounded-2xl px-3 py-4">
                        <span class="w-10 h-10 rounded-xl bg-done-wash text-done flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        </span>
                        <p class="text-sm text-ink-soft"><b class="text-ink">Tidak ada pekerjaan tertunda.</b> Tagihan, stok, dan kamar aman.</p>
                    </li>
                @endforelse
            </ul>
        </section>

        <!-- Buku kas bulan ini: gaya struk -->
        <section aria-labelledby="kas-title" class="ledger relative bg-surface rounded-3xl border border-line shadow-xs p-5">
            <div class="flex items-center justify-between">
                <h2 id="kas-title" class="text-base font-bold text-ink">Buku kas {{ $monthName }}</h2>
                <a href="{{ route('pengelola.laporan.index') }}" class="text-xs font-semibold text-brand hover:underline underline-offset-2">Laporan</a>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-baseline gap-2"><dt class="text-ink-soft">Sewa masuk</dt><span class="ledger-dots"></span><dd class="font-mono font-semibold tabular-nums text-ink">{{ $rp($rentIncomeThisMonth) }}</dd></div>
                <div class="flex items-baseline gap-2"><dt class="text-ink-soft">Kios &amp; galon</dt><span class="ledger-dots"></span><dd class="font-mono font-semibold tabular-nums text-ink">{{ $rp($serviceSalesThisMonth) }}</dd></div>
            </dl>
            <div class="my-3 border-t-2 border-dashed border-line"></div>
            <div class="flex items-baseline justify-between gap-2">
                <span class="text-sm font-semibold text-ink">Total masuk</span>
                <span class="font-display text-2xl sm:text-3xl font-bold text-brand-deep tabular-nums">{{ $rp($totalIncomeThisMonth) }}</span>
            </div>
            <a href="{{ route('pengelola.tagihan.index', ['month' => 'semua', 'year' => 'semua', 'status' => 'jatuh_tempo']) }}"
               class="mt-4 flex items-center justify-between gap-2 rounded-2xl px-3.5 py-3 {{ $unpaidTotal > 0 ? 'bg-alert-wash text-alert hover:bg-alert-wash/70' : 'bg-done-wash text-done' }} transition-colors">
                <span class="text-xs font-semibold leading-snug">
                    {{ $unpaidTotal > 0 ? 'Belum dibayar' : 'Tidak ada tunggakan' }}
                    @if($overdueOther->isNotEmpty())
                        <span class="block font-normal opacity-90">termasuk tunggakan bulan lalu</span>
                    @endif
                </span>
                <span class="font-mono text-base font-bold tabular-nums whitespace-nowrap">{{ $rp($unpaidTotal) }}</span>
            </a>
        </section>
    </div>

    <!-- 3. Denah kamar: status setiap pintu sekali lihat -->
    <section aria-labelledby="denah-title" class="bg-surface rounded-3xl border border-line shadow-xs p-5">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <h2 id="denah-title" class="text-base font-bold text-ink">Denah kamar</h2>
                <p class="text-xs text-ink-soft mt-0.5">
                    <b class="text-ink tabular-nums">{{ $occupiedRoomsCount }}/{{ $roomsCount }}</b> terisi ({{ $occ }}%) · {{ $tenantsCount }} penghuni aktif<span class="hidden sm:inline"> · ketuk pintu untuk membuka rinciannya</span>
                </p>
            </div>
            <a href="{{ route('pengelola.kamar.index') }}" class="text-xs font-semibold text-brand hover:underline underline-offset-2">Kelola kamar</a>
        </div>

        <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Keterangan status">
            @foreach($stateStyle as $key => $st)
                @if($stateCounts->get($key))
                    <li class="inline-flex items-center gap-1.5 rounded-full border border-line bg-canvas/60 pl-2 pr-2.5 py-0.5 text-[11px] font-semibold text-ink-soft">
                        <span class="w-2 h-2 rounded-full {{ $st['dot'] }}"></span>{{ $st['label'] }}
                        <span class="font-mono text-ink tabular-nums">{{ $stateCounts->get($key) }}</span>
                    </li>
                @endif
            @endforeach
        </ul>

        <div class="mt-4 space-y-4">
            @foreach($blocks as $block => $blockRooms)
                <div class="sm:flex sm:gap-3">
                    <div class="sm:w-7 shrink-0 flex sm:items-end sm:pb-1 mb-1.5 sm:mb-0">
                        <span class="font-mono text-[11px] font-bold text-ink-faint tracking-widest sm:[writing-mode:vertical-rl] sm:rotate-180">BLOK {{ $block }}</span>
                    </div>
                    <div class="flex-1 grid grid-cols-5 gap-1.5 sm:gap-3 border-b-4 border-line/80 pb-2 sm:pb-2.5">
                        @foreach($blockRooms as $room)
                            @php
                                $state = $roomState($room);
                                $st = $stateStyle[$state];
                                $tenantName = $room->activeTenantProfile?->user?->name;
                                $href = in_array($state, ['menunggak', 'menunggu', 'cek'])
                                    ? route('pengelola.tagihan.index', ['month' => 'semua', 'year' => 'semua', 'q' => $short($room->room_number)])
                                    : ($state === 'kosong' ? route('pengelola.penghuni.index', ['baru' => 1]) : route('pengelola.kamar.index'));
                            @endphp
                            <a href="{{ $href }}"
                               class="room-door group relative flex flex-col items-center text-center overflow-hidden rounded-t-2xl sm:rounded-t-[1.25rem] rounded-b-md border-2 px-1 sm:px-1.5 pt-2 sm:pt-2.5 sm:pb-2 min-h-[86px] sm:min-h-[112px] {{ $st['door'] }}"
                               title="{{ $room->room_number }} · {{ $st['label'] }}{{ $tenantName ? ' · ' . $tenantName : '' }}">
                                <span class="rounded border border-ink/25 bg-surface px-1 sm:px-1.5 font-mono text-[10px] sm:text-[11px] font-bold leading-4 text-ink">{{ $short($room->room_number) }}</span>
                                <span class="mt-1.5 sm:mt-2 w-full text-[11px] sm:text-xs font-semibold text-ink leading-tight truncate sm:whitespace-normal sm:line-clamp-2">{{ $tenantName ? Str::of($tenantName)->before(' ') : '—' }}</span>
                                <span class="absolute right-1.5 sm:right-2.5 top-[56%] w-1.5 h-1.5 rounded-full bg-ink/40" aria-hidden="true"></span>
                                <span class="hidden sm:inline-block mt-auto rounded-full px-1.5 py-0.5 text-[10.5px] font-bold whitespace-nowrap {{ $st['pill'] }}">{{ $st['label'] }}</span>
                                <span class="sm:hidden mt-auto h-1.5 w-[calc(100%+0.5rem)] {{ $st['dot'] }}" aria-hidden="true"></span>
                                <span class="sr-only sm:hidden">{{ $st['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- 4. Pesanan terbaru + belanja kios -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <section aria-labelledby="order-title" class="lg:col-span-2 bg-surface rounded-3xl border border-line shadow-xs overflow-hidden">
            <div class="px-5 pt-5 pb-3 flex items-center justify-between gap-2">
                <div>
                    <h2 id="order-title" class="text-base font-bold text-ink">Pesanan kios terbaru</h2>
                    <p class="text-xs text-ink-soft">{{ $activeOrdersCount }} sedang diproses petugas</p>
                </div>
            </div>
            <ul class="divide-y divide-line" role="list">
                @forelse($recentOrders as $order)
                    <li class="px-4 sm:px-5 py-3 items-start gap-3 {{ $loop->index >= 4 ? 'hidden sm:flex' : 'flex' }}">
                        <span class="mt-0.5 rounded border border-ink/25 px-1.5 font-mono text-[11px] font-bold leading-5 text-ink shrink-0">{{ $short($order->room_number) }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="text-sm text-ink truncate">{{ $order->items->pluck('item_name')->join(', ') }}</p>
                                <p class="text-sm font-bold text-ink tabular-nums whitespace-nowrap">{{ $order->formatted_total }}</p>
                            </div>
                            <div class="mt-1 flex items-center justify-between gap-3">
                                <p class="text-2xs text-ink-faint truncate">{{ $order->created_at->diffForHumans() }} · {{ $order->payment_method }}</p>
                                <x-status-badge :status="$order->status" :label="$order->status_badge['label']" />
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-ink-faint text-xs">Belum ada pesanan.</li>
                @endforelse
            </ul>
        </section>

        <section aria-labelledby="shop-title" class="bg-surface rounded-3xl border border-line shadow-xs p-5">
            <div class="flex items-center justify-between">
                <h2 id="shop-title" class="text-base font-bold text-ink">Belanja kios</h2>
                <a href="{{ route('pengelola.prediksi.index') }}" class="text-xs font-semibold text-brand hover:underline underline-offset-2">Rincian</a>
            </div>
            <p class="text-xs text-ink-soft mt-0.5">Saran untuk 7 hari ke depan</p>
            @php $restockItems = $predictions->filter(fn ($p) => $p->recommended_restock_qty > 0)->take(5); @endphp
            <ul class="mt-3 space-y-2" role="list">
                @forelse($restockItems as $pred)
                    <li class="flex items-center gap-3 rounded-2xl bg-canvas/70 px-3 py-2.5">
                        <span class="w-2 h-2 rounded-full shrink-0 {{ $pred->status === 'kritis' ? 'bg-alert' : 'bg-wait' }}" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink truncate">{{ $pred->catalogItem->name ?? 'Item' }}</p>
                            <p class="text-2xs text-ink-soft">Sisa {{ $pred->current_stock }} · cukup ~{{ $pred->days_of_supply }} hari</p>
                        </div>
                        <span class="font-mono text-sm font-bold text-brand tabular-nums shrink-0">+{{ $pred->recommended_restock_qty }}</span>
                    </li>
                @empty
                    <li class="rounded-2xl bg-done-wash px-3 py-4 text-center text-xs text-done font-semibold">Stok kios aman untuk 7 hari ke depan.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <!-- 5. Tren pemasukan -->
    <section aria-labelledby="trend-title" class="bg-surface rounded-3xl border border-line shadow-xs p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 id="trend-title" class="text-base font-bold text-ink">Pemasukan 6 bulan terakhir</h2>
                <p class="text-xs text-ink-soft">Uang sewa kamar dibanding penjualan kios &amp; galon</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium text-ink-soft">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-brand"></span>Sewa</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-ochre"></span>Kios &amp; galon</span>
            </div>
        </div>
        <div class="mt-4 relative h-48 sm:h-60 w-full">
            <canvas id="revenueTrendChart" role="img" aria-label="Grafik batang pemasukan sewa kamar dan penjualan kios selama enam bulan terakhir"></canvas>
        </div>
    </section>
</div>
@endsection

@push('scripts')
@include('layouts.partials.charts-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('revenueTrendChart');
    if (!ctx) return;

    const labels = {!! json_encode($chartLabels) !!};
    const rentData = {!! json_encode($rentSeries) !!};
    const salesData = {!! json_encode($salesSeries) !!};

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Sewa Kamar',
                    data: rentData,
                    backgroundColor: '#006059',
                    borderRadius: 4,
                    maxBarThickness: 28,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                },
                {
                    label: 'Kios & Galon',
                    data: salesData,
                    backgroundColor: '#c98a2b',
                    borderRadius: 4,
                    maxBarThickness: 28,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 12
                        },
                        color: '#46544f',
                        maxRotation: 0,
                        autoSkip: true,
                        callback: function (value) {
                            const label = this.getLabelForValue(value);
                            return window.innerWidth < 640 ? String(label).split(' ')[0] : label;
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#dedbd0',
                        lineWidth: 1
                    },
                    ticks: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11
                        },
                        color: '#46544f',
                        maxTicksLimit: 5,
                        callback: function (value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(value % 1000000 ? 1 : 0) + ' jt';
                            }
                            return (value / 1000).toFixed(0) + ' rb';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
