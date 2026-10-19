<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();
            return $this->redirectByRole($user->role)
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        throw ValidationException::withMessages([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ]);
    }

    public function quickLogin(Request $request, string $role)
    {
        $roleMap = [
            'pengelola' => 'pengelola@kostiana.com',
            'petugas'   => 'petugas@kostiana.com',
            'penghuni'  => 'penghuni@kostiana.com',
        ];

        if (!isset($roleMap[$role])) {
            return redirect()->route('login')->with('error', 'Role tidak valid.');
        }

        $user = User::where('email', $roleMap[$role])->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Akun demo untuk peran tersebut belum tersedia.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user->role)
            ->with('success', 'Berhasil masuk sebagai demo ' . ucfirst($user->role) . ' (' . $user->name . ')');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }

    private function redirectByRole(string $role)
    {
        return match ($role) {
            'pengelola' => redirect()->route('pengelola.dashboard'),
            'petugas'   => redirect()->route('petugas.antrean'),
            'penghuni'  => redirect()->route('penghuni.beranda'),
            default     => redirect('/'),
        };
    }
}
