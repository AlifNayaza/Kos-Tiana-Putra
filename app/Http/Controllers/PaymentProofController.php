<?php

namespace App\Http\Controllers;

use App\Models\RentBill;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentProofController extends Controller
{
    /**
     * Tampilkan bukti pembayaran. Hanya pengelola dan penghuni pemilik tagihan yang boleh melihat.
     */
    public function show(RentBill $bill)
    {
        $user = Auth::user();

        $isOwner = $user->role === 'penghuni'
            && $bill->tenantProfile
            && $bill->tenantProfile->user_id === $user->id;

        abort_unless($user->role === 'pengelola' || $isOwner, 403, 'Anda tidak berhak melihat bukti pembayaran ini.');
        abort_if(empty($bill->payment_proof), 404);

        // Disk privat lebih dulu; disk publik hanya untuk berkas lama sebelum migrasi ke disk privat.
        foreach (array_unique([config('kos.upload.proof_disk'), 'public']) as $disk) {
            if (Storage::disk($disk)->exists($bill->payment_proof)) {
                return Storage::disk($disk)->response($bill->payment_proof, null, [
                    'Cache-Control'          => 'private, max-age=300',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404);
    }
}
