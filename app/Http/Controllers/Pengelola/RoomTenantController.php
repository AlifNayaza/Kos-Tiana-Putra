<?php

namespace App\Http\Controllers\Pengelola;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RoomTenantController extends Controller
{
    // =========================================================================
    // MODUL DATA KAMAR (FR-03)
    // =========================================================================

    /**
     * Tampilkan daftar seluruh kamar kos beserta status hunian.
     */
    public function roomsIndex(Request $request)
    {
        $statusFilter = $request->query('status');
        $floorFilter = $request->query('floor');

        $query = Room::with('activeTenantProfile.user');

        if ($statusFilter && $statusFilter !== 'semua') {
            $query->where('status', $statusFilter);
        }

        if ($floorFilter) {
            $query->where('floor', $floorFilter);
        }

        $rooms = $query->orderBy('floor')->orderBy('room_number')->get();

        $stats = [
            'total'     => Room::count(),
            'terisi'    => Room::where('status', 'terisi')->count(),
            'tersedia'  => Room::where('status', 'tersedia')->count(),
            'perbaikan' => Room::where('status', 'perbaikan')->count(),
        ];

        return view('pengelola.kamar.index', compact('rooms', 'stats', 'statusFilter', 'floorFilter'));
    }

    /**
     * Tambah data kamar baru.
     */
    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'room_number'   => ['required', 'string', 'max:50', 'unique:rooms,room_number'],
            'floor'         => ['required', 'integer', 'min:1', 'max:10'],
            'type'          => ['required', 'string', 'max:100'],
            'monthly_price' => ['required', 'integer', 'min:100000'],
            'facilities'    => ['required', 'string'],
            'status'        => ['required', 'in:tersedia,terisi,perbaikan'],
            'size'          => ['nullable', 'string', 'max:50'],
            'description'   => ['nullable', 'string'],
        ]);

        Room::create($validated);

        return redirect()->route('pengelola.kamar.index')
            ->with('success', "Kamar {$validated['room_number']} berhasil ditambahkan ke daftar.");
    }

    /**
     * Perbarui data kamar.
     */
    public function updateRoom(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_number'   => ['required', 'string', 'max:50', Rule::unique('rooms')->ignore($room->id)],
            'floor'         => ['required', 'integer', 'min:1', 'max:10'],
            'type'          => ['required', 'string', 'max:100'],
            'monthly_price' => ['required', 'integer', 'min:100000'],
            'facilities'    => ['required', 'string'],
            'status'        => ['required', 'in:tersedia,terisi,perbaikan'],
            'size'          => ['nullable', 'string', 'max:50'],
            'description'   => ['nullable', 'string'],
        ]);

        $room->update($validated);

        return redirect()->route('pengelola.kamar.index')
            ->with('success', "Informasi kamar {$room->room_number} berhasil diperbarui.");
    }

    /**
     * Hapus kamar jika tidak sedang berpenghuni.
     */
    public function destroyRoom(Room $room)
    {
        if ($room->status === 'terisi' || $room->activeTenantProfile) {
            return redirect()->back()
                ->with('error', "Kamar {$room->room_number} tidak dapat dihapus karena masih berstatus terisi penghuni.");
        }

        $roomNumber = $room->room_number;
        $room->delete();

        return redirect()->route('pengelola.kamar.index')
            ->with('success', "Kamar {$roomNumber} telah berhasil dihapus dari sistem.");
    }

    // =========================================================================
    // MODUL DATA PENGHUNI KOS (FR-02)
    // =========================================================================

    /**
     * Tampilkan daftar penghuni aktif dan riwayat penghuni.
     */
    public function tenantsIndex(Request $request)
    {
        $status = $request->query('status', 'aktif');
        $search = $request->query('q');

        $query = TenantProfile::with(['user', 'room']);

        if ($status === 'aktif') {
            $query->where('is_active', true);
        } elseif ($status === 'riwayat') {
            $query->where('is_active', false);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('room', function ($rq) use ($search) {
                    $rq->where('room_number', 'like', "%{$search}%");
                });
            });
        }

        $tenants = $query->latest('start_date')->get();

        // Ambil daftar kamar yang tersedia untuk pendaftaran penghuni baru
        $availableRooms = Room::where('status', 'tersedia')->orderBy('room_number')->get();

        return view('pengelola.penghuni.index', compact('tenants', 'availableRooms', 'status', 'search'));
    }

    /**
     * Daftarkan penghuni baru dan alokasikan kamar (FR-02).
     */
    public function storeTenant(Request $request)
    {
        $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'                   => ['required', 'string', 'max:20'],
            'room_id'                 => ['required', 'exists:rooms,id'],
            'id_card_number'          => ['nullable', 'string', 'max:30'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'occupation'              => ['nullable', 'string', 'max:255'],
            'institution'             => ['nullable', 'string', 'max:255'],
            'start_date'              => ['required', 'date'],
        ]);

        DB::transaction(function () use ($request) {
            // 1. Buat akun user penghuni
            $user = User::create([
                'name'              => $request->input('name'),
                'email'             => $request->input('email'),
                'phone'             => $request->input('phone'),
                'role'              => 'penghuni',
                'password'          => Hash::make('password'), // default password
                'email_verified_at' => now(),
            ]);

            // 2. Buat profil penghuni
            TenantProfile::create([
                'user_id'                 => $user->id,
                'room_id'                 => $request->input('room_id'),
                'id_card_number'          => $request->input('id_card_number'),
                'emergency_contact_name'  => $request->input('emergency_contact_name'),
                'emergency_contact_phone' => $request->input('emergency_contact_phone'),
                'occupation'              => $request->input('occupation'),
                'institution'             => $request->input('institution'),
                'start_date'              => $request->input('start_date'),
                'is_active'               => true,
                'notes'                   => 'Terdaftar resmi melalui sistem Kos Tiana Putra 1',
            ]);

            // 3. Otomatis ubah status kamar menjadi 'terisi'
            Room::where('id', $request->input('room_id'))->update(['status' => 'terisi']);
        });

        return redirect()->route('pengelola.penghuni.index')
            ->with('success', "Penghuni baru {$request->input('name')} berhasil didaftarkan dan ditempatkan di kamar.");
    }

    /**
     * Perbarui data identitas & kontak penghuni.
     */
    public function updateTenant(Request $request, TenantProfile $tenant)
    {
        $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'email', 'max:255', Rule::unique('users')->ignore($tenant->user_id)],
            'phone'                   => ['required', 'string', 'max:20'],
            'id_card_number'          => ['nullable', 'string', 'max:30'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'occupation'              => ['nullable', 'string', 'max:255'],
            'institution'             => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $tenant) {
            $tenant->user->update([
                'name'  => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ]);

            $tenant->update([
                'id_card_number'          => $request->input('id_card_number'),
                'emergency_contact_name'  => $request->input('emergency_contact_name'),
                'emergency_contact_phone' => $request->input('emergency_contact_phone'),
                'occupation'              => $request->input('occupation'),
                'institution'             => $request->input('institution'),
            ]);
        });

        return redirect()->route('pengelola.penghuni.index')
            ->with('success', "Data penghuni {$tenant->user->name} berhasil diperbarui.");
    }

    /**
     * Checkout / Nonaktifkan penghuni dan kosongkan kembali kamar kos.
     */
    public function checkoutTenant(Request $request, TenantProfile $tenant)
    {
        DB::transaction(function () use ($tenant) {
            $roomId = $tenant->room_id;

            $tenant->update([
                'is_active' => false,
                'end_date'  => now()->toDateString(),
                'notes'     => trim(($tenant->notes ? $tenant->notes . ' • ' : '') . 'Checkout pada ' . now()->format('d/m/Y')),
            ]);

            // Kembalikan status kamar menjadi 'tersedia'
            if ($roomId) {
                Room::where('id', $roomId)->update(['status' => 'tersedia']);
            }
        });

        return redirect()->route('pengelola.penghuni.index')
            ->with('success', "Penghuni {$tenant->user->name} berhasil di-checkout. Kamar kembali berstatus Tersedia.");
    }
}
