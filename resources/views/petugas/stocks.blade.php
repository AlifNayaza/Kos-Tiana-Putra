@extends('layouts.mobile')

@section('content')
@php
    \Carbon\Carbon::setLocale('id');

    $allItems = $galonItems->concat($otherItems);

    // Pisahkan item yang perlu restock vs item aman
    // Item habis di atas (stock <= 0), kemudian item menipis (stock <= minimum_stock)
    $needRestockItems = $allItems->filter(fn($i) => $i->stock <= $i->minimum_stock)
        ->sortBy(fn($i) => [$i->stock > 0 ? 1 : 0, $i->name]);

    // Item aman diurutkan secara alfabetis
    $safeItems = $allItems->filter(fn($i) => $i->stock > $i->minimum_stock)
        ->sortBy('name');

    $habisCount = $allItems->where('stock', '<=', 0)->count();
    $menipisCount = $allItems->filter(fn($i) => $i->stock > 0 && $i->stock <= $i->minimum_stock)->count();
    $totalNeedRestock = $needRestockItems->count();

    // Index log mutasi terakhir per item dari data $recentLogs yang sudah tersedia (tanpa query baru)
    $latestLogsByItem = $recentLogs->groupBy('catalog_item_id')->map->first();

    // Group logs untuk riwayat mutasi di bawah
    $groupedLogs = [];
    foreach ($recentLogs as $log) {
        $lastIndex = count($groupedLogs) - 1;
        if ($lastIndex >= 0 &&
            $groupedLogs[$lastIndex]['log']->catalog_item_id === $log->catalog_item_id &&
            $groupedLogs[$lastIndex]['log']->type === $log->type &&
            $groupedLogs[$lastIndex]['log']->notes === $log->notes &&
            $groupedLogs[$lastIndex]['log']->user_id === $log->user_id &&
            $groupedLogs[$lastIndex]['log']->quantity === $log->quantity) {
            $groupedLogs[$lastIndex]['count']++;
        } else {
            $groupedLogs[] = [
                'log' => $log,
                'count' => 1,
            ];
        }
    }
@endphp

<div x-data="{
    searchQuery: '',
    catFilter: 'semua',
    modalOpen: false,
    selectedItem: null,
    actionType: 'in', // 'in' | 'out' | 'recount'
    quantity: 10,
    physicalCount: 0,
    notes: '',
    items: {{ $allItems->map(fn($i) => [
        'id' => $i->id, 
        'name' => $i->name, 
        'category' => $i->category, 
        'stock' => $i->stock, 
        'minimum_stock' => $i->minimum_stock,
        'unit' => $i->unit
    ])->toJson() }},

    openModal(item, type) {
        this.selectedItem = item;
        this.actionType = type;
        if (type === 'in') {
            this.quantity = item.category === 'galon' ? 10 : 5;
            this.notes = 'Restock kiriman agen';
        } else if (type === 'out') {
            this.quantity = 1;
            this.notes = item.category === 'galon' ? 'Bocor / pecah saat penyimpanan' : 'Kedaluwarsa / rusak';
        } else if (type === 'recount') {
            this.physicalCount = item.stock;
            this.notes = 'Hasil hitung ulang fisik';
        }
        this.modalOpen = true;
    },

    get diff() {
        if (!this.selectedItem) return 0;
        if (this.actionType === 'in') {
            return parseInt(this.quantity) || 0;
        }
        if (this.actionType === 'out') {
            return -(parseInt(this.quantity) || 0);
        }
        if (this.actionType === 'recount') {
            return (parseInt(this.physicalCount) || 0) - this.selectedItem.stock;
        }
        return 0;
    },

    get afterStock() {
        if (!this.selectedItem) return 0;
        return this.selectedItem.stock + this.diff;
    },

    get diffText() {
        const d = this.diff;
        if (d > 0) return '+' + d;
        return '' + d;
    },

    get submitType() {
        if (this.actionType === 'in') return 'in';
        if (this.actionType === 'out') return 'out';
        if (this.actionType === 'recount') {
            return this.diff >= 0 ? 'in' : 'out';
        }
        return 'in';
    },

    get submitQuantity() {
        if (this.actionType === 'in' || this.actionType === 'out') {
            return Math.max(1, parseInt(this.quantity) || 1);
        }
        if (this.actionType === 'recount') {
            return Math.max(1, Math.abs(this.diff));
        }
        return 1;
    },

    get submitNotes() {
        if (this.actionType === 'recount') {
            const prefix = this.notes ? this.notes.trim() + ': ' : '';
            return prefix + 'Hitung fisik ' + this.selectedItem.stock + ' -> ' + this.physicalCount;
        }
        return this.notes.trim() || (this.actionType === 'in' ? 'Restock' : 'Barang rusak');
    },

    get canSubmit() {
        if (!this.selectedItem) return false;
        if (this.afterStock < 0) return false;
        if (this.actionType === 'recount') {
            return this.diff !== 0;
        }
        return this.quantity > 0 && this.notes.trim().length > 0;
    },

    matches(category, stock, minStock, name) {
        if (this.searchQuery.trim() !== '') {
            const q = this.searchQuery.toLowerCase();
            if (!name.toLowerCase().includes(q)) return false;
        }
        if (this.catFilter === 'semua') return true;
        if (this.catFilter === 'galon') return category === 'galon';
        if (this.catFilter === 'makanan') return category === 'makanan';
        if (this.catFilter === 'minuman') return category === 'minuman';
        if (this.catFilter === 'menipis') return stock <= minStock;
        return true;
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

    <!-- ============================================================== -->
    <!-- 1. HEADER RINGKAS & STATUS UTAMA                              -->
    <!-- ============================================================== -->
    <div class="flex items-baseline justify-between pb-1">
        <div class="flex items-baseline gap-2.5">
            <h1 class="text-base sm:text-lg font-bold text-ink leading-tight">Stok</h1>
            @if($totalNeedRestock > 0)
                <span class="text-xs font-semibold tabular-nums {{ $habisCount > 0 ? 'text-alert' : 'text-wait' }}">
                    @if($menipisCount > 0 && $habisCount > 0)
                        {{ $menipisCount }} menipis &bull; {{ $habisCount }} habis
                    @elseif($habisCount > 0)
                        {{ $habisCount }} habis
                    @else
                        {{ $menipisCount }} menipis
                    @endif
                </span>
            @else
                <span class="text-xs font-medium text-done flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-done"></span>
                    <span>Semua stok aman</span>
                </span>
            @endif
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2. SEKSI PERLU RESTOCK (HANYA MUNCUL JIKA ADA MENIPIS/HABIS)    -->
    <!-- ============================================================== -->
    @if($needRestockItems->isNotEmpty())
        <section class="space-y-2" x-show="hasVisibleNeedRestock">
            <div class="flex items-center justify-between px-0.5">
                <h2 class="text-xs font-bold text-alert uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-alert"></span>
                    <span>Perlu Restock ({{ $needRestockItems->count() }})</span>
                </h2>
                <span class="text-[11px] text-ink-faint">Prioritas pengadaan barang</span>
            </div>

            <div class="bg-surface rounded-xl border border-line divide-y divide-line">
                @foreach($needRestockItems as $item)
                    @php
                        $latestLog = $latestLogsByItem->get($item->id);
                        $historyText = null;
                        if ($latestLog) {
                            if (str_contains($latestLog->notes ?? '', 'Hitung fisik')) {
                                $historyText = 'Dihitung ' . ($latestLog->created_at ? $latestLog->created_at->diffForHumans() : '');
                            } elseif ($latestLog->type === 'in') {
                                $historyText = 'Masuk +' . $latestLog->quantity . ' · ' . ($latestLog->created_at ? $latestLog->created_at->diffForHumans() : '');
                            } elseif ($latestLog->type === 'out') {
                                $historyText = 'Keluar -' . $latestLog->quantity . ' · ' . ($latestLog->created_at ? $latestLog->created_at->diffForHumans() : '');
                            }
                        }
                    @endphp
                    @include('petugas.partials.stock-row', [
                        'item' => $item,
                        'isNeedRestock' => true,
                        'historyText' => $historyText,
                    ])
                @endforeach
            </div>
        </section>
    @endif

    <!-- ============================================================== -->
    <!-- 3. FILTER KATEGORI, PENCARIAN & DAFTAR LENGKAP ITEM AMAN       -->
    <!-- ============================================================== -->
    <section class="space-y-3">
        <!-- Filter Tabs & Pencarian Instan -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <!-- Tabs Filter Horizontal -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
                <button type="button" 
                        @click="catFilter = 'semua'"
                        :class="catFilter === 'semua' ? 'bg-brand-wash text-brand font-bold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                    Semua
                </button>
                <button type="button" 
                        @click="catFilter = 'galon'"
                        :class="catFilter === 'galon' ? 'bg-brand-wash text-brand font-bold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                    Galon
                </button>
                <button type="button" 
                        @click="catFilter = 'makanan'"
                        :class="catFilter === 'makanan' ? 'bg-brand-wash text-brand font-bold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                    Makanan
                </button>
                <button type="button" 
                        @click="catFilter = 'minuman'"
                        :class="catFilter === 'minuman' ? 'bg-brand-wash text-brand font-bold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                    Minuman
                </button>
                @if($totalNeedRestock > 0)
                    <button type="button" 
                            @click="catFilter = 'menipis'"
                            :class="catFilter === 'menipis' ? 'bg-wait text-surface font-semibold' : 'bg-wait-wash border border-wait/30 text-wait hover:bg-wait-wash/80 font-medium'"
                            class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                        Perlu Restock ({{ $totalNeedRestock }})
                    </button>
                @endif
            </div>

            <!-- Input Pencarian Instan -->
            <div class="relative w-full sm:w-64">
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari nama item..." 
                       class="w-full text-xs rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand pl-8 pr-3 min-h-[44px] bg-surface text-ink placeholder-ink-faint">
                <svg class="w-4 h-4 text-ink-faint absolute left-2.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- Indikator Tenang Jika Semua Stok Aman -->
        @if($needRestockItems->isEmpty())
            <div class="flex items-center gap-2 py-2.5 px-3.5 bg-surface border border-line rounded-xl text-xs text-ink-soft">
                <span class="w-2 h-2 rounded-full bg-done shrink-0"></span>
                <span class="font-semibold text-ink">Semua stok aman</span>
                <span class="text-ink-faint">&bull;</span>
                <span class="text-ink-faint">Seluruh persediaan berada di atas batas minimum</span>
            </div>
        @endif

        <!-- Daftar Item Stok Aman -->
        <div class="bg-surface rounded-xl border border-line divide-y divide-line" x-show="hasVisibleSafeItems">
            @foreach($safeItems as $item)
                @php
                    $latestLog = $latestLogsByItem->get($item->id);
                    $historyText = null;
                    if ($latestLog) {
                        if (str_contains($latestLog->notes ?? '', 'Hitung fisik')) {
                            $historyText = 'Dihitung ' . ($latestLog->created_at ? $latestLog->created_at->diffForHumans() : '');
                        } elseif ($latestLog->type === 'in') {
                            $historyText = 'Masuk +' . $latestLog->quantity . ' · ' . ($latestLog->created_at ? $latestLog->created_at->diffForHumans() : '');
                        } elseif ($latestLog->type === 'out') {
                            $historyText = 'Keluar -' . $latestLog->quantity . ' · ' . ($latestLog->created_at ? $latestLog->created_at->diffForHumans() : '');
                        }
                    }
                @endphp
                @include('petugas.partials.stock-row', [
                    'item' => $item,
                    'isNeedRestock' => false,
                    'historyText' => $historyText,
                ])
            @endforeach
        </div>

        <!-- Keadaan Khusus: Hasil Pencarian / Filter Kosong -->
        <div x-show="!hasVisibleItems" class="py-10 text-center space-y-3 bg-surface rounded-xl border border-line p-6">
            <p class="text-xs text-ink-soft font-medium">Tidak ada item stok yang cocok dengan kriteria filter.</p>
            <button type="button" 
                    @click="searchQuery = ''; catFilter = 'semua'" 
                    class="min-h-[44px] px-4 py-2 text-xs font-semibold bg-canvas hover:bg-line border border-line rounded-xl text-ink transition-colors">
                Hapus pencarian & filter
            </button>
        </div>
    </section>

    <!-- ============================================================== -->
    <!-- 4. RIWAYAT MUTASI STOK (AUDIT LOG DI BAWAH DAFTAR STOK)        -->
    <!-- Entri mutasi berulang digabung dengan badge 'xN'                -->
    <!-- ============================================================== -->
    <div class="pt-6">
        <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 shadow-none space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-line">
                <div>
                    <h2 class="text-sm font-bold text-ink leading-snug">Riwayat Mutasi Stok</h2>
                    <p class="text-[11px] text-ink-faint">Catatan masuk, keluar, dan penyesuaian terbaru</p>
                </div>
                <span class="text-[11px] font-medium text-ink-soft bg-canvas px-2.5 py-0.5 rounded-full border border-line">
                    {{ count($groupedLogs) }} Aktivitas
                </span>
            </div>

            <div class="divide-y divide-line">
                @forelse($groupedLogs as $entry)
                    @php $log = $entry['log']; @endphp
                    <div class="py-2.5 first:pt-1 last:pb-1 flex items-start justify-between gap-3 text-xs">
                        <div class="min-w-0 flex-1 space-y-0.5">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-bold text-ink">{{ $log->catalogItem->name ?? 'Item' }}</span>
                                @if($entry['count'] > 1)
                                    <span class="px-1.5 py-0.2 text-[10px] font-bold bg-canvas text-ink-soft rounded border border-line tabular-nums">
                                        x{{ $entry['count'] }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-ink-soft leading-snug">{{ $log->notes ?? '-' }}</p>
                            <p class="text-[10px] text-ink-faint">
                                <span>{{ $log->user->name ?? 'Sistem' }}</span>
                                <span class="mx-1">&bull;</span>
                                <span>{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</span>
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            @if($log->type === 'in')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-done-wash text-done border border-line tabular-nums">
                                    +{{ $log->quantity * $entry['count'] }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-alert-wash text-alert border border-line tabular-nums">
                                    -{{ $log->quantity * $entry['count'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-ink-faint">
                        Belum ada riwayat mutasi stok tercatat.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 5. DIALOG PENYESUAIAN STOK (MASUK, RUSAK, HITUNG ULANG FISIK)  -->
    <!-- ============================================================== -->
    <div x-show="modalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-xs transition-opacity" @click="modalOpen = false"></div>

        <div class="min-h-full flex items-end sm:items-center justify-center p-3 sm:p-4">
            <div x-show="modalOpen" 
                 x-transition
                 class="relative bg-surface rounded-t-2xl sm:rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-xl border border-line text-left space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-line">
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-ink" 
                            x-text="actionType === 'in' ? 'Stok Masuk' : (actionType === 'out' ? 'Catat Barang Rusak' : 'Hitung Fisik Aktual')"></h3>
                        <p class="text-xs text-ink-soft mt-0.5" x-text="selectedItem ? selectedItem.name : ''"></p>
                    </div>
                    <button @click="modalOpen = false" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-ink-faint hover:text-ink rounded-lg" aria-label="Tutup dialog">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('petugas.stocks.adjust') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="catalog_item_id" :value="selectedItem ? selectedItem.id : ''">
                    <input type="hidden" name="type" :value="submitType">
                    <input type="hidden" name="quantity" :value="submitQuantity">
                    <input type="hidden" name="notes" :value="submitNotes">

                    <!-- Field Input: Jika Masuk atau Rusak -->
                    <template x-if="actionType === 'in' || actionType === 'out'">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-ink-soft mb-1">
                                    <span x-text="actionType === 'in' ? 'Jumlah masuk' : 'Jumlah rusak'"></span>
                                    (<span x-text="selectedItem ? selectedItem.unit : ''"></span>)
                                </label>
                                <input type="number" 
                                       x-model="quantity" 
                                       min="1" 
                                       max="500" 
                                       required
                                       class="w-full text-base font-bold font-mono text-ink rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand min-h-[44px] px-3 tabular-nums bg-surface">
                                
                                <!-- Preset Jumlah -->
                                <div class="flex items-center gap-1.5 mt-2">
                                    <template x-for="chip in (actionType === 'in' ? [5, 10, 20, 50] : [1, 2, 5])" :key="chip">
                                        <button type="button" 
                                                @click="quantity = chip" 
                                                class="min-h-[44px] px-3 py-1 text-xs font-medium bg-canvas hover:bg-surface rounded-lg text-ink-soft border border-line">
                                            +<span x-text="chip"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-ink-soft mb-1">
                                    <span x-text="actionType === 'in' ? 'Catatan (opsional)' : 'Alasan rusak (wajib)'"></span>
                                </label>
                                <input type="text" 
                                       x-model="notes" 
                                       required 
                                       placeholder="Tuliskan keterangan..." 
                                       class="w-full text-xs rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand min-h-[44px] px-3 bg-surface text-ink">
                                
                                <!-- Preset Keterangan -->
                                <div class="flex items-center gap-1.5 mt-2 flex-wrap text-[11px]">
                                    <template x-if="actionType === 'in'">
                                        <div class="flex gap-1.5 flex-wrap">
                                            <button type="button" @click="notes = 'Restock kiriman agen'" class="px-2.5 py-1 bg-canvas hover:bg-surface border border-line rounded-lg text-ink-soft">Restock agen</button>
                                            <button type="button" @click="notes = 'Belanja warkop'" class="px-2.5 py-1 bg-canvas hover:bg-surface border border-line rounded-lg text-ink-soft">Belanja warkop</button>
                                        </div>
                                    </template>
                                    <template x-if="actionType === 'out'">
                                        <div class="flex gap-1.5 flex-wrap">
                                            <button type="button" @click="notes = 'Bocor / pecah saat penyimpanan'" class="px-2.5 py-1 bg-canvas hover:bg-surface border border-line rounded-lg text-ink-soft">Bocor / pecah</button>
                                            <button type="button" @click="notes = 'Kedaluwarsa / rusak'" class="px-2.5 py-1 bg-canvas hover:bg-surface border border-line rounded-lg text-ink-soft">Kedaluwarsa</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Field Input: Jika Hitung Ulang Fisik -->
                    <template x-if="actionType === 'recount'">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-ink-soft mb-1">
                                    Jumlah Fisik Aktual (<span x-text="selectedItem ? selectedItem.unit : ''"></span>)
                                </label>
                                <input type="number" 
                                       x-model="physicalCount" 
                                       min="0" 
                                       max="1000" 
                                       required
                                       class="w-full text-base font-bold font-mono text-ink rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand min-h-[44px] px-3 tabular-nums bg-surface">
                                <p class="text-[11px] text-ink-faint mt-1">
                                    Masukkan jumlah barang nyata yang ada di tempat saat ini.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-ink-soft mb-1">Keterangan / Catatan</label>
                                <input type="text" 
                                       x-model="notes" 
                                       placeholder="Contoh: Hasil stok opname mingguan" 
                                       class="w-full text-xs rounded-xl border border-line focus:border-brand focus:ring-1 focus:ring-brand min-h-[44px] px-3 bg-surface text-ink">
                            </div>
                        </div>
                    </template>

                    <!-- Tinjauan Perubahan Sebelum Simpan -->
                    <div class="bg-canvas rounded-xl p-3 border border-line text-xs space-y-1">
                        <div class="text-ink-soft font-medium">Tinjauan perubahan:</div>
                        <div class="flex items-center gap-2 font-mono text-sm">
                            <span class="text-ink-soft tabular-nums" x-text="selectedItem ? selectedItem.name + ': ' + selectedItem.stock : ''"></span>
                            <span class="text-ink-faint">&rarr;</span>
                            <span class="font-bold tabular-nums" :class="afterStock < 0 ? 'text-alert' : 'text-ink'" x-text="afterStock"></span>
                            <span class="text-xs font-semibold ml-1" 
                                  :class="diff > 0 ? 'text-done' : (diff < 0 ? 'text-alert' : 'text-ink-faint')" 
                                  x-text="'(' + diffText + ' ' + (selectedItem ? selectedItem.unit : '') + ')'"></span>
                        </div>
                        <div x-show="afterStock < 0" class="text-[11px] text-alert font-semibold pt-1">
                            Stok akhir tidak boleh kurang dari 0.
                        </div>
                        <div x-show="actionType === 'recount' && diff === 0" class="text-[11px] text-wait font-medium pt-1">
                            Jumlah fisik sama dengan stok sistem saat ini (tidak ada perubahan).
                        </div>
                    </div>

                    <!-- Tombol Konfirmasi Aksi -->
                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" 
                                @click="modalOpen = false" 
                                class="min-h-[44px] px-4 py-2 rounded-xl border border-line text-xs font-semibold text-ink-soft hover:bg-canvas transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="!canSubmit"
                                class="min-h-[44px] px-5 py-2 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-semibold shadow-xs transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection
