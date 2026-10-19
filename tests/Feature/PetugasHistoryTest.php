<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PetugasHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $petugas;
    protected User $otherPetugas;
    protected User $penghuni;
    protected CatalogItem $catalogItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = User::where('role', 'petugas')->first() ?? User::factory()->create(['role' => 'petugas']);
        $this->otherPetugas = User::firstOrCreate(
            ['email' => 'cadangan@kostiana.com'],
            [
                'name' => 'Petugas Cadangan',
                'password' => bcrypt('password'),
                'role' => 'petugas',
            ]
        );
        $this->penghuni = User::where('role', 'penghuni')->first() ?? User::factory()->create(['role' => 'penghuni']);
        $this->catalogItem = CatalogItem::first();
    }

    /**
     * Test header displays cash and QRIS summary when completed orders exist today.
     */
    public function test_history_header_shows_cash_and_qris_totals(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-25 14:00:00'));

        // Order 1: Tunai
        $orderCash = Order::create([
            'order_code' => 'ORD-TEST-CASH',
            'user_id' => $this->penghuni->id,
            'room_number' => 'B-01',
            'order_type' => 'quick_galon',
            'total_amount' => 18000,
            'status' => 'selesai',
            'payment_status' => 'lunas',
            'payment_method' => 'Tunai / Bayar di Kamar',
            'processed_by' => $this->petugas->id,
            'completed_at' => Carbon::now(),
            'notes' => 'Tolong taruh depan pintu',
        ]);
        OrderItem::create([
            'order_id' => $orderCash->id,
            'catalog_item_id' => $this->catalogItem->id,
            'item_name' => 'Galon Aqua 19L',
            'quantity' => 1,
            'unit_price' => 18000,
            'subtotal' => 18000,
        ]);

        // Order 2: QRIS
        $orderQris = Order::create([
            'order_code' => 'ORD-TEST-QRIS',
            'user_id' => $this->penghuni->id,
            'room_number' => 'B-02',
            'order_type' => 'regular',
            'total_amount' => 25000,
            'status' => 'selesai',
            'payment_status' => 'lunas',
            'payment_method' => 'QRIS',
            'processed_by' => $this->otherPetugas->id,
            'created_at' => Carbon::now()->subMinutes(20),
            'completed_at' => Carbon::now(),
        ]);
        OrderItem::create([
            'order_id' => $orderQris->id,
            'catalog_item_id' => $this->catalogItem->id,
            'item_name' => 'Nasi Goreng Spesial',
            'quantity' => 1,
            'unit_price' => 25000,
            'subtotal' => 25000,
        ]);

        $response = $this->actingAs($this->petugas)->get(route('petugas.history'));

        $response->assertStatus(200);

        // 1. Header check: Tunai hari ini Rp 18.000 · QRIS Rp 25.000
        $response->assertSee('Tunai hari ini');
        $response->assertSee('18.000');
        $response->assertSee('QRIS');
        $response->assertSee('25.000');

        // 2. Daily group header check: subtotal tunai and + QRIS
        $response->assertSee('tunai');
        $response->assertSee('+');

        // 3. Closed row check: QRIS badge appears for non-cash
        $response->assertSee('QRIS');
        $response->assertSee('ORD-TEST-CASH');
        $response->assertSee('ORD-TEST-QRIS');

        // 4. Open nota check: delivery duration "selesai dalam 20 mnt"
        $response->assertSee('selesai dalam 20 mnt');

        // 5. Petugas check: "Petugas Cadangan" is displayed because processed_by is different from logged-in petugas
        $response->assertSee('Petugas Cadangan');

        // 6. Format total method check: "Total"
        $response->assertSee('Total');

        Carbon::setTestNow();
    }

    /**
     * Test header shows "Belum ada pesanan selesai hari ini" when no orders completed today.
     */
    public function test_history_header_shows_empty_message_when_no_completed_today(): void
    {
        // Set test date to a far future date with zero orders
        Carbon::setTestNow(Carbon::parse('2099-01-01 10:00:00'));

        $response = $this->actingAs($this->petugas)->get(route('petugas.history'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada pesanan selesai hari ini');
        $response->assertDontSee('Tunai hari ini');

        Carbon::setTestNow();
    }

    /**
     * Test search and date filter functionality on history page.
     */
    public function test_history_filter_by_query_and_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-18 14:00:00'));

        $order = Order::create([
            'order_code' => 'ORD-FILTER-TARGET',
            'user_id' => $this->penghuni->id,
            'room_number' => 'C-99',
            'order_type' => 'regular',
            'total_amount' => 15000,
            'status' => 'selesai',
            'payment_status' => 'lunas',
            'payment_method' => 'Tunai',
            'processed_by' => $this->petugas->id,
            'completed_at' => Carbon::now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'catalog_item_id' => $this->catalogItem->id,
            'item_name' => 'Kopi Susu',
            'quantity' => 1,
            'unit_price' => 15000,
            'subtotal' => 15000,
        ]);

        // Search match
        $searchRes = $this->actingAs($this->petugas)->get(route('petugas.history', ['q' => 'ORD-FILTER-TARGET']));
        $searchRes->assertStatus(200);
        $searchRes->assertSee('ORD-FILTER-TARGET');
        $searchRes->assertSee('C-99');

        // Date filter match
        $dateRes = $this->actingAs($this->petugas)->get(route('petugas.history', ['date' => '2026-10-18']));
        $dateRes->assertStatus(200);
        $dateRes->assertSee('ORD-FILTER-TARGET');

        Carbon::setTestNow();
    }
}
