<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesKosFixtures;
use Tests\TestCase;

class UxImprovementsTest extends TestCase
{
    use RefreshDatabase, CreatesKosFixtures;

    // ---------------------------------------------------------------- pengelola: modal form
    public function test_failed_modal_submission_flashes_old_input_and_marks_the_form_to_reopen(): void
    {
        $pengelola = $this->makeUser('pengelola');

        $response = $this->actingAs($pengelola)->from(route('pengelola.kamar.index'))->post(route('pengelola.kamar.store'), [
            '_restore' => 'kamar', '_edit' => '0', '_action' => route('pengelola.kamar.store'),
            'room_number' => 'Kamar Z-9', 'floor' => 2, 'type' => 'Deluxe', 'monthly_price' => 50000, // harga terlalu rendah
            'facilities' => 'AC', 'status' => 'tersedia',
        ]);

        // Catatan: halaman dibuka langsung setelah POST (isian lama & error masih terbawa di sesi).
        $page = $this->get(route('pengelola.kamar.index'));
        $page->assertOk();
        $page->assertSee('window.__kosOld', false);   // isian lama disuntikkan untuk dipulihkan
        $page->assertSee('Kamar Z-9', false);
        $page->assertSee('data-restore="kamar"', false);
        $page->assertSee('Data belum tersimpan');       // ringkasan error di modal
        $page->assertSee('role="alert"', false);        // pesan error per kolom
        $page->assertSee('Harga sewa bulanan minimal 100000.');  // kalimat Indonesia, bukan kode validation.*
        $page->assertDontSee('validation.');
    }

    public function test_pages_do_not_inject_old_input_without_a_restore_marker(): void
    {
        $this->actingAs($this->makeUser('pengelola'))
            ->from(route('pengelola.kamar.index'))
            ->post(route('pengelola.kamar.store'), ['room_number' => '']);

        $this->get(route('pengelola.kamar.index'))->assertDontSee('window.__kosOld', false);
    }

    public function test_every_pengelola_modal_form_is_wired_for_restore(): void
    {
        $this->actingAs($this->makeUser('pengelola'));
        $this->makeRoom(['status' => 'tersedia']);
        $tenant = $this->makeTenant();
        $this->makeBill($tenant);
        $this->makeItem();

        foreach ([
            'pengelola.kamar.index' => ['kamar'],
            'pengelola.katalog.index' => ['katalog'],
            'pengelola.penghuni.index' => ['penghuni-baru', 'penghuni-ubah'],
            'pengelola.tagihan.index' => ['tagihan-terbit'],
        ] as $route => $ids) {
            $html = $this->get(route($route))->assertOk()->getContent();
            foreach ($ids as $id) {
                $this->assertStringContainsString('data-restore="' . $id . '"', $html, "{$id} belum terpasang di {$route}");
                $this->assertStringContainsString('name="_restore" value="' . $id . '"', $html);
            }
        }
    }

    // ---------------------------------------------------------------- pengelola: pengingat WhatsApp
    public function test_bill_reminder_link_contains_a_complete_message_and_skips_paid_bills(): void
    {
        $tenant = $this->makeTenant(['name' => 'Dimas Pratama', 'phone' => '0812-9876-5432']);
        $open = $this->makeBill($tenant, ['amount' => 1150000, 'month' => 10, 'year' => 2026, 'due_date' => '2026-10-10']);
        $paid = $this->makeBill($tenant, ['status' => 'lunas', 'month' => 9, 'year' => 2026]);

        $url = $open->fresh()->reminder_url;

        $this->assertStringStartsWith('https://wa.me/628129876543' . '2?text=', $url);
        $text = urldecode(substr($url, strpos($url, '?text=') + 6));
        $this->assertStringContainsString('Dimas Pratama', $text);
        $this->assertStringContainsString('Rp 1.150.000', $text);
        $this->assertStringContainsString('10 Oktober 2026', $text);
        $this->assertStringContainsString(config('kos.payment.account_no'), $text);

        $this->assertNull($paid->fresh()->reminder_url);
    }

    public function test_bill_without_valid_phone_has_no_reminder_and_the_button_is_hidden(): void
    {
        $tenant = $this->makeTenant(['phone' => '-']);
        $bill = $this->makeBill($tenant);

        $this->assertNull($bill->fresh()->reminder_url);

        $this->actingAs($this->makeUser('pengelola'))
            ->get(route('pengelola.tagihan.index'))
            ->assertOk()
            ->assertDontSee('Ingatkan WA');
    }

    public function test_tagihan_page_shows_the_reminder_button_for_unpaid_bills(): void
    {
        $tenant = $this->makeTenant(['phone' => '081298765401']);
        $this->makeBill($tenant);

        $this->actingAs($this->makeUser('pengelola'))
            ->get(route('pengelola.tagihan.index'))
            ->assertOk()
            ->assertSee('Ingatkan WA')
            ->assertSee('https://wa.me/6281298765401', false);
    }

    // ---------------------------------------------------------------- penghuni
    public function test_search_parameter_is_not_injected_raw_into_javascript(): void
    {
        $this->makeItem();
        $tenant = $this->makeTenant();

        $html = $this->actingAs($tenant)
            ->get(route('penghuni.katalog', ['q' => "');alert(1);//"]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString("');alert(1)", $html);
    }

    public function test_catalog_exposes_a_full_item_map_so_a_saved_cart_can_be_restored(): void
    {
        $food = $this->makeItem(['name' => 'Nasi Uji', 'price' => 12000]);
        $galon = $this->makeGalon();
        $tenant = $this->makeTenant();

        // Walau difilter kategori, peta berisi semua menu tersedia (untuk memulihkan keranjang lintas kategori).
        $response = $this->actingAs($tenant)->get(route('penghuni.katalog', ['category' => 'galon']))->assertOk();

        $map = $response->viewData('catalogMap');
        $this->assertArrayHasKey($food->id, $map->all());
        $this->assertArrayHasKey($galon->id, $map->all());
        $response->assertSee('kos-cart-' . $tenant->id, false);
    }

    public function test_placing_an_order_flags_the_saved_cart_for_clearing(): void
    {
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['stock' => 5]);

        $this->actingAs($tenant)
            ->post(route('penghuni.order.store'), ['items_json' => json_encode([['id' => $item->id, 'quantity' => 1]])])
            ->assertRedirect(route('penghuni.pesanan'))
            ->assertSessionHas('cart_clear', true);

        $this->get(route('penghuni.pesanan'))->assertSee("localStorage.removeItem('kos-cart-{$tenant->id}')", false);
    }

    public function test_failed_order_keeps_the_cart(): void
    {
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['stock' => 1]);

        $this->actingAs($tenant)
            ->post(route('penghuni.order.store'), ['items_json' => json_encode([['id' => $item->id, 'quantity' => 5]])])
            ->assertSessionMissing('cart_clear');
    }

    public function test_orders_page_supports_live_refresh_and_reorder(): void
    {
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['stock' => 10]);

        $active = $this->placeOrder($tenant, $item);
        $done = $this->placeOrder($tenant, $item);
        $done->update(['status' => 'selesai', 'completed_at' => now()]);

        $html = $this->actingAs($tenant)->get(route('penghuni.pesanan'))->assertOk()->getContent();

        $this->assertStringContainsString('id="orders-active"', $html);
        $this->assertStringContainsString('data-order-code="' . $active->order_code . '"', $html);
        $this->assertStringContainsString('data-order-status="menunggu"', $html);
        $this->assertStringContainsString('Pesan lagi', $html);
        $this->assertStringContainsString('u0022id', $html);   // data pesanan untuk Pesan lagi (JSON aman untuk atribut)
    }

    // ---------------------------------------------------------------- petugas
    public function test_queue_card_offers_call_whatsapp_and_the_exact_cash_amount(): void
    {
        $tenant = $this->makeTenant(['phone' => '0812-1111-2222']);
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['price' => 22000, 'stock' => 10]);

        $order = $this->placeOrder($tenant, $item);
        $order->update(['status' => 'diantar']);

        $html = $this->actingAs($petugas)->get(route('petugas.antrean'))->assertOk()->getContent();

        $this->assertStringContainsString('tel:+6281211112222', $html);
        $this->assertStringContainsString('https://wa.me/6281211112222', $html);
        $this->assertStringContainsString('Selesai, terima tunai Rp 22.000', $html);
        $this->assertStringContainsString('data-order-id="' . $order->id . '"', $html);
        $this->assertStringContainsString('data-order-status="diantar"', $html);
    }

    public function test_stale_status_tap_explains_what_happened(): void
    {
        $tenant = $this->makeTenant();
        $a = $this->makeUser('petugas');
        $b = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 10]);
        $order = $this->placeOrder($tenant, $item);

        // Petugas A sudah memproses; petugas B masih melihat kartu "Mulai proses" lama.
        $this->actingAs($a)->patch(route('petugas.orders.update-status', $order), ['status' => 'diproses']);

        $response = $this->actingAs($b)->patch(route('petugas.orders.update-status', $order), ['status' => 'diproses']);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('sudah berubah', session('error'));
        $this->assertStringContainsString('diproses', session('error'));
        $this->assertSame('diproses', $order->fresh()->status);
    }

    // ---------------------------------------------------------------- umum
    public function test_toast_is_an_accessible_live_region_that_listens_for_events(): void
    {
        $html = $this->actingAs($this->makeUser('petugas'))->get(route('petugas.antrean'))->assertOk()->getContent();

        $this->assertStringContainsString('@kos-toast.window', $html);
        $this->assertStringContainsString('role="status"', $html);
    }

    private function placeOrder($tenant, $item): Order
    {
        $this->actingAs($tenant)->post(route('penghuni.order.store'), [
            'items_json' => json_encode([['id' => $item->id, 'quantity' => 1]]),
            'payment_method' => 'Tunai / Bayar di Kamar',
        ]);

        return Order::latest('id')->firstOrFail();
    }
}
