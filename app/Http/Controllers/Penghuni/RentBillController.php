<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\RentBill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentBillController extends Controller
{
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
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $activeBill = RentBill::where('tenant_profile_id', $tenantProfile->id)
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
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

        // Informasi rekening pembayaran resmi Kos Tiana Putra 1
        $paymentInfo = [
            'bank_name'     => 'Bank Central Asia (BCA)',
            'account_no'    => '7700-8899-21',
            'account_name'  => 'H. Tiana Sutisna',
            'qris_name'     => 'KOS TIANA PUTRA 1',
            'whatsapp_cs'   => '0812-3456-7890',
            'owner_name'    => 'Pak H. Tiana - Pengelola',
        ];

        return view('penghuni.bills', compact('user', 'tenantProfile', 'room', 'activeBill', 'billsHistory', 'paymentInfo'));
    }

    /**
     * Unggah bukti transfer sewa kamar untuk konfirmasi manual pengelola (FR-05).
     */
    public function uploadProof(Request $request, $id)
    {
        $user = Auth::user();
        $tenantProfile = $user->tenantProfile;

        $bill = RentBill::where('tenant_profile_id', $tenantProfile->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'payment_method' => ['required', 'string'],
            'payment_proof'  => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'notes'          => ['nullable', 'string', 'max:255'],
        ]);

        $filePath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $fileName = 'bukti_' . $bill->bill_code . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('payment_proofs', $fileName, 'public');
        }

        $bill->update([
            'payment_method' => $request->input('payment_method'),
            'payment_proof'  => $filePath,
            'payment_date'   => now(),
            'notes'          => $request->input('notes') ?: 'Bukti pembayaran diunggah oleh penghuni.',
        ]);

        // Notifikasi ke Pengelola
        $pengelolaList = User::where('role', 'pengelola')->get();
        $roomNumber = $tenantProfile->room ? $tenantProfile->room->room_number : 'Penghuni';
        foreach ($pengelolaList as $pengelola) {
            AppNotification::create([
                'user_id'    => $pengelola->id,
                'title'      => "Bukti Pembayaran Sewa dari {$roomNumber}",
                'message'    => "Penghuni {$roomNumber} mengunggah bukti pembayaran sewa {$bill->period_label} ({$bill->formatted_amount}). Mohon verifikasi.",
                'type'       => 'rent',
                'action_url' => route('pengelola.tagihan.index'),
                'is_read'    => false,
            ]);
        }

        // Notifikasi ke Penghuni
        AppNotification::create([
            'user_id'    => $user->id,
            'title'      => 'Bukti Pembayaran Telah Diunggah',
            'message'    => "Bukti pembayaran sewa bulan {$bill->period_label} berhasil dikirim. Pengelola akan segera memverifikasi pelunasan.",
            'type'       => 'rent',
            'action_url' => route('penghuni.tagihan'),
            'is_read'    => false,
        ]);

        return redirect()->route('penghuni.tagihan')
            ->with('success', "Bukti pembayaran sewa bulan {$bill->period_label} berhasil diunggah! Pengelola akan segera memeriksa dan mengonfirmasi.");
    }
}
