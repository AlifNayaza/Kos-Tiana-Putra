<?php

namespace Tests\Feature;

use App\Models\RentBill;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesKosFixtures;
use Tests\TestCase;

class RentBillTest extends TestCase
{
    use RefreshDatabase, CreatesKosFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->image('transfer.jpg', 200, 200);
    }

    public function test_tenant_uploads_proof_to_private_disk_and_pengelola_is_notified(): void
    {
        $tenant = $this->makeTenant();
        $pengelola = $this->makeUser('pengelola');
        $bill = $this->makeBill($tenant);

        $this->actingAs($tenant)
            ->post(route('penghuni.tagihan.upload_proof', $bill->id), ['payment_method' => 'QRIS', 'payment_proof' => $this->proof()])
            ->assertRedirect(route('penghuni.tagihan'))
            ->assertSessionHas('success');

        $bill->refresh();
        $this->assertNotNull($bill->payment_proof);
        Storage::disk('local')->assertExists($bill->payment_proof);
        Storage::disk('public')->assertMissing($bill->payment_proof);
        $this->assertDatabaseHas('notifications', ['user_id' => $pengelola->id, 'type' => 'rent']);
    }

    public function test_reuploading_replaces_the_previous_file(): void
    {
        $tenant = $this->makeTenant();
        $bill = $this->makeBill($tenant);
        $this->actingAs($tenant);

        $this->post(route('penghuni.tagihan.upload_proof', $bill->id), ['payment_method' => 'QRIS', 'payment_proof' => $this->proof()]);
        $first = $bill->fresh()->payment_proof;

        $this->post(route('penghuni.tagihan.upload_proof', $bill->id), ['payment_method' => 'QRIS', 'payment_proof' => $this->proof()]);
        $second = $bill->fresh()->payment_proof;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_paid_bill_rejects_new_proof(): void
    {
        $tenant = $this->makeTenant();
        $bill = $this->makeBill($tenant, ['status' => 'lunas']);

        $this->actingAs($tenant)
            ->post(route('penghuni.tagihan.upload_proof', $bill->id), ['payment_method' => 'QRIS', 'payment_proof' => $this->proof()])
            ->assertSessionHas('info');

        $this->assertNull($bill->fresh()->payment_proof);
    }

    public function test_upload_validates_method_and_file_type(): void
    {
        $tenant = $this->makeTenant();
        $bill = $this->makeBill($tenant);
        $this->actingAs($tenant);

        $this->post(route('penghuni.tagihan.upload_proof', $bill->id), ['payment_method' => 'Bitcoin', 'payment_proof' => $this->proof()])
            ->assertSessionHasErrors('payment_method');

        $this->post(route('penghuni.tagihan.upload_proof', $bill->id), ['payment_method' => 'QRIS', 'payment_proof' => UploadedFile::fake()->create('virus.php', 10)])
            ->assertSessionHasErrors('payment_proof');
    }

    public function test_tenant_cannot_upload_to_someone_elses_bill(): void
    {
        $owner = $this->makeTenant();
        $other = $this->makeTenant();
        $bill = $this->makeBill($owner);

        $this->actingAs($other)
            ->post(route('penghuni.tagihan.upload_proof', $bill->id), ['payment_method' => 'QRIS', 'payment_proof' => $this->proof()])
            ->assertNotFound();
    }

    public function test_proof_is_only_visible_to_pengelola_and_the_owner(): void
    {
        $owner = $this->makeTenant();
        $other = $this->makeTenant();
        $pengelola = $this->makeUser('pengelola');
        $petugas = $this->makeUser('petugas');
        $bill = $this->makeBill($owner);

        $this->actingAs($owner)->post(route('penghuni.tagihan.upload_proof', $bill->id), ['payment_method' => 'QRIS', 'payment_proof' => $this->proof()]);
        $url = route('tagihan.proof', $bill);

        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));

        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($petugas)->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk();
        $this->actingAs($pengelola)->get($url)->assertOk();
    }

    public function test_confirming_payment_marks_paid_once_and_keeps_tenant_notes(): void
    {
        $tenant = $this->makeTenant();
        $pengelola = $this->makeUser('pengelola');
        $bill = $this->makeBill($tenant, ['notes' => 'Transfer atas nama Dimas']);

        $this->actingAs($pengelola)->post(route('pengelola.tagihan.confirm', $bill))->assertSessionHas('success');

        $bill->refresh();
        $this->assertSame('lunas', $bill->status);
        $this->assertSame($pengelola->id, $bill->confirmed_by);
        $this->assertStringContainsString('Transfer atas nama Dimas', $bill->notes);

        // Konfirmasi kedua tidak mengirim notifikasi ganda.
        $before = $tenant->appNotifications()->count();
        $this->post(route('pengelola.tagihan.confirm', $bill))->assertSessionHas('info');
        $this->assertSame($before, $tenant->appNotifications()->count());
    }

    public function test_marking_overdue_cannot_downgrade_a_paid_bill(): void
    {
        $tenant = $this->makeTenant();
        $pengelola = $this->makeUser('pengelola');
        $paid = $this->makeBill($tenant, ['status' => 'lunas', 'month' => 1, 'year' => 2026]);
        $open = $this->makeBill($tenant, ['month' => 2, 'year' => 2026]);

        $this->actingAs($pengelola);

        $this->post(route('pengelola.tagihan.overdue', $paid))->assertSessionHas('info');
        $this->assertSame('lunas', $paid->fresh()->status);

        $this->post(route('pengelola.tagihan.overdue', $open))->assertSessionHas('warning');
        $this->assertSame('menunggak', $open->fresh()->status);
    }

    public function test_generating_bills_is_idempotent_and_skips_checked_out_tenants(): void
    {
        $active = $this->makeTenant();
        $gone = $this->makeTenant();
        $gone->tenantProfile->update(['is_active' => false]);
        $pengelola = $this->makeUser('pengelola');

        $payload = ['month' => 11, 'year' => 2026, 'due_date' => '2026-11-10'];

        $this->actingAs($pengelola)->post(route('pengelola.tagihan.generate'), $payload)->assertSessionHas('success');
        $this->post(route('pengelola.tagihan.generate'), $payload);

        $this->assertSame(1, RentBill::count());
        $this->assertDatabaseHas('rent_bills', ['tenant_profile_id' => $active->tenantProfile->id, 'month' => 11, 'year' => 2026, 'amount' => 850000]);
    }

    public function test_scheduled_commands_issue_bills_and_mark_overdue(): void
    {
        $tenant = $this->makeTenant();
        $this->makeBill($tenant, ['month' => 9, 'year' => 2026, 'due_date' => '2026-09-10']);
        $this->makeBill($tenant, ['month' => 10, 'year' => 2026, 'due_date' => '2026-10-10', 'status' => 'lunas']);

        Carbon::setTestNow('2026-11-09 06:00:00');

        $this->artisan('tagihan:terbitkan')->assertSuccessful();
        $this->assertDatabaseHas('rent_bills', ['month' => 11, 'year' => 2026, 'due_date' => '2026-11-10 00:00:00']);

        $this->artisan('tagihan:tandai-menunggak')->assertSuccessful();
        $this->assertSame('menunggak', RentBill::where('month', 9)->first()->status);
        $this->assertSame('lunas', RentBill::where('month', 10)->first()->status);
        $this->assertSame('menunggu', RentBill::where('month', 11)->first()->status);

        Carbon::setTestNow();
    }

    public function test_bill_filters_survive_pagination_and_all_months_filter_works(): void
    {
        $pengelola = $this->makeUser('pengelola');
        $tenant = $this->makeTenant();
        $this->makeBill($tenant, ['month' => 1, 'year' => 2026, 'status' => 'lunas']);
        $this->makeBill($tenant, ['month' => 2, 'year' => 2026]);

        $response = $this->actingAs($pengelola)->get(route('pengelola.tagihan.index', ['month' => 'semua', 'year' => 2026]));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('stats')['total_bills']);
        $this->assertSame(850000, (int) $response->viewData('stats')['lunas_sum']);
    }
}
