@extends('layouts.app')

@section('page_title', 'Katalog Menu Warkop & Stok Galon')
@section('page_subtitle', 'Kelola daftar menu makanan, racikan minuman warkop, air galon, dan batas minimum stok')

@section('content')
<div class="space-y-6" x-data="{ 
    modalOpen: false, 
    isEdit: false,
    formUrl: '',
    itemForm: {
        name: '',
        category: 'makanan',
        price: 10000,
        stock: 20,
        minimum_stock: 5,
        unit: 'porsi',
        description: '',
        is_available: true
    },
    openCreate() {
        this.isEdit = false;
        this.formUrl = '{{ route('pengelola.katalog.store') }}';
        this.itemForm = {
            name: '',
            category: 'makanan',
            price: 10000,
            stock: 20,
            minimum_stock: 5,
            unit: 'porsi',
            description: '',
            is_available: true
        };
        this.modalOpen = true;
    },
    openEdit(item) {
        this.isEdit = true;
        this.formUrl = `/pengelola/katalog/${item.id}`;
        this.itemForm = {
            name: item.name,
            category: item.category,
            price: item.price,
            stock: item.stock,
            minimum_stock: item.minimum_stock,
            unit: item.unit,
            description: item.description || '',
            is_available: Boolean(item.is_available)
        };
        this.modalOpen = true;
    }
}">

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <a href="{{ route('pengelola.katalog.index') }}" 
           class="p-4 rounded-2xl border transition-all {{ !$category || $category === 'semua' ? 'bg-surface border-brand shadow-xs' : 'bg-surface border-line hover:border-brand/40' }}">
            <p class="text-[11px] font-semibold text-ink-soft uppercase tracking-wider">Total Menu</p>
            <div class="mt-1 text-2xl font-extrabold text-ink tabular-nums">{{ $stats['total'] }}</div>
        </a>

        <a href="{{ route('pengelola.katalog.index', ['category' => 'galon']) }}" 
           class="p-4 rounded-2xl border transition-all {{ $category === 'galon' ? 'bg-brand-wash border-brand shadow-xs' : 'bg-surface border-line hover:border-brand/40' }}">
            <p class="text-[11px] font-semibold text-brand uppercase tracking-wider">Galon</p>
            <div class="mt-1 text-2xl font-extrabold text-ink tabular-nums">{{ $stats['galon'] }}</div>
        </a>

        <a href="{{ route('pengelola.katalog.index', ['category' => 'makanan']) }}" 
           class="p-4 rounded-2xl border transition-all {{ $category === 'makanan' ? 'bg-ochre-wash border-ochre shadow-xs' : 'bg-surface border-line hover:border-brand/40' }}">
            <p class="text-[11px] font-semibold text-ochre uppercase tracking-wider">Makanan</p>
            <div class="mt-1 text-2xl font-extrabold text-ink tabular-nums">{{ $stats['makanan'] }}</div>
        </a>

        <a href="{{ route('pengelola.katalog.index', ['category' => 'minuman']) }}" 
           class="p-4 rounded-2xl border transition-all {{ $category === 'minuman' ? 'bg-brand-wash border-brand shadow-xs' : 'bg-surface border-line hover:border-brand/40' }}">
            <p class="text-[11px] font-semibold text-brand uppercase tracking-wider">Minuman</p>
            <div class="mt-1 text-2xl font-extrabold text-ink tabular-nums">{{ $stats['minuman'] }}</div>
        </a>

        <div class="p-4 rounded-2xl border bg-surface border-line">
            <p class="text-[11px] font-semibold text-alert uppercase tracking-wider">Stok Menipis</p>
            <div class="mt-1 text-2xl font-extrabold text-alert tabular-nums">{{ $stats['menipis'] }}</div>
        </div>
    </div>

    <!-- Filter & Action Header -->
    <div class="bg-surface p-4 sm:p-5 rounded-2xl border border-line shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Tab Filter Kategori & Search -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="inline-flex p-1 bg-canvas rounded-xl overflow-x-auto">
                <a href="{{ route('pengelola.katalog.index', ['category' => 'semua']) }}" 
                   class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-colors whitespace-nowrap {{ !$category || $category === 'semua' ? 'bg-surface text-ink shadow-xs' : 'text-ink-soft hover:text-ink' }}">
                    Semua
                </a>
                <a href="{{ route('pengelola.katalog.index', ['category' => 'galon']) }}" 
                   class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-colors whitespace-nowrap {{ $category === 'galon' ? 'bg-surface text-ink shadow-xs' : 'text-ink-soft hover:text-ink' }}">
                    Galon
                </a>
                <a href="{{ route('pengelola.katalog.index', ['category' => 'makanan']) }}" 
                   class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-colors whitespace-nowrap {{ $category === 'makanan' ? 'bg-surface text-ink shadow-xs' : 'text-ink-soft hover:text-ink' }}">
                    Makanan
                </a>
                <a href="{{ route('pengelola.katalog.index', ['category' => 'minuman']) }}" 
                   class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-colors whitespace-nowrap {{ $category === 'minuman' ? 'bg-surface text-ink shadow-xs' : 'text-ink-soft hover:text-ink' }}">
                    Minuman
                </a>
            </div>

            <form method="GET" action="{{ route('pengelola.katalog.index') }}" class="relative">
                <input type="hidden" name="category" value="{{ $category }}">
                <div class="relative">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama menu..." 
                           class="text-xs rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none pl-8 pr-4 py-2 bg-canvas w-full sm:w-56">
                    <svg class="w-4 h-4 text-ink-faint absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </form>
        </div>

        <!-- Button: Tambah Item Menu Baru -->
        <button type="button" 
                @click="openCreate()" 
                class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Item Menu</span>
        </button>
    </div>

    <!-- Catalog Items Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($items as $item)
            @php
                $isLow = $item->stock <= $item->minimum_stock;
                $isOut = $item->stock <= 0;
            @endphp
            <div class="bg-surface rounded-2xl border border-line shadow-xs p-5 flex flex-col justify-between hover:border-line transition-colors">
                <div>
                    <!-- Header Kartu Menu -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-canvas flex items-center justify-center text-lg flex-shrink-0 border border-line text-brand">
                                @if($item->category === 'galon')
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                @elseif($item->category === 'makanan')
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-ink leading-tight">{{ $item->name }}</h3>
                                <span class="text-[10px] font-semibold text-ink-soft uppercase tracking-wider block mt-0.5">
                                    {{ ucfirst($item->category) }}
                                </span>
                            </div>
                        </div>

                        <!-- Status Ketersediaan -->
                        <x-status-badge :status="$item->is_available ? 'selesai' : 'netral'" :label="$item->is_available ? 'Aktif' : 'Nonaktif'" />
                    </div>

                    <!-- Harga & Deskripsi -->
                    <div class="mt-4 pb-3 border-b border-line">
                        <div class="text-base font-extrabold text-ink tabular-nums">
                            {{ $item->formatted_price }}
                            <span class="text-xs font-normal text-ink-soft">/ {{ $item->unit }}</span>
                        </div>
                        @if($item->description)
                            <p class="text-xs text-ink-soft mt-1 line-clamp-2 leading-relaxed">{{ $item->description }}</p>
                        @endif
                    </div>

                    <!-- Informasi Stok & Minimum Threshold -->
                    <div class="mt-3 flex items-center justify-between text-xs">
                        <div>
                            <span class="text-ink-soft">Stok Saat Ini:</span>
                            <span class="font-extrabold ml-1 tabular-nums {{ $isOut ? 'text-alert' : ($isLow ? 'text-wait' : 'text-ink') }}">
                                {{ $item->stock }} {{ $item->unit }}
                            </span>
                        </div>
                        <div class="text-[11px] text-ink-faint">
                            Min: {{ $item->minimum_stock }} {{ $item->unit }}
                        </div>
                    </div>

                    @if($isLow)
                        <div class="mt-2 p-2 rounded-lg bg-alert-wash border border-alert/20 text-[11px] font-semibold text-alert flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 text-wait flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Stok menipis, di bawah batas minimum</span>
                        </div>
                    @endif
                </div>

                <!-- Aksi Edit & Hapus -->
                <div class="mt-4 pt-3 border-t border-line flex items-center justify-between">
                    <button type="button" 
                            @click="openEdit({{ json_encode($item) }})" 
                            class="text-xs font-semibold text-ink hover:text-brand bg-canvas hover:bg-brand-wash px-3 py-1.5 rounded-lg border border-line transition-colors">
                        Edit Menu
                    </button>

                    <form action="{{ route('pengelola.katalog.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus menu {{ $item->name }} dari katalog?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-alert hover:text-alert p-1.5 rounded-lg hover:bg-alert-wash transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-surface rounded-2xl p-10 text-center border border-line">
                <p class="text-xs text-ink-faint">Tidak ada item menu pada filter yang dipilih.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Form (Tambah / Edit Item Menu) -->
    <div x-show="modalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" @click="modalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div x-show="modalOpen" 
                 x-transition
                 class="relative bg-surface rounded-2xl max-w-md w-full p-6 shadow-2xl border border-line text-left">
                
                <div class="flex items-center justify-between pb-4 border-b border-line">
                    <h3 class="text-sm font-bold text-ink" x-text="isEdit ? 'Edit Data Menu' : 'Tambah Menu Baru'"></h3>
                    <button @click="modalOpen = false" class="text-ink-faint hover:text-ink-soft p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form :action="formUrl" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Nama Menu / Produk <span class="text-alert">*</span></label>
                        <input type="text" name="name" x-model="itemForm.name" required placeholder="Contoh: Kopi Susu Aren" 
                               class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Kategori <span class="text-alert">*</span></label>
                            <select name="category" x-model="itemForm.category" required 
                                    class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                                <option value="makanan">Makanan</option>
                                <option value="minuman">Minuman</option>
                                <option value="galon">Galon</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Satuan (Unit) <span class="text-alert">*</span></label>
                            <input type="text" name="unit" x-model="itemForm.unit" required placeholder="porsi / gelas / galon" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Harga Satuan (Rp) <span class="text-alert">*</span></label>
                            <input type="number" name="price" x-model="itemForm.price" required min="500" step="500" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5 tabular-nums">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Batas Minimum Stok <span class="text-alert">*</span></label>
                            <input type="number" name="minimum_stock" x-model="itemForm.minimum_stock" required min="1" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5 tabular-nums">
                        </div>
                    </div>

                    <template x-if="!isEdit">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Stok Awal Masuk <span class="text-alert">*</span></label>
                            <input type="number" name="stock" x-model="itemForm.stock" required min="0" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5 tabular-nums">
                            <p class="text-[11px] text-ink-faint mt-1">Stok awal akan dicatat ke dalam audit log.</p>
                        </div>
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Keterangan / Komposisi</label>
                        <input type="text" name="description" x-model="itemForm.description" placeholder="Disajikan panas / dingin" 
                               class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox" name="is_available" id="is_available" value="1" x-model="itemForm.is_available" 
                               class="rounded border-line text-brand focus:ring-brand-wash">
                        <label for="is_available" class="text-xs font-semibold text-ink select-none">
                            Tersedia untuk dipesan penghuni
                        </label>
                    </div>

                    <div class="pt-4 border-t border-line flex items-center justify-end space-x-3">
                        <button type="button" @click="modalOpen = false" 
                                class="px-4 py-2 rounded-xl border border-line text-xs font-semibold text-ink-soft hover:bg-canvas">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-5 py-2 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs">
                            Simpan Menu
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection
