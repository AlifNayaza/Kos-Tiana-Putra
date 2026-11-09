<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesKosFixtures;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase, CreatesKosFixtures;

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

    public function test_login_failure_displays_humanized_inline_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@email.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.']);
        $this->assertGuest();
    }

    public function test_successful_login_redirects_to_role_dashboard(): void
    {
        $user = $this->makeTenant();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('penghuni.beranda'));
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = $this->makeUser('pengelola');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah']);
        }

        // Percobaan ke-6 ditolak walau kata sandinya benar.
        $response = $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_checked_out_tenant_cannot_log_in(): void
    {
        $user = $this->makeTenant();
        $user->tenantProfile->update(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_checked_out_tenant_with_live_session_is_kicked_out(): void
    {
        $user = $this->makeTenant();
        $this->actingAs($user);
        $user->tenantProfile->update(['is_active' => false]);

        $this->get(route('penghuni.beranda'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_demo_logins_work_when_enabled(): void
    {
        $this->makeUser('pengelola', ['email' => 'pengelola@kostiana.com']);
        $this->makeUser('petugas', ['email' => 'petugas@kostiana.com']);
        $this->makeTenant(['email' => 'penghuni@kostiana.com']);

        $this->get(route('demo.login', 'pengelola'))->assertRedirect(route('pengelola.dashboard'));
        $this->assertEquals('pengelola', auth()->user()->role);

        $this->get(route('demo.login', 'petugas'))->assertRedirect(route('petugas.antrean'));
        $this->assertEquals('petugas', auth()->user()->role);

        $this->get(route('demo.login', 'penghuni'))->assertRedirect(route('penghuni.beranda'));
        $this->assertEquals('penghuni', auth()->user()->role);
    }

    public function test_demo_login_is_not_available_when_disabled(): void
    {
        config(['kos.demo_login' => false]);
        $this->makeUser('pengelola', ['email' => 'pengelola@kostiana.com']);

        $this->get(route('demo.login', 'pengelola'))->assertNotFound();
        $this->assertGuest();

        $this->get('/login')->assertOk()->assertDontSee('Coba tanpa akun');
    }

    public function test_temporary_password_must_be_changed_before_using_the_app(): void
    {
        $user = $this->makeTenant(['must_change_password' => true]);
        $this->actingAs($user);

        $this->get(route('penghuni.beranda'))->assertRedirect(route('penghuni.profil'));
        $this->get(route('penghuni.profil'))->assertOk();

        $this->put(route('profil.password'), [
            'current_password'      => 'password',
            'password'              => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertRedirect(route('penghuni.profil'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->get(route('penghuni.beranda'))->assertOk();
    }

    public function test_password_change_rejects_wrong_current_password_and_weak_passwords(): void
    {
        $user = $this->makeUser('petugas');
        $this->actingAs($user);

        $this->put(route('profil.password'), [
            'current_password' => 'salah', 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('profil.password'), [
            'current_password' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
