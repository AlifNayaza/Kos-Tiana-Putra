<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Halaman profil + form ganti kata sandi untuk semua peran.
     */
    public function show()
    {
        $user = Auth::user();

        return view($user->role . '.profil', [
            'user' => $user,
            'tenantProfile' => $user->role === 'penghuni' ? $user->tenantProfile()->with('room')->first() : null,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.']);
        }

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['password' => 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.']);
        }

        $user->forceFill([
            'password'             => $validated['password'], // di-hash otomatis oleh cast "hashed"
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route($user->role . '.profil')
            ->with('success', 'Kata sandi berhasil diganti.');
    }
}
