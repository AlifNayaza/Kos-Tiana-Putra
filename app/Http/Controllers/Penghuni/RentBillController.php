<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\RentBill;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RentBillController extends Controller
{
    public const PAYMENT_METHODS = ['Transfer BCA', 'QRIS', 'Tunai / Cash', 'Transfer Bank Lain'];

    public function __construct(private NotificationService $notifier)
    {
    }

    /**
     * Tampilkan halaman tagihan sewa kamar dan riwayat pembayaran (FR-04).
     */
    public function index()
    {
        $user = Auth::user();
        $tenantProfile = $user->tenantProfile()->with('room')->first();
        $room = $tenantProfile?->room;

        if (!$tenantProfile) {
            return redirect()->route('penghuni.beranda')
                ->with('error', 'Profil kamar Anda belum terdaftar aktif.');
        }

        // Ambil tagihan bulan berjalan
        $activeBill = RentBill::where('tenant_profile_id', $tenantProfile->id)
            ->where('year', Carbon::now()->year)
            ->where('month', Carbon::now()->month)
            ->first();

        // Jika tidak ada tagihan bulan ini, ambil tagihan yang belum lunas
        if (!$activeBill) {
            $activeBill = RentBill::where('tenant_profile_id', $tenantProfile->id)
                ->whereIn('status', ['menunggu', 'menunggak'])
                ->latest('due_date')
                ->first();
        }

        // Jika semua lunas, ambil tagihan terakhir
        if (!$activeBill) {
            $activeBill = RentBill::where('tenant_profile_id', $tenantProfile->id)
                ->latest('id')
                ->first();
        }

        // Seluruh riwayat tagihan sewa
        $billsHistory = RentBill::where('tenant_profile_id', $tenantProfile->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $paymentInfo = config('kos.payment');

        return view('penghuni.bills', compact('user', 'tenantProfile', 'room', 'activeBill', 'billsHistory', 'paymentInfo'));
    }

    /**
     * Unggah bukti transfer sewa kamar untuk konfirmasi manual pengelola (FR-05).
     * File disimpan di disk privat dan hanya dapat dilihat lewat route yang diotorisasi.
     */
    public function uploadProof(Request $request, $id)
    {
        $user = Auth::user();
        $tenantProfile = $user->tenantProfile;

        if (!$tenantProfile) {
            return redirect()->route('penghuni.beranda')
                ->with('error', 'Profil kamar Anda belum terdaftar aktif.');
        }

        $bill = RentBill::where('tenant_profile_id', $tenantProfile->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($bill->status === 'lunas') {
            return redirect()->route('penghuni.tagihan')
                ->with('info', "Tagihan sewa bulan {$bill->period_label} sudah lunas. Tidak perlu mengunggah bukti lagi.");
        }

        $request->validate([
            'payment_method' => ['required', 'string', Rule::in(self::PAYMENT_METHODS)],
            'payment_proof'  => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'notes'          => ['nullable', 'string', 'max:255'],
        ]);

        $disk = config('kos.upload.proof_disk');
        $dir = config('kos.upload.proof_dir');
        $file = $request->file('payment_proof');

        // Ekstensi diambil dari isi file, bukan dari nama berkas kiriman klien.
        $fileName = 'bukti_' . $bill->bill_code . '_' . bin2hex(random_bytes(8)) . '.' . $file->extension();
        $filePath = $file->storeAs($dir, $fileName, $disk);

        $oldProof = $bill->payment_proof;

        DB::transaction(function () use ($bill, $request, $filePath) {
            $bill->update([
                'payment_method' => $request->input('payment_method'),
                'payment_proof'  => $filePath,
                'payment_date'   => now(),
                'notes'          => $request->input('notes') ?: 'Bukti pembayaran diunggah oleh penghuni.',
            ]);
        });

        // Hapus berkas lama agar tidak menumpuk (cek disk privat maupun disk publik lama).
        if ($oldProof && $oldProof !== $filePath) {
            foreach (array_unique([$disk, 'public']) as $d) {
                Storage::disk($d)->delete($oldProof);
            }
        }

        $roomNumber = $tenantProfile->room ? $tenantProfile->room->room_number : 'Penghuni';

        $this->notifier->notifyRole(
            'pengelola',
            "Bukti Pembayaran Sewa dari {$roomNumber}",
            "Penghuni {$roomNumber} mengunggah bukti pembayaran sewa {$bill->period_label} ({$bill->formatted_amount}). Mohon verifikasi.",
            'rent',
            route('pengelola.tagihan.index'),
        );

        $this->notifier->notify(
            $user->id,
            'Bukti Pembayaran Telah Diunggah',
            "Bukti pembayaran sewa bulan {$bill->period_label} berhasil dikirim. Pengelola akan segera memverifikasi pelunasan.",
            'rent',
            route('penghuni.tagihan'),
        );

        return redirect()->route('penghuni.tagihan')
            ->with('success', "Bukti pembayaran sewa bulan {$bill->period_label} berhasil diunggah! Pengelola akan segera memeriksa dan mengonfirmasi.");
    }
}
