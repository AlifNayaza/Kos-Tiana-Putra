@extends('layouts.mobile')

@section('content')
@php
    \Carbon\Carbon::setLocale('id');

    $allItems = $galonItems->concat($otherItems);

    // Item habis paling atas, lalu item menipis; sisanya (aman) urut abjad.
    $needRestockItems = $allItems->filter(fn($i) => $i->stock <= $i->minimum_stock)
        ->sortBy(fn($i) => [$i->stock > 0 ? 1 : 0, $i->name]);
    $safeItems = $allItems->filter(fn($i) => $i->stock > $i->minimum_stock)->sortBy('name');

    $habisCount = $allItems->where('stock', '<=', 0)->count();
    $menipisCount = $allItems->filter(fn($i) => $i->stock > 0 && $i->stock <= $i->minimum_stock)->count();
    $totalNeedRestock = $needRestockItems->count();

    // Catatan terakhir per item dari $recentLogs yang sudah dimuat (tanpa query baru).
    $latestLogsByItem = $recentLogs->groupBy('catalog_item_id')->map->first();
@endphp

<div x-data="{
    searchQuery: '',
    catFilter: 'semua',
    guideOpen: true,
    modalOpen: false,
    selectedItem: null,
    actionType: 'in', // 'in' = Tambah | 'out' = Kurangi | 'recount' = Hitung ulang
    quantity: 1,
    physicalCount: 0,
    notes: '',
    items: @js($allItems->map(fn($i) => [
        'id' => $i->id,
        'name' => $i->name,
        'category' => $i->category,
        'stock' => $i->stock,
        'minimum_stock' => $i->minimum_stock,
    ])->values()),

    init() {
        try { this.guideOpen = localStorage.getItem('petugas-stok-guide') !== 'hidden'; } catch (e) {}
    },
    toggleGuide(open) {
        this.guideOpen = open;
        try { localStorage.setItem('petugas-stok-guide', open ? 'shown' : 'hidden'); } catch (e) {}
    },

    openModal(item, type) {
        this.selectedItem = item;
        this.setAction(type);
        this.modalOpen = true;
    },
    setAction(type) {
        const item = this.selectedItem;
        this.actionType = type;
        if (type === 'in') {
            this.quantity = item.category === 'galon' ? 10 : 5;
            this.notes = 'Kiriman agen';
        } else if (type === 'out') {
            this.quantity = 1;
            this.notes = item.category === 'galon' ? 'Bocor / pecah' : 'Kedaluwarsa';
        } else {
            this.physicalCount = item.stock;
            this.notes = 'Hitung ulang rutin';
        }
    },
    step(field, by) {
        const min = field === 'physicalCount' ? 0 : 1;
        this[field] = Math.max(min, (parseInt(this[field]) || 0) + by);
    },

    get diff() {
        if (!this.selectedItem) return 0;
        if (this.actionType === 'in') return parseInt(this.quantity) || 0;
        if (this.actionType === 'out') return -(parseInt(this.quantity) || 0);
        return (parseInt(this.physicalCount) || 0) - this.selectedItem.stock;
    },
    get afterStock() {
        return this.selectedItem ? this.selectedItem.stock + this.diff : 0;
    },
    get diffText() {
        return this.diff > 0 ? '+' + this.diff : (this.diff < 0 ? '−' + Math.abs(this.diff) : '0');
    },
    get unit() {
        return this.selectedItem ? this.selectedItem.unit : '';
    },
    get submitType() {
        if (this.actionType === 'recount') return this.diff >= 0 ? 'in' : 'out';
        return this.actionType;
    },
    get submitQuantity() {
        if (this.actionType === 'recount') return Math.max(1, Math.abs(this.diff));
        return Math.max(1, parseInt(this.quantity) || 1);
    },
    get submitNotes() {
        const fallback = { in: 'Barang datang', out: 'Rusak / hilang', recount: 'Hitung ulang rutin' };
        return this.notes.trim() || fallback[this.actionType];
    },
    get submitLabel() {
        if (this.actionType === 'in') return 'Tambah ' + (parseInt(this.quantity) || 0) + ' ' + this.unit;
        if (this.actionType === 'out') return 'Kurangi ' + (parseInt(this.quantity) || 0) + ' ' + this.unit;
        return this.diff === 0 ? 'Jumlah sudah cocok' : 'Simpan, stok jadi ' + this.afterStock + ' ' + this.unit;
    },
    get canSubmit() {
        if (!this.selectedItem || this.afterStock < 0) return false;
        if (this.actionType === 'recount') return this.diff !== 0 && this.physicalCount !== '';
        return (parseInt(this.quantity) || 0) > 0;
    },

    matches(category, stock, minStock, name) {
        const q = this.searchQuery.trim().toLowerCase();
        if (q !== '' && !name.toLowerCase().includes(q)) return false;
        if (this.catFilter === 'semua') return true;
        if (this.catFilter === 'menipis') return stock <= minStock;
        return category === this.catFilter;
    },
    get hasVisibleItems() {
        return this.items.some(i => this.matches(i.category, i.stock, i.minimum_stock, i.name));
    },
    get hasVisibleNeedRestock() {
        return this.items.some(i => i.stock <= i.minimum_stock && this.matches(i.category, i.stock, i.minimum_stock, i.name));
    },
    get hasVisibleSafeItems() {
        return this.items.some(i => i.stock > i.minimum_stock && this.matches(i.category, i.stock, i.minimum_stock, i.name));
    }
}" class="space-y-4">

    <!-- 1. Judul & status singkat -->
    <div class="space-y-1">
        <div class="flex items-center justify-between gap-2">
            <h1 class="text-3xl text-brand-deep leading-tight">Stok Barang</h1>
            <button type="button" x-show="!guideOpen" x-cloak @click="toggleGuide(true)"
                    class="min-h-[44px] px-3 text-xs font-semibold text-brand hover:underline">
                Cara pakai?
            </button>
        </div>
        <p class="text-xs text-ink-soft leading-relaxed">
            @if($totalNeedRestock > 0)
                <span class="font-semibold {{ $habisCount > 0 ? 'text-alert' : 'text-wait' }}">
                    {{ collect([$habisCount ? "$habisCount habis" : null, $menipisCount ? "$menipisCount menipis" : null])->filter()->implode(', ') }}.
                </span>
                Barang ini perlu dibelikan lagi.
            @else
                <span class="font-semibold text-done">Semua stok aman.</span>
                Tidak ada barang yang perlu dibeli sekarang.
            @endif
        </p>
    </div>

    <!-- 2. Panduan singkat (bisa disembunyikan, diingat per perangkat) -->
    <section x-show="guideOpen" x-cloak class="bg-brand-wash/60 border border-brand/20 rounded-xl p-4 space-y-3">
        <div class="flex items-start justify-between gap-2">
            <h2 class="text-sm font-bold text-brand-deep">Kapan pakai tombol apa?</h2>
            <button type="button" @click="toggleGuide(false)"
                    class="-mt-2 -mr-2 min-h-[44px] px-2 text-2xs font-semibold text-ink-soft hover:text-ink">
                Sembunyikan
            </button>
        </div>
        <ul class="space-y-2.5 text-xs text-ink">
            <li class="flex gap-2.5">
                <span class="w-6 h-6 shrink-0 rounded-md bg-brand text-surface flex items-center justify-center font-bold">+</span>
                <span><b>Tambah</b>: ada barang datang (kiriman agen atau belanja). Isi berapa banyak yang datang.</span>
            </li>
            <li class="flex gap-2.5">
                <span class="w-6 h-6 shrink-0 rounded-md bg-surface border border-line text-alert flex items-center justify-center font-bold">−</span>
                <span><b>Kurangi</b>: ada barang rusak, bocor, kedaluwarsa, atau hilang. Isi jumlahnya dan alasannya.</span>
            </li>
            <li class="flex gap-2.5">
                <span class="w-6 h-6 shrink-0 rounded-md bg-surface border border-line text-ink-soft flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
                <span><b>Hitung ulang</b>: angka di aplikasi beda dengan barang di rak. Hitung barangnya, lalu isi jumlah hasil hitunganmu. Selisihnya dibetulkan otomatis.</span>
            </li>
        </ul>
        <p class="text-2xs text-ink-soft leading-relaxed border-t border-brand/15 pt-2.5">
            Barang yang dipesan penghuni sudah berkurang otomatis. Tidak perlu dicatat lagi.
            Label <b class="text-wait">Menipis</b> muncul saat stok sudah sampai batas minimum.
        </p>
    </section>

    <!-- 3. Filter & pencarian -->
    <div class="space-y-2.5">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
            @foreach(['semua' => 'Semua', 'galon' => 'Galon', 'makanan' => 'Makanan', 'minuman' => 'Minuman'] as $key => $label)
                <button type="button" @click="catFilter = '{{ $key }}'"
                        :class="catFilter === '{{ $key }}' ? 'bg-brand text-surface font-bold border-brand' : 'bg-surface text-ink-soft hover:bg-canvas font-medium border-line'"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-full border transition-colors whitespace-nowrap">
                    {{ $label }}
                </button>
            @endforeach
            @if($totalNeedRestock > 0)
                <button type="button" @click="catFilter = 'menipis'"
                        :class="catFilter === 'menipis' ? 'bg-wait text-surface font-semibold border-wait' : 'bg-wait-wash border-wait/30 text-wait font-medium'"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-full border transition-colors whitespace-nowrap">
                    Perlu dibeli ({{ $totalNeedRestock }})
                </button>
            @endif
        </div>

        <div class="relative">
            <input type="search" x-model="searchQuery" placeholder="Cari barang, misal: Aqua"
                   class="w-full text-sm rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand pl-9 pr-3 min-h-[44px] bg-surface text-ink placeholder-ink-faint">
            <svg class="w-4 h-4 text-ink-faint absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- 4. Perlu dibeli -->
    @if($needRestockItems->isNotEmpty())
        <section class="space-y-2" x-show="hasVisibleNeedRestock">
            <h2 class="text-xs font-bold text-alert uppercase tracking-wider flex items-center gap-1.5 px-0.5">
                <span class="w-2 h-2 rounded-full bg-alert"></span>
                Perlu dibeli ({{ $totalNeedRestock }})
            </h2>
            <div class="bg-surface rounded-xl border border-line divide-y divide-line overflow-hidden lg:bg-transparent lg:border-0 lg:divide-y-0 lg:overflow-visible lg:grid lg:grid-cols-2 lg:gap-3">
                @foreach($needRestockItems as $item)
                    @include('petugas.partials.stock-row', ['item' => $item, 'lastLog' => $latestLogsByItem->get($item->id)])
                @endforeach
            </div>
        </section>
    @endif

    <!-- 5. Stok aman -->
    @if($safeItems->isNotEmpty())
        <section class="space-y-2" x-show="hasVisibleSafeItems">
            <h2 class="text-xs font-bold text-ink-soft uppercase tracking-wider flex items-center gap-1.5 px-0.5">
                <span class="w-2 h-2 rounded-full bg-done"></span>
                Stok aman ({{ $safeItems->count() }})
            </h2>
            <div class="bg-surface rounded-xl border border-line divide-y divide-line overflow-hidden lg:bg-transparent lg:border-0 lg:divide-y-0 lg:overflow-visible lg:grid lg:grid-cols-2 lg:gap-3">
                @foreach($safeItems as $item)
                    @include('petugas.partials.stock-row', ['item' => $item, 'lastLog' => $latestLogsByItem->get($item->id)])
                @endforeach
            </div>
        </section>
    @endif

    <div x-show="!hasVisibleItems" x-cloak class="py-8 text-center space-y-3 bg-surface rounded-xl border border-line p-6">
        <p class="text-xs text-ink-soft font-medium">Tidak ada barang yang cocok.</p>
        <button type="button" @click="searchQuery = ''; catFilter = 'semua'"
                class="min-h-[44px] px-4 py-2 text-xs font-semibold bg-canvas hover:bg-line border border-line rounded-xl text-ink transition-colors">
            Tampilkan semua barang
        </button>
    </div>

    <!-- 6. Riwayat perubahan stok -->
    <section class="pt-4 space-y-2" x-data="{ showAll: false }">
        <div class="px-0.5">
            <h2 class="text-sm font-bold text-ink">Riwayat perubahan stok</h2>
            <p class="text-2xs text-ink-faint">{{ $recentLogs->count() }} catatan terakhir, termasuk barang yang keluar karena pesanan.</p>
        </div>

        <div class="bg-surface rounded-xl border border-line divide-y divide-line">
            @forelse($recentLogs as $log)
                <div class="p-3 flex items-start gap-3 text-xs" @if($loop->index >= 6) x-show="showAll" x-cloak @endif>
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-semibold text-ink">{{ $log->catalogItem->name ?? 'Item dihapus' }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-canvas border border-line text-2xs text-ink-soft">{{ $log->reference_label }}</span>
                        </div>
                        <p class="text-2xs text-ink-soft tabular-nums">Stok {{ $log->before_stock }} jadi {{ $log->after_stock }} {{ $log->catalogItem->unit ?? '' }}</p>
                        @if($log->display_notes)
                            <p class="text-2xs text-ink-faint line-clamp-2">{{ $log->display_notes }}</p>
                        @endif
                        <p class="text-2xs text-ink-faint">{{ $log->user->name ?? 'Sistem' }} &middot; {{ $log->created_at?->diffForHumans() }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-bold tabular-nums {{ $log->type === 'in' ? 'text-done' : 'text-alert' }}">
                        {{ $log->type === 'in' ? '+' : '−' }}{{ $log->quantity }}
                    </span>
                </div>
            @empty
                <div class="py-6 text-center text-xs text-ink-faint">Belum ada perubahan stok yang tercatat.</div>
            @endforelse
        </div>

        @if($recentLogs->count() > 6)
            <button type="button" @click="showAll = !showAll"
                    class="w-full min-h-[44px] text-xs font-semibold text-brand hover:underline"
                    x-text="showAll ? 'Tampilkan lebih sedikit' : 'Lihat {{ $recentLogs->count() - 6 }} catatan lainnya'"></button>
        @endif
    </section>

    <!-- 7. Lembar catat perubahan stok -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
         @keydown.escape.window="modalOpen = false">
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-xs" @click="modalOpen = false"></div>

        <div class="min-h-full flex items-end sm:items-center justify-center sm:p-4">
            <div x-show="modalOpen" x-transition
                 class="relative bg-surface rounded-t-2xl sm:rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-xl border border-line space-y-4">

                <!-- Barang yang dipilih -->
                <div class="flex items-center gap-3">
                    <img :src="selectedItem ? selectedItem.image_url : ''" alt="" class="w-11 h-11 rounded-lg object-cover bg-canvas border border-line shrink-0">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-bold text-ink truncate" x-text="selectedItem ? selectedItem.name : ''"></h3>
                        <p class="text-xs text-ink-soft">Stok di aplikasi: <b class="text-ink tabular-nums" x-text="selectedItem ? selectedItem.stock : 0"></b> <span x-text="unit"></span></p>
                    </div>
                    <button type="button" @click="modalOpen = false" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-ink-faint hover:text-ink rounded-lg" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Pilih kejadian -->
                <div>
                    <p class="text-xs font-semibold text-ink-soft mb-1.5">Apa yang terjadi?</p>
                    <div class="grid grid-cols-3 gap-1 p-1 bg-canvas rounded-xl border border-line text-xs font-semibold">
                        <button type="button" @click="setAction('in')" class="min-h-[40px] rounded-lg transition-colors"
                                :class="actionType === 'in' ? 'bg-surface text-brand shadow-xs' : 'text-ink-soft'">Tambah</button>
                        <button type="button" @click="setAction('out')" class="min-h-[40px] rounded-lg transition-colors"
                                :class="actionType === 'out' ? 'bg-surface text-alert shadow-xs' : 'text-ink-soft'">Kurangi</button>
                        <button type="button" @click="setAction('recount')" class="min-h-[40px] rounded-lg transition-colors"
                                :class="actionType === 'recount' ? 'bg-surface text-ink shadow-xs' : 'text-ink-soft'">Hitung ulang</button>
                    </div>
                    <p class="text-2xs text-ink-soft mt-2 leading-relaxed" x-show="actionType === 'in'">Untuk barang yang baru datang. Stok akan <b>bertambah</b>.</p>
                    <p class="text-2xs text-ink-soft mt-2 leading-relaxed" x-show="actionType === 'out'">Untuk barang rusak, bocor, kedaluwarsa, atau hilang. Stok akan <b>berkurang</b>. Pesanan penghuni tidak perlu dicatat di sini.</p>
                    <p class="text-2xs text-ink-soft mt-2 leading-relaxed" x-show="actionType === 'recount'">Hitung barang yang ada di rak sekarang, lalu isi angkanya. Stok aplikasi akan disamakan dengan angka itu.</p>
                </div>

                <form action="{{ route('petugas.stocks.adjust') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="catalog_item_id" :value="selectedItem ? selectedItem.id : ''">
                    <input type="hidden" name="type" :value="submitType">
                    <input type="hidden" name="quantity" :value="submitQuantity">
                    <input type="hidden" name="notes" :value="submitNotes">
                    <input type="hidden" name="physical_count" :value="actionType === 'recount' ? physicalCount : ''">

                    <!-- Jumlah -->
                    <div>
                        <label for="stock-amount" class="block text-xs font-semibold text-ink mb-1.5"
                               x-text="actionType === 'in' ? 'Berapa ' + unit + ' yang datang?' : (actionType === 'out' ? 'Berapa ' + unit + ' yang rusak / hilang?' : 'Berapa ' + unit + ' yang ada di rak sekarang?')"></label>

                        <div class="flex items-stretch gap-2">
                            <button type="button" @click="step(actionType === 'recount' ? 'physicalCount' : 'quantity', -1)"
                                    class="min-h-[48px] w-12 shrink-0 rounded-xl border border-line bg-canvas text-lg font-bold text-ink-soft hover:bg-line" aria-label="Kurangi satu">−</button>
                            <template x-if="actionType !== 'recount'">
                                <input id="stock-amount" type="number" inputmode="numeric" x-model="quantity" min="1" max="500"
                                       class="flex-1 min-w-0 text-center text-xl font-bold font-mono tabular-nums rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand min-h-[48px] bg-surface text-ink">
                            </template>
                            <template x-if="actionType === 'recount'">
                                <input id="stock-amount" type="number" inputmode="numeric" x-model="physicalCount" min="0" max="100000"
                                       class="flex-1 min-w-0 text-center text-xl font-bold font-mono tabular-nums rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand min-h-[48px] bg-surface text-ink">
                            </template>
                            <button type="button" @click="step(actionType === 'recount' ? 'physicalCount' : 'quantity', 1)"
                                    class="min-h-[48px] w-12 shrink-0 rounded-xl border border-line bg-canvas text-lg font-bold text-ink-soft hover:bg-line" aria-label="Tambah satu">+</button>
                        </div>

                        <div class="flex gap-1.5 mt-2" x-show="actionType === 'in'">
                            <template x-for="n in [5, 10, 20, 50]" :key="n">
                                <button type="button" @click="quantity = n"
                                        :class="parseInt(quantity) === n ? 'border-brand text-brand bg-brand-wash' : 'border-line text-ink-soft bg-surface'"
                                        class="flex-1 min-h-[40px] text-xs font-semibold rounded-lg border" x-text="n"></button>
                            </template>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label for="stock-notes" class="block text-xs font-semibold text-ink mb-1.5"
                               x-text="actionType === 'in' ? 'Dari mana?' : (actionType === 'out' ? 'Kenapa berkurang?' : 'Catatan (boleh dikosongkan)')"></label>
                        <div class="flex gap-1.5 flex-wrap mb-2" x-show="actionType !== 'recount'">
                            <template x-for="opt in (actionType === 'in' ? ['Kiriman agen', 'Belanja sendiri'] : ['Bocor / pecah', 'Kedaluwarsa', 'Hilang'])" :key="opt">
                                <button type="button" @click="notes = opt"
                                        :class="notes === opt ? 'border-brand text-brand bg-brand-wash' : 'border-line text-ink-soft bg-surface'"
                                        class="min-h-[40px] px-3 text-xs font-medium rounded-lg border" x-text="opt"></button>
                            </template>
                        </div>
                        <input id="stock-notes" type="text" x-model="notes" maxlength="150"
                               placeholder="Atau tulis keterangan sendiri"
                               class="w-full text-sm rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand min-h-[44px] px-3 bg-surface text-ink placeholder-ink-faint">
                    </div>

                    <!-- Hasil sebelum disimpan -->
                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 bg-canvas rounded-xl p-3 border border-line text-center">
                        <div>
                            <div class="text-2xs text-ink-soft">Sekarang</div>
                            <div class="text-lg font-bold font-mono tabular-nums text-ink-soft" x-text="selectedItem ? selectedItem.stock : 0"></div>
                        </div>
                        <div class="text-xs font-bold tabular-nums px-2 py-1 rounded-full"
                             :class="diff > 0 ? 'bg-done-wash text-done' : (diff < 0 ? 'bg-alert-wash text-alert' : 'bg-surface text-ink-faint')"
                             x-text="diffText"></div>
                        <div>
                            <div class="text-2xs text-ink-soft">Setelah disimpan</div>
                            <div class="text-lg font-bold font-mono tabular-nums" :class="afterStock < 0 ? 'text-alert' : 'text-ink'">
                                <span x-text="afterStock"></span> <span class="text-xs font-normal font-sans text-ink-soft" x-text="unit"></span>
                            </div>
                        </div>
                    </div>
                    <p x-show="afterStock < 0" class="-mt-2 text-2xs text-alert font-semibold">Jumlahnya lebih banyak dari stok yang ada. Kalau angka di aplikasi salah, pakai Hitung ulang.</p>
                    <p x-show="actionType === 'recount' && diff === 0" class="-mt-2 text-2xs text-done font-medium">Hasil hitunganmu sama dengan aplikasi. Tidak ada yang perlu diubah.</p>

                    <div class="grid grid-cols-[auto_1fr] gap-2">
                        <button type="button" @click="modalOpen = false"
                                class="min-h-[48px] px-4 rounded-xl border border-line text-sm font-semibold text-ink-soft hover:bg-canvas">Batal</button>
                        <button type="submit" :disabled="!canSubmit"
                                :class="actionType === 'out' ? 'bg-alert hover:bg-alert/90' : 'bg-brand hover:bg-brand-deep'"
                                class="min-h-[48px] px-4 rounded-xl text-surface text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                x-text="submitLabel"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
