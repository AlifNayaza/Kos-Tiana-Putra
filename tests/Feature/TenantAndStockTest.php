<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\StockLog;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesKosFixtures;
use Tests\TestCase;

class TenantAndStockTest extends TestCase
{
    use RefreshDatabase, CreatesKosFixtures;

    private function tenantPayload(Room $room, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dimas Pratama', 'email' => 'dimas@example.test', 'phone' => '081234567890',
            'room_id' => $room->id, 'start_date' => '2026-11-01',
        ], $overrides);
    }

    public function test_new_tenant_gets_random_temporary_password_and_must_change_it(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $room = $this->makeRoom(['status' => 'tersedia']);

        $response = $this->actingAs($pengelola)->post(route('pengelola.penghuni.store'), $this->tenantPayload($room));

        $response->assertRedirect(route('pengelola.penghuni.index'));
        $temp = session('temp_password');
        $this->assertNotSame('password', $temp['password']);
        $this->assertGreaterThanOrEqual(10, strlen($temp['password']));

        $user = User::where('email', 'dimas@example.test')->firstOrFail();
        $this->assertTrue(Hash::check($temp['password'], $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertSame('terisi', $room->fresh()->status);
    }

    public function test_cannot_assign_an_occupied_or_under_repair_room(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $occupied = $this->makeRoom(['status' => 'terisi']);
        $repair = $this->makeRoom(['status' => 'perbaikan']);

        $this->actingAs($pengelola);

        $this->post(route('pengelola.penghuni.store'), $this->tenantPayload($occupied))->assertSessionHasErrors('room_id');
        $this->post(route('pengelola.penghuni.store'), $this->tenantPayload($repair, ['email' => 'lain@example.test']))->assertSessionHasErrors('room_id');

        $this->assertSame(0, TenantProfile::count());
        $this->assertSame(0, User::where('role', 'penghuni')->count(), 'Akun tidak boleh terbuat bila kamar ditolak.');
    }

    public function test_checkout_frees_room_and_blocks_the_account(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $tenant = $this->makeTenant();
        $this->makeBill($tenant);
        $roomId = $tenant->tenantProfile->room_id;

        $this->actingAs($pengelola)
            ->post(route('pengelola.penghuni.checkout', $tenant->tenantProfile))
            ->assertSessionHas('success')
            ->assertSessionHas('warning'); // masih ada tagihan belum lunas

        $this->assertFalse($tenant->tenantProfile->fresh()->is_active);
        $this->assertSame('tersedia', Room::find($roomId)->status);
        $this->assertFalse($tenant->fresh()->canAccessApp());
    }

    public function test_pengelola_can_reset_a_tenant_password(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $tenant = $this->makeTenant();

        $this->actingAs($pengelola)->post(route('pengelola.penghuni.reset_password', $tenant->tenantProfile))->assertSessionHas('temp_password');

        $temp = session('temp_password')['password'];
        $fresh = $tenant->fresh();
        $this->assertTrue(Hash::check($temp, $fresh->password));
        $this->assertTrue($fresh->must_change_password);
    }

    public function test_room_with_history_cannot_be_deleted_but_an_unused_one_can(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $tenant = $this->makeTenant();
        $this->makeBill($tenant);
        $tenant->tenantProfile->update(['is_active' => false]);
        $used = $tenant->tenantProfile->room;
        $used->update(['status' => 'tersedia']);
        $unused = $this->makeRoom(['status' => 'tersedia']);

        $this->actingAs($pengelola);

        $this->delete(route('pengelola.kamar.destroy', $used))->assertSessionHas('error');
        $this->assertNotNull(Room::find($used->id));

        $this->delete(route('pengelola.kamar.destroy', $unused))->assertSessionHas('success');
        $this->assertNull(Room::find($unused->id));
    }

    public function test_petugas_can_add_and_remove_stock_but_never_below_zero(): void
    {
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 5]);
        $this->actingAs($petugas);

        $this->post(route('petugas.stocks.adjust'), ['catalog_item_id' => $item->id, 'type' => 'in', 'quantity' => 10, 'notes' => 'Restock'])
            ->assertSessionHas('success');
        $this->assertSame(15, $item->fresh()->stock);

        $this->post(route('petugas.stocks.adjust'), ['catalog_item_id' => $item->id, 'type' => 'out', 'quantity' => 100, 'notes' => 'Rusak'])
            ->assertSessionHasErrors('quantity');
        $this->assertSame(15, $item->fresh()->stock);

        $this->assertSame(1, StockLog::where('type', 'in')->count());
    }

    public function test_stock_opname_uses_the_current_stock_not_the_value_shown_on_screen(): void
    {
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 10]);
        $this->actingAs($petugas);

        // Layar petugas menampilkan stok 10, lalu pesanan masuk sehingga stok sebenarnya 7.
        $item->update(['stock' => 7]);

        // Hasil hitung fisik 9 -> stok harus menjadi 9 (selisih +2 dari 7), bukan 7 + (9 - 10).
        $this->post(route('petugas.stocks.adjust'), [
            'catalog_item_id' => $item->id, 'physical_count' => 9, 'type' => 'out', 'quantity' => 1, 'notes' => 'Hasil hitung ulang fisik',
        ])->assertSessionHas('success');

        $this->assertSame(9, $item->fresh()->stock);
        $log = StockLog::latest('id')->first();
        $this->assertSame('in', $log->type);
        $this->assertSame(2, $log->quantity);
        $this->assertSame('stock_opname', $log->reference_type);
        $this->assertStringContainsString('Hitung fisik 7 -> 9', $log->notes);
    }

    public function test_stock_opname_without_difference_changes_nothing(): void
    {
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 10]);

        $this->actingAs($petugas)->post(route('petugas.stocks.adjust'), [
            'catalog_item_id' => $item->id, 'physical_count' => 10, 'notes' => 'Cek mingguan',
        ])->assertSessionHas('info');

        $this->assertSame(0, StockLog::count());
    }

    public function test_stock_opname_can_set_stock_to_zero(): void
    {
        $petugas = $this->makeUser('petugas');
        $item = $this->makeItem(['stock' => 4]);

        $this->actingAs($petugas)->post(route('petugas.stocks.adjust'), [
            'catalog_item_id' => $item->id, 'physical_count' => 0, 'notes' => 'Habis semua',
        ])->assertSessionHas('success');

        $this->assertSame(0, $item->fresh()->stock);
    }
}
