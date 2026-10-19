<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RentBill;
use App\Models\Room;
use App\Models\StockLog;
use App\Models\StockPrediction;
use App\Models\TenantProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic data for "Kos Tiana Putra 1".
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // 1. PENGGUNA (USERS & RBAC)
        // ---------------------------------------------------------------------
        $pengelola = User::create([
            'name' => 'H. Tiana Sutisna',
            'email' => 'pengelola@kostiana.com',
            'phone' => '081234567890',
            'role' => 'pengelola',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $petugas1 = User::create([
            'name' => 'Joko Prasetyo',
            'email' => 'petugas@kostiana.com',
            'phone' => '081234567891',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $petugas2 = User::create([
            'name' => 'Budi Hartono',
            'email' => 'budi.petugas@kostiana.com',
            'phone' => '081234567892',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Penghuni Utama untuk Demo & Testing
        $penghuniUtama = User::create([
            'name' => 'Dimas Pratama',
            'email' => 'penghuni@kostiana.com',
            'phone' => '081298765401',
            'role' => 'penghuni',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Penghuni Lainnya
        $penghuniUsers = [
            'A-01' => User::create([
                'name' => 'Reza Fahlevi',
                'email' => 'reza@gmail.com',
                'phone' => '081298765402',
                'role' => 'penghuni',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]),
            'A-02' => User::create([
                'name' => 'Fajar Nugroho',
                'email' => 'fajar@gmail.com',
                'phone' => '081298765403',
                'role' => 'penghuni',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]),
            'A-03' => User::create([
                'name' => 'Aditya Wicaksono',
                'email' => 'aditya@gmail.com',
                'phone' => '081298765404',
                'role' => 'penghuni',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]),
            'A-04' => $penghuniUtama, // Dimas Pratama di Kamar A-04
            'A-05' => User::create([
                'name' => 'Muhammad Ilham',
                'email' => 'ilham@gmail.com',
                'phone' => '081298765405',
                'role' => 'penghuni',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]),
            'B-01' => User::create([
                'name' => 'Bagas Satria',
                'email' => 'bagas@gmail.com',
                'phone' => '081298765406',
                'role' => 'penghuni',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]),
            'B-02' => User::create([
                'name' => 'Gilang Ramadhan',
                'email' => 'gilang@gmail.com',
                'phone' => '081298765407',
                'role' => 'penghuni',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]),
            'B-03' => User::create([
                'name' => 'Rian Hidayat',
                'email' => 'rian@gmail.com',
                'phone' => '081298765408',
                'role' => 'penghuni',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]),
        ];

        // ---------------------------------------------------------------------
        // 2. DATA KAMAR (10 KAMAR - KOS TIANA PUTRA 1)
        // ---------------------------------------------------------------------
        $roomsData = [
            [
                'room_number' => 'Kamar A-01',
                'floor' => 1,
                'type' => 'Standar Single',
                'monthly_price' => 850000,
                'facilities' => 'Kamar Mandi Dalam, Kasur Springbed, Lemari 2 Pintu, Meja Belajar, Kipas Angin, WiFi 50Mbps',
                'status' => 'terisi',
                'size' => '3 x 4 m',
                'description' => 'Kamar lantai 1 dekat dengan ruang jemuran dan parkir motor depan.',
            ],
            [
                'room_number' => 'Kamar A-02',
                'floor' => 1,
                'type' => 'Standar Single',
                'monthly_price' => 850000,
                'facilities' => 'Kamar Mandi Dalam, Kasur Springbed, Lemari 2 Pintu, Meja Belajar, Kipas Angin, WiFi 50Mbps',
                'status' => 'terisi',
                'size' => '3 x 4 m',
                'description' => 'Kamar lantai 1 ventilasi sejuk dekat taman kecil samping.',
            ],
            [
                'room_number' => 'Kamar A-03',
                'floor' => 1,
                'type' => 'Standar Single',
                'monthly_price' => 850000,
                'facilities' => 'Kamar Mandi Dalam, Kasur Springbed, Lemari 2 Pintu, Meja Belajar, Kipas Angin, WiFi 50Mbps',
                'status' => 'terisi',
                'size' => '3 x 4 m',
                'description' => 'Kamar lantai 1 posisi tenang, dekat dapur bersama.',
            ],
            [
                'room_number' => 'Kamar A-04',
                'floor' => 1,
                'type' => 'Deluxe AC',
                'monthly_price' => 1150000,
                'facilities' => 'AC 1/2 PK, Kamar Mandi Dalam (Shower & Kloset Duduk), Kasur Springbed No. 2, Lemari Kaca, Meja Kerja Ergonomis, WiFi 50Mbps',
                'status' => 'terisi',
                'size' => '3.5 x 4.5 m',
                'description' => 'Kamar Deluxe lantai 1 dengan pencahayaan alami terbaik dan fasilitas lengkap.',
            ],
            [
                'room_number' => 'Kamar A-05',
                'floor' => 1,
                'type' => 'Standar Single',
                'monthly_price' => 850000,
                'facilities' => 'Kamar Mandi Dalam, Kasur Springbed, Lemari 2 Pintu, Meja Belajar, Kipas Angin, WiFi 50Mbps',
                'status' => 'terisi',
                'size' => '3 x 4 m',
                'description' => 'Kamar lantai 1 paling pojok, sangat tenang untuk belajar/istirahat.',
            ],
            [
                'room_number' => 'Kamar B-01',
                'floor' => 2,
                'type' => 'Deluxe AC',
                'monthly_price' => 1200000,
                'facilities' => 'AC 1/2 PK, Kamar Mandi Dalam, Balkon Pribadi Menghadap Luar, Kasur Queen Size, Meja Kerja, WiFi 50Mbps',
                'status' => 'terisi',
                'size' => '4 x 4 m',
                'description' => 'Kamar utama lantai 2 dengan akses balkon pribadi dan pemandangan luas.',
            ],
            [
                'room_number' => 'Kamar B-02',
                'floor' => 2,
                'type' => 'Standar Single',
                'monthly_price' => 800000,
                'facilities' => 'Kamar Mandi Luar Bersih (2 unit), Kasur Single Springbed, Lemari Baju, Meja Belajar, WiFi 50Mbps',
                'status' => 'terisi',
                'size' => '3 x 3.5 m',
                'description' => 'Kamar lantai 2 sejuk dengan jendela besar dan sirkulasi udara baik.',
            ],
            [
                'room_number' => 'Kamar B-03',
                'floor' => 2,
                'type' => 'Standar Single',
                'monthly_price' => 800000,
                'facilities' => 'Kamar Mandi Luar Bersih, Kasur Single Springbed, Lemari Baju, Meja Belajar, WiFi 50Mbps',
                'status' => 'terisi',
                'size' => '3 x 3.5 m',
                'description' => 'Kamar lantai 2 berdampingan dengan ruang santai bersama.',
            ],
            [
                'room_number' => 'Kamar B-04',
                'floor' => 2,
                'type' => 'Deluxe AC',
                'monthly_price' => 1200000,
                'facilities' => 'AC 1/2 PK, Kamar Mandi Dalam, Kasur Queen Size, Lemari Pakaian Geser, Meja Belajar, WiFi 50Mbps',
                'status' => 'tersedia',
                'size' => '3.5 x 4 m',
                'description' => 'Kamar kosong siap huni baru saja dibersihkan dan dicat ulang.',
            ],
            [
                'room_number' => 'Kamar B-05',
                'floor' => 2,
                'type' => 'Standar Single',
                'monthly_price' => 800000,
                'facilities' => 'Kasur Single, Meja Belajar, Lemari Baju, Kamar Mandi Luar',
                'status' => 'perbaikan',
                'size' => '3 x 3.5 m',
                'description' => 'Sedang dalam perbaikan instalasi lampu dan pengecatan dinding (estimasi selesai 3 hari).',
            ],
        ];

        $createdRooms = [];
        foreach ($roomsData as $r) {
            $createdRooms[$r['room_number']] = Room::create($r);
        }

        // ---------------------------------------------------------------------
        // 3. PROFIL PENGHUNI (TENANT PROFILES)
        // ---------------------------------------------------------------------
        $tenantsData = [
            'A-01' => [
                'user' => $penghuniUsers['A-01'],
                'room' => $createdRooms['Kamar A-01'],
                'ktp' => '3273012903010001',
                'emergency_name' => 'Bpk. Ahmad Fahlevi (Ayah)',
                'emergency_phone' => '081322001122',
                'occupation' => 'Mahasiswa Informatika',
                'institution' => 'Telkom University',
                'start_date' => Carbon::now()->subMonths(6)->startOfMonth(),
            ],
            'A-02' => [
                'user' => $penghuniUsers['A-02'],
                'room' => $createdRooms['Kamar A-02'],
                'ktp' => '3273012903010002',
                'emergency_name' => 'Ibu Siti Nugroho (Ibu)',
                'emergency_phone' => '081322001133',
                'occupation' => 'Karyawan Swasta (IT Support)',
                'institution' => 'PT Solusi Pratama',
                'start_date' => Carbon::now()->subMonths(8)->startOfMonth(),
            ],
            'A-03' => [
                'user' => $penghuniUsers['A-03'],
                'room' => $createdRooms['Kamar A-03'],
                'ktp' => '3273012903010003',
                'emergency_name' => 'Bpk. Bambang Wicaksono',
                'emergency_phone' => '081322001144',
                'occupation' => 'Mahasiswa Desain Komunikasi Visual',
                'institution' => 'ITB Bandung',
                'start_date' => Carbon::now()->subMonths(4)->startOfMonth(),
            ],
            'A-04' => [
                'user' => $penghuniUtama,
                'room' => $createdRooms['Kamar A-04'],
                'ktp' => '3273011405020004',
                'emergency_name' => 'Bpk. Hendra Pratama (Ayah)',
                'emergency_phone' => '081288992211',
                'occupation' => 'Mahasiswa Sistem Informasi',
                'institution' => 'Universitas Telkom',
                'start_date' => Carbon::now()->subMonths(5)->startOfMonth(),
            ],
            'A-05' => [
                'user' => $penghuniUsers['A-05'],
                'room' => $createdRooms['Kamar A-05'],
                'ktp' => '3273012903010005',
                'emergency_name' => 'Ibu Ratna Ilham',
                'emergency_phone' => '081322001155',
                'occupation' => 'Pekerja Lepas / Freelance Designer',
                'institution' => 'Remote Worker',
                'start_date' => Carbon::now()->subMonths(3)->startOfMonth(),
            ],
            'B-01' => [
                'user' => $penghuniUsers['B-01'],
                'room' => $createdRooms['Kamar B-01'],
                'ktp' => '3273012903010006',
                'emergency_name' => 'Bpk. Gunawan Satria',
                'emergency_phone' => '081322001166',
                'occupation' => 'Software Engineer Junior',
                'institution' => 'PT Digital Kreasi',
                'start_date' => Carbon::now()->subMonths(10)->startOfMonth(),
            ],
            'B-02' => [
                'user' => $penghuniUsers['B-02'],
                'room' => $createdRooms['Kamar B-02'],
                'ktp' => '3273012903010007',
                'emergency_name' => 'Bpk. Ramadhan',
                'emergency_phone' => '081322001177',
                'occupation' => 'Mahasiswa Manajemen Bisnis',
                'institution' => 'Universitas Pasundan',
                'start_date' => Carbon::now()->subMonths(2)->startOfMonth(),
            ],
            'B-03' => [
                'user' => $penghuniUsers['B-03'],
                'room' => $createdRooms['Kamar B-03'],
                'ktp' => '3273012903010008',
                'emergency_name' => 'Bpk. Hidayat',
                'emergency_phone' => '081322001188',
                'occupation' => 'Mahasiswa Teknik Elektro',
                'institution' => 'Telkom University',
                'start_date' => Carbon::now()->subMonths(7)->startOfMonth(),
            ],
        ];

        $tenantProfiles = [];
        foreach ($tenantsData as $code => $data) {
            $tenantProfiles[$code] = TenantProfile::create([
                'user_id' => $data['user']->id,
                'room_id' => $data['room']->id,
                'id_card_number' => $data['ktp'],
                'emergency_contact_name' => $data['emergency_name'],
                'emergency_contact_phone' => $data['emergency_phone'],
                'occupation' => $data['occupation'],
                'institution' => $data['institution'],
                'start_date' => $data['start_date'],
                'is_active' => true,
                'notes' => 'Penyewa terdaftar resmi sejak ' . $data['start_date']->format('d M Y'),
            ]);
        }

        // ---------------------------------------------------------------------
        // 4. TAGIHAN SEWA (RENT BILLS) - REALISTIS 3 BULAN TERAKHIR
        // ---------------------------------------------------------------------
        $months = [
            ['month' => 8, 'year' => 2026, 'status' => 'lunas'],
            ['month' => 9, 'year' => 2026, 'status' => 'lunas'],
            ['month' => 10, 'year' => 2026, 'status' => 'mixed'],
        ];

        foreach ($months as $m) {
            foreach ($tenantProfiles as $code => $tp) {
                // Jangan buat tagihan jika penghuni belum mulai pada bulan tersebut
                $billDate = Carbon::create($m['year'], $m['month'], 1);
                if ($billDate->lt($tp->start_date->copy()->startOfMonth())) {
                    continue;
                }

                $dueDate = Carbon::create($m['year'], $m['month'], 10);
                $status = 'lunas';
                $paymentDate = $dueDate->copy()->subDays(rand(1, 4));
                $method = 'Transfer BCA';
                $confirmedBy = $pengelola->id;

                if ($m['status'] === 'mixed') {
                    if ($code === 'A-04') { // Dimas Pratama (Bulan berjalan, Menunggu Pembayaran)
                        $status = 'menunggu';
                        $paymentDate = null;
                        $method = null;
                        $confirmedBy = null;
                    } elseif ($code === 'B-03') { // Rian Hidayat menunggak
                        $status = 'menunggak';
                        $paymentDate = null;
                        $method = null;
                        $confirmedBy = null;
                    } elseif ($code === 'A-05') {
                        $status = 'menunggu';
                        $paymentDate = null;
                        $method = null;
                        $confirmedBy = null;
                    }
                }

                RentBill::create([
                    'bill_code' => sprintf('TAG-%d%02d-%s', $m['year'], $m['month'], str_replace('Kamar ', '', $tp->room->room_number)),
                    'tenant_profile_id' => $tp->id,
                    'room_id' => $tp->room_id,
                    'month' => $m['month'],
                    'year' => $m['year'],
                    'amount' => $tp->room->monthly_price,
                    'due_date' => $dueDate,
                    'status' => $status,
                    'payment_date' => $paymentDate,
                    'payment_method' => $method,
                    'confirmed_by' => $confirmedBy,
                    'notes' => $status === 'lunas' ? 'Lunas terverifikasi tepat waktu' : ($status === 'menunggak' ? 'Jatuh tempo terlewati, belum ada konfirmasi' : 'Tagihan sewa bulan berjalan'),
                ]);
            }
        }

        // ---------------------------------------------------------------------
        // 5. KATALOG MENU (WARKOP & KEBUTUHAN KOS)
        // ---------------------------------------------------------------------
        $catalogItemsData = [
            // Kategori Galon
            [
                'name' => 'Galon Aqua 19L (Refill Asli)',
                'category' => 'galon',
                'price' => 22000,
                'stock' => 9,
                'minimum_stock' => 8,
                'unit' => 'galon',
                'description' => 'Air mineral Aqua asli 19 Liter bersegel resmi dan higienis. Petugas akan mengantar dan memasangkan di dispenser kamar.',
                'is_available' => true,
            ],
            [
                'name' => 'Galon Le Minerale 15L',
                'category' => 'galon',
                'price' => 21000,
                'stock' => 2,
                'minimum_stock' => 5,
                'unit' => 'galon',
                'description' => 'Galon Le Minerale sekali pakai dengan teknologi kedap udara bebas BPA, cocok untuk cadangan kamar.',
                'is_available' => true,
            ],
            [
                'name' => 'Galon Isi Ulang RO Segar',
                'category' => 'galon',
                'price' => 8000,
                'stock' => 18,
                'minimum_stock' => 10,
                'unit' => 'galon',
                'description' => 'Air minum isi ulang Reverse Osmosis (RO) steril, proses sterilisasi ozon & ultraviolet. Praktis & hemat.',
                'is_available' => true,
            ],

            // Kategori Makanan
            [
                'name' => 'Indomie Goreng Double + Telur',
                'category' => 'makanan',
                'price' => 14000,
                'stock' => 16,
                'minimum_stock' => 15,
                'unit' => 'porsi',
                'description' => '2 porsi mie goreng gurih disajikan dengan telur setengah matang atau matang, sawi caisim segar, dan bawang goreng renyah.',
                'is_available' => true,
            ],
            [
                'name' => 'Indomie Kuah Soto Spesial Telur',
                'category' => 'makanan',
                'price' => 13000,
                'stock' => 22,
                'minimum_stock' => 12,
                'unit' => 'porsi',
                'description' => 'Mie kuah kaldu soto harum panas dengan telur rebus, jeruk nipis segar, dan potongan cabai rawit merah pedas.',
                'is_available' => true,
            ],
            [
                'name' => 'Nasi Goreng Spesial Kos Tiana',
                'category' => 'makanan',
                'price' => 18000,
                'stock' => 25,
                'minimum_stock' => 8,
                'unit' => 'porsi',
                'description' => 'Nasi goreng bumbu racikan warkop dengan suwiran ayam gurih, sosis, bakso sapi, telur mata sapi, dan kerupuk udang.',
                'is_available' => true,
            ],
            [
                'name' => 'Nasi Telur Kecap Pontianak',
                'category' => 'makanan',
                'price' => 12000,
                'stock' => 20,
                'minimum_stock' => 8,
                'unit' => 'porsi',
                'description' => 'Nasi putih pulen hangat dengan 2 telur ceplok disiram kuah gurih kecap manis bawang putih cincang khas Pontianak.',
                'is_available' => true,
            ],
            [
                'name' => 'Magelangan Goreng Warkop',
                'category' => 'makanan',
                'price' => 16000,
                'stock' => 18,
                'minimum_stock' => 6,
                'unit' => 'porsi',
                'description' => 'Kombinasi nasi dan mie kuning yang digoreng wangi bersama bumbu khas, potongan bakso, sayuran, dan telur orak-arik.',
                'is_available' => true,
            ],
            [
                'name' => 'Roti Bakar Coklat Keju Susu',
                'category' => 'makanan',
                'price' => 11000,
                'stock' => 16,
                'minimum_stock' => 5,
                'unit' => 'porsi',
                'description' => 'Roti tawar tebal dipanggang dengan olesan mentega wangi, meses coklat pekat, dan keju parut cheddar melimpah.',
                'is_available' => true,
            ],

            // Kategori Minuman
            [
                'name' => 'Kopi Susu Gula Aren Warkop',
                'category' => 'minuman',
                'price' => 8000,
                'stock' => 8,
                'minimum_stock' => 10,
                'unit' => 'gelas',
                'description' => 'Kopi tubruk robusta disaring halus dipadu susu kental manis dan gula aren asli. Pas untuk teman lembur nugas.',
                'is_available' => true,
            ],
            [
                'name' => 'Es Teh Manis Jumbo',
                'category' => 'minuman',
                'price' => 4000,
                'stock' => 95,
                'minimum_stock' => 20,
                'unit' => 'gelas',
                'description' => 'Teh seduh melati wangi dengan es kristal dalam gelas jumbo 500ml yang menyegarkan di siang hari.',
                'is_available' => true,
            ],
            [
                'name' => 'Nutrisari Jeruk Peras Dingin',
                'category' => 'minuman',
                'price' => 5000,
                'stock' => 42,
                'minimum_stock' => 15,
                'unit' => 'gelas',
                'description' => 'Minuman serbuk sari buah jeruk manis segar bervitamin C tinggi dengan es batu dingin melimpah.',
                'is_available' => true,
            ],
            [
                'name' => 'Es Milo Susu Kental',
                'category' => 'minuman',
                'price' => 9000,
                'stock' => 28,
                'minimum_stock' => 10,
                'unit' => 'gelas',
                'description' => 'Susu cokelat malt Milo kental pekat disajikan dingin dengan taburan bubuk milo di atasnya.',
                'is_available' => true,
            ],
        ];

        $catalogMap = [];
        foreach ($catalogItemsData as $item) {
            $createdItem = CatalogItem::create($item);
            $catalogMap[$item['name']] = $createdItem;

            // Catat Initial Stock Log
            StockLog::create([
                'catalog_item_id' => $createdItem->id,
                'user_id' => $pengelola->id,
                'type' => 'in',
                'quantity' => $createdItem->stock + 15,
                'before_stock' => 0,
                'after_stock' => $createdItem->stock + 15,
                'reference_type' => 'manual_restock',
                'notes' => 'Stok awal pembukaan bulan dan pasokan bahan warkop Kos Tiana Putra 1',
                'created_at' => Carbon::now()->subDays(30),
            ]);
        }

        // ---------------------------------------------------------------------
        // 6. RIWAYAT TRANSAKSI (30 HARI TERAKHIR) & ANTREAN PESANAN HARI INI
        // ---------------------------------------------------------------------
        $tenantsList = array_values($tenantProfiles);
        $catalogList = array_values($catalogMap);

        // A. Pesanan historis 30 hari ke belakang (~190 transaksi realistis anak kos)
        // Pemodelan perilaku nyata penghuni kos 10 kamar:
        // 1. Galon: Siklus refill berkala per kamar (tiap 7-10 hari sekali per kamar)
        // 2. Makanan & Minuman: Waktu makan nyata (Siang, Sore, Malam nugas) + lonjakan akhir pekan (+40%)
        // 3. Tren 14 hari terakhir: Indomie Double & Kopi Susu naik tajam (musim nugas / persiapan ujian)
        $tenantPreferences = [
            'A-01' => ['galon' => 'Galon Aqua 19L (Refill Asli)',       'food' => 'Indomie Goreng Double + Telur',   'drink' => 'Kopi Susu Gula Aren Warkop'],
            'A-02' => ['galon' => 'Galon Isi Ulang RO Segar',           'food' => 'Nasi Telur Kecap Pontianak',      'drink' => 'Es Teh Manis Jumbo'],
            'A-03' => ['galon' => 'Galon Aqua 19L (Refill Asli)',       'food' => 'Roti Bakar Coklat Keju Susu',     'drink' => 'Es Milo Susu Kental'],
            'A-04' => ['galon' => 'Galon Aqua 19L (Refill Asli)',       'food' => 'Indomie Goreng Double + Telur',   'drink' => 'Kopi Susu Gula Aren Warkop'],
            'A-05' => ['galon' => 'Galon Aqua 19L (Refill Asli)',       'food' => 'Nasi Goreng Spesial Kos Tiana',   'drink' => 'Nutrisari Jeruk Peras Dingin'],
            'B-01' => ['galon' => 'Galon Le Minerale 15L',              'food' => 'Magelangan Goreng Warkop',        'drink' => 'Kopi Susu Gula Aren Warkop'],
            'B-02' => ['galon' => 'Galon Isi Ulang RO Segar',           'food' => 'Indomie Kuah Soto Spesial Telur', 'drink' => 'Es Teh Manis Jumbo'],
            'B-03' => ['galon' => 'Galon Le Minerale 15L',              'food' => 'Nasi Goreng Spesial Kos Tiana',   'drink' => 'Es Teh Manis Jumbo'],
        ];

        // Jadwal hari pemesanan galon per kamar dalam 30 hari terakhir
        $galonSchedule = [
            'A-01' => [28, 19, 10, 2],
            'A-02' => [27, 17, 7],
            'A-03' => [26, 16, 6],
            'A-04' => [29, 20, 11, 3],
            'A-05' => [25, 15, 5],
            'B-01' => [28, 18, 9, 1],
            'B-02' => [24, 14, 4],
            'B-03' => [23, 13, 3],
        ];

        for ($day = 30; $day >= 1; $day--) {
            $currentDate = Carbon::now()->subDays($day);
            $isWeekend = $currentDate->isWeekend();
            $isExamPeriod = ($day <= 14); // 14 hari terakhir: musim ujian / tugas akhir (lonjakan kopi & mie)

            // 1. Pemesanan Galon Terjadwal Hari Ini
            foreach ($galonSchedule as $roomNo => $days) {
                if (in_array($day, $days)) {
                    $pref = $tenantPreferences[$roomNo];
                    $tenantUser = $penghuniUsers[$roomNo] ?? $penghuniUtama;
                    $galonItem = $catalogMap[$pref['galon']];
                    $orderHour = rand(8, 17);
                    $orderDate = $currentDate->copy()->setHour($orderHour)->setMinute(rand(5, 55))->setSecond(rand(0, 59));
                    $orderCode = sprintf('ORD-%s-%s-G', $orderDate->format('Ymd'), str_replace('-', '', $roomNo));

                    $order = Order::create([
                        'order_code' => $orderCode,
                        'user_id' => $tenantUser->id,
                        'room_number' => 'Kamar ' . $roomNo,
                        'order_type' => 'quick_galon',
                        'total_amount' => $galonItem->price,
                        'status' => 'selesai',
                        'payment_status' => 'lunas',
                        'payment_method' => rand(1, 10) <= 7 ? 'Tunai / Bayar di Kamar' : 'QRIS',
                        'notes' => 'Tolong sekalian pasangkan ke dispenser ya mas Joko, terima kasih.',
                        'processed_by' => rand(0, 1) ? $petugas1->id : $petugas2->id,
                        'completed_at' => $orderDate->copy()->addMinutes(rand(10, 20)),
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate->copy()->addMinutes(20),
                    ]);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'catalog_item_id' => $galonItem->id,
                        'item_name' => $galonItem->name,
                        'quantity' => 1,
                        'unit_price' => $galonItem->price,
                        'subtotal' => $galonItem->price,
                        'notes' => 'Pasang di dispenser kamar',
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);

                    StockLog::create([
                        'catalog_item_id' => $galonItem->id,
                        'user_id' => $order->processed_by,
                        'type' => 'out',
                        'quantity' => 1,
                        'before_stock' => $galonItem->stock + 1,
                        'after_stock' => $galonItem->stock,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'notes' => 'Pesanan galon Kamar ' . $roomNo . ' (' . $orderCode . ')',
                        'created_at' => $orderDate,
                    ]);
                }
            }

            // 2. Pemesanan Makanan & Minuman Warkop Hari Ini
            // Hari kerja: 4-6 pesanan. Akhir pekan: 7-9 pesanan.
            $orderCount = $isWeekend ? rand(7, 9) : rand(4, 6);

            for ($k = 0; $k < $orderCount; $k++) {
                $roomNo = array_rand($tenantPreferences);
                $pref = $tenantPreferences[$roomNo];
                $tenantUser = $penghuniUsers[$roomNo] ?? $penghuniUtama;

                // Tentukan slot waktu nyata anak kos:
                $slotRoll = rand(1, 100);
                if ($slotRoll <= 25) {
                    // Siang (11:30 - 13:45)
                    $hour = rand(11, 13);
                    $minute = rand(15, 55);
                    $foodCandidate = ['Nasi Goreng Spesial Kos Tiana', 'Nasi Telur Kecap Pontianak', 'Indomie Kuah Soto Spesial Telur'][rand(0, 2)];
                    $drinkCandidate = ['Es Teh Manis Jumbo', 'Nutrisari Jeruk Peras Dingin'][rand(0, 1)];
                } elseif ($slotRoll <= 45) {
                    // Sore Santai (16:00 - 18:00)
                    $hour = rand(16, 18);
                    $minute = rand(0, 45);
                    $foodCandidate = ['Roti Bakar Coklat Keju Susu', 'Magelangan Goreng Warkop'][rand(0, 1)];
                    $drinkCandidate = ['Es Teh Manis Jumbo', 'Es Milo Susu Kental'][rand(0, 1)];
                } else {
                    // Malam Nugas (19:30 - 23:30) - Paling ramai di kosan!
                    $hour = rand(19, 23);
                    $minute = rand(0, 55);

                    if ($isExamPeriod && rand(1, 100) <= 75) {
                        $foodCandidate = 'Indomie Goreng Double + Telur';
                        $drinkCandidate = 'Kopi Susu Gula Aren Warkop';
                    } else {
                        $foodCandidate = ['Indomie Goreng Double + Telur', 'Magelangan Goreng Warkop', 'Roti Bakar Coklat Keju Susu'][rand(0, 2)];
                        $drinkCandidate = ['Kopi Susu Gula Aren Warkop', 'Es Teh Manis Jumbo'][rand(0, 1)];
                    }
                }

                $orderDate = $currentDate->copy()->setHour($hour)->setMinute($minute)->setSecond(rand(0, 59));
                $orderCode = sprintf('ORD-%s-%02d-%03d', $orderDate->format('Ymd'), $k + 1, rand(100, 999));

                $foodItem = $catalogMap[$foodCandidate];
                $drinkItem = $catalogMap[$drinkCandidate];

                // Kombinasi: 75% Makanan + Minuman, 15% Minuman saja, 10% Makanan saja
                $comboRoll = rand(1, 100);
                $itemsInThisOrder = [];
                if ($comboRoll <= 75) {
                    $itemsInThisOrder[] = ['item' => $foodItem, 'qty' => 1, 'note' => 'Pedas sedang'];
                    $itemsInThisOrder[] = ['item' => $drinkItem, 'qty' => 1, 'note' => str_contains($drinkItem->name, 'Kopi') ? 'Gula aren normal' : 'Es sedang'];
                } elseif ($comboRoll <= 90) {
                    $itemsInThisOrder[] = ['item' => $drinkItem, 'qty' => rand(1, 2), 'note' => null];
                } else {
                    $itemsInThisOrder[] = ['item' => $foodItem, 'qty' => 1, 'note' => 'Bungkus rapi ya'];
                }

                $totalAmount = 0;
                foreach ($itemsInThisOrder as $it) {
                    $totalAmount += $it['qty'] * $it['item']->price;
                }

                $order = Order::create([
                    'order_code' => $orderCode,
                    'user_id' => $tenantUser->id,
                    'room_number' => 'Kamar ' . $roomNo,
                    'order_type' => 'regular',
                    'total_amount' => $totalAmount,
                    'status' => 'selesai',
                    'payment_status' => 'lunas',
                    'payment_method' => rand(1, 10) <= 6 ? 'Tunai / Bayar di Kamar' : 'QRIS',
                    'notes' => 'Antar ke depan kamar ya mas Joko, makasih.',
                    'processed_by' => rand(0, 1) ? $petugas1->id : $petugas2->id,
                    'completed_at' => $orderDate->copy()->addMinutes(rand(12, 25)),
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate->copy()->addMinutes(rand(12, 25)),
                ]);

                foreach ($itemsInThisOrder as $it) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'catalog_item_id' => $it['item']->id,
                        'item_name' => $it['item']->name,
                        'quantity' => $it['qty'],
                        'unit_price' => $it['item']->price,
                        'subtotal' => $it['qty'] * $it['item']->price,
                        'notes' => $it['note'],
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);

                    StockLog::create([
                        'catalog_item_id' => $it['item']->id,
                        'user_id' => $order->processed_by,
                        'type' => 'out',
                        'quantity' => $it['qty'],
                        'before_stock' => $it['item']->stock + $it['qty'],
                        'after_stock' => $it['item']->stock,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'notes' => 'Pesanan Kamar ' . $roomNo . ' (' . $orderCode . ')',
                        'created_at' => $orderDate,
                    ]);
                }
            }
        }

        // B. Pesanan Hari Ini (Live Demo Queue untuk Petugas & Penghuni)
        // 1. Pesanan 'menunggu' dari Dimas Pratama (Quick Galon)
        $todayOrder1 = Order::create([
            'order_code' => sprintf('ORD-%s-001', Carbon::now()->format('Ymd')),
            'user_id' => $penghuniUtama->id,
            'room_number' => 'Kamar A-04',
            'order_type' => 'quick_galon',
            'total_amount' => 22000,
            'status' => 'menunggu',
            'payment_status' => 'belum_bayar',
            'payment_method' => 'Tunai / Bayar di Kamar',
            'notes' => 'Tolong pasangkan ke dispenser ya mas Joko, terima kasih.',
            'created_at' => Carbon::now()->subMinutes(15),
            'updated_at' => Carbon::now()->subMinutes(15),
        ]);
        OrderItem::create([
            'order_id' => $todayOrder1->id,
            'catalog_item_id' => $catalogMap['Galon Aqua 19L (Refill Asli)']->id,
            'item_name' => 'Galon Aqua 19L (Refill Asli)',
            'quantity' => 1,
            'unit_price' => 22000,
            'subtotal' => 22000,
            'notes' => 'Pasang di dispenser',
            'created_at' => Carbon::now()->subMinutes(15),
        ]);

        // 2. Pesanan 'diproses' dari Kamar A-01 (Indomie & Es Teh)
        $todayOrder2 = Order::create([
            'order_code' => sprintf('ORD-%s-002', Carbon::now()->format('Ymd')),
            'user_id' => $penghuniUsers['A-01']->id,
            'room_number' => 'Kamar A-01',
            'order_type' => 'regular',
            'total_amount' => 18000,
            'status' => 'diproses',
            'payment_status' => 'belum_bayar',
            'payment_method' => 'Tunai / Bayar di Kamar',
            'notes' => 'Telur ceploknya setengah matang ya mas.',
            'processed_by' => $petugas1->id,
            'created_at' => Carbon::now()->subMinutes(25),
            'updated_at' => Carbon::now()->subMinutes(10),
        ]);
        OrderItem::create([
            'order_id' => $todayOrder2->id,
            'catalog_item_id' => $catalogMap['Indomie Goreng Double + Telur']->id,
            'item_name' => 'Indomie Goreng Double + Telur',
            'quantity' => 1,
            'unit_price' => 14000,
            'subtotal' => 14000,
            'notes' => 'Telur 1/2 matang',
            'created_at' => Carbon::now()->subMinutes(25),
        ]);
        OrderItem::create([
            'order_id' => $todayOrder2->id,
            'catalog_item_id' => $catalogMap['Es Teh Manis Jumbo']->id,
            'item_name' => 'Es Teh Manis Jumbo',
            'quantity' => 1,
            'unit_price' => 4000,
            'subtotal' => 4000,
            'created_at' => Carbon::now()->subMinutes(25),
        ]);

        // 3. Pesanan 'diantar' dari Kamar B-01 (Kopi Susu & Roti Bakar)
        $todayOrder3 = Order::create([
            'order_code' => sprintf('ORD-%s-003', Carbon::now()->format('Ymd')),
            'user_id' => $penghuniUsers['B-01']->id,
            'room_number' => 'Kamar B-01',
            'order_type' => 'regular',
            'total_amount' => 19000,
            'status' => 'diantar',
            'payment_status' => 'belum_bayar',
            'payment_method' => 'QRIS',
            'notes' => 'Bawa QRIS ya mas.',
            'processed_by' => $petugas2->id,
            'created_at' => Carbon::now()->subMinutes(35),
            'updated_at' => Carbon::now()->subMinutes(5),
        ]);
        OrderItem::create([
            'order_id' => $todayOrder3->id,
            'catalog_item_id' => $catalogMap['Kopi Susu Gula Aren Warkop']->id,
            'item_name' => 'Kopi Susu Gula Aren Warkop',
            'quantity' => 1,
            'unit_price' => 8000,
            'subtotal' => 8000,
            'created_at' => Carbon::now()->subMinutes(35),
        ]);
        OrderItem::create([
            'order_id' => $todayOrder3->id,
            'catalog_item_id' => $catalogMap['Roti Bakar Coklat Keju Susu']->id,
            'item_name' => 'Roti Bakar Coklat Keju Susu',
            'quantity' => 1,
            'unit_price' => 11000,
            'subtotal' => 11000,
            'created_at' => Carbon::now()->subMinutes(35),
        ]);

        // ---------------------------------------------------------------------
        // 7. PREDIKSI KEBUTUHAN STOK (FR-13)
        // ---------------------------------------------------------------------
        $predictionSuccess = false;
        try {
            // Jalankan model Python Machine Learning secara otomatis
            $exitCode = Artisan::call('stok:prediksi');
            if ($exitCode === 0 && StockPrediction::count() >= count($catalogMap)) {
                $predictionSuccess = true;
            }
        } catch (\Throwable $e) {
            $predictionSuccess = false;
        }

        // Fallback: Jika Python belum terpasang atau gagal di lingkungan tertentu,
        // buat prediksi realistis untuk seluruh 13 katalog item secara lengkap.
        if (! $predictionSuccess || StockPrediction::count() === 0) {
            $this->generateFallbackPredictions($catalogMap);
        }

        // ---------------------------------------------------------------------
        // 8. NOTIFIKASI DI DALAM APLIKASI (FR-12)
        // ---------------------------------------------------------------------
        // Notifikasi untuk Dimas Pratama (Penghuni)
        AppNotification::create([
            'user_id' => $penghuniUtama->id,
            'title' => 'Tagihan Sewa Kamar A-04 Diterbitkan',
            'message' => 'Tagihan sewa bulan Oktober 2026 sebesar Rp 1.150.000 telah terbit. Jatuh tempo pada 10 Oktober 2026.',
            'type' => 'rent',
            'action_url' => '/penghuni/tagihan',
            'is_read' => false,
            'created_at' => Carbon::now()->subDays(4),
        ]);

        AppNotification::create([
            'user_id' => $penghuniUtama->id,
            'title' => 'Pesanan Galon Diterima',
            'message' => 'Pesanan Quick Order #ORD-' . Carbon::now()->format('Ymd') . '-001 telah masuk antrean petugas.',
            'type' => 'order',
            'action_url' => '/penghuni/pesanan',
            'is_read' => false,
            'created_at' => Carbon::now()->subMinutes(14),
        ]);

        // Notifikasi untuk Petugas
        AppNotification::create([
            'user_id' => $petugas1->id,
            'title' => 'Pesanan Baru Masuk!',
            'message' => 'Quick Order Galon dari Kamar A-04 (Dimas Pratama). Mohon segera diantar.',
            'type' => 'order',
            'action_url' => '/petugas/antrean',
            'is_read' => false,
            'created_at' => Carbon::now()->subMinutes(15),
        ]);

        // Notifikasi untuk Pengelola
        AppNotification::create([
            'user_id' => $pengelola->id,
            'title' => 'Pengingat Jatuh Tempo Sewa',
            'message' => 'Kamar B-03 (Rian Hidayat) belum melakukan pelunasan sewa per 10 Oktober 2026.',
            'type' => 'rent',
            'action_url' => '/pengelola/tagihan',
            'is_read' => false,
            'created_at' => Carbon::now()->subDays(3),
        ]);

        AppNotification::create([
            'user_id' => $pengelola->id,
            'title' => 'Peringatan Stok Kritis & Waspada',
            'message' => 'Stok Galon Le Minerale 15L tersisa 2 unit (di bawah batas minimum 5 unit) & Kopi Susu tersisa 8 cup. Disarankan segera restock.',
            'type' => 'stock',
            'action_url' => '/pengelola/prediksi-stok',
            'is_read' => false,
            'created_at' => Carbon::now()->subHours(2),
        ]);
    }

    /**
     * Fallback prediksi cerdas untuk seluruh 13 item katalog warkop & galon
     * apabila lingkungan runtime belum menginstal paket Python Machine Learning.
     */
    private function generateFallbackPredictions(array $catalogMap): void
    {
        $fallbackData = [
            'Galon Le Minerale 15L' => [
                'predicted_demand_qty' => 7,
                'target_stock' => 12,
                'current_stock' => 2,
                'recommended_restock_qty' => 10,
                'daily_avg' => 1.00,
                'safety_stock' => 5,
                'sales_trend' => 'stabil',
                'status' => 'kritis',
                'note' => 'PERINGATAN KRITIS: Stok tersisa 2 galon (di bawah batas minimum 5 galon), diperkirakan habis dalam 2.0 hari! Saran segera restock +10 galon Le Minerale.',
                'confidence' => 0.86,
            ],
            'Kopi Susu Gula Aren Warkop' => [
                'predicted_demand_qty' => 26,
                'target_stock' => 36,
                'current_stock' => 8,
                'recommended_restock_qty' => 28,
                'daily_avg' => 3.71,
                'safety_stock' => 10,
                'sales_trend' => 'naik',
                'status' => 'kritis',
                'note' => 'PERINGATAN KRITIS: Stok 8 cup berada di bawah batas minimum (10 cup). Tren konsumsi naik signifikan saat jam belajar malam (20.00-24.00 WIB). Restock darurat +28 cup.',
                'confidence' => 0.92,
            ],
            'Galon Aqua 19L (Refill Asli)' => [
                'predicted_demand_qty' => 18,
                'target_stock' => 26,
                'current_stock' => 14,
                'recommended_restock_qty' => 12,
                'daily_avg' => 2.57,
                'safety_stock' => 8,
                'sales_trend' => 'stabil',
                'status' => 'waspada',
                'note' => 'Stok galon Aqua mendekati batas aman (14 galon, cukup ~5.4 hari). Proyeksi kebutuhan 7 hari: 18 galon. Saran restock +12 galon cadangan sebelum akhir pekan.',
                'confidence' => 0.88,
            ],
            'Indomie Goreng Double + Telur' => [
                'predicted_demand_qty' => 28,
                'target_stock' => 43,
                'current_stock' => 12,
                'recommended_restock_qty' => 31,
                'daily_avg' => 4.00,
                'safety_stock' => 15,
                'sales_trend' => 'naik',
                'status' => 'kritis',
                'note' => 'PERINGATAN KRITIS: Stok 12 porsi di bawah batas aman (15 porsi). Tren penjualan naik tajam pada akhir pekan dan malam hari. Saran restock +31 porsi mie & telur.',
                'confidence' => 0.93,
            ],
            'Indomie Kuah Soto Spesial Telur' => [
                'predicted_demand_qty' => 16,
                'target_stock' => 28,
                'current_stock' => 22,
                'recommended_restock_qty' => 6,
                'daily_avg' => 2.29,
                'safety_stock' => 12,
                'sales_trend' => 'stabil',
                'status' => 'waspada',
                'note' => 'Stok 22 porsi mencukupi ~9.6 hari. Puncak pesanan saat cuaca hujan dan malam hari. Saran restock santai +6 porsi.',
                'confidence' => 0.89,
            ],
            'Galon Isi Ulang RO Segar' => [
                'predicted_demand_qty' => 12,
                'target_stock' => 22,
                'current_stock' => 18,
                'recommended_restock_qty' => 4,
                'daily_avg' => 1.71,
                'safety_stock' => 10,
                'sales_trend' => 'stabil',
                'status' => 'waspada',
                'note' => 'Konsumsi galon RO stabil (~1.7 galon/hari). Stok 18 galon mencukupi 10.5 hari. Saran restock +4 galon untuk menjaga buffer.',
                'confidence' => 0.85,
            ],
            'Nasi Goreng Spesial Kos Tiana' => [
                'predicted_demand_qty' => 18,
                'target_stock' => 26,
                'current_stock' => 25,
                'recommended_restock_qty' => 1,
                'daily_avg' => 2.57,
                'safety_stock' => 8,
                'sales_trend' => 'stabil',
                'status' => 'aman',
                'note' => 'Stok bahan baku beras, bumbu warkop, dan topping aman untuk 9.7 hari ke depan. Penjualan stabil.',
                'confidence' => 0.91,
            ],
            'Nasi Telur Kecap Pontianak' => [
                'predicted_demand_qty' => 15,
                'target_stock' => 23,
                'current_stock' => 20,
                'recommended_restock_qty' => 3,
                'daily_avg' => 2.14,
                'safety_stock' => 8,
                'sales_trend' => 'stabil',
                'status' => 'aman',
                'note' => 'Menu favorit makan siang & tanggal tua mahasiswa. Pasokan telur & kecap aman untuk 9.3 hari.',
                'confidence' => 0.90,
            ],
            'Magelangan Goreng Warkop' => [
                'predicted_demand_qty' => 11,
                'target_stock' => 17,
                'current_stock' => 18,
                'recommended_restock_qty' => 0,
                'daily_avg' => 1.57,
                'safety_stock' => 6,
                'sales_trend' => 'stabil',
                'status' => 'aman',
                'note' => 'Stok aman mencukupi ~11.5 hari ke depan. Tren stabil pada jam makan malam.',
                'confidence' => 0.87,
            ],
            'Roti Bakar Coklat Keju Susu' => [
                'predicted_demand_qty' => 12,
                'target_stock' => 17,
                'current_stock' => 16,
                'recommended_restock_qty' => 1,
                'daily_avg' => 1.71,
                'safety_stock' => 5,
                'sales_trend' => 'stabil',
                'status' => 'aman',
                'note' => 'Camilan malam mahasiswa. Stok roti dan topping keju/coklat aman untuk 9.3 hari.',
                'confidence' => 0.86,
            ],
            'Es Teh Manis Jumbo' => [
                'predicted_demand_qty' => 65,
                'target_stock' => 85,
                'current_stock' => 95,
                'recommended_restock_qty' => 0,
                'daily_avg' => 9.29,
                'safety_stock' => 20,
                'sales_trend' => 'stabil',
                'status' => 'aman',
                'note' => 'Stok bahan daun teh wangi dan gula sangat melimpah, mencukupi kebutuhan ~10.2 hari ke depan.',
                'confidence' => 0.95,
            ],
            'Nutrisari Jeruk Peras Dingin' => [
                'predicted_demand_qty' => 22,
                'target_stock' => 37,
                'current_stock' => 42,
                'recommended_restock_qty' => 0,
                'daily_avg' => 3.14,
                'safety_stock' => 15,
                'sales_trend' => 'stabil',
                'status' => 'aman',
                'note' => 'Stok sachet Nutrisari aman untuk ~13.4 hari ke depan. Permintaan tinggi saat terik siang.',
                'confidence' => 0.91,
            ],
            'Es Milo Susu Kental' => [
                'predicted_demand_qty' => 18,
                'target_stock' => 28,
                'current_stock' => 28,
                'recommended_restock_qty' => 0,
                'daily_avg' => 2.57,
                'safety_stock' => 10,
                'sales_trend' => 'stabil',
                'status' => 'aman',
                'note' => 'Stok bubuk susu coklat Milo aman untuk 10.9 hari. Konsumsi stabil pada sore dan malam hari.',
                'confidence' => 0.88,
            ],
        ];

        StockPrediction::truncate();

        foreach ($catalogMap as $name => $catalogItem) {
            $data = $fallbackData[$name] ?? [
                'predicted_demand_qty' => 14,
                'target_stock' => 20,
                'current_stock' => $catalogItem->stock,
                'recommended_restock_qty' => max(0, 20 - $catalogItem->stock),
                'daily_avg' => 2.00,
                'safety_stock' => $catalogItem->minimum_stock,
                'sales_trend' => 'stabil',
                'status' => 'aman',
                'note' => 'Stok mencukupi kebutuhan harian.',
                'confidence' => 0.85,
            ];

            StockPrediction::create([
                'catalog_item_id' => $catalogItem->id,
                'predicted_demand_qty' => $data['predicted_demand_qty'],
                'target_stock' => $data['target_stock'],
                'current_stock' => $data['current_stock'],
                'recommended_restock_qty' => $data['recommended_restock_qty'],
                'prediction_period' => '7 Hari ke Depan',
                'daily_avg_sales' => $data['daily_avg'],
                'safety_stock' => $data['safety_stock'],
                'sales_trend' => $data['sales_trend'],
                'status' => $data['status'],
                'recommendation_note' => $data['note'],
                'confidence_score' => $data['confidence'],
                'calculated_at' => Carbon::now()->subMinutes(10),
            ]);
        }
    }
}
