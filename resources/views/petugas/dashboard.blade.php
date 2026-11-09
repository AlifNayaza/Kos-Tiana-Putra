@extends('layouts.mobile')

@section('content')
<div x-data="{
    activeFilter: 'semua',
    onlyGalon: false,
    secondsAgo: 0,
    isRefreshing: false,
    sig: @js($signature),

    init() {
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                document.title = document.title.replace('(Baru) ', '');
                this.checkQueue();
            }
        });
        setInterval(() => {
            this.secondsAgo++;
            // Tab tersembunyi tidak perlu polling; segarkan sekali saat kembali dibuka.
            if (this.secondsAgo >= 30 && !document.hidden) {
                this.checkQueue();
            }
        }, 1000);
    },

    // Polling hemat: tanya tanda perubahan (JSON beberapa byte); unduh HTML penuh hanya bila berubah.
    checkQueue() {
        this.secondsAgo = 0;
        fetch('{{ route('petugas.antrean.signal') }}', { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => { if (data.sig !== this.sig) this.refreshQueue(); })
            .catch(() => {});
    },

    refreshQueue() {
        this.isRefreshing = true;
        fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newQueue = doc.getElementById('queue-content');
            const target = document.getElementById('queue-content');
            if (newQueue && target) {
                const known = new Set([...target.querySelectorAll('[data-order-id]')].map(e => e.dataset.orderId));
                const fresh = [...newQueue.querySelectorAll('[data-order-id][data-order-status=menunggu]')]
                    .filter(e => !known.has(e.dataset.orderId));
                target.innerHTML = newQueue.innerHTML;
                if (newQueue.dataset.sig) this.sig = newQueue.dataset.sig;
                if (fresh.length > 0) this.announceNewOrders(fresh.length);
            }
            this.secondsAgo = 0;
            this.isRefreshing = false;
        })
        .catch(() => {
            this.isRefreshing = false;
        });
    },

    announceNewOrders(count) {
        window.kosToast(count === 1 ? '1 pesanan baru masuk ke antrean' : count + ' pesanan baru masuk ke antrean', 'info');
        if (navigator.vibrate) navigator.vibrate([120, 60, 120]);
        if (document.hidden && !document.title.startsWith('(Baru)')) document.title = '(Baru) ' + document.title;
    },

    matches(orderStatus, hasGalon) {
        if (this.onlyGalon && !hasGalon) return false;
        if (this.activeFilter === 'semua') return true;
        return this.activeFilter === orderStatus;
    }
}" class="space-y-4">

    <!-- ============================================================== -->
    <!-- 1. HEADER RINGKAS DENGAN TIMER & REFRESH SEKUNDER               -->
    <!-- ============================================================== -->
    <div class="flex items-center justify-between pb-1">
        <div>
            <h1 class="text-3xl text-brand-deep leading-tight">Antrean</h1>
            <p class="text-2xs text-ink-faint mt-0.5">
                <span x-text="'diperbarui ' + secondsAgo + ' detik lalu'"></span>
                <span x-show="isRefreshing" class="text-brand font-semibold ml-1 animate-pulse">Menyinkronkan...</span>
            </p>
        </div>

        <button type="button" 
                @click="refreshQueue()" 
                :disabled="isRefreshing"
                class="min-h-[44px] min-w-[44px] p-2.5 rounded-xl border border-line bg-surface hover:bg-canvas text-ink-soft hover:text-ink transition-colors flex items-center justify-center disabled:opacity-50"
                aria-label="Segarkan Antrean"
                title="Segarkan antrean">
            <svg class="w-4 h-4" :class="isRefreshing ? 'animate-spin text-brand' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>

    <!-- ============================================================== -->
    <!-- 2. FILTER STATUS, TOGGLE GALON, & TAUTAN SELESAI HARI INI     -->
    <!-- ============================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-1">
        <!-- Filter Tabs Horizontal (Bisa digeser di mobile) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
            <button type="button" 
                    @click="activeFilter = 'semua'"
                    :class="activeFilter === 'semua' ? 'bg-brand text-surface font-bold border border-brand shadow-xs' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                    class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                Semua ({{ $orders->count() }})
            </button>
            <button type="button" 
                    @click="activeFilter = 'menunggu'"
                    :class="activeFilter === 'menunggu' ? 'bg-brand text-surface font-bold border border-brand shadow-xs' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                    class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                Menunggu ({{ $metrics['menunggu'] }})
            </button>
            <button type="button" 
                    @click="activeFilter = 'diproses'"
                    :class="activeFilter === 'diproses' ? 'bg-brand text-surface font-bold border border-brand shadow-xs' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                    class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                Diproses ({{ $metrics['diproses'] }})
            </button>
            <button type="button" 
                    @click="activeFilter = 'diantar'"
                    :class="activeFilter === 'diantar' ? 'bg-brand text-surface font-bold border border-brand shadow-xs' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                    class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                Diantar ({{ $metrics['diantar'] }})
            </button>
        </div>

        <!-- Bagian Kanan: Toggle Galon Saja & Tautan Selesai Hari Ini -->
        <div class="flex items-center justify-between sm:justify-end gap-3 text-xs">
            <!-- Toggle Galon Saja (Terpisah, bukan chip status) -->
            <label class="inline-flex items-center gap-2 font-medium text-ink-soft cursor-pointer min-h-[44px] px-2 select-none">
                <input type="checkbox" 
                       x-model="onlyGalon" 
                       class="w-4 h-4 rounded border-line text-brand focus:ring-brand focus:ring-offset-0">
                <span>Galon saja</span>
            </label>

            <!-- Tautan Ringkas ke Riwayat -->
            <a href="{{ route('petugas.history') }}" 
               class="text-ink-faint hover:text-brand font-medium whitespace-nowrap min-h-[44px] flex items-center transition-colors">
                <span>Selesai hari ini: <strong class="text-ink font-semibold tabular-nums">{{ $metrics['selesai'] }}</strong></span>
            </a>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 3. KONTEN ANTREAN (Auto-Refresh Fragment)                       -->
    <!-- ============================================================== -->
    <div id="queue-content" aria-live="polite" data-sig="{{ $signature }}">
        @if($orders->isEmpty())
            <div class="bg-surface rounded-xl border border-line p-8 text-center text-xs text-ink-faint shadow-xs space-y-1">
                <p class="font-medium text-ink-soft">Tidak ada antrean pesanan aktif saat ini.</p>
                <p>Semua pesanan kamar telah selesai diproses dan diantar.</p>
            </div>
        @else

            <!-- ========================================================== -->
            <!-- TAMPILAN DESKTOP (>= 1024px): PAPAN 3 KOLOM KANBAN        -->
            <!-- Menunggu | Diproses | Diantar                             -->
            <!-- ========================================================== -->
            <div class="hidden lg:grid lg:grid-cols-3 gap-5 items-start">
                
                <!-- KOLOM 1: MENUNGGU -->
                <div class="space-y-3" x-show="activeFilter === 'semua' || activeFilter === 'menunggu'">
                    <div class="flex items-center justify-between pb-2 border-b border-line px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-wait"></span>
                            <h2 class="text-xs font-bold text-ink-soft uppercase tracking-wider">Menunggu</h2>
                        </div>
                        <span class="text-xs font-semibold text-ink-faint tabular-nums">{{ $metrics['menunggu'] }}</span>
                    </div>

                    <div class="space-y-3">
                        @php $menungguOrders = $orders->where('status', 'menunggu'); @endphp
                        @forelse($menungguOrders as $order)
                            <div x-show="!onlyGalon || {{ $order->has_galon ? 'true' : 'false' }}">
                                @include('petugas.partials.order-card', ['order' => $order])
                            </div>
                        @empty
                            <p class="text-xs text-ink-faint p-4 text-center bg-surface rounded-xl border border-line">Kosong</p>
                        @endforelse
                    </div>
                </div>

                <!-- KOLOM 2: DIPROSES -->
                <div class="space-y-3" x-show="activeFilter === 'semua' || activeFilter === 'diproses'">
                    <div class="flex items-center justify-between pb-2 border-b border-line px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-process"></span>
                            <h2 class="text-xs font-bold text-ink-soft uppercase tracking-wider">Diproses</h2>
                        </div>
                        <span class="text-xs font-semibold text-ink-faint tabular-nums">{{ $metrics['diproses'] }}</span>
                    </div>

                    <div class="space-y-3">
                        @php $diprosesOrders = $orders->where('status', 'diproses'); @endphp
                        @forelse($diprosesOrders as $order)
                            <div x-show="!onlyGalon || {{ $order->has_galon ? 'true' : 'false' }}">
                                @include('petugas.partials.order-card', ['order' => $order])
                            </div>
                        @empty
                            <p class="text-xs text-ink-faint p-4 text-center bg-surface rounded-xl border border-line">Kosong</p>
                        @endforelse
                    </div>
                </div>

                <!-- KOLOM 3: DIANTAR -->
                <div class="space-y-3" x-show="activeFilter === 'semua' || activeFilter === 'diantar'">
                    <div class="flex items-center justify-between pb-2 border-b border-line px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-brand"></span>
                            <h2 class="text-xs font-bold text-ink-soft uppercase tracking-wider">Diantar</h2>
                        </div>
                        <span class="text-xs font-semibold text-ink-faint tabular-nums">{{ $metrics['diantar'] }}</span>
                    </div>

                    <div class="space-y-3">
                        @php $diantarOrders = $orders->where('status', 'diantar'); @endphp
                        @forelse($diantarOrders as $order)
                            <div x-show="!onlyGalon || {{ $order->has_galon ? 'true' : 'false' }}">
                                @include('petugas.partials.order-card', ['order' => $order])
                            </div>
                        @empty
                            <p class="text-xs text-ink-faint p-4 text-center bg-surface rounded-xl border border-line">Kosong</p>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- ========================================================== -->
            <!-- TAMPILAN MOBILE / TABLET (< 1024px): DAFTAR TIKET TERURUT  -->
            <!-- Urut: Menunggu -> Diproses -> Diantar (Terlama paling atas) -->
            <!-- ========================================================== -->
            <div class="block lg:hidden space-y-3">
                @foreach($orders as $order)
                    <div x-show="matches('{{ $order->status }}', {{ $order->has_galon ? 'true' : 'false' }})">
                        @include('petugas.partials.order-card', ['order' => $order])
                    </div>
                @endforeach
            </div>

        @endif
    </div>

</div>
@endsection
