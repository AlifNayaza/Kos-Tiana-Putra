<?php

namespace Tests\Feature;

use App\Jobs\RunStockPrediction;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\StockPrediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesKosFixtures;
use Tests\TestCase;

class SystemIntegrationTest extends TestCase
{
    use RefreshDatabase, CreatesKosFixtures;

    public function test_all_pengelola_pages_load_successfully(): void
    {
        $this->actingAs($this->makeUser('pengelola'));
        $tenant = $this->makeTenant();
        $this->makeBill($tenant);
        $this->makeItem();

        foreach ([
            'pengelola.dashboard', 'pengelola.kamar.index', 'pengelola.penghuni.index', 'pengelola.tagihan.index',
            'pengelola.katalog.index', 'pengelola.stok.index', 'pengelola.laporan.index', 'pengelola.prediksi.index',
            'pengelola.profil',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_all_petugas_pages_load_successfully(): void
    {
        $this->actingAs($this->makeUser('petugas'));
        $this->makeItem();

        foreach (['petugas.antrean', 'petugas.dashboard', 'petugas.stocks.index', 'petugas.history', 'petugas.profil'] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_all_penghuni_pages_load_successfully(): void
    {
        $tenant = $this->makeTenant();
        $this->makeBill($tenant);
        $this->makeItem();
        $this->makeGalon();
        $this->actingAs($tenant);

        foreach (['penghuni.beranda', 'penghuni.katalog', 'penghuni.pesanan', 'penghuni.tagihan', 'penghuni.profil'] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    /**
     * Atribut x-data/Alpine yang rusak (mis. tanda kutip ganda di dalam atribut) membuat kode JavaScript
     * tampil sebagai teks biasa di layar. Status 200 tidak menangkap ini, jadi teks yang terlihat diperiksa.
     */
    public function test_pages_do_not_leak_script_code_into_visible_text(): void
    {
        $tenant = $this->makeTenant();
        $this->makeBill($tenant);
        $this->makeItem(['stock' => 1, 'minimum_stock' => 5]);
        $this->makeGalon();

        $pages = [
            [$this->makeUser('pengelola'), ['pengelola.dashboard', 'pengelola.kamar.index', 'pengelola.penghuni.index', 'pengelola.tagihan.index', 'pengelola.katalog.index', 'pengelola.stok.index', 'pengelola.laporan.index', 'pengelola.prediksi.index', 'pengelola.profil']],
            [$this->makeUser('petugas'), ['petugas.antrean', 'petugas.stocks.index', 'petugas.history', 'petugas.profil']],
            [$tenant, ['penghuni.beranda', 'penghuni.katalog', 'penghuni.pesanan', 'penghuni.tagihan', 'penghuni.profil']],
        ];

        foreach ($pages as [$user, $routes]) {
            $this->actingAs($user);

            foreach ($routes as $routeName) {
                $visible = $this->visibleText($this->get(route($routeName))->assertOk()->getContent());

                foreach (['return this.', 'this.selectedItem', 'x-data', '=> {', 'canSubmit'] as $codeFragment) {
                    $this->assertStringNotContainsString($codeFragment, $visible, "Kode muncul sebagai teks di halaman {$routeName}");
                }
            }
        }
    }

    private function visibleText(string $html): string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        foreach (['script', 'style', 'template'] as $tag) {
            $nodes = iterator_to_array($dom->getElementsByTagName($tag));
            foreach ($nodes as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        return $dom->getElementsByTagName('body')->item(0)?->textContent ?? '';
    }

    public function test_monthly_report_totals_and_invalid_period_fallback(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['category' => 'galon']);

        $this->makeBill($tenant, ['status' => 'lunas', 'month' => 11, 'year' => 2026]);

        $order = Order::create([
            'order_code' => 'ORD-R-1', 'user_id' => $tenant->id, 'room_number' => 'A-1', 'order_type' => 'regular',
            'total_amount' => 36000, 'status' => 'selesai', 'payment_status' => 'lunas',
            'created_at' => '2026-11-03 10:00:00', 'updated_at' => '2026-11-03 10:00:00', 'completed_at' => '2026-11-03 10:20:00',
        ]);
        $order->items()->create(['catalog_item_id' => $item->id, 'item_name' => $item->name, 'quantity' => 2, 'unit_price' => 18000, 'subtotal' => 36000]);

        $this->actingAs($pengelola);

        $response = $this->get(route('pengelola.laporan.index', ['month' => 11, 'year' => 2026]))->assertOk();
        $this->assertSame(36000, (int) $response->viewData('totalServiceSales'));
        $this->assertSame(850000, (int) $response->viewData('totalRentPaid'));
        $this->assertSame(36000, (int) $response->viewData('galonSales'));
        $this->assertSame(36000, $response->viewData('dailyServiceSales')[2], 'Hari ke-3 harus berisi penjualan 3 Nov.');
        $this->assertCount(30, $response->viewData('dailyLabels'));

        // Bulan tidak valid tidak boleh menyebabkan error 500.
        $this->get(route('pengelola.laporan.index', ['month' => 13, 'year' => 2026]))->assertOk();
        $this->get(route('pengelola.laporan.index', ['month' => 'abc']))->assertOk();
    }

    public function test_trigger_prediction_dispatches_job_after_response_without_blocking(): void
    {
        Bus::fake();
        $this->actingAs($this->makeUser('pengelola'));

        $this->post(route('pengelola.prediksi.calculate'))
            ->assertRedirect(route('pengelola.prediksi.index'))
            ->assertSessionHas('success');

        Bus::assertDispatchedAfterResponse(RunStockPrediction::class);
    }

    public function test_prediction_trigger_is_ignored_while_a_run_is_in_progress(): void
    {
        Bus::fake();
        Cache::put(RunStockPrediction::RUNNING_CACHE_KEY, true, now()->addMinutes(5));
        $this->actingAs($this->makeUser('pengelola'));

        $this->post(route('pengelola.prediksi.calculate'))->assertSessionHas('info');

        Bus::assertNotDispatchedAfterResponse(RunStockPrediction::class);
    }

    public function test_prediction_page_hides_deleted_items_and_shows_running_state(): void
    {
        $this->actingAs($this->makeUser('pengelola'));
        $kept = $this->makeItem(['name' => 'Menu Dipertahankan']);
        $gone = $this->makeItem(['name' => 'Menu Dihapus Sudah']);

        foreach ([$kept, $gone] as $item) {
            StockPrediction::create([
                'catalog_item_id' => $item->id, 'predicted_demand_qty' => 10, 'target_stock' => 15, 'current_stock' => 3,
                'recommended_restock_qty' => 12, 'daily_avg_sales' => 1.4, 'safety_stock' => 5, 'sales_trend' => 'stabil',
                'status' => 'kritis', 'confidence_score' => 0.8, 'calculated_at' => now(),
            ]);
        }
        $gone->delete();
        Cache::put(RunStockPrediction::RUNNING_CACHE_KEY, true, now()->addMinutes(5));

        $this->get(route('pengelola.prediksi.index'))
            ->assertOk()
            ->assertSee('Menu Dipertahankan')
            ->assertDontSee('Menu Dihapus Sudah')
            ->assertSee('Sedang dihitung di latar belakang');
    }

    public function test_pengelola_can_apply_1_click_restock_from_prediction(): void
    {
        $this->actingAs($this->makeUser('pengelola'));
        $item = $this->makeItem(['stock' => 3]);
        $prediction = StockPrediction::create([
            'catalog_item_id' => $item->id, 'predicted_demand_qty' => 10, 'target_stock' => 15, 'current_stock' => 3,
            'recommended_restock_qty' => 12, 'daily_avg_sales' => 1.4, 'safety_stock' => 5, 'sales_trend' => 'stabil',
            'status' => 'kritis', 'confidence_score' => 0.8, 'calculated_at' => now(),
        ]);

        $this->post(route('pengelola.prediksi.restock', $prediction->id), ['quantity' => 10, 'notes' => 'Test'])
            ->assertRedirect(route('pengelola.prediksi.index'))
            ->assertSessionHas('success');

        $this->assertSame(13, $item->fresh()->stock);
        $this->assertDatabaseHas('stock_logs', ['catalog_item_id' => $item->id, 'quantity' => 10, 'reference_type' => 'restock_ai']);
        $this->assertSame(13, $prediction->fresh()->current_stock);
    }

    public function test_user_can_mark_single_notification_as_read_but_not_others(): void
    {
        $user = $this->makeTenant();
        $stranger = $this->makeTenant();
        $this->actingAs($user);

        $mine = AppNotification::create(['user_id' => $user->id, 'title' => 'Uji', 'message' => 'Uji', 'type' => 'system', 'is_read' => false]);
        $theirs = AppNotification::create(['user_id' => $stranger->id, 'title' => 'Uji', 'message' => 'Uji', 'type' => 'system', 'is_read' => false]);

        $this->postJson(route('notifications.read', $mine->id))->assertOk()->assertJson(['success' => true]);
        $this->assertTrue($mine->fresh()->is_read);

        $this->postJson(route('notifications.read', $theirs->id))->assertNotFound();
        $this->assertFalse($theirs->fresh()->is_read);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->makeTenant();
        $this->actingAs($user);

        foreach ([1, 2] as $n) {
            AppNotification::create(['user_id' => $user->id, 'title' => "N{$n}", 'message' => 'Belum dibaca', 'type' => 'system', 'is_read' => false]);
        }

        $this->assertSame(2, $user->unreadNotificationsCount());
        $this->postJson(route('notifications.read_all'))->assertOk()->assertJson(['success' => true, 'unread_count' => 0]);
        $this->assertSame(0, $user->unreadNotificationsCount());
    }
}
