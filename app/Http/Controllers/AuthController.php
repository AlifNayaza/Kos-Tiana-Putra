<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

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

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
            ])->status(429);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Penghuni yang sudah checkout tidak boleh masuk lagi.
            if (!$user->canAccessApp()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'Akun ini sudah tidak aktif. Hubungi pengelola kos bila ada kekeliruan.',
                ]);
            }

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return $this->redirectByRole($user->role)
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

        throw ValidationException::withMessages([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ]);
    }

    /**
     * Masuk cepat sebagai akun demo. Hanya untuk development: aktif di environment
     * "local" atau saat KOS_DEMO_LOGIN=true, selain itu rute ini tidak ada (404).
     */
    public function quickLogin(Request $request, string $role)
    {
        abort_unless(config('kos.demo_login'), 404);

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
