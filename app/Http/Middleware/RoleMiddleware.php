<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    private const DENIED = 'Akses ditolak: Anda tidak memiliki wewenang untuk membuka halaman tersebut.';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan masuk ke akun Anda terlebih dahulu.');
        }

        $user = Auth::user();

        // Akun penghuni yang sudah checkout dikeluarkan walau sesinya masih berjalan.
        if (!$user->canAccessApp()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akun ini sudah tidak aktif. Hubungi pengelola kos bila ada kekeliruan.');
        }

        if (!in_array($user->role, $roles)) {
            // Redirect sesuai role yang valid jika mencoba akses halaman role lain
            return match ($user->role) {
                'pengelola' => redirect()->route('pengelola.dashboard')->with('error', self::DENIED),
                'petugas' => redirect()->route('petugas.antrean')->with('error', self::DENIED),
                'penghuni' => redirect()->route('penghuni.beranda')->with('error', self::DENIED),
                default => abort(403, 'Akses tidak diizinkan untuk peran ini.'),
            };
        }

        // Kata sandi sementara wajib diganti sebelum memakai fitur lain.
        if ($user->must_change_password && !$request->routeIs('*.profil')) {
            return redirect()->route($user->role . '.profil')
                ->with('warning', 'Demi keamanan, ganti kata sandi sementara Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
