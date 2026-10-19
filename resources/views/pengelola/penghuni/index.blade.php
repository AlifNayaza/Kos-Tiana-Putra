@extends('layouts.app')

@section('page_title', 'Manajemen Data Penghuni')
@section('page_subtitle', 'Pendaftaran penghuni baru, data kontak darurat, dan alokasi kamar kos')

@section('content')
<div class="space-y-6" x-data="{ 
    createModalOpen: false,
    editModalOpen: false,
    editUrl: '',
    editForm: {
        name: '',
        email: '',
        phone: '',
        id_card_number: '',
        occupation: '',
        institution: '',
        emergency_contact_name: '',
        emergency_contact_phone: ''
    },
    openEdit(tenant) {
        this.editUrl = `/pengelola/penghuni/${tenant.id}`;
        this.editForm = {
            name: tenant.user ? tenant.user.name : '',
            email: tenant.user ? tenant.user.email : '',
            phone: tenant.user ? tenant.user.phone : '',
            id_card_number: tenant.id_card_number || '',
            occupation: tenant.occupation || '',
            institution: tenant.institution || '',
            emergency_contact_name: tenant.emergency_contact_name || '',
            emergency_contact_phone: tenant.emergency_contact_phone || ''
        };
        this.editModalOpen = true;
    }
}">

    <!-- Filter & Action Header -->
    <div class="bg-surface p-4 sm:p-5 rounded-2xl border border-line shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Tab Filter & Search -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="inline-flex p-1 bg-canvas rounded-xl">
                <a href="{{ route('pengelola.penghuni.index', ['status' => 'aktif']) }}" 
                   class="px-4 py-2 text-xs font-bold rounded-lg transition-colors {{ $status === 'aktif' ? 'bg-surface text-ink shadow-xs' : 'text-ink-soft hover:text-ink' }}">
                    Penghuni Aktif
                </a>
                <a href="{{ route('pengelola.penghuni.index', ['status' => 'riwayat']) }}" 
                   class="px-4 py-2 text-xs font-bold rounded-lg transition-colors {{ $status === 'riwayat' ? 'bg-surface text-ink shadow-xs' : 'text-ink-soft hover:text-ink' }}">
                    Riwayat Checkout
                </a>
            </div>

            <form method="GET" action="{{ route('pengelola.penghuni.index') }}" class="relative">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, kamar, telp..." 
                           class="text-xs rounded-xl border border-line text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none pl-8 pr-4 py-2 bg-canvas w-full sm:w-64">
                    <svg class="w-4 h-4 text-ink-faint absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </form>
        </div>

        <!-- Button: Daftarkan Penghuni Baru -->
        <button type="button" 
                @click="createModalOpen = true" 
                class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span>Daftarkan Penghuni Baru</span>
        </button>
    </div>

    <!-- Tenants Table List -->
    <div class="bg-surface rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-canvas border-b border-line text-ink-soft uppercase tracking-wider font-semibold">
                        <th class="py-3.5 px-4 sm:px-6">Nama & Kontak</th>
                        <th class="py-3.5 px-4">Kamar</th>
                        <th class="py-3.5 px-4">Identitas & Instansi</th>
                        <th class="py-3.5 px-4">Kontak Darurat</th>
                        <th class="py-3.5 px-4">Periode Huni</th>
                        <th class="py-3.5 px-4 sm:px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($tenants as $tenant)
                        <tr class="hover:bg-canvas transition-colors">
                            <!-- Nama & Kontak -->
                            <td class="py-4 px-4 sm:px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-brand-wash text-brand font-bold flex items-center justify-center text-xs flex-shrink-0">
                                        {{ substr($tenant->user->name ?? 'P', 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-ink leading-tight">{{ $tenant->user->name ?? 'Tanpa Nama' }}</p>
                                        <p class="text-ink-soft text-[11px] mt-0.5">{{ $tenant->user->phone ?? '-' }}</p>
                                        <p class="text-ink-faint text-[10px]">{{ $tenant->user->email ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Kamar -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($tenant->room)
                                    <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-brand-wash border border-brand/20 text-brand font-bold text-xs">
                                        {{ $tenant->room->room_number }}
                                    </div>
                                    <p class="text-[11px] text-ink-soft mt-1 tabular-nums">{{ $tenant->room->formatted_price }}/bln</p>
                                @else
                                    <span class="text-ink-faint italic">Tidak ada kamar</span>
                                @endif
                            </td>

                            <!-- Identitas & Instansi -->
                            <td class="py-4 px-4">
                                <div class="space-y-0.5">
                                    <p class="font-medium text-ink">{{ $tenant->occupation ?? 'Mahasiswa/Karyawan' }}</p>
                                    <p class="text-ink-soft text-[11px]">{{ $tenant->institution ?? '-' }}</p>
                                    <p class="text-ink-faint text-[10px] tabular-nums font-mono">NIK: {{ $tenant->id_card_number ?? '-' }}</p>
                                </div>
                            </td>

                            <!-- Kontak Darurat -->
                            <td class="py-4 px-4">
                                @if($tenant->emergency_contact_name)
                                    <div>
                                        <p class="font-semibold text-ink">{{ $tenant->emergency_contact_name }}</p>
                                        <p class="text-ink-soft text-[11px]">{{ $tenant->emergency_contact_phone }}</p>
                                    </div>
                                @else
                                    <span class="text-ink-faint italic">Belum diisi</span>
                                @endif
                            </td>

                            <!-- Periode Huni -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="text-ink">
                                    <span class="font-medium">Masuk:</span> {{ \Carbon\Carbon::parse($tenant->start_date)->format('d M Y') }}
                                </div>
                                @if(!$tenant->is_active && $tenant->end_date)
                                    <div class="text-alert text-[11px] mt-0.5 font-medium">
                                        Keluar: {{ \Carbon\Carbon::parse($tenant->end_date)->format('d M Y') }}
                                    </div>
                                @else
                                    <span class="inline-flex items-center mt-1 text-[10px] font-bold text-done bg-done-wash px-2 py-0.5 rounded-full border border-done/20">
                                        Aktif Menghuni
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <button type="button" 
                                            @click="openEdit({{ json_encode($tenant) }})" 
                                            class="text-xs font-semibold text-ink hover:text-brand bg-canvas hover:bg-brand-wash px-2.5 py-1.5 rounded-lg border border-line transition-colors">
                                        Edit
                                    </button>

                                    @if($tenant->is_active)
                                        <form action="{{ route('pengelola.penghuni.checkout', $tenant->id) }}" method="POST" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin memproses Checkout untuk {{ $tenant->user->name }}? Kamar {{ $tenant->room->room_number ?? '' }} akan otomatis berstatus Tersedia kembali.');">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-alert hover:text-alert bg-alert-wash hover:bg-alert-wash/80 px-2.5 py-1.5 rounded-lg border border-alert/20 transition-colors">
                                                Checkout
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-ink-faint text-xs">
                                Belum ada data penghuni kos yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form: Daftarkan Penghuni Baru (FR-02) -->
    <div x-show="createModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" @click="createModalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div x-show="createModalOpen" 
                 x-transition
                 class="relative bg-surface rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-line text-left">
                
                <div class="flex items-center justify-between pb-4 border-b border-line">
                    <div>
                        <h3 class="text-sm font-bold text-ink">Pendaftaran Penghuni Baru</h3>
                        <p class="text-xs text-ink-soft">Buat akun login & alokasikan kamar kos</p>
                    </div>
                    <button @click="createModalOpen = false" class="text-ink-faint hover:text-ink-soft p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('pengelola.penghuni.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Nama Lengkap Penghuni <span class="text-alert">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: Budi Santoso" 
                               class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Email (Untuk Login) <span class="text-alert">*</span></label>
                            <input type="email" name="email" required placeholder="budi@email.com" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Nomor WhatsApp / HP <span class="text-alert">*</span></label>
                            <input type="text" name="phone" required placeholder="08123456789" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Alokasi Kamar <span class="text-alert">*</span></label>
                            <select name="room_id" required class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                                <option value="">-- Pilih Kamar Tersedia --</option>
                                @foreach($availableRooms as $availRoom)
                                    <option value="{{ $availRoom->id }}">
                                        {{ $availRoom->room_number }} (Lt. {{ $availRoom->floor }}) — {{ $availRoom->formatted_price }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Mulai Huni <span class="text-alert">*</span></label>
                            <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">NIK KTP</label>
                            <input type="text" name="id_card_number" placeholder="16 digit NIK" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Pekerjaan</label>
                            <input type="text" name="occupation" placeholder="Mahasiswa / Karyawan Swasta" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Kampus / Instansi / Kantor</label>
                        <input type="text" name="institution" placeholder="Contoh: Universitas Brawijaya / PT Telkom" 
                               class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                    </div>

                    <div class="p-3 bg-canvas rounded-xl border border-line space-y-3">
                        <p class="text-[11px] font-bold text-ink uppercase tracking-wider">Kontak Darurat (Keluarga/Wali)</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-ink-soft mb-1">Nama Kontak Darurat</label>
                                <input type="text" name="emergency_contact_name" placeholder="Nama Orang Tua / Wali" 
                                       class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-ink-soft mb-1">Nomor Telepon Darurat</label>
                                <input type="text" name="emergency_contact_phone" placeholder="08..." 
                                       class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2">
                            </div>
                        </div>
                    </div>

                    <p class="text-[11px] text-ink-faint">
                        * Password default akun penghuni baru diset otomatis ke: <strong class="font-mono text-ink">password</strong>
                    </p>

                    <div class="pt-4 border-t border-line flex items-center justify-end space-x-3">
                        <button type="button" @click="createModalOpen = false" 
                                class="px-4 py-2 rounded-xl border border-line text-xs font-semibold text-ink-soft hover:bg-canvas">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-5 py-2 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs">
                            Simpan & Daftarkan Penghuni
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Modal Form: Edit Data Penghuni -->
    <div x-show="editModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" @click="editModalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div x-show="editModalOpen" 
                 x-transition
                 class="relative bg-surface rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-line text-left">
                
                <div class="flex items-center justify-between pb-4 border-b border-line">
                    <h3 class="text-sm font-bold text-ink">Perbarui Profil & Kontak Penghuni</h3>
                    <button @click="editModalOpen = false" class="text-ink-faint hover:text-ink-soft p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form :action="editUrl" method="POST" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Nama Lengkap <span class="text-alert">*</span></label>
                        <input type="text" name="name" x-model="editForm.name" required 
                               class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Email <span class="text-alert">*</span></label>
                            <input type="email" name="email" x-model="editForm.email" required 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Nomor WhatsApp / HP <span class="text-alert">*</span></label>
                            <input type="text" name="phone" x-model="editForm.phone" required 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">NIK KTP</label>
                            <input type="text" name="id_card_number" x-model="editForm.id_card_number" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Pekerjaan</label>
                            <input type="text" name="occupation" x-model="editForm.occupation" 
                                   class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink mb-1">Kampus / Instansi</label>
                        <input type="text" name="institution" x-model="editForm.institution" 
                               class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2.5">
                    </div>

                    <div class="p-3 bg-canvas rounded-xl border border-line space-y-3">
                        <p class="text-[11px] font-bold text-ink uppercase tracking-wider">Kontak Darurat</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-ink-soft mb-1">Nama Kontak Darurat</label>
                                <input type="text" name="emergency_contact_name" x-model="editForm.emergency_contact_name" 
                                       class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-ink-soft mb-1">Telepon Darurat</label>
                                <input type="text" name="emergency_contact_phone" x-model="editForm.emergency_contact_phone" 
                                       class="w-full text-xs rounded-xl border border-line bg-surface text-ink focus:border-brand focus:ring-2 focus:ring-brand-wash focus:outline-none p-2">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-line flex items-center justify-end space-x-3">
                        <button type="button" @click="editModalOpen = false" 
                                class="px-4 py-2 rounded-xl border border-line text-xs font-semibold text-ink-soft hover:bg-canvas">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-5 py-2 rounded-xl bg-brand hover:bg-brand-deep text-surface text-xs font-bold shadow-xs">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection
