<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test login page loads successfully with the new design elements.
     */
    public function test_login_page_renders_with_redesigned_ui_elements(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Branding & Hierarchy
        $response->assertSee('Kos Tiana Putra');
        $response->assertSee('Kopi hangat dan galon sampai depan pintu.');
        $response->assertSee('Dikelola langsung oleh Pak H. Tiana');
        $response->assertSee('Warkop buka');

        // Form Title & Warm Subtitle
        $response->assertSee('Masuk');
        $response->assertSee('Tagihan, pesanan, dan antrean menunggu di balik pintu ini.');

        // Inputs & Labels
        $response->assertSee('Alamat Email');
        $response->assertSee('Kata Sandi');
        $response->assertSee('nama@email.com');
        $response->assertSee('Lupa sandi?');
        $response->assertSee('Ingat saya di perangkat ini');

        // Verify generic brochure & obsolete defaults are removed
        $response->assertDontSee('Default: password');
        $response->assertDontSee('1-Click Masuk Demo Akun');
        $response->assertDontSee('Masuk ke Aplikasi');

        // Demo Role Switcher & Matching Corridor Spot Tags
        $response->assertSee('Coba tanpa akun');
        $response->assertSee('Pemilik');
        $response->assertSee('Pengelola');
        $response->assertSee('Kelola kamar, tagihan sewa bulanan, dan laporan');
        $response->assertSee('Warkop');
        $response->assertSee('Petugas');
        $response->assertSee('Antrean tugas, antar galon, dan periksa stok');
        $response->assertSee('A-04');
        $response->assertSee('Penghuni');
        $response->assertSee('Pesan menu & galon, cek tagihan sewa bulanan', false);

        // Footer
        $response->assertSee('Kos Tiana Putra 1');

        // Forgot password modal
        $response->assertSee('Lupa Kata Sandi');
        $response->assertSee('Pak H. Tiana');
    }

    /**
     * Test validation failure renders friendly error message.
     */
    public function test_login_failure_displays_humanized_inline_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@email.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $view = $this->view('auth.login', [
            'errors' => session('errors'),
        ]);

        $view->assertSee('Email atau kata sandi tidak cocok. Coba lagi.');
        $view->assertSee('border-alert');
    }

    /**
     * Test successful login redirects to respective role page.
     */
    public function test_successful_login_redirects_to_role_dashboard(): void
    {
        $user = User::where('role', 'penghuni')->first();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('penghuni.beranda'));
    }

    /**
     * Test quick demo logins still function properly.
     */
    public function test_demo_logins_work_properly(): void
    {
        $resPengelola = $this->get(route('demo.login', 'pengelola'));
        $resPengelola->assertRedirect(route('pengelola.dashboard'));
        $this->assertEquals('pengelola', auth()->user()->role);

        $resPetugas = $this->get(route('demo.login', 'petugas'));
        $resPetugas->assertRedirect(route('petugas.antrean'));
        $this->assertEquals('petugas', auth()->user()->role);

        $resPenghuni = $this->get(route('demo.login', 'penghuni'));
        $resPenghuni->assertRedirect(route('penghuni.beranda'));
        $this->assertEquals('penghuni', auth()->user()->role);
    }
}
