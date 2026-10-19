<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
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

        if (!in_array($user->role, $roles)) {
            // Redirect sesuai role yang valid jika mencoba akses halaman role lain
            return match ($user->role) {
                'pengelola' => redirect()->route('pengelola.dashboard')->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk membuka halaman tersebut.'),
                'petugas' => redirect()->route('petugas.antrean')->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk membuka halaman tersebut.'),
                'penghuni' => redirect()->route('penghuni.beranda')->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk membuka halaman tersebut.'),
                default => abort(403, 'Akses tidak diizinkan untuk peran ini.'),
            };
        }

        return $next($request);
    }
}
