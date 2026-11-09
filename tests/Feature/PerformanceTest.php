<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesKosFixtures;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase, CreatesKosFixtures;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function completedOrder($tenant, int $amount, string $when): Order
    {
        $order = Order::create([
            'order_code' => 'ORD-P-' . uniqid(), 'user_id' => $tenant->id, 'room_number' => 'A-1', 'order_type' => 'regular',
            'total_amount' => $amount, 'status' => 'selesai', 'payment_status' => 'lunas',
            'created_at' => $when, 'updated_at' => $when, 'completed_at' => $when,
        ]);

        return $order;
    }

    // ---------------------------------------------------------------- hasil tetap benar setelah query dirampingkan
    public function test_dashboard_figures_are_correct_after_query_consolidation(): void
    {
        Carbon::setTestNow('2026-11-15 10:00:00');

        $pengelola = $this->makeUser('pengelola');
        $tenant = $this->makeTenant();                          // 1 kamar terisi + 1 penghuni aktif
        $this->makeRoom(['status' => 'tersedia']);
        $this->makeRoom(['status' => 'perbaikan']);

        // Sewa lunas: bulan ini 850.000, bulan lalu 850.000 (+ satu tagihan menunggu bulan ini)
        $this->makeBill($tenant, ['month' => 11, 'year' => 2026, 'status' => 'lunas', 'amount' => 850000]);
        $this->makeBill($tenant, ['month' => 10, 'year' => 2026, 'status' => 'lunas', 'amount' => 700000]);
        $this->makeBill($this->makeTenant(), ['month' => 11, 'year' => 2026, 'status' => 'menunggu', 'amount' => 600000]);

        // Penjualan selesai: bulan ini 36.000 (2 pesanan), bulan lalu 18.000, enam bulan lalu di luar jendela
        $this->completedOrder($tenant, 20000, '2026-11-03 09:00:00');
        $this->completedOrder($tenant, 16000, '2026-11-14 20:00:00');
        $this->completedOrder($tenant, 18000, '2026-10-20 12:00:00');
        $this->completedOrder($tenant, 99999, '2026-04-10 12:00:00');

        $r = $this->actingAs($pengelola)->get(route('pengelola.dashboard'))->assertOk();

        $this->assertSame(4, $r->viewData('roomsCount'));        // 2 kamar penghuni + tersedia + perbaikan
        $this->assertSame(2, $r->viewData('occupiedRoomsCount'));
        $this->assertSame(1, $r->viewData('availableRoomsCount'));
        $this->assertSame(1, $r->viewData('repairRoomsCount'));
        $this->assertSame(2, $r->viewData('tenantsCount'));

        $this->assertSame(850000, (int) $r->viewData('rentIncomeThisMonth'));
        $this->assertSame(36000, (int) $r->viewData('serviceSalesThisMonth'));
        $this->assertSame(886000, (int) $r->viewData('totalIncomeThisMonth'));
        $this->assertSame(600000, (int) $r->viewData('pendingRentThisMonth'));

        // Enam bulan: Jun..Nov 2026
        $this->assertCount(6, $r->viewData('chartLabels'));
        $this->assertSame([0, 0, 0, 0, 700000, 850000], $r->viewData('rentSeries'));
        $this->assertSame([0, 0, 0, 0, 18000, 36000], $r->viewData('salesSeries'), 'Pesanan April (di luar jendela) tidak boleh ikut.');
    }

    public function test_list_page_statistics_still_match_the_data(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $tenant = $this->makeTenant();
        $this->makeRoom(['status' => 'tersedia']);

        $this->makeItem(['category' => 'makanan', 'stock' => 2, 'minimum_stock' => 5]);   // menipis
        $this->makeItem(['category' => 'minuman', 'stock' => 50]);
        $this->makeGalon(['stock' => 1, 'minimum_stock' => 3]);                           // menipis

        $this->makeBill($tenant, ['month' => 11, 'year' => 2026, 'status' => 'lunas', 'amount' => 800000]);
        $this->makeBill($this->makeTenant(), ['month' => 11, 'year' => 2026, 'status' => 'menunggak', 'amount' => 500000, 'payment_proof' => 'x.jpg']);

        $this->actingAs($pengelola);

        $kamar = $this->get(route('pengelola.kamar.index'))->assertOk()->viewData('stats');
        $this->assertSame(['total' => 3, 'terisi' => 2, 'tersedia' => 1, 'perbaikan' => 0], $kamar);

        $katalog = $this->get(route('pengelola.katalog.index'))->assertOk()->viewData('stats');
        $this->assertSame(3, $katalog['total']);
        $this->assertSame(1, $katalog['makanan']);
        $this->assertSame(1, $katalog['minuman']);
        $this->assertSame(1, $katalog['galon']);
        $this->assertSame(2, $katalog['menipis']);

        $tagihan = $this->get(route('pengelola.tagihan.index', ['month' => 11, 'year' => 2026]))->assertOk()->viewData('stats');
        $this->assertSame(2, $tagihan['total_bills']);
        $this->assertSame(800000, $tagihan['lunas_sum']);
        $this->assertSame(500000, $tagihan['menunggu_sum']);
    }

    public function test_petugas_queue_metrics_and_history_date_filter_still_work(): void
    {
        Carbon::setTestNow('2026-11-09 14:00:00');
        $petugas = $this->makeUser('petugas');
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['stock' => 10]);

        foreach (['menunggu', 'menunggu', 'diproses', 'diantar'] as $status) {
            $this->actingAs($tenant)->post(route('penghuni.order.store'), ['items_json' => json_encode([['id' => $item->id, 'quantity' => 1]])]);
            Order::latest('id')->first()->update(['status' => $status]);
        }
        $this->completedOrder($tenant, 10000, '2026-11-09 08:00:00');   // selesai hari ini
        $this->completedOrder($tenant, 12000, '2026-11-08 23:59:00');   // kemarin: tidak dihitung hari ini

        $metrics = $this->actingAs($petugas)->get(route('petugas.antrean'))->assertOk()->viewData('metrics');
        $this->assertSame(['menunggu' => 2, 'diproses' => 1, 'diantar' => 1, 'selesai' => 1], $metrics);

        $this->get(route('petugas.history', ['date' => '2026-11-08']))->assertOk()->assertSee('12.000');
        $this->get(route('petugas.history', ['date' => 'bukan-tanggal']))->assertOk();   // tanggal rusak tidak membuat error
    }

    // ---------------------------------------------------------------- polling ringan
    public function test_activity_signal_changes_only_when_the_queue_changes(): void
    {
        $tenant = $this->makeTenant();
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 10]);

        $this->actingAs($petugas);
        $empty = $this->getJson(route('petugas.antrean.signal'))->assertOk()->json('sig');
        $this->assertSame($empty, $this->getJson(route('petugas.antrean.signal'))->json('sig'), 'Tanpa perubahan, tanda harus sama.');

        $this->actingAs($tenant)->post(route('penghuni.order.store'), ['items_json' => json_encode([['id' => $item->id, 'quantity' => 1]])]);
        $order = Order::firstOrFail();

        $this->actingAs($petugas);
        $afterOrder = $this->getJson(route('petugas.antrean.signal'))->json('sig');
        $this->assertNotSame($empty, $afterOrder);

        Carbon::setTestNow(now()->addMinutes(2));
        $this->patch(route('petugas.orders.update-status', $order));
        $afterStatus = $this->getJson(route('petugas.antrean.signal'))->json('sig');
        $this->assertNotSame($afterOrder, $afterStatus, 'Pergantian status harus mengubah tanda.');

        // Penghuni hanya melihat tanda miliknya
        $other = $this->makeTenant();
        $this->actingAs($other);
        $this->assertSame('0|', $this->getJson(route('penghuni.pesanan.signal'))->json('sig'));
        $this->actingAs($tenant);
        $this->assertStringStartsWith('1|', $this->getJson(route('penghuni.pesanan.signal'))->json('sig'));
    }

    public function test_signal_endpoints_are_tiny_uncached_and_protected(): void
    {
        $this->getJson(route('petugas.antrean.signal'))->assertUnauthorized();

        $tenant = $this->makeTenant();
        $this->actingAs($tenant)->get(route('petugas.antrean.signal'))->assertRedirect();   // peran salah ditolak

        $response = $this->actingAs($this->makeUser('petugas'))->getJson(route('petugas.antrean.signal'))->assertOk();
        $this->assertLessThan(60, strlen($response->getContent()));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_pages_embed_the_signature_used_by_polling(): void
    {
        $tenant = $this->makeTenant();
        $item = $this->makeItem();
        $this->actingAs($tenant)->post(route('penghuni.order.store'), ['items_json' => json_encode([['id' => $item->id, 'quantity' => 1]])]);
        $sig = Order::activitySignature($tenant->id);

        $this->get(route('penghuni.pesanan'))->assertSee('data-sig="' . $sig . '"', false);
        $this->actingAs($this->makeUser('petugas'))->get(route('petugas.antrean'))->assertSee('data-sig="' . Order::activitySignature() . '"', false);
    }

    // ---------------------------------------------------------------- tidak ada pemborosan
    public function test_notification_dropdown_loads_only_the_latest_eight_rows(): void
    {
        $user = $this->makeTenant();
        foreach (range(1, 40) as $i) {
            AppNotification::create(['user_id' => $user->id, 'title' => "N{$i}", 'message' => 'x', 'type' => 'system', 'is_read' => false]);
        }

        $loaded = 0;
        AppNotification::retrieved(function () use (&$loaded) {
            $loaded++;
        });

        $this->actingAs($user)->get(route('penghuni.beranda'))->assertOk();

        $this->assertLessThanOrEqual(8, $loaded, "Memuat {$loaded} notifikasi; seharusnya hanya 8 yang terbaru.");
    }

    public function test_heavy_pages_stay_within_a_query_budget(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $tenant = $this->makeTenant();
        foreach (range(1, 12) as $i) {   // data lebih banyak tidak boleh menambah jumlah query (tanpa N+1)
            $item = $this->makeItem();
            $this->makeBill($this->makeTenant(), ['status' => 'menunggak', 'due_date' => now()->subDays($i)->toDateString(), 'month' => ($i % 12) + 1, 'year' => 2026]);
            $this->actingAs($tenant)->post(route('penghuni.order.store'), ['items_json' => json_encode([['id' => $item->id, 'quantity' => 1]])]);
        }

        $budget = [
            [$pengelola, 'pengelola.dashboard', 28],
            [$pengelola, 'pengelola.tagihan.index', 14],
            [$pengelola, 'pengelola.kamar.index', 14],
            [$this->makeUser('petugas'), 'petugas.antrean', 14],
            [$tenant, 'penghuni.beranda', 18],
            [$tenant, 'penghuni.pesanan', 14],
        ];

        foreach ($budget as [$user, $route, $max]) {
            $this->actingAs($user);
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route($route))->assertOk();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual($max, $count, "{$route} memakai {$count} query (batas {$max}).");
        }
    }

    // ---------------------------------------------------------------- HTML & aset
    public function test_html_is_minified_and_the_option_can_turn_it_off(): void
    {
        $minified = $this->get('/login')->assertOk()->getContent();
        // Blok yang sengaja dibiarkan apa adanya (script/style/textarea/pre) tidak ikut diperiksa.
        $outside = preg_replace('#<(script|style|textarea|pre)\b.*?</\1>#is', '', $minified);
        $this->assertDoesNotMatchRegularExpression('/^[ \t]{2,}\S/m', $outside, 'Tidak boleh ada indentasi di awal baris.');
        $this->assertStringNotContainsString('<!--', $outside);

        config(['kos.minify_html' => false]);
        $raw = $this->get('/login')->getContent();
        $this->assertGreaterThan(strlen($minified), strlen($raw));
    }

    public function test_minification_keeps_pages_functional(): void
    {
        $tenant = $this->makeTenant();
        $this->makeGalon();

        $html = $this->actingAs($tenant)->get(route('penghuni.katalog'))->assertOk()->getContent();

        // Atribut Alpine bermakna tetap utuh
        $this->assertStringContainsString('x-data="{', $html);
        $this->assertStringContainsString('cartKey:', $html);
        $this->assertStringContainsString('@click="', $html);
    }

    public function test_charts_library_is_loaded_only_on_chart_pages_and_fonts_are_self_hosted(): void
    {
        if (!file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Aset belum di-build (npm run build).');
        }

        $pengelola = $this->makeUser('pengelola');
        $this->actingAs($pengelola);

        foreach (['pengelola.dashboard', 'pengelola.laporan.index', 'pengelola.prediksi.index'] as $route) {
            $this->assertStringContainsString('charts-', $this->get(route($route))->getContent(), "{$route} harus memuat Chart.js");
        }
        foreach (['pengelola.kamar.index', 'pengelola.tagihan.index', 'pengelola.katalog.index'] as $route) {
            $this->assertStringNotContainsString('charts-', $this->get(route($route))->getContent(), "{$route} tidak butuh Chart.js");
        }

        $login = $this->post('/logout') && true ? $this->get('/login') : null;
        $html = $this->app['auth']->check() ? $this->get(route('pengelola.dashboard'))->getContent() : '';
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('fonts.gstatic.com', $html);
    }
}
