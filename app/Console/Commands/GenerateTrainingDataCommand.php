<?php

namespace App\Console\Commands;

use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TenantProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTrainingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:training {count=100 : Jumlah transaksi dummy yang ingin digenerate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate transaksi pesanan dummy (status: selesai) dalam 30 hari terakhir untuk training model machine learning prediksi stok';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        if ($count <= 0) {
            $this->error('Jumlah data harus lebih besar dari 0.');
            return 1;
        }

        $this->info("Menyiapkan {$count} data transaksi pelatihan untuk model Machine Learning...");

        $tenants = TenantProfile::with(['user', 'room'])->where('is_active', true)->get();
        if ($tenants->isEmpty()) {
            $this->error('Tidak ada tenant profil aktif di database.');
            return 1;
        }

        $catalogItems = CatalogItem::where('is_available', true)->get();
        if ($catalogItems->isEmpty()) {
            $this->error('Tidak ada katalog item aktif.');
            return 1;
        }

        $petugasUser = User::where('role', 'petugas')->first();
        $petugasId = $petugasUser ? $petugasUser->id : null;

        $catalogByName = $catalogItems->keyBy('name');
        $galonAqua = $catalogByName->get('Galon Aqua 19L (Refill Asli)');
        $galonLeMin = $catalogByName->get('Galon Le Minerale 15L');
        $galonRO = $catalogByName->get('Galon Isi Ulang RO Segar');

        $mieDouble = $catalogByName->get('Indomie Goreng Double + Telur');
        $mieKuah = $catalogByName->get('Indomie Kuah Soto Spesial Telur');
        $nasgor = $catalogByName->get('Nasi Goreng Spesial Kos Tiana');
        $nasiTelur = $catalogByName->get('Nasi Telur Kecap Pontianak');
        $magelangan = $catalogByName->get('Magelangan Goreng Warkop');
        $rotiBakar = $catalogByName->get('Roti Bakar Coklat Keju Susu');

        $kopiSusu = $catalogByName->get('Kopi Susu Gula Aren Warkop');
        $esTeh = $catalogByName->get('Es Teh Manis Jumbo');
        $nutrisari = $catalogByName->get('Nutrisari Jeruk Peras Dingin');
        $esMilo = $catalogByName->get('Es Milo Susu Kental');

        $now = Carbon::now();
        $createdOrders = 0;
        $createdItems = 0;

        DB::beginTransaction();
        try {
            for ($i = 1; $i <= $count; $i++) {
                // Pilih penghuni secara proporsional
                $tenant = $tenants->random();
                $roomNumber = $tenant->room ? $tenant->room->room_number : 'Kamar A-04';

                // Tentukan hari dalam 30 hari terakhir dengan probabilitas lebih tinggi pada akhir pekan
                $daysAgo = rand(0, 29);
                $tempDate = $now->copy()->subDays($daysAgo);
                $isWeekend = in_array($tempDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);

                // Tipe sesi: 1=Galon (15%), 2=Siang (25%), 3=Sore (15%), 4=Malam (25%), 5=Begadang/Nugas (20%)
                $sessionRoll = rand(1, 100);

                $orderItemsToCreate = [];
                $orderType = 'regular';
                $orderNotes = null;

                if ($sessionRoll <= 15) {
                    // Quick Galon Order (pagi/siang)
                    $orderType = 'quick_galon';
                    $hour = rand(8, 16);
                    $minute = rand(0, 59);

                    $galonChoice = rand(1, 10);
                    $selectedGalon = $galonChoice <= 5 ? ($galonAqua ?? $galonItems->random()) : ($galonChoice <= 8 ? ($galonRO ?? $galonItems->random()) : ($galonLeMin ?? $galonItems->random()));
                    $qty = rand(1, 10) <= 8 ? 1 : 2;

                    $orderItemsToCreate[] = [
                        'item' => $selectedGalon,
                        'qty' => $qty,
                        'note' => $qty > 1 ? 'Titip 2 galon ya mas' : 'Tolong bantu pasangkan ke dispenser',
                    ];
                    $orderNotes = 'Tolong antar ke lantai kamar, terima kasih mas Joko';
                } elseif ($sessionRoll <= 40) {
                    // Sesi Makan Siang (11:30 - 13:45)
                    $hour = rand(11, 13);
                    $minute = rand(15, 55);

                    $lunchFood = [
                        $nasgor,
                        $nasiTelur,
                        $mieDouble,
                        $magelangan,
                    ];
                    $food = collect($lunchFood)->filter()->random();
                    $drink = collect([$esTeh, $nutrisari, $esTeh])->filter()->random();

                    $orderItemsToCreate[] = [
                        'item' => $food,
                        'qty' => 1,
                        'note' => rand(0, 1) ? 'Pedas sedang' : 'Jangan pakai mentimun',
                    ];
                    if ($drink) {
                        $orderItemsToCreate[] = [
                            'item' => $drink,
                            'qty' => rand(1, 2),
                            'note' => 'Es batu banyak ya mas',
                        ];
                    }
                } elseif ($sessionRoll <= 55) {
                    // Sesi Sore & Santai (15:00 - 17:30)
                    $hour = rand(15, 17);
                    $minute = rand(0, 50);

                    $snack = collect([$rotiBakar, $esMilo, $nutrisari, $esTeh])->filter()->random();
                    $orderItemsToCreate[] = [
                        'item' => $snack,
                        'qty' => 1,
                        'note' => 'Coklat dan keju dipisah ya kalau bisa',
                    ];
                    if ($snack->category === 'makanan' && $esTeh) {
                        $orderItemsToCreate[] = [
                            'item' => $esTeh,
                            'qty' => 1,
                            'note' => 'Manis sedang',
                        ];
                    }
                } elseif ($sessionRoll <= 75) {
                    // Sesi Makan Malam (18:30 - 20:45)
                    $hour = rand(18, 20);
                    $minute = rand(10, 50);

                    $dinnerFoods = collect([$nasgor, $magelangan, $mieKuah, $mieDouble, $nasiTelur])->filter();
                    $mainDish = $dinnerFoods->random();

                    $orderItemsToCreate[] = [
                        'item' => $mainDish,
                        'qty' => 1,
                        'note' => rand(0, 1) ? 'Telur ceplok 1/2 matang' : 'Pedas gurih',
                    ];
                    if ($esTeh) {
                        $orderItemsToCreate[] = [
                            'item' => $esTeh,
                            'qty' => 1,
                            'note' => null,
                        ];
                    }
                } else {
                    // Sesi Nugas / Begadang Mahasiswa (21:00 - 01:30)
                    // Peningkatan frekuensi jika dalam 10 hari terakhir (musim ujian)
                    $hour = rand(0, 1) ? rand(21, 23) : 0;
                    $minute = rand(5, 50);

                    $midnightFav = collect([$mieDouble, $kopiSusu, $rotiBakar])->filter()->random();
                    $orderItemsToCreate[] = [
                        'item' => $midnightFav,
                        'qty' => 1,
                        'note' => 'Teman nugas lembur',
                    ];

                    // Kopi susu sering dipesan bersamaan
                    if ($midnightFav->id !== ($kopiSusu->id ?? 0) && $kopiSusu && rand(1, 10) <= 7) {
                        $orderItemsToCreate[] = [
                            'item' => $kopiSusu,
                            'qty' => 1,
                            'note' => 'Kopi mantap jangan kemanisan',
                        ];
                    }
                }

                $orderTime = $now->copy()->subDays($daysAgo)->setHour($hour)->setMinute($minute)->setSecond(rand(0, 59));

                $orderCode = sprintf(
                    'ORD-%s-%03d-%04d',
                    $orderTime->format('Ymd'),
                    $daysAgo,
                    rand(1000, 9999)
                );

                $paymentMethod = rand(1, 10) <= 6 ? 'Tunai / Bayar di Kamar' : 'Transfer QRIS / BCA';

                $order = Order::create([
                    'order_code'     => $orderCode,
                    'user_id'        => $tenant->user_id,
                    'room_number'    => $roomNumber,
                    'order_type'     => $orderType,
                    'total_amount'   => 0,
                    'status'         => 'selesai',
                    'payment_status' => 'lunas',
                    'payment_method' => $paymentMethod,
                    'notes'          => $orderNotes,
                    'processed_by'   => $petugasId,
                    'completed_at'   => $orderTime->copy()->addMinutes(rand(12, 28)),
                    'created_at'     => $orderTime,
                    'updated_at'     => $orderTime->copy()->addMinutes(28),
                ]);

                $totalAmount = 0;
                foreach ($orderItemsToCreate as $oi) {
                    $item = $oi['item'];
                    $qty = $oi['qty'];
                    $subtotal = $qty * $item->price;
                    $totalAmount += $subtotal;

                    OrderItem::create([
                        'order_id'        => $order->id,
                        'catalog_item_id' => $item->id,
                        'item_name'       => $item->name,
                        'quantity'        => $qty,
                        'unit_price'      => $item->price,
                        'subtotal'        => $subtotal,
                        'notes'           => $oi['note'],
                        'created_at'      => $orderTime,
                        'updated_at'      => $orderTime,
                    ]);
                    $createdItems++;
                }

                $order->update(['total_amount' => $totalAmount]);
                $createdOrders++;
            }

            DB::commit();

            $this->info("✓ Sukses membuat {$createdOrders} order transaksi dummy ({$createdItems} item pesanan) dalam rentang 30 hari terakhir!");
            $this->newLine();

            // Jalankan model prediksi Python secara instan dengan data baru
            $this->info("Memulai pelatihan model Python Machine Learning dengan data historis terbaru...");
            $exitCode = $this->call('stok:prediksi');

            if ($exitCode === 0) {
                $this->info("✓ Model Machine Learning Python berhasil dilatih ulang dan tabel stock_predictions telah diperbarui!");
            } else {
                $this->warn("Model Python selesai dengan kode status: {$exitCode}");
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Gagal men-generate data pelatihan: " . $e->getMessage());
            return 1;
        }
    }
}
