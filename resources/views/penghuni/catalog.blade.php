@extends('layouts.mobile')

@section('content')
<div x-data="{
    activeCategory: 'semua',
    searchQuery: '{{ $search ?? '' }}',
    cart: {},
    checkoutOpen: false,
    detailOpen: false,
    detailItem: null,
    paymentMethod: 'Tunai / Bayar di Kamar',
    orderNotes: '',

    openDetail(item) {
        this.detailItem = item;
        this.detailOpen = true;
    },

    addToCart(item) {
        if (item.stock <= 0) return;
        if (!this.cart[item.id]) {
            this.cart[item.id] = {
                id: item.id,
                name: item.name,
                price: item.price,
                quantity: 1,
                maxStock: item.stock,
                category: item.category
            };
        } else {
            if (this.cart[item.id].quantity < item.stock) {
                this.cart[item.id].quantity++;
            }
        }
    },

    decreaseQty(itemId) {
        if (this.cart[itemId]) {
            this.cart[itemId].quantity--;
            if (this.cart[itemId].quantity <= 0) {
                delete this.cart[itemId];
            }
        }
    },

    getItemQty(itemId) {
        return this.cart[itemId] ? this.cart[itemId].quantity : 0;
    },

    get cartItems() {
        return Object.values(this.cart);
    },

    get cartCount() {
        return this.cartItems.reduce((acc, i) => acc + i.quantity, 0);
    },

    get cartTotal() {
        return this.cartItems.reduce((acc, i) => acc + (i.price * i.quantity), 0);
    },

    get cartFormattedTotal() {
        return 'Rp ' + this.cartTotal.toLocaleString('id-ID');
    },

    get itemsJson() {
        return JSON.stringify(this.cartItems.map(i => ({
            id: i.id,
            quantity: i.quantity
        })));
    },

    matchesFilter(itemCategory, itemName) {
        const matchCategory = (this.activeCategory === 'semua' || this.activeCategory === itemCategory);
        const matchSearch = (!this.searchQuery || itemName.toLowerCase().includes(this.searchQuery.toLowerCase()));
        return matchCategory && matchSearch;
    }
}" class="space-y-4">

    <!-- Header & Search Bar -->
    <div class="space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h1 class="text-base sm:text-lg font-bold text-ink">Pesan Menu</h1>
                <p class="text-xs text-ink-soft">Makanan, minuman, dan galon diantar langsung ke {{ $roomNumber }}</p>
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-ink-faint">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari menu atau galon..." 
                       class="w-full pl-9 pr-8 py-2 text-xs bg-surface border border-line rounded-xl text-ink placeholder-ink-faint focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand transition-colors">
                <button type="button" 
                        x-show="searchQuery" 
                        @click="searchQuery = ''" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-ink-faint hover:text-ink">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Category Filter Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
            <button type="button" 
                    @click="activeCategory = 'semua'"
                    :class="activeCategory === 'semua' ? 'bg-brand-wash text-brand font-bold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                    class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                Semua ({{ $counts['semua'] ?? $items->count() }})
            </button>
            <button type="button" 
                    @click="activeCategory = 'makanan'"
                    :class="activeCategory === 'makanan' ? 'bg-brand-wash text-brand font-bold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                    class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                Makanan ({{ $counts['makanan'] ?? 0 }})
            </button>
            <button type="button" 
                    @click="activeCategory = 'minuman'"
                    :class="activeCategory === 'minuman' ? 'bg-brand-wash text-brand font-bold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                    class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                Minuman ({{ $counts['minuman'] ?? 0 }})
            </button>
            <button type="button" 
                    @click="activeCategory = 'galon'"
                    :class="activeCategory === 'galon' ? 'bg-brand-wash text-brand font-bold border border-line' : 'bg-surface border border-line text-ink-soft hover:bg-canvas font-medium'"
                    class="min-h-[44px] px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap">
                Galon ({{ $counts['galon'] ?? 0 }})
            </button>
        </div>
    </div>

    <!-- Main Content Layout (Desktop 2 Kolom: Kiri Menu, Kanan Keranjang) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Kolom Daftar Menu (lg:col-span-8) -->
        <div class="lg:col-span-8 space-y-6">
            @php
                $categoriesOrder = [
                    'makanan' => 'Makanan',
                    'minuman' => 'Minuman',
                    'galon'   => 'Galon & Air Minum',
                ];
            @endphp

            @foreach($categoriesOrder as $catKey => $catLabel)
                @php
                    $catItems = $items->where('category', $catKey);
                @endphp

                @if($catItems->isNotEmpty())
                    <div x-show="activeCategory === 'semua' || activeCategory === '{{ $catKey }}'" class="space-y-2.5">
                        <!-- Sticky Category Header -->
                        <div class="sticky top-14 sm:top-16 z-20 bg-canvas/95 backdrop-blur-xs py-2 px-1 border-b border-line flex items-center justify-between">
                            <h2 class="text-xs font-bold text-ink-soft uppercase tracking-wider">
                                {{ $catLabel }}
                            </h2>
                            <span class="text-[11px] text-ink-faint font-medium">
                                {{ $catItems->count() }} item
                            </span>
                        </div>

                        <!-- Baris Daftar Menu (Desktop 2 Kolom, Mobile 1 Kolom) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            @foreach($catItems as $item)
                                <div x-show="matchesFilter('{{ $item->category }}', '{{ addslashes($item->name) }}')"
                                     class="bg-surface rounded-xl border border-line p-3 sm:p-3.5 flex items-center justify-between gap-3 shadow-xs hover:border-ink-faint transition-colors">
                                    <div class="flex-1 min-w-0 pr-1 cursor-pointer group active:opacity-75 transition-opacity"
                                         @click="openDetail({
                                             id: {{ $item->id }},
                                             name: '{{ addslashes($item->name) }}',
                                             price: {{ $item->price }},
                                             formatted_price: '{{ $item->formatted_price }}',
                                             stock: {{ $item->stock }},
                                             minimum_stock: {{ $item->minimum_stock }},
                                             unit: '{{ addslashes($item->unit) }}',
                                             category: '{{ addslashes($item->category) }}',
                                             description: '{{ addslashes($item->description ?? '') }}'
                                         })">
                                        <div class="flex items-center gap-1.5">
                                            <h3 class="text-xs sm:text-sm font-bold text-ink group-hover:text-brand transition-colors leading-snug line-clamp-1 sm:line-clamp-none">
                                                {{ $item->name }}
                                            </h3>
                                        </div>

                                        @if($item->description)
                                            <p class="text-[11px] text-ink-soft leading-normal mt-0.5 line-clamp-1 sm:line-clamp-2 lg:line-clamp-none" title="{{ $item->description }}">
                                                {{ $item->description }}
                                            </p>
                                        @endif

                                        <div class="flex items-center gap-2 mt-1.5 sm:mt-2">
                                            <span class="text-xs font-semibold text-ink whitespace-nowrap tabular-nums">
                                                {{ $item->formatted_price }}
                                            </span>

                                            <!-- Sembunyikan angka stok: sisa sedikit jika <= min_stock, habis jika 0 -->
                                            @if($item->stock <= 0)
                                                <span class="text-[10px] font-semibold text-alert">
                                                    Habis
                                                </span>
                                            @elseif($item->stock <= $item->minimum_stock)
                                                <span class="text-[10px] font-semibold text-wait">
                                                    Sisa sedikit
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Tombol Tambah / Qty-Stepper -->
                                    <div class="flex-shrink-0 flex items-center" @click.stop>
                                        @if($item->stock <= 0)
                                            <button type="button" 
                                                    disabled 
                                                    class="min-h-[44px] px-3.5 py-1.5 bg-canvas text-ink-faint rounded-xl text-xs font-medium cursor-not-allowed">
                                                Habis
                                            </button>
                                        @else
                                            <!-- Jika belum ditambah ke keranjang -->
                                            <template x-if="getItemQty({{ $item->id }}) === 0">
                                                <button type="button" 
                                                        @click="addToCart({
                                                            id: {{ $item->id }},
                                                            name: '{{ addslashes($item->name) }}',
                                                            price: {{ $item->price }},
                                                            stock: {{ $item->stock }},
                                                            category: '{{ $item->category }}'
                                                        })"
                                                        class="min-h-[44px] min-w-[44px] px-3.5 py-1.5 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors flex items-center justify-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    <span>Tambah</span>
                                                </button>
                                            </template>

                                            <!-- Jika sudah ada di keranjang: Qty-Stepper -->
                                            <template x-if="getItemQty({{ $item->id }}) > 0">
                                                <div class="inline-flex items-center border border-line rounded-xl bg-canvas overflow-hidden">
                                                    <button type="button" 
                                                            @click="decreaseQty({{ $item->id }})"
                                                            class="min-w-[44px] min-h-[44px] flex items-center justify-center text-ink-soft hover:text-ink transition-colors"
                                                            aria-label="Kurangi">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                                        </svg>
                                                    </button>
                                                    <span class="w-7 text-center font-bold text-xs text-ink tabular-nums select-none" 
                                                          x-text="getItemQty({{ $item->id }})"></span>
                                                    <button type="button" 
                                                            @click="addToCart({
                                                                id: {{ $item->id }},
                                                                name: '{{ addslashes($item->name) }}',
                                                                price: {{ $item->price }},
                                                                stock: {{ $item->stock }},
                                                                category: '{{ $item->category }}'
                                                            })"
                                                            :disabled="getItemQty({{ $item->id }}) >= {{ $item->stock }}"
                                                            class="min-w-[44px] min-h-[44px] flex items-center justify-center text-ink-soft hover:text-ink disabled:opacity-30 disabled:pointer-events-none transition-colors"
                                                            aria-label="Tambah">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Kolom Panel Keranjang Desktop (Hidden on mobile, sticky on desktop) -->
        <div class="hidden lg:block lg:col-span-4 sticky top-20">
            <div class="bg-surface rounded-xl border border-line p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-line">
                    <h2 class="text-sm font-bold text-ink">Keranjang Pesanan</h2>
                    <span class="text-xs text-ink-soft font-medium" x-text="cartCount + ' item'"></span>
                </div>

                <!-- Kosong -->
                <div x-show="cartCount === 0" class="py-8 text-center text-xs text-ink-faint">
                    Belum ada menu yang dipilih.
                </div>

                <!-- Ada Item -->
                <div x-show="cartCount > 0" class="space-y-3">
                    <div class="max-h-64 overflow-y-auto divide-y divide-line pr-1 text-xs">
                        <template x-for="item in cartItems" :key="item.id">
                            <div class="py-2 flex items-center justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-ink truncate" x-text="item.name"></p>
                                    <p class="text-[11px] text-ink-faint tabular-nums" 
                                       x-text="'Rp ' + item.price.toLocaleString('id-ID') + ' × ' + item.quantity"></p>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button type="button" @click="decreaseQty(item.id)" class="p-1 text-ink-faint hover:text-ink">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                    </button>
                                    <span class="font-bold text-ink w-5 text-center tabular-nums" x-text="item.quantity"></span>
                                    <button type="button" @click="addToCart(item)" :disabled="item.quantity >= item.maxStock" class="p-1 text-ink-faint hover:text-ink disabled:opacity-30">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Garis Putus-putus Nota -->
                    <div class="border-t border-dashed border-line pt-3 flex items-center justify-between">
                        <span class="text-xs font-semibold text-ink-soft">Total Pembayaran</span>
                        <span class="text-base font-bold text-ink tabular-nums whitespace-nowrap" x-text="cartFormattedTotal"></span>
                    </div>

                    <button type="button" 
                            @click="checkoutOpen = true"
                            class="w-full min-h-[44px] bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors flex items-center justify-center">
                        Konfirmasi Pesanan
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- STICKY BOTTOM CART BAR (Mobile Only: lg:hidden)               -->
    <!-- ============================================================== -->
    <div x-show="cartCount > 0" 
         x-cloak
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-16 inset-x-0 z-30 p-3 bg-surface/95 backdrop-blur-md border-t border-line shadow-lg lg:hidden">
        <div class="max-w-lg mx-auto flex items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-ink block" x-text="cartCount + ' item dipilih'"></span>
                <span class="text-sm font-bold text-brand tabular-nums whitespace-nowrap" x-text="cartFormattedTotal"></span>
            </div>
            <button type="button" 
                    @click="checkoutOpen = true"
                    class="min-h-[44px] px-5 py-2.5 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors">
                Lihat pesanan
            </button>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL / BOTTOM SHEET KONFIRMASI PESANAN                        -->
    <!-- ============================================================== -->
    <x-bottom-sheet show="checkoutOpen" title="Konfirmasi Pesanan">
        <form action="{{ route('penghuni.order.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="items_json" :value="itemsJson">

            <!-- Rincian Pesanan Bergaya Nota -->
            <x-nota code="{{ 'ANTREAN-' . date('Hi') }}" date="{{ date('d M Y') }}">
                <template x-for="item in cartItems" :key="item.id">
                    <div class="flex items-center justify-between text-xs py-1">
                        <div class="pr-2">
                            <span class="font-medium text-ink" x-text="item.name"></span>
                            <span class="text-ink-faint tabular-nums text-[11px]" 
                                  x-text="' × ' + item.quantity"></span>
                        </div>
                        <span class="font-semibold text-ink tabular-nums whitespace-nowrap" 
                              x-text="'Rp ' + (item.price * item.quantity).toLocaleString('id-ID')"></span>
                    </div>
                </template>

                <x-slot:footer>
                    <span class="text-xs font-semibold text-ink-soft">Total Pembayaran</span>
                    <span class="text-base font-bold text-ink tabular-nums whitespace-nowrap" 
                          x-text="cartFormattedTotal"></span>
                </x-slot:footer>
            </x-nota>

            <!-- Metode Pembayaran -->
            <div>
                <label class="block text-xs font-semibold text-ink-soft mb-1.5">Metode Pembayaran</label>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <label class="flex items-center p-3 rounded-xl border cursor-pointer font-medium transition-colors"
                           :class="paymentMethod === 'Tunai / Bayar di Kamar' ? 'border-brand bg-brand-wash text-brand font-bold' : 'border-line text-ink-soft bg-surface'">
                        <input type="radio" name="payment_method" value="Tunai / Bayar di Kamar" x-model="paymentMethod" class="sr-only">
                        <span>Tunai saat Antar</span>
                    </label>
                    <label class="flex items-center p-3 rounded-xl border cursor-pointer font-medium transition-colors"
                           :class="paymentMethod === 'QRIS' ? 'border-brand bg-brand-wash text-brand font-bold' : 'border-line text-ink-soft bg-surface'">
                        <input type="radio" name="payment_method" value="QRIS" x-model="paymentMethod" class="sr-only">
                        <span>QRIS</span>
                    </label>
                </div>
            </div>

            <!-- Catatan Tunggal untuk Petugas (Diisi saat konfirmasi, bukan per item) -->
            <div>
                <label class="block text-xs font-semibold text-ink-soft mb-1">Catatan untuk Petugas</label>
                <input type="text" 
                       name="order_notes" 
                       x-model="orderNotes" 
                       placeholder="Contoh: Es teh tawar, antar ke kamar A-04" 
                       class="w-full text-xs px-3.5 py-2.5 border border-line rounded-xl text-ink placeholder-ink-faint focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface">
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-3 border-t border-line flex items-center justify-end gap-2">
                <button type="button" 
                        @click="checkoutOpen = false" 
                        class="min-h-[44px] px-4 py-2 border border-line rounded-xl text-xs font-semibold text-ink-soft hover:bg-canvas transition-colors">
                    Batal
                </button>
                <button type="submit" 
                        class="min-h-[44px] px-5 py-2 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors">
                    Kirim Pesanan
                </button>
            </div>
        </form>
    </x-bottom-sheet>

    <!-- ============================================================== -->
    <!-- MODAL / BOTTOM SHEET DETAIL MENU                               -->
    <!-- ============================================================== -->
    <x-bottom-sheet show="detailOpen" title="Detail Menu">
        <template x-if="detailItem">
            <div class="space-y-4">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-[10px] font-bold text-brand bg-brand-wash px-2.5 py-0.5 rounded-full border border-line uppercase tracking-wider" 
                              x-text="detailItem.category"></span>
                        
                        <span x-show="detailItem.stock <= 0" 
                              class="text-[10px] font-semibold text-alert">
                            Stok Habis
                        </span>
                        <span x-show="detailItem.stock > 0 && detailItem.stock <= detailItem.minimum_stock" 
                              class="text-[10px] font-semibold text-wait">
                            Sisa Sedikit
                        </span>
                    </div>

                    <h3 class="text-base font-bold text-ink leading-snug" x-text="detailItem.name"></h3>
                    <p class="text-base font-bold text-brand mt-1 tabular-nums whitespace-nowrap" 
                       x-text="'Rp ' + detailItem.price.toLocaleString('id-ID')"></p>
                </div>

                <!-- Deskripsi Rinci -->
                <div class="bg-canvas rounded-xl p-3.5 border border-line text-xs text-ink-soft leading-relaxed space-y-1">
                    <p class="font-bold text-ink">Deskripsi Menu:</p>
                    <p x-text="detailItem.description || 'Tidak ada deskripsi detail untuk menu ini.'"></p>
                </div>

                <div class="pt-1 text-[11px] text-ink-faint">
                    Satuan: <span class="font-medium text-ink-soft" x-text="'1 ' + detailItem.unit"></span>
                </div>

                <!-- Tombol Aksi / Stepper di Modal -->
                <div class="pt-3 border-t border-line flex items-center justify-between gap-3">
                    <button type="button" 
                            @click="detailOpen = false" 
                            class="min-h-[44px] px-4 py-2 border border-line rounded-xl text-xs font-semibold text-ink-soft hover:bg-canvas transition-colors">
                        Tutup
                    </button>

                    <div>
                        <!-- Jika Habis -->
                        <template x-if="detailItem.stock <= 0">
                            <button type="button" disabled class="min-h-[44px] px-4 py-2 bg-canvas text-ink-faint rounded-xl text-xs font-semibold cursor-not-allowed">
                                Stok Habis
                            </button>
                        </template>

                        <!-- Jika Tersedia & Belum Ada di Keranjang -->
                        <template x-if="detailItem.stock > 0 && getItemQty(detailItem.id) === 0">
                            <button type="button" 
                                    @click="addToCart(detailItem)" 
                                    class="min-h-[44px] px-5 py-2.5 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Tambah ke Keranjang</span>
                            </button>
                        </template>

                        <!-- Jika Sudah Ada di Keranjang: Qty Stepper -->
                        <template x-if="detailItem.stock > 0 && getItemQty(detailItem.id) > 0">
                            <div class="inline-flex items-center border border-line rounded-xl bg-canvas overflow-hidden">
                                <button type="button" 
                                        @click="decreaseQty(detailItem.id)"
                                        class="min-w-[44px] min-h-[44px] flex items-center justify-center text-ink-soft hover:text-ink transition-colors"
                                        aria-label="Kurangi">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                    </svg>
                                </button>
                                <span class="w-8 text-center font-bold text-xs text-ink tabular-nums select-none" 
                                      x-text="getItemQty(detailItem.id)"></span>
                                <button type="button" 
                                        @click="addToCart(detailItem)"
                                        :disabled="getItemQty(detailItem.id) >= detailItem.stock"
                                        class="min-w-[44px] min-h-[44px] flex items-center justify-center text-ink-soft hover:text-ink disabled:opacity-30 disabled:pointer-events-none transition-colors"
                                        aria-label="Tambah">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </x-bottom-sheet>

</div>
@endsection
