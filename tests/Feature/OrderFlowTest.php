<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\StockLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesKosFixtures;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase, CreatesKosFixtures;

    private function cart(array $rows): array
    {
        return ['items_json' => json_encode($rows), 'payment_method' => 'Tunai / Bayar di Kamar'];
    }

    public function test_placing_an_order_reduces_stock_logs_it_and_notifies_staff(): void
    {
        $tenant = $this->makeTenant();
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 10, 'price' => 12000]);

        $this->actingAs($tenant)
            ->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 3, 'notes' => 'pedas']]))
            ->assertRedirect(route('penghuni.pesanan'));

        $order = Order::firstOrFail();
        $this->assertSame(36000, $order->total_amount);
        $this->assertSame('menunggu', $order->status);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-0001$/', $order->order_code);
        $this->assertSame(7, $item->fresh()->stock);

        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'quantity' => 3, 'notes' => 'pedas']);
        $this->assertDatabaseHas('stock_logs', [
            'catalog_item_id' => $item->id, 'type' => 'out', 'quantity' => 3,
            'before_stock' => 10, 'after_stock' => 7, 'reference_id' => $order->id,
        ]);
        $this->assertDatabaseHas('notifications', ['user_id' => $petugas->id, 'type' => 'order']);
    }

    public function test_order_codes_are_sequential_and_unique(): void
    {
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['stock' => 50]);

        foreach (range(1, 3) as $i) {
            $this->actingAs($tenant)->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 1]]));
        }

        $codes = Order::orderBy('id')->pluck('order_code')->all();
        $this->assertCount(3, array_unique($codes));
        $this->assertStringEndsWith('-0003', $codes[2]);
    }

    public function test_order_is_rejected_when_stock_is_insufficient_and_nothing_changes(): void
    {
        $tenant = $this->makeTenant();
        $enough = $this->makeItem(['stock' => 10]);
        $short = $this->makeItem(['stock' => 1]);

        $this->actingAs($tenant)
            ->post(route('penghuni.order.store'), $this->cart([
                ['id' => $enough->id, 'quantity' => 2],
                ['id' => $short->id, 'quantity' => 5],
            ]))
            ->assertSessionHasErrors('items_json');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, $enough->fresh()->stock, 'Stok item pertama harus dikembalikan (rollback).');
        $this->assertSame(0, StockLog::count());
    }

    public function test_duplicate_cart_lines_are_merged_and_checked_against_stock(): void
    {
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['stock' => 3]);

        $this->actingAs($tenant)
            ->post(route('penghuni.order.store'), $this->cart([
                ['id' => $item->id, 'quantity' => 2],
                ['id' => $item->id, 'quantity' => 2],
            ]))
            ->assertSessionHasErrors('items_json');

        $this->assertSame(3, $item->fresh()->stock);
    }

    public function test_invalid_payment_method_and_oversized_values_are_rejected(): void
    {
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['stock' => 100]);

        $this->actingAs($tenant)
            ->post(route('penghuni.order.store'), ['items_json' => json_encode([['id' => $item->id, 'quantity' => 1]]), 'payment_method' => 'Bitcoin'])
            ->assertSessionHasErrors('payment_method');

        $this->actingAs($tenant)
            ->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 1, 'notes' => str_repeat('x', 300)]]))
            ->assertSessionHasErrors('items_json');

        $this->actingAs($tenant)
            ->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 21]]))
            ->assertSessionHasErrors('items_json');

        $this->assertSame(0, Order::count());
    }

    public function test_quick_galon_only_accepts_galon_items(): void
    {
        $tenant = $this->makeTenant();
        $food = $this->makeItem(['stock' => 10]);
        $galon = $this->makeGalon(['stock' => 10]);

        $this->actingAs($tenant)
            ->post(route('penghuni.quick_galon'), ['catalog_item_id' => $food->id, 'quantity' => 1])
            ->assertSessionHasErrors('catalog_item_id');

        $this->actingAs($tenant)
            ->post(route('penghuni.quick_galon'), ['catalog_item_id' => $galon->id, 'quantity' => 2])
            ->assertRedirect(route('penghuni.pesanan'));

        $this->assertSame('quick_galon', Order::firstOrFail()->order_type);
        $this->assertSame(8, $galon->fresh()->stock);
    }

    public function test_tenant_can_cancel_waiting_order_and_stock_is_restored(): void
    {
        $tenant = $this->makeTenant();
        $item = $this->makeItem(['stock' => 10]);

        $this->actingAs($tenant)->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 4]]));
        $order = Order::firstOrFail();

        $this->post(route('penghuni.pesanan.batal', $order->id))->assertRedirect(route('penghuni.pesanan'));

        $this->assertSame('batal', $order->fresh()->status);
        $this->assertSame(10, $item->fresh()->stock);
        $this->assertDatabaseHas('stock_logs', ['reference_type' => 'order_cancellation', 'reference_id' => $order->id, 'quantity' => 4]);
    }

    public function test_order_cannot_be_cancelled_once_processing_even_with_stale_page(): void
    {
        $tenant = $this->makeTenant();
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 10]);

        $this->actingAs($tenant)->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 2]]));
        $order = Order::firstOrFail();

        $this->actingAs($petugas)->patch(route('petugas.orders.update-status', $order));

        $this->actingAs($tenant)->post(route('penghuni.pesanan.batal', $order->id))->assertSessionHas('error');

        $this->assertSame('diproses', $order->fresh()->status);
        $this->assertSame(8, $item->fresh()->stock, 'Stok tidak boleh dikembalikan untuk pesanan yang sudah diproses.');
    }

    public function test_tenant_cannot_cancel_someone_elses_order(): void
    {
        $owner = $this->makeTenant();
        $other = $this->makeTenant();
        $item = $this->makeItem(['stock' => 10]);

        $this->actingAs($owner)->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 1]]));
        $order = Order::firstOrFail();

        $this->actingAs($other)->post(route('penghuni.pesanan.batal', $order->id))->assertNotFound();
        $this->assertSame('menunggu', $order->fresh()->status);
    }

    public function test_petugas_advances_status_one_step_at_a_time(): void
    {
        $tenant = $this->makeTenant();
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 10]);

        $this->actingAs($tenant)->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 1]]));
        $order = Order::firstOrFail();

        $this->actingAs($petugas);

        foreach (['diproses', 'diantar', 'selesai'] as $expected) {
            $this->patch(route('petugas.orders.update-status', $order))->assertSessionHas('success');
            $this->assertSame($expected, $order->fresh()->status);
        }

        $done = $order->fresh();
        $this->assertSame('lunas', $done->payment_status);
        $this->assertNotNull($done->completed_at);
        $this->assertSame(3, $tenant->appNotifications()->where('title', 'like', 'Pesanan%')->where('title', '!=', 'Pesanan Berhasil Masuk Antrean!')->count());
    }

    public function test_status_cannot_be_skipped_or_reopened(): void
    {
        $tenant = $this->makeTenant();
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 10]);

        $this->actingAs($tenant)->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 1]]));
        $order = Order::firstOrFail();

        $this->actingAs($petugas);

        // Melompat langsung ke "selesai" ditolak.
        $this->patch(route('petugas.orders.update-status', $order), ['status' => 'selesai'])->assertSessionHas('error');
        $this->assertSame('menunggu', $order->fresh()->status);

        // Pesanan batal tidak bisa dihidupkan lagi.
        $order->update(['status' => 'batal']);
        $this->patch(route('petugas.orders.update-status', $order), ['status' => 'diproses'])->assertSessionHas('error');
        $this->patch(route('petugas.orders.update-status', $order))->assertSessionHas('error');
        $this->assertSame('batal', $order->fresh()->status);
    }

    public function test_roles_are_isolated(): void
    {
        $tenant = $this->makeTenant();
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem();
        $this->actingAs($tenant)->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 1]]));
        $order = Order::firstOrFail();

        $this->actingAs($tenant)->get(route('pengelola.dashboard'))->assertRedirect(route('penghuni.beranda'));
        $this->actingAs($tenant)->patch(route('petugas.orders.update-status', $order))->assertRedirect(route('penghuni.beranda'));
        $this->assertSame('menunggu', $order->fresh()->status);
        $this->actingAs($petugas)->get(route('penghuni.tagihan'))->assertRedirect(route('petugas.antrean'));
    }

    public function test_cash_payment_helper_handles_configured_and_legacy_values(): void
    {
        $this->assertTrue(Order::isCashMethod('Tunai / Bayar di Kamar'));
        $this->assertTrue(Order::isCashMethod(null));
        $this->assertTrue(Order::isCashMethod('Tunai'));
        $this->assertFalse(Order::isCashMethod('QRIS'));
        $this->assertFalse(Order::isCashMethod('Transfer QRIS / BCA'));
    }

    public function test_deleting_a_catalog_item_keeps_order_history_and_reports(): void
    {
        $tenant = $this->makeTenant();
        $pengelola = $this->makeUser('pengelola');
        $item = $this->makeItem(['stock' => 10]);

        $this->actingAs($tenant)->post(route('penghuni.order.store'), $this->cart([['id' => $item->id, 'quantity' => 2]]));
        $order = Order::firstOrFail();

        $this->actingAs($pengelola)->delete(route('pengelola.katalog.destroy', $item))->assertSessionHas('success');

        $this->assertSoftDeleted('catalog_items', ['id' => $item->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'catalog_item_id' => $item->id]);
        $this->assertGreaterThan(0, StockLog::where('catalog_item_id', $item->id)->count());
        $this->assertNull(CatalogItem::find($item->id));

        // Halaman audit stok tetap dapat dibuka walau item sudah dihapus.
        $this->get(route('pengelola.stok.index'))->assertOk();
    }
}
