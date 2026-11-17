@extends('layouts.app')

@section('page_title', 'Rencana Belanja')
@section('page_subtitle', 'Saran belanja kios 7 hari ke depan dari prediksi machine learning')
@section('no_page_band', '1')

@section('content')
@php
    $rp = fn ($v) => 'Rp ' . number_format((int) $v, 0, ',', '.');
    $updatedAt = $lastCalculatedAt ? \Carbon\Carbon::parse($lastCalculatedAt) : null;
    $needCount = $shoppingChecklist->count();

    // ---- Grafik mini: 30 hari data yang dipelajari model + garis prediksi 7 hari ke depan ----
    $hist = $mlInfo['history']->all();
    $fc = (float) $mlInfo['forecast_daily'];
    $W = 360; $H = 84; $pad = 6;
    $histW = $W * 30 / 37;                       // 30 hari riwayat, 7 hari prediksi
    $step = $histW / 29;
    $max = max(max($hist ?: [0]), $fc * 1.35, 1);
    $y = fn ($v) => round($H - $pad - ($v / $max) * ($H - 2 * $pad), 1);
    $points = collect($hist)->map(fn ($v, $i) => round($i * $step, 1) . ',' . $y($v))->join(' ');
    $lastX = round(29 * $step, 1);
    $lastY = $y(end($hist) ?: 0);
    $fcY = $y($fc);
    $band = $fc * (($mlInfo['accuracy'] !== null ? (100 - $mlInfo['accuracy']) : 35) / 100);
    $bandTop = $y($fc + $band);
    $bandBot = $y(max(0, $fc - $band));
    $area = "0,{$H} {$points} {$lastX},{$H}";

    $statusStyle = [
        'kritis'  => ['label' => 'Mendesak',       'pill' => 'bg-alert-wash text-alert',  'c' => 'var(--color-alert)'],
        'waspada' => ['label' => 'Perlu ditambah', 'pill' => 'bg-wait-wash text-wait',   'c' => 'var(--color-wait)'],
        'aman'    => ['label' => 'Aman',           'pill' => 'bg-done-wash text-done',    'c' => 'var(--color-done)'],
    ];
    $trend = [
        'naik'   => ['↗ makin laris', 'text-done'],
        'turun'  => ['↘ mulai sepi', 'text-ink-soft'],
        'stabil' => ['→ stabil', 'text-ink-soft'],
    ];
    $restockPayload = fn ($p) => [
        'prediction_id' => $p->id,
        'name' => $p->catalogItem?->name,
        'unit' => $p->catalogItem?->unit,
        'current_stock' => $p->current_stock,
        'restock_qty' => $p->recommended_restock_qty,
    ];
@endphp

<div class="space-y-6" x-data="{
    isRunning: {{ $isCalculating ? 'true' : 'false' }},
    restockModalOpen: false,
    selectedItem: null,
    restockQty: 1,
    restockNotes: '',
    restockActionUrl: '',
    filter: 'semua',
    showAll: false,
    copiedWa: false,
    openRestock(item) {
        this.selectedItem = item;
        this.restockQty = item.restock_qty > 0 ? item.restock_qty : 10;
        this.restockNotes = 'Belanja sesuai prediksi';
        this.restockActionUrl = '{{ url('/pengelola/prediksi-stok/restock') }}/' + item.prediction_id;
        this.restockModalOpen = true;
    },
    copyWaList() {
        navigator.clipboard.writeText(document.getElementById('waShoppingText').value);
        this.copiedWa = true;
        setTimeout(() => this.copiedWa = false, 2500);
    }
}">
    <textarea id="waShoppingText" aria-label="Teks daftar belanja untuk WhatsApp" class="sr-only" readonly>{{ $waShoppingText }}</textarea>

    <!-- ============================================================== -->
    <!-- 1. KARTU UTAMA: HASIL PREDIKSI MACHINE LEARNING                -->
    <!-- ============================================================== -->
    <section aria-labelledby="ml-title" class="ml-hero nav-tiles relative overflow-hidden rounded-3xl text-white p-4 sm:p-7">
        <div class="relative grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,25rem)] gap-4 sm:gap-6 lg:gap-8">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="ml-badge inline-flex items-center gap-1.5 rounded-full bg-ochre px-2.5 py-1 text-[11px] font-bold text-ink">
                        <svg class="ml-spark w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9L12 2zm7 11l.9 2.6 2.6.9-2.6.9L19 20l-.9-2.6-2.6-.9 2.6-.9L19 13z"/></svg>
                        Prediksi Machine Learning
                    </span>
                    <span class="text-xs text-white/70">
                        @if($isCalculating)
                            Sedang dihitung di latar belakang... muat ulang halaman beberapa saat lagi.
                        @elseif($updatedAt)
                            Diperbarui {{ $updatedAt->diffForHumans() }}
                        @else
                            Belum pernah dihitung
                        @endif
                    </span>
                </div>

                <h1 id="ml-title" class="mt-3 font-display text-[1.45rem] sm:text-4xl font-bold leading-tight tracking-tight">
                    @if($needCount > 0)
                        Minggu ini belanja <span class="text-ochre">{{ $needCount }} barang</span>
                    @else
                        Persediaan aman untuk 7 hari ke depan
                    @endif
                </h1>
                @if($needCount > 0)
                    <p class="mt-1 text-sm sm:text-base text-white/80">Perkiraan biaya <b class="text-white font-mono tabular-nums">{{ $rp($stats['estimated_budget']) }}</b>
                        @if($stats['kritis'] > 0) · <span class="text-[#ffb4a3] font-semibold">{{ $stats['kritis'] }} mendesak</span>@endif
                    </p>
                @endif
                @if($lastError && !$isCalculating)
                    <p class="mt-2 text-xs text-[#ffb4a3]">Perhitungan terakhir gagal: {{ $lastError }}</p>
                @endif

                <div class="mt-4 sm:mt-5 grid {{ $needCount > 0 ? 'grid-cols-[1fr_auto]' : 'grid-cols-1' }} gap-2 sm:flex sm:flex-wrap">
                    @if($needCount > 0)
                        <button type="button" @click="copyWaList()"
                                class="h-11 inline-flex items-center justify-center gap-2 px-4 rounded-xl bg-surface text-brand-deep text-sm font-bold hover:bg-brand-wash transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/></svg>
                            <span x-text="copiedWa ? 'Tersalin, tempel di WhatsApp' : 'Salin daftar belanja'">Salin daftar belanja</span>
                        </button>
                    @endif
                    <form action="{{ route('pengelola.prediksi.calculate') }}" method="POST" @submit="isRunning = true">
                        @csrf
                        <button type="submit" :disabled="isRunning" aria-label="Hitung ulang prediksi"
                                class="h-11 w-full inline-flex items-center justify-center gap-2 px-3.5 sm:px-4 rounded-xl bg-white/10 ring-1 ring-inset ring-white/25 text-sm font-semibold hover:bg-white/15 disabled:opacity-60 transition-colors">
                            <svg class="w-4 h-4" :class="isRunning && 'animate-spin'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            <span class="{{ $needCount > 0 ? 'hidden sm:inline' : '' }}" x-text="isRunning ? 'Menghitung...' : 'Hitung ulang'">Hitung ulang</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Grafik mini: data belajar (30 hari) + prediksi (7 hari) -->
            <figure class="rounded-2xl bg-white/[.07] ring-1 ring-inset ring-white/10 p-3 sm:p-4">
                <figcaption class="flex items-center justify-between gap-2 text-[11px] text-white/70">
                    <span>Barang terjual per hari</span>
                    <span class="flex items-center gap-3">
                        <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-white rounded"></span>30 hari lalu</span>
                        <span class="flex items-center gap-1"><span class="w-3 border-t-2 border-dotted border-ochre"></span>prediksi</span>
                    </span>
                </figcaption>
                <svg viewBox="0 0 {{ $W }} {{ $H + 16 }}" class="mt-2 w-full h-auto" role="img"
                     aria-label="Penjualan 30 hari terakhir dan prediksi rata-rata {{ $fc }} barang per hari untuk 7 hari ke depan">
                    <polygon points="{{ $area }}" fill="rgb(255 255 255 / .08)"/>
                    <polygon class="ml-band" points="{{ $lastX }},{{ $lastY }} {{ $W }},{{ $bandTop }} {{ $W }},{{ $bandBot }}" fill="rgb(201 138 43 / .22)"/>
                    <polyline points="{{ $points }}" fill="none" stroke="#fff" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>
                    <line x1="{{ $lastX }}" y1="0" x2="{{ $lastX }}" y2="{{ $H }}" stroke="rgb(255 255 255 / .3)" stroke-dasharray="2 3"/>
                    <polyline class="ml-forecast" points="{{ $lastX }},{{ $lastY }} {{ $W }},{{ $fcY }}" fill="none" stroke="#c98a2b" stroke-width="2.4" stroke-dasharray="3 4" stroke-linecap="round"/>
                    <circle class="ml-dot" cx="{{ $W - 3 }}" cy="{{ $fcY }}" r="3.5" fill="#c98a2b"/>
                    <text x="0" y="{{ $H + 13 }}" font-size="10" fill="rgb(255 255 255 / .6)">30 hari lalu</text>
                    <text x="{{ $lastX }}" y="{{ $H + 13 }}" font-size="10" fill="rgb(255 255 255 / .85)" text-anchor="middle">hari ini</text>
                    <text x="{{ $W }}" y="{{ $H + 13 }}" font-size="10" fill="#e0b46a" text-anchor="end">+7 hari</text>
                </svg>
                <dl class="mt-2 sm:mt-3 grid grid-cols-3 gap-1.5 sm:gap-2 text-center">
                    <div class="rounded-xl bg-white/[.06] px-1 py-2">
                        <dt class="text-[10.5px] text-white/65">Akurasi uji</dt>
                        <dd class="mt-0.5 font-mono text-sm sm:text-base font-bold tabular-nums">{{ $mlInfo['accuracy'] !== null ? $mlInfo['accuracy'] . '%' : '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-white/[.06] px-1 py-2">
                        <dt class="text-[10.5px] text-white/65">Prediksi/hari</dt>
                        <dd class="mt-0.5 font-mono text-sm sm:text-base font-bold tabular-nums">{{ rtrim(rtrim(number_format($fc, 1, ',', '.'), '0'), ',') }}</dd>
                    </div>
                    <div class="rounded-xl bg-white/[.06] px-1 py-2">
                        <dt class="text-[10.5px] text-white/65">Dipantau</dt>
                        <dd class="mt-0.5 font-mono text-sm sm:text-base font-bold tabular-nums">{{ $stats['total'] }}</dd>
                    </div>
                </dl>
                @if($mlInfo['accuracy'] === null)
                    <p class="mt-2 text-[11px] text-white/60">Akurasi muncul setelah ada 3 minggu riwayat pesanan.</p>
                @endif
            </figure>
        </div>

        <!-- Cara AI menghitung: 3 langkah singkat + rumus untuk yang ingin tahu -->
        <div class="relative mt-4 pt-3 sm:mt-6 sm:pt-5 border-t border-white/10">
            <ol class="hidden sm:grid grid-cols-3 gap-3 text-xs">
                @foreach([
                    ['Belajar', 'Membaca 30 hari pesanan penghuni setiap barang.'],
                    ['Memprediksi', 'Regresi linier + rata-rata 7 hari menebak kebutuhan minggu depan.'],
                    ['Menyarankan', 'Beli = prediksi + cadangan − stok yang ada.'],
                ] as $i => [$t, $d])
                    <li class="flex flex-col sm:flex-row items-center sm:items-start gap-1.5 sm:gap-2.5 text-center sm:text-left">
                        <span class="sm:mt-0.5 w-6 h-6 sm:w-5 sm:h-5 shrink-0 rounded-full bg-white/15 font-mono text-[11px] font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                        <span><b class="text-white">{{ $t }}<span class="hidden sm:inline">.</span></b> <span class="hidden sm:inline text-white/75">{{ $d }}</span></span>
                    </li>
                @endforeach
            </ol>
            <details class="group sm:mt-3 text-xs">
                <summary class="cursor-pointer list-none flex sm:inline-flex items-center gap-1.5 text-white/75 sm:text-white/65 hover:text-white min-h-[36px] sm:min-h-0">
                    <svg class="w-3 h-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="sm:hidden">Bagaimana AI menghitung? <span class="text-white/55">Belajar · Memprediksi · Menyarankan</span></span>
                    <span class="hidden sm:inline">Lihat rumus model</span>
                </summary>
                <ol class="sm:hidden mt-2 space-y-1.5">
                    <li><b>1. Belajar</b> <span class="text-white/75">dari 30 hari pesanan penghuni.</span></li>
                    <li><b>2. Memprediksi</b> <span class="text-white/75">kebutuhan minggu depan (regresi linier + rata-rata 7 hari).</span></li>
                    <li><b>3. Menyarankan</b> <span class="text-white/75">beli = prediksi + cadangan − stok.</span></li>
                </ol>
                <div class="mt-2 rounded-xl bg-black/25 p-3 font-mono text-[11px] leading-relaxed text-white/85 space-y-0.5">
                    <p>Prediksi_7H = 0,5 × Regresi_Linier_7H + 0,5 × Rata2_7_hari × 7</p>
                    <p>Target = Prediksi_7H + Cadangan · Saran_beli = max(0, Target − Stok)</p>
                    <p>Akurasi = 1 − |prediksi − aktual| ÷ aktual (uji mundur 7 hari terakhir)</p>
                    <p class="text-white/55">Dijalankan Python (scikit-learn) di latar belakang; hasil disimpan ke basis data.</p>
                </div>
            </details>
        </div>
    </section>

    <!-- ============================================================== -->
    <!-- 2. BELANJA MINGGU INI                                          -->
    <!-- ============================================================== -->
    <section aria-labelledby="buy-title">
        <div class="flex items-end justify-between gap-3 mb-3 px-0.5">
            <div>
                <h2 id="buy-title" class="text-base sm:text-lg font-bold text-ink">Belanja minggu ini</h2>
                <p class="text-xs text-ink-soft">
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-2 rounded-sm bg-ink-faint/60"></span>stok sekarang</span>
                    <span class="mx-1.5 text-ink-faint">·</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-2 rounded-sm forecast-hatch" style="--c: var(--color-brand)"></span>prediksi 7 hari</span>
                    <span class="mx-1.5 text-ink-faint">·</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-2 rounded-sm bg-brand/25"></span>cadangan</span>
                </p>
            </div>
        </div>

        @if($needCount > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
                @foreach($shoppingChecklist as $p)
                    @php
                        $st = $statusStyle[$p->status] ?? $statusStyle['waspada'];
                        $target = $p->target_stock ?: ($p->predicted_demand_qty + $p->safety_stock);
                        $scale = max($target, $p->current_stock, 1);
                        $belowBuffer = $p->current_stock <= $p->safety_stock;
                        $unit = $p->catalogItem?->unit;
                        $tr = $trend[$p->sales_trend] ?? $trend['stabil'];
                    @endphp
                    <article class="bg-surface rounded-3xl border border-line shadow-xs p-4 sm:p-5 flex flex-col">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-ink leading-snug">{{ $p->catalogItem?->name }}</h3>
                                <p class="text-2xs mt-0.5">
                                    @if($p->days_of_supply <= 2.5)
                                        <span class="text-alert font-semibold">Habis ~{{ $p->days_of_supply }} hari</span>
                                    @elseif($belowBuffer)
                                        <span class="text-alert font-semibold">Di bawah cadangan</span>
                                    @else
                                        <span class="text-ink-soft">Cukup ~{{ $p->days_of_supply }} hari</span>
                                    @endif
                                    <span class="text-ink-faint">·</span>
                                    <span class="{{ $tr[1] }}">{{ $tr[0] }}</span>
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $st['pill'] }}">{{ $st['label'] }}</span>
                        </div>

                        <!-- Stok sekarang vs prediksi kebutuhan -->
                        <div class="mt-3 sm:mt-4 space-y-1.5 text-2xs text-ink-soft">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2.5 rounded-full bg-canvas overflow-hidden">
                                    <div class="h-full rounded-full bg-ink-faint/60" style="width: {{ round($p->current_stock / $scale * 100) }}%"></div>
                                </div>
                                <span class="w-14 text-right font-mono tabular-nums text-ink">{{ $p->current_stock }}</span>
                            </div>
                            <div class="flex items-center gap-2" title="Target {{ $target }} = prediksi {{ $p->predicted_demand_qty }} + cadangan {{ $target - $p->predicted_demand_qty }}">
                                <div class="flex-1 h-2.5 rounded-full bg-canvas overflow-hidden">
                                    <div class="h-full flex ml-grow" style="width: {{ round($target / $scale * 100) }}%">
                                        <div class="h-full forecast-hatch" style="--c: {{ $st['c'] }}; width: {{ round($p->predicted_demand_qty / max($target, 1) * 100) }}%"></div>
                                        <div class="h-full flex-1 bg-brand/25"></div>
                                    </div>
                                </div>
                                <span class="w-14 text-right font-mono tabular-nums text-ink">{{ $target }}</span>
                            </div>
                            <p class="text-[11px] text-ink-faint">Butuh {{ $target }} = prediksi {{ $p->predicted_demand_qty }} + cadangan {{ $target - $p->predicted_demand_qty }}</p>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-3 border-t border-dashed border-line flex items-center justify-between gap-3">
                            <div>
                                <p class="font-display text-xl font-bold text-ink leading-none">+{{ $p->recommended_restock_qty }} <span class="text-sm font-semibold text-ink-soft">{{ $unit }}</span></p>
                                <p class="text-2xs text-ink-soft mt-1 font-mono">≈ {{ $p->formatted_estimated_cost }}</p>
                            </div>
                            <button type="button" @click="openRestock(@js($restockPayload($p)))"
                                    class="h-10 px-3.5 rounded-xl bg-brand text-surface text-xs font-bold inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Sudah dibeli
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="bg-done-wash border border-done/20 rounded-3xl p-6 text-center">
                <p class="text-sm font-bold text-done">Tidak ada yang perlu dibeli</p>
                <p class="text-xs text-ink-soft mt-1">Stok semua barang cukup untuk perkiraan 7 hari ke depan.</p>
            </div>
        @endif
    </section>

    <!-- ============================================================== -->
    <!-- 3. SEMUA BARANG YANG DIPANTAU                                  -->
    <!-- ============================================================== -->
    @php
        $countGalon = $predictions->filter(fn ($p) => $p->catalogItem?->category === 'galon')->count();
    @endphp
    <section aria-labelledby="all-title" class="bg-surface rounded-3xl border border-line shadow-xs">
        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 id="all-title" class="text-base font-bold text-ink">Semua barang dipantau</h2>
                <p class="text-xs text-ink-soft">Berapa lama stok bertahan menurut prediksi.</p>
            </div>
            <div class="flex gap-1.5 text-xs" role="tablist">
                @foreach(['semua' => ['Semua', $stats['total']], 'beli' => ['Perlu beli', $needCount], 'galon' => ['Galon', $countGalon]] as $key => [$label, $n])
                    <button type="button" role="tab" @click="filter = '{{ $key }}'" :aria-selected="filter === '{{ $key }}'"
                            :class="filter === '{{ $key }}' ? 'bg-brand text-surface border-brand' : 'bg-surface text-ink-soft border-line hover:bg-canvas'"
                            class="h-9 px-3 rounded-full border font-semibold inline-flex items-center gap-1.5">
                        {{ $label }} <span class="font-mono text-[11px] opacity-80">{{ $n }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <ul class="border-t border-line divide-y divide-line" role="list">
            @forelse($predictions as $p)
                @php
                    $st = $statusStyle[$p->status] ?? $statusStyle['aman'];
                    $days = $p->days_of_supply;
                    $daysPct = min(100, round(min($days, 14) / 14 * 100));
                    $cat = $p->catalogItem?->category;
                @endphp
                <li x-show="filter === 'semua' || (filter === 'beli' && {{ $p->recommended_restock_qty > 0 ? 'true' : 'false' }}) || (filter === 'galon' && {{ $cat === 'galon' ? 'true' : 'false' }})"
                    @if($loop->index >= 6) :class="!showAll && filter === 'semua' && 'max-sm:hidden'" @endif>
                    <button type="button" @click="openRestock(@js($restockPayload($p)))"
                            class="w-full text-left px-4 sm:px-5 py-3 flex items-center gap-3 hover:bg-canvas/60 active:bg-canvas transition-colors"
                            aria-label="Catat barang masuk untuk {{ $p->catalogItem?->name }}">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $st['c'] }}" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-ink truncate">{{ $p->catalogItem?->name }}</span>
                                <span class="shrink-0 text-2xs {{ $days <= 2.5 ? 'text-alert font-semibold' : 'text-ink-soft' }}">{{ $days >= 14 ? '2 minggu+' : '~' . $days . ' hari' }}</span>
                            </span>
                            <span class="mt-1.5 flex items-center gap-2">
                                <span class="flex-1 h-1.5 rounded-full bg-canvas overflow-hidden" title="Bertahan sekitar {{ $days }} hari (skala 14 hari)">
                                    <span class="block h-full rounded-full" style="width: {{ $daysPct }}%; background: {{ $st['c'] }}"></span>
                                </span>
                                <span class="shrink-0 text-2xs font-mono tabular-nums text-ink-soft">stok {{ $p->current_stock }} · butuh {{ $p->predicted_demand_qty }}</span>
                            </span>
                        </span>
                        <span class="shrink-0 w-8 h-8 rounded-lg border border-line text-ink-soft flex items-center justify-center" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </span>
                    </button>
                </li>
            @empty
                <li class="p-8 text-center text-xs text-ink-faint">Belum ada hasil prediksi. Tekan <b>Hitung ulang</b> di atas.</li>
            @endforelse
        </ul>
        @if($predictions->count() > 6)
            <button type="button" x-show="!showAll && filter === 'semua'" @click="showAll = true"
                    class="sm:hidden w-full h-12 border-t border-line text-xs font-semibold text-brand">
                Tampilkan semua {{ $predictions->count() }} barang
            </button>
        @endif
    </section>

    <!-- Bilah salin yang menempel di HP setelah kartu ML digulir lewat -->
    @if($needCount > 0)
        <div class="lg:hidden h-14" aria-hidden="true"></div>
        <div x-data="{ stuck: false }"
             x-init="'IntersectionObserver' in window && new IntersectionObserver(([e]) => stuck = !e.isIntersecting && e.boundingClientRect.top < 0).observe(document.querySelector('.ml-hero'))"
             x-show="stuck" x-cloak
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-3"
             class="lg:hidden fixed inset-x-3 bottom-[calc(5.25rem+env(safe-area-inset-bottom))] z-20">
            <button type="button" @click="copyWaList()"
                    class="w-full h-12 rounded-2xl ml-hero text-white shadow-lg flex items-center justify-between gap-3 pl-3 pr-4 text-sm">
                <span class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-ochre text-ink flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9L12 2z"/></svg>
                    </span>
                    <span class="truncate"><b>{{ $needCount }} barang</b> · <span class="font-mono">{{ $rp($stats['estimated_budget']) }}</span></span>
                </span>
                <span class="shrink-0 font-bold" x-text="copiedWa ? 'Tersalin ✓' : 'Salin daftar'">Salin daftar</span>
            </button>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- LEMBAR CATAT BARANG MASUK                                      -->
    <!-- ============================================================== -->
    <x-bottom-sheet show="restockModalOpen" title="Catat barang masuk">
        <template x-if="selectedItem">
            <form :action="restockActionUrl" method="POST" class="space-y-4">
                @csrf
                <div class="flex items-center justify-between rounded-2xl bg-canvas px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-ink truncate" x-text="selectedItem.name"></p>
                        <p class="text-2xs text-ink-soft">Stok sekarang <span class="font-mono" x-text="selectedItem.current_stock + ' ' + selectedItem.unit"></span></p>
                    </div>
                    <p class="text-2xs text-ink-soft text-right" x-show="selectedItem.restock_qty > 0">Saran AI<br><b class="font-mono text-brand text-sm" x-text="'+' + selectedItem.restock_qty"></b></p>
                </div>
                <div>
                    <label for="restock-qty" class="block text-xs font-semibold text-ink mb-1.5">Berapa <span x-text="selectedItem.unit"></span> yang dibeli?</label>
                    <input id="restock-qty" type="number" name="quantity" x-model="restockQty" min="1" max="1000" required inputmode="numeric"
                           class="w-full h-12 text-center text-xl font-bold font-mono rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand bg-surface text-ink tabular-nums">
                    <p class="text-2xs text-ink-soft mt-1.5">Stok menjadi <b class="font-mono text-ink" x-text="(parseInt(selectedItem.current_stock) + parseInt(restockQty || 0)) + ' ' + selectedItem.unit"></b></p>
                </div>
                <div>
                    <label for="restock-notes" class="block text-xs font-semibold text-ink mb-1.5">Catatan (opsional)</label>
                    <input id="restock-notes" type="text" name="notes" x-model="restockNotes" maxlength="150"
                           class="w-full h-11 text-sm px-3 rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand bg-surface text-ink">
                </div>
                <div class="grid grid-cols-[auto_1fr] gap-2 pt-1">
                    <button type="button" @click="restockModalOpen = false" class="h-12 px-4 rounded-xl border border-line text-sm font-semibold text-ink-soft hover:bg-canvas">Batal</button>
                    <button type="submit" class="h-12 rounded-xl bg-brand text-surface text-sm font-bold">Tambah ke stok</button>
                </div>
            </form>
        </template>
    </x-bottom-sheet>
</div>
@endsection
