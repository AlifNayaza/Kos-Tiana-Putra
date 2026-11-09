<?php

namespace Tests\Concerns;

use App\Models\CatalogItem;
use App\Models\RentBill;
use App\Models\Room;
use App\Models\TenantProfile;
use App\Models\User;

/**
 * Data minimal yang dibuat sendiri oleh tiap test, sehingga test tidak
 * bergantung pada isi database development / seeder.
 */
trait CreatesKosFixtures
{
    protected function makeUser(string $role, array $overrides = []): User
    {
        static $n = 0;
        $n++;

        return User::create(array_merge([
            'name'     => ucfirst($role) . " {$n}",
            'email'    => "{$role}{$n}@example.test",
            'phone'    => '0812000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'role'     => $role,
            'password' => 'password',
        ], $overrides));
    }

    protected function makeRoom(array $overrides = []): Room
    {
        static $n = 0;
        $n++;

        return Room::create(array_merge([
            'room_number'   => 'Kamar T-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'floor'         => 1,
            'type'          => 'Standar',
            'monthly_price' => 850000,
            'facilities'    => 'Kasur, Lemari',
            'status'        => 'terisi',
        ], $overrides));
    }

    /**
     * Penghuni lengkap: akun + kamar + profil hunian aktif.
     */
    protected function makeTenant(array $userOverrides = [], ?Room $room = null): User
    {
        $user = $this->makeUser('penghuni', $userOverrides);
        $room ??= $this->makeRoom();

        TenantProfile::create([
            'user_id'    => $user->id,
            'room_id'    => $room->id,
            'start_date' => now()->subMonths(2)->toDateString(),
            'is_active'  => true,
        ]);

        return $user->fresh();
    }

    protected function makeItem(array $overrides = []): CatalogItem
    {
        static $n = 0;
        $n++;

        return CatalogItem::create(array_merge([
            'name'          => "Item Uji {$n}",
            'category'      => 'makanan',
            'price'         => 10000,
            'stock'         => 20,
            'minimum_stock' => 5,
            'unit'          => 'porsi',
            'is_available'  => true,
        ], $overrides));
    }

    protected function makeGalon(array $overrides = []): CatalogItem
    {
        return $this->makeItem(array_merge([
            'name'     => 'Galon Uji ' . uniqid(),
            'category' => 'galon',
            'price'    => 18000,
            'unit'     => 'galon',
        ], $overrides));
    }

    protected function makeBill(User $tenant, array $overrides = []): RentBill
    {
        $profile = $tenant->tenantProfile;

        return RentBill::create(array_merge([
            'bill_code'         => 'TAG-' . uniqid(),
            'tenant_profile_id' => $profile->id,
            'room_id'           => $profile->room_id,
            'month'             => (int) now()->month,
            'year'              => (int) now()->year,
            'amount'            => 850000,
            'due_date'          => now()->addDays(5)->toDateString(),
            'status'            => 'menunggu',
        ], $overrides));
    }
}
