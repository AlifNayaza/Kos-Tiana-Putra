@extends('layouts.app')

@section('page_title', 'Manajemen Data Kamar')
@section('page_subtitle', 'Kelola nomor kamar, harga sewa bulanan, fasilitas, dan status ketersediaan')

@section('content')
<div class="space-y-6" x-data="{ 
    modalOpen: false, 
    isEdit: false,
    editUrl: '',
    roomForm: {
        room_number: '',
        floor: 1,
        type: 'Standard (Kamar Mandi Luar)',
        monthly_price: 650000,
        size: '3x3 m',
        status: 'tersedia',
        facilities: 'Kasur Busa, Lemari Pakaian, Meja Belajar, WiFi',
        description: ''
    },
    openCreate() {
        this.isEdit = false;
        this.editUrl = '{{ route('pengelola.kamar.store') }}';
        this.roomForm = {
            room_number: '',
            floor: 1,
            type: 'Standard (Kamar Mandi Luar)',
            monthly_price: 650000,
            size: '3x3 m',
            status: 'tersedia',
            facilities: 'Kasur Busa, Lemari Pakaian, Meja Belajar, WiFi',
            description: ''
        };
        this.modalOpen = true;
    },
    openEdit(room) {
        this.isEdit = true;
        this.editUrl = `/pengelola/kamar/${room.id}`;
        this.roomForm = {
            room_number: room.room_number,
            floor: room.floor,
            type: room.type,
            monthly_price: room.monthly_price,
            size: room.size || '',
            status: room.status,
            facilities: room.facilities || '',
            description: room.description || ''
        };
        this.modalOpen = true;
    }
}">

    <!-- Stats Bar & Quick Filter -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('pengelola.kamar.index') }}" 
           class="p-4 rounded-2xl border transition-all {{ !$statusFilter || $statusFilter === 'semua' ? 'bg-surface border-brand shadow-xs' : 'bg-surface border-line hover:border-brand/40' }}">
            <p class="text-xs font-semibold text-ink-soft uppercase tracking-wider">Total Kamar</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-ink tabular-nums">{{ $stats['total'] }}</span>
                <span class="text-xs text-ink-faint">Unit</span>
            </div>
        </a>

        <a href="{{ route('pengelola.kamar.index', ['status' => 'terisi']) }}" 
           class="p-4 rounded-2xl border transition-all {{ $statusFilter === 'terisi' ? 'bg-done-wash border-done shadow-xs' : 'bg-surface border-line hover:border-brand/40' }}">
            <p class="text-xs font-semibold text-done uppercase tracking-wider">Kamar Terisi</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-ink tabular-nums">{{ $stats['terisi'] }}</span>
                <span class="text-xs font-bold text-done bg-surface px-2 py-0.5 rounded-full border border-done/20">
                    {{ round(($stats['terisi'] / max($stats['total'], 1)) * 100) }}%
                </span>
            </div>
        </a>

        <a href="{{ route('pengelola.kamar.index', ['status' => 'tersedia']) }}" 
           class="p-4 rounded-2xl border transition-all {{ $statusFilter === 'tersedia' ? 'bg-brand-wash border-brand shadow-xs' : 'bg-surface border-line hover:border-brand/40' }}">
            <p class="text-xs font-semibold text-brand uppercase tracking-wider">Kamar Tersedia</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-ink tabular-nums">{{ $stats['tersedia'] }}</span>
                <span class="text-xs font-semibold text-brand">Siap Huni</span>
            </div>
        </a>

        <a href="{{ route('pengelola.kamar.index', ['status' => 'perbaikan']) }}" 
           class="p-4 rounded-2xl border transition-all {{ $statusFilter === 'perbaikan' ? 'bg-wait-wash border-wait shadow-xs' : 'bg-surface border-line hover:border-brand/40' }}">
            <p class="text-xs font-semibold text-wait uppercase tracking-wider">Dalam Perbaikan</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-ink tabular-nums">{{ $stats['perbaikan'] }}</span>
                <span class="text-xs font-semibold text-wait">Maintenance</span>
            </div>
        </a>
    </div>

    <!-- Filter & Action Header -->
    <div class="bg-surface p-4 sm:p-5 rounded-2xl border border-line shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Floor & Status Dropdowns -->
        <form method="GET" action="{{ route('pengelola.kamar.index') }}" class="flex flex-wrap items-center gap-3">
            <div>
                <label for="status" class="sr-only">Filter Status</label>
                <select name="status" id="status" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    <option value="semua" {{ !$statusFilter || $statusFilter === 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="tersedia" {{ $statusFilter === 'tersedia' ? 'selected' : '' }}>Tersedia (Kosong)</option>
                    <option value="terisi" {{ $statusFilter === 'terisi' ? 'selected' : '' }}>Terisi Penghuni</option>
                    <option value="perbaikan" {{ $statusFilter === 'perbaikan' ? 'selected' : '' }}>Dalam Perbaikan</option>
                </select>
            </div>

            <div>
                <label for="floor" class="sr-only">Filter Lantai</label>
                <select name="floor" id="floor" onchange="this.form.submit()" 
                        class="text-xs font-semibold rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none py-2 pl-3 pr-8 bg-canvas">
                    <option value="" {{ !$floorFilter ? 'selected' : '' }}>Semua Lantai</option>
                    <option value="1" {{ $floorFilter == '1' ? 'selected' : '' }}>Lantai 1</option>
                    <option value="2" {{ $floorFilter == '2' ? 'selected' : '' }}>Lantai 2</option>
                </select>
            </div>

            @if($statusFilter || $floorFilter)
                <a href="{{ route('pengelola.kamar.index') }}" class="text-xs text-alert hover:text-alert font-semibold px-2 py-1">
                    Reset Filter
                </a>
            @endif
        </form>

        <!-- Button: Tambah Kamar Baru -->
        <button type="button" 
                @click="openCreate()" 
                class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Kamar Baru</span>
        </button>
    </div>

    <!-- Room Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($rooms as $room)
            @php
                $statusColor = match($room->status) {
                    'tersedia'  => ['status' => 'tersedia', 'label' => 'Tersedia'],
                    'terisi'    => ['status' => 'lunas', 'label' => 'Terisi'],
                    'perbaikan' => ['status' => 'menunggu', 'label' => 'Perbaikan'],
                    default     => ['status' => 'netral', 'label' => ucfirst($room->status)],
                };
                $tenant = $room->activeTenantProfile;
            @endphp
            <div class="bg-surface rounded-2xl border border-line shadow-xs p-5 flex flex-col justify-between hover:border-brand/40 transition-colors">
                <div>
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-base font-bold text-ink tracking-tight">{{ $room->room_number }}</h3>
                                <span class="text-[11px] font-semibold text-ink-soft bg-canvas px-2 py-0.5 rounded-md">
                                    Lt. {{ $room->floor }}
                                </span>
                            </div>
                            <p class="text-xs text-ink-soft mt-0.5 font-medium">{{ $room->type }} {{ $room->size ? '• ' . $room->size : '' }}</p>
                        </div>

                        <x-status-badge :status="$statusColor['status']" :label="$statusColor['label']" />
                    </div>

                    <!-- Price Tag -->
                    <div class="mt-4 pb-4 border-b border-line">
                        <div class="text-lg font-extrabold text-ink tabular-nums">
                            {{ $room->formatted_price }}
                            <span class="text-xs font-normal text-ink-soft">/ bulan</span>
                        </div>
                    </div>

                    <!-- Occupant Info / Available status -->
                    <div class="py-3 border-b border-line text-xs">
                        @if($room->status === 'terisi' && $tenant)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-7 h-7 rounded-full bg-brand-wash text-brand font-bold flex items-center justify-center text-[10px]">
                                        {{ substr($tenant->user->name ?? 'P', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-ink leading-tight">{{ $tenant->user->name ?? 'Penghuni' }}</p>
                                        <p class="text-[11px] text-ink-soft">{{ $tenant->user->phone ?? '-' }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] text-ink-faint font-medium">Huni sejak {{ \Carbon\Carbon::parse($tenant->start_date)->format('d M Y') }}</span>
                            </div>
                        @elseif($room->status === 'perbaikan')
                            <div class="flex items-center space-x-2 text-wait">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="font-medium text-xs">Sedang dalam proses perbaikan / renovasi</span>
                            </div>
                        @else
                            <div class="flex items-center space-x-2 text-brand font-medium">
                                <svg class="w-4 h-4 flex-shrink-0 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Kamar kosong, siap dialokasikan untuk penghuni baru</span>
                            </div>
                        @endif
                    </div>

                    <!-- Facilities List -->
                    <div class="mt-3">
                        <p class="text-[11px] font-semibold text-ink-faint uppercase tracking-wider mb-1.5">Fasilitas</p>
                        <p class="text-xs text-ink-soft leading-relaxed">{{ $room->facilities }}</p>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="mt-5 pt-4 border-t border-line flex items-center justify-between">
                    <button type="button" 
                            @click="openEdit({{ json_encode($room) }})" 
                            class="text-xs font-semibold text-ink hover:text-brand bg-canvas hover:bg-brand-wash px-3 py-1.5 rounded-lg border border-line hover:border-brand/30 transition-colors">
                        Edit Informasi
                    </button>

                    @if($room->status !== 'terisi')
                        <form action="{{ route('pengelola.kamar.destroy', $room->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data {{ $room->room_number }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-alert hover:text-alert p-1.5 rounded-lg hover:bg-alert-wash transition-colors">
                                Hapus
                            </button>
                        </form>
                    @else
                        <span class="text-[10px] text-ink-faint font-medium" title="Kamar terisi tidak dapat dihapus">
                            Terkunci (Terisi)
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-surface rounded-2xl p-12 text-center border border-line">
                <p class="text-sm font-semibold text-ink">Tidak ada data kamar yang sesuai dengan kriteria filter.</p>
                <p class="text-xs text-ink-faint mt-1">Coba ubah opsi filter atau tambahkan data kamar baru.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Form (Tambah / Edit Kamar) -->
    <div x-show="modalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" @click="modalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div x-show="modalOpen" 
                 x-transition
                 class="relative bg-surface rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-line text-left">
                
                <div class="flex items-center justify-between pb-4 border-b border-line">
                    <h3 class="text-sm font-bold text-ink" x-text="isEdit ? 'Edit Data Kamar' : 'Tambah Kamar Baru'"></h3>
                    <button @click="modalOpen = false" class="text-ink-faint hover:text-ink-soft p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form :action="editUrl" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Nomor Kamar <span class="text-alert">*</span></label>
                            <input type="text" name="room_number" x-model="roomForm.room_number" required placeholder="Contoh: Kamar A-04" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Lantai <span class="text-alert">*</span></label>
                            <select name="floor" x-model="roomForm.floor" required 
                                    class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                                <option value="1">Lantai 1</option>
                                <option value="2">Lantai 2</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Tipe Kamar <span class="text-alert">*</span></label>
                            <input type="text" name="type" x-model="roomForm.type" required placeholder="Contoh: Standard AC" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Ukuran Ruangan</label>
                            <input type="text" name="size" x-model="roomForm.size" placeholder="Contoh: 3x4 m" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Harga Sewa / Bulan (Rp) <span class="text-alert">*</span></label>
                            <input type="number" name="monthly_price" x-model="roomForm.monthly_price" required min="100000" step="25000" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5 tabular-nums">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Status Kamar <span class="text-alert">*</span></label>
                            <select name="status" x-model="roomForm.status" required 
                                    class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                                <option value="tersedia">Tersedia (Kosong)</option>
                                <option value="terisi">Terisi Penghuni</option>
                                <option value="perbaikan">Dalam Perbaikan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Daftar Fasilitas Kamar <span class="text-alert">*</span></label>
                        <textarea name="facilities" x-model="roomForm.facilities" required rows="2" 
                                  placeholder="Kasur Springbed, Kamar Mandi Dalam, Lemari Pakaian, Meja Belajar, WiFi Cepat" 
                                  class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="description" x-model="roomForm.description" placeholder="Dekat dapur bersama / Jendela hadap timur" 
                               class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                    </div>

                    <div class="pt-4 border-t border-line flex items-center justify-end space-x-3">
                        <button type="button" @click="modalOpen = false" 
                                class="px-4 py-2 rounded-xl border border-line text-xs font-semibold text-ink-soft hover:bg-canvas">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-5 py-2 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs">
                            Simpan Data Kamar
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection
