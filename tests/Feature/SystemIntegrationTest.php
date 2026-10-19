<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemIntegrationTest extends TestCase
{
    /**
     * Test Demo Quick Switcher logins for all 3 roles.
     */
    public function test_demo_switcher_works_for_all_roles(): void
    {
        // 1. Pengelola
        $resPengelola = $this->get('/demo-login/pengelola');
        $resPengelola->assertRedirect(route('pengelola.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals('pengelola', auth()->user()->role);

        // 2. Petugas
        $resPetugas = $this->get('/demo-login/petugas');
        $resPetugas->assertRedirect(route('petugas.antrean'));
        $this->assertAuthenticated();
        $this->assertEquals('petugas', auth()->user()->role);

        // 3. Penghuni
        $resPenghuni = $this->get('/demo-login/penghuni');
        $resPenghuni->assertRedirect(route('penghuni.beranda'));
        $this->assertAuthenticated();
        $this->assertEquals('penghuni', auth()->user()->role);
    }

    /**
     * Test all Pengelola endpoints return 200 OK.
     */
    public function test_all_pengelola_pages_load_successfully(): void
    {
        $pengelola = User::where('role', 'pengelola')->first();
        $this->actingAs($pengelola);

        $routes = [
            'pengelola.dashboard',
            'pengelola.kamar.index',
            'pengelola.penghuni.index',
            'pengelola.tagihan.index',
            'pengelola.katalog.index',
            'pengelola.stok.index',
            'pengelola.laporan.index',
            'pengelola.prediksi.index',
        ];

        foreach ($routes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertStatus(200);
        }
    }

    /**
     * Test all Petugas endpoints return 200 OK.
     */
    public function test_all_petugas_pages_load_successfully(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $this->actingAs($petugas);

        $routes = [
            'petugas.antrean',
            'petugas.stocks.index',
            'petugas.history',
        ];

        foreach ($routes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertStatus(200);
        }
    }

    /**
     * Test all Penghuni endpoints return 200 OK.
     */
    public function test_all_penghuni_pages_load_successfully(): void
    {
        $penghuni = User::where('role', 'penghuni')->first();
        $this->actingAs($penghuni);

        $routes = [
            'penghuni.beranda',
            'penghuni.katalog',
            'penghuni.pesanan',
            'penghuni.tagihan',
        ];

        foreach ($routes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertStatus(200);
        }
    }

    /**
     * Test Machine Learning Prediction Command and DB table persistence.
     */
    public function test_python_ml_stock_prediction_command(): void
    {
        $exitCode = Artisan::call('stok:prediksi');
        $this->assertEquals(0, $exitCode);

        // Pastikan tabel terisi dengan rekomendasi prediksi
        $this->assertGreaterThan(0, \App\Models\StockPrediction::count());
        $firstPrediction = \App\Models\StockPrediction::first();
        $this->assertNotNull($firstPrediction);
        $this->assertContains($firstPrediction->status, ['kritis', 'waspada', 'aman']);
    }

    /**
     * Test web action trigger "Hitung Ulang Prediksi" via Controller POST route.
     */
    public function test_pengelola_can_trigger_prediction_calculation_via_web(): void
    {
        $pengelola = User::where('role', 'pengelola')->first();
        $this->actingAs($pengelola);

        $response = $this->post(route('pengelola.prediksi.calculate'));
        $response->assertRedirect(route('pengelola.prediksi.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Test pengelola can apply 1-click restock directly from prediction page.
     */
    public function test_pengelola_can_apply_1_click_restock_from_prediction(): void
    {
        $pengelola = User::where('role', 'pengelola')->first();
        $this->actingAs($pengelola);

        $prediction = \App\Models\StockPrediction::first();
        $item = $prediction->catalogItem;
        $initialStock = $item->stock;

        $response = $this->post(route('pengelola.prediksi.restock', $prediction->id), [
            'quantity' => 10,
            'notes' => 'Test 1-click restock',
        ]);

        $response->assertRedirect(route('pengelola.prediksi.index'));
        $response->assertSessionHas('success');

        $this->assertEquals($initialStock + 10, $item->fresh()->stock);
        $this->assertDatabaseHas('stock_logs', [
            'catalog_item_id' => $item->id,
            'quantity' => 10,
            'reference_type' => 'restock_ai',
        ]);
    }

    /**
     * Test user can mark a single unread notification as read on tap.
     */
    public function test_user_can_mark_single_notification_as_read(): void
    {
        $user = User::where('role', 'penghuni')->first();
        $this->actingAs($user);

        $notif = \App\Models\AppNotification::create([
            'user_id' => $user->id,
            'title' => 'Test Notifikasi Unread',
            'message' => 'Notifikasi belum dibaca untuk pengetesan',
            'type' => 'system',
            'action_url' => '/penghuni/tagihan',
            'is_read' => false,
        ]);

        $this->assertFalse($notif->is_read);

        // POST /notifications/{id}/read
        $response = $this->postJson(route('notifications.read', $notif->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertTrue($notif->fresh()->is_read);
        $this->assertNotNull($notif->fresh()->read_at);
    }

    /**
     * Test user can mark all notifications as read at once.
     */
    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::where('role', 'penghuni')->first();
        $this->actingAs($user);

        \App\Models\AppNotification::create([
            'user_id' => $user->id,
            'title' => 'Notifikasi 1',
            'message' => 'Belum dibaca 1',
            'is_read' => false,
        ]);

        \App\Models\AppNotification::create([
            'user_id' => $user->id,
            'title' => 'Notifikasi 2',
            'message' => 'Belum dibaca 2',
            'is_read' => false,
        ]);

        $this->assertGreaterThanOrEqual(2, $user->unreadNotificationsCount());

        $response = $this->postJson(route('notifications.read_all'));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'unread_count' => 0]);

        $this->assertEquals(0, $user->unreadNotificationsCount());
    }
}
