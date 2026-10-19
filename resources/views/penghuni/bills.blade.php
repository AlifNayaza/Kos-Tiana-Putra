@extends('layouts.mobile')

@section('content')
<div x-data="{
    uploadModalOpen: false,
    copiedAccount: false,
    copyAccount() {
        navigator.clipboard.writeText('{{ $paymentInfo['account_no'] }}');
        this.copiedAccount = true;
        setTimeout(() => this.copiedAccount = false, 2000);
    }
}" class="space-y-4">

    <!-- Header Halaman -->
    <div class="flex items-center justify-between pb-1">
        <div>
            <h1 class="text-base sm:text-lg font-bold text-ink">Tagihan Sewa</h1>
            <p class="text-xs text-ink-soft">Tagihan sewa kamar dan riwayat pembayaran resmi</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- ============================================================== -->
        <!-- KOLOM KIRI: TAGIHAN BERJALAN & RIWAYAT (lg:col-span-7/8)       -->
        <!-- ============================================================== -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-5">
            
            @if($activeBill)
                <!-- Kartu Nota Tagihan Berjalan -->
                <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs space-y-4">
                    
                    <!-- Baris Atas Nota: ID & Status Badge -->
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-line">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-ink">{{ $activeBill->bill_code }}</span>
                                <x-status-badge :status="$activeBill->effective_status" :label="$activeBill->status_badge['label']" />
                            </div>
                            <h2 class="text-sm sm:text-base font-bold text-ink mt-1">
                                Tagihan Sewa {{ $activeBill->period_label }}
                            </h2>
                            <p class="text-xs text-ink-soft mt-0.5">
                                {{ $room ? $room->room_number . ' (' . $room->type . ')' : 'Kamar Penghuni' }}
                            </p>
                        </div>

                        <div class="text-right">
                            <span class="text-[11px] text-ink-faint block font-medium">Jatuh Tempo</span>
                            <span class="text-xs font-semibold {{ $activeBill->is_overdue ? 'text-alert' : 'text-ink' }}">
                                {{ $activeBill->due_date ? $activeBill->due_date->translatedFormat('d F Y') : '-' }}
                            </span>
                        </div>
                    </div>

                    <!-- Rincian Biaya Nota (Garis Putus-Putus Sebelum Total) -->
                    <div class="bg-canvas rounded-xl p-3.5 border border-line space-y-2 text-xs">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-ink-soft">Sewa Kamar & Fasilitas:</span>
                            <span class="font-semibold text-ink tabular-nums whitespace-nowrap">
                                {{ $activeBill->formatted_amount }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-ink-soft">WiFi & Air Bersih:</span>
                            <span class="font-semibold text-done">Termasuk</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-ink-soft">Kebersihan Lingkungan:</span>
                            <span class="font-semibold text-done">Termasuk</span>
                        </div>

                        <!-- Garis Pemisah Putus-Putus Khas Nota -->
                        <div class="border-t border-dashed border-line my-2 pt-2 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-ink block">Total Tagihan</span>
                                @if($activeBill->is_overdue)
                                    <span class="text-[11px] font-semibold text-alert">
                                        Menunggak {{ $activeBill->days_overdue > 0 ? $activeBill->days_overdue . ' hari' : '' }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-base sm:text-lg font-bold text-ink tabular-nums whitespace-nowrap">
                                {{ $activeBill->formatted_amount }}
                            </span>
                        </div>
                    </div>

                    <!-- Status Tindakan Pembayaran -->
                    <div class="pt-1">
                        @if($activeBill->is_paid)
                            <div class="p-3 bg-done-wash rounded-xl border border-line flex items-start gap-2.5 text-ink text-xs">
                                <svg class="w-4 h-4 text-done flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="font-bold">Tagihan telah lunas terverifikasi</p>
                                    <p class="text-[11px] text-ink-soft mt-0.5">
                                        Diverifikasi pada {{ $activeBill->payment_date ? $activeBill->payment_date->translatedFormat('d M Y, H:i') : '-' }} &bull; Metode: {{ $activeBill->payment_method ?? 'Transfer' }}
                                    </p>
                                </div>
                            </div>
                        @elseif($activeBill->is_waiting_verification)
                            <div class="space-y-3">
                                <div class="p-3 bg-wait-wash rounded-xl border border-line flex items-start gap-2.5 text-ink text-xs">
                                    <svg class="w-4 h-4 text-wait flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="font-bold">Bukti pembayaran sedang dicek pengelola</p>
                                        <p class="text-[11px] text-ink-soft mt-0.5">
                                            Bukti transfer telah dikirim. Pengelola akan mengonfirmasi status pelunasan dalam 1x24 jam.
                                        </p>
                                    </div>
                                </div>
                                <button type="button" 
                                        @click="uploadModalOpen = true"
                                        class="min-h-[44px] w-full border border-line text-ink-soft hover:bg-canvas rounded-xl text-xs font-semibold transition-colors flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    <span>Unggah Ulang Bukti Pembayaran</span>
                                </button>
                            </div>
                        @else
                            <!-- Tombol Aksi Utama: Unggah Bukti -->
                            <button type="button" 
                                    @click="uploadModalOpen = true"
                                    class="min-h-[44px] w-full bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <span>Unggah Bukti Pembayaran</span>
                            </button>
                        @endif
                    </div>

                </div>
            @endif

            <!-- Riwayat Tagihan (Daftar Rapat) -->
            <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs space-y-3">
                <h3 class="text-xs font-bold text-ink-soft uppercase tracking-wider">
                    Riwayat Tagihan Sewa
                </h3>

                <div class="divide-y divide-line text-xs">
                    @forelse($billsHistory as $bill)
                        <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-ink">{{ $bill->period_label }}</span>
                                    <span class="font-mono text-[10px] text-ink-faint select-all">{{ $bill->bill_code }}</span>
                                </div>
                                <p class="text-[11px] text-ink-soft mt-0.5">
                                    Jatuh tempo: {{ $bill->due_date ? $bill->due_date->translatedFormat('d M Y') : '-' }}
                                    @if($bill->payment_date)
                                        &bull; Bayar: {{ $bill->payment_date->translatedFormat('d/m/Y') }}
                                    @endif
                                </p>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <span class="font-semibold text-ink tabular-nums whitespace-nowrap block">
                                    {{ $bill->formatted_amount }}
                                </span>
                                <div class="mt-0.5">
                                    <x-status-badge :status="$bill->effective_status" :label="$bill->status_badge['label']" />
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-ink-faint">
                            Belum ada riwayat tagihan sewa.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- ============================================================== -->
        <!-- KOLOM KANAN: REKENING RESMI & BANTUAN (lg:col-span-5/4)        -->
        <!-- ============================================================== -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-4 lg:sticky lg:top-20">
            
            <!-- Kotak Info Rekening Berborder Biasa (Bukan Kartu Kredit Gradasi) -->
            <div class="bg-surface rounded-xl border border-line p-4 sm:p-5 text-ink shadow-xs space-y-3">
                <span class="text-[11px] font-bold text-ink-faint uppercase tracking-wider block">
                    Rekening Pembayaran Resmi
                </span>

                <div class="bg-canvas rounded-xl p-3.5 border border-line space-y-2">
                    <span class="text-xs font-semibold text-ink-soft block">
                        {{ $paymentInfo['bank_name'] }}
                    </span>

                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-base sm:text-lg font-bold text-ink tracking-wider tabular-nums select-all">
                            {{ $paymentInfo['account_no'] }}
                        </span>

                        <button type="button" 
                                @click="copyAccount()" 
                                class="min-h-[44px] min-w-[44px] px-3 py-1.5 border border-line bg-surface hover:bg-canvas rounded-xl text-xs font-semibold text-ink-soft transition-colors flex items-center justify-center">
                            <span x-text="copiedAccount ? 'Tersalin' : 'Salin'"></span>
                        </button>
                    </div>

                    <p class="text-xs text-ink-soft">
                        a.n. <strong class="text-ink font-semibold">{{ $paymentInfo['account_name'] }}</strong>
                    </p>
                </div>

                <!-- Langkah Bayar Singkat -->
                <div class="pt-2 border-t border-line text-xs text-ink-soft space-y-2">
                    <span class="font-semibold text-ink text-[11px] uppercase tracking-wider block">
                        Petunjuk Transfer:
                    </span>
                    <ol class="list-decimal list-inside space-y-1 text-[11px] leading-relaxed text-ink-soft">
                        <li>Transfer nominal tagihan ke rekening BCA di atas.</li>
                        <li>Simpan tangkapan layar atau struk bukti transfer.</li>
                        <li>Klik <strong>Unggah Bukti Pembayaran</strong> di samping.</li>
                    </ol>
                </div>
            </div>

            <!-- Kontak Bantuan WhatsApp (Tanpa Tanda Kurung Ganda) -->
            <div class="bg-surface rounded-xl border border-line p-4 text-xs shadow-xs space-y-2">
                <span class="text-[11px] font-bold text-ink-faint uppercase tracking-wider block">
                    Bantuan Pembayaran
                </span>
                <p class="text-ink-soft leading-relaxed text-[11px]">
                    Jika membutuhkan bantuan atau verifikasi manual, hubungi pengelola via WhatsApp:
                </p>
                <div class="flex items-center justify-between pt-1">
                    <div class="min-w-0 pr-2">
                        <span class="font-bold text-ink block truncate">{{ $paymentInfo['whatsapp_cs'] }}</span>
                        <span class="text-[11px] text-ink-soft truncate block">{{ $paymentInfo['owner_name'] }}</span>
                    </div>
                    <a href="https://wa.me/6281234567890?text={{ urlencode('Halo Pak H. Tiana, saya penghuni ' . ($room ? $room->room_number : '') . ' ingin konfirmasi pembayaran sewa.') }}" 
                       target="_blank" 
                       rel="noopener"
                       class="min-h-[44px] px-3.5 py-1.5 border border-line rounded-xl text-xs font-semibold text-brand hover:bg-canvas transition-colors flex items-center justify-center flex-shrink-0">
                        Chat WhatsApp
                    </a>
                </div>
            </div>

        </div>

    </div>

    <!-- ============================================================== -->
    <!-- MODAL / BOTTOM SHEET UNGGAH BUKTI PEMBAYARAN                   -->
    <!-- ============================================================== -->
    @if($activeBill)
        <x-bottom-sheet show="uploadModalOpen" title="Unggah Bukti Pembayaran">
            <form action="{{ route('penghuni.tagihan.upload_proof', $activeBill->id) }}" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  class="space-y-4">
                @csrf

                <div class="p-3 bg-canvas rounded-xl border border-line text-xs">
                    <span class="text-ink-soft block">Tagihan:</span>
                    <span class="font-bold text-ink block mt-0.5">
                        {{ $activeBill->period_label }} &bull; {{ $activeBill->formatted_amount }}
                    </span>
                </div>

                <!-- Metode Pembayaran -->
                <div>
                    <label class="block text-xs font-semibold text-ink-soft mb-1">Metode Pembayaran</label>
                    <select name="payment_method" 
                            class="w-full text-xs px-3.5 py-2.5 border border-line rounded-xl text-ink focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface">
                        <option value="Transfer BCA">Transfer Bank BCA (7700-8899-21)</option>
                        <option value="QRIS">QRIS Kos Tiana Putra 1</option>
                        <option value="Tunai / Cash">Tunai Langsung ke Pengelola</option>
                        <option value="Transfer Bank Lain">Transfer Bank Lain / Antar Bank</option>
                    </select>
                </div>

                <!-- Upload File Bukti Transfer -->
                <div>
                    <label class="block text-xs font-semibold text-ink-soft mb-1">Foto / Bukti Transfer</label>
                    <input type="file" 
                           name="payment_proof" 
                           accept="image/jpeg,image/png,image/jpg,image/webp" 
                           required 
                            class="block w-full text-xs text-ink-soft file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-canvas file:text-ink-soft hover:file:bg-line cursor-pointer border border-line rounded-xl p-1 bg-surface">
                    <span class="text-[10px] text-ink-faint mt-1 block">Format JPG, PNG, atau WEBP (maksimal 4MB).</span>
                </div>

                <!-- Catatan Opsional -->
                <div>
                    <label class="block text-xs font-semibold text-ink-soft mb-1">Catatan Tambahan (Opsional)</label>
                    <input type="text" 
                           name="notes" 
                           placeholder="Contoh: Transfer atas nama Dimas Pratama" 
                           class="w-full text-xs px-3.5 py-2.5 border border-line rounded-xl text-ink placeholder-ink-faint focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface">
                </div>

                <!-- Tombol Aksi -->
                <div class="pt-3 border-t border-line flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="uploadModalOpen = false" 
                            class="min-h-[44px] px-4 py-2 border border-line rounded-xl text-xs font-semibold text-ink-soft hover:bg-canvas transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="min-h-[44px] px-5 py-2 bg-brand hover:bg-brand-deep text-surface rounded-xl text-xs font-semibold transition-colors">
                        Kirim Bukti
                    </button>
                </div>
            </form>
        </x-bottom-sheet>
    @endif

</div>
@endsection
