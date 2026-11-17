<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesKosFixtures;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase, CreatesKosFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_items_and_rooms_without_photo_use_category_placeholder(): void
    {
        $this->assertStringEndsWith('/images/placeholders/galon.svg', $this->makeGalon()->image_url);
        $this->assertStringEndsWith('/images/placeholders/minuman.svg', $this->makeItem(['category' => 'minuman'])->image_url);
        $this->assertStringEndsWith('/images/placeholders/kamar.svg', $this->makeRoom()->image_url);
    }

    public function test_pengelola_can_add_catalog_item_with_photo(): void
    {
        $pengelola = $this->makeUser('pengelola');

        $this->actingAs($pengelola)->post(route('pengelola.katalog.store'), [
            'name' => 'Es Kopi Susu', 'category' => 'minuman', 'price' => 8000, 'stock' => 10,
            'minimum_stock' => 2, 'unit' => 'gelas', 'is_available' => 1,
            'image' => UploadedFile::fake()->image('kopi.jpg', 400, 300),
        ])->assertRedirect(route('pengelola.katalog.index'));

        $item = CatalogItem::where('name', 'Es Kopi Susu')->firstOrFail();
        $this->assertStringStartsWith('catalog/', $item->image);
        Storage::disk('public')->assertExists($item->image);
    }

    public function test_replacing_and_removing_room_photo_cleans_up_old_file(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $room = $this->makeRoom(['status' => 'tersedia']);
        $payload = [
            'room_number' => $room->room_number, 'floor' => 1, 'type' => 'Standar',
            'monthly_price' => 850000, 'facilities' => 'Kasur', 'status' => 'tersedia',
        ];

        $this->actingAs($pengelola)->put(route('pengelola.kamar.update', $room), $payload + [
            'image' => UploadedFile::fake()->image('a.png'),
        ]);
        $first = $room->fresh()->image;
        Storage::disk('public')->assertExists($first);

        $this->put(route('pengelola.kamar.update', $room), $payload + [
            'image' => UploadedFile::fake()->image('b.png'),
        ]);
        $second = $room->fresh()->image;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        // Edit tanpa berkas baru tidak mengubah foto.
        $this->put(route('pengelola.kamar.update', $room), $payload);
        $this->assertSame($second, $room->fresh()->image);

        $this->put(route('pengelola.kamar.update', $room), $payload + ['remove_image' => 1]);
        $this->assertNull($room->fresh()->image);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $room = $this->makeRoom(['status' => 'tersedia']);

        $this->actingAs($pengelola)->put(route('pengelola.kamar.update', $room), [
            'room_number' => $room->room_number, 'floor' => 1, 'type' => 'Standar',
            'monthly_price' => 850000, 'facilities' => 'Kasur', 'status' => 'tersedia',
            'image' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        $this->assertNull($room->fresh()->image);
    }
}
