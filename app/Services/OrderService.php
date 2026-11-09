<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockLog;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public const MAX_QTY_PER_ITEM = 20;
    public const MAX_NOTE_LENGTH = 255;

    /**
     * Urutan transisi status yang sah. Status tidak boleh dilompati
     * maupun dimundurkan, dan pesanan batal/selesai tidak bisa diubah lagi.
     */
    public const NEXT_STATUS = [
        'menunggu' => 'diproses',
        'diproses' => 'diantar',
        'diantar'  => 'selesai',
    ];

    public function __construct(private NotificationService $notifier)
    {
    }

    /**
     * Buat pesanan baru dari keranjang. Stok dikunci dan dikurangi dalam satu transaksi.
     *
     * @param  array<int, array{id:mixed, quantity:mixed, notes?:mixed}>  $cart
     */
    public function place(
        User $user,
        string $roomNumber,
        array $cart,
        ?string $paymentMethod = null,
        ?string $orderNotes = null,
        ?string $onlyCategory = null,
        string $errorKey = 'items_json',
    ): Order {
        $lines = $this->normalizeCart($cart, $errorKey);

        return DB::transaction(function () use ($user, $roomNumber, $lines, $paymentMethod, $orderNotes, $onlyCategory, $errorKey) {
            $catalogItems = CatalogItem::whereIn('id', array_keys($lines))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $orderItems = [];
            $stockLogs = [];
            $hasNonGalon = false;
            $now = now();

            foreach ($lines as $itemId => $line) {
                $item = $catalogItems->get($itemId);
                $qty = $line['quantity'];

                if (!$item) {
                    $this->fail($errorKey, 'Salah satu item yang Anda pilih sudah tidak tersedia.');
                }
                if (!$item->is_available) {
                    $this->fail($errorKey, "Menu {$item->name} saat ini sedang dinonaktifkan.");
                }
                if ($onlyCategory && $item->category !== $onlyCategory) {
                    $this->fail($errorKey, 'Pilihan item tidak valid untuk jenis pesanan ini.');
                }
                if ($item->stock < $qty) {
                    $this->fail($errorKey, "Maaf, stok \"{$item->name}\" tersisa {$item->stock} {$item->unit}. Tidak mencukupi jumlah pesanan ({$qty}).");
                }

                if ($item->category !== 'galon') {
                    $hasNonGalon = true;
                }

                $subtotal = $qty * $item->price;
                $total += $subtotal;
                $before = $item->stock;

                $item->decrement('stock', $qty);

                $orderItems[] = [
                    'catalog_item_id' => $item->id,
                    'item_name'       => $item->name,
                    'quantity'        => $qty,
                    'unit_price'      => $item->price,
                    'subtotal'        => $subtotal,
                    'notes'           => $line['notes'],
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];

                $stockLogs[] = [
                    'catalog_item_id' => $item->id,
                    'user_id'         => $user->id,
                    'type'            => 'out',
                    'quantity'        => $qty,
                    'before_stock'    => $before,
                    'after_stock'     => $before - $qty,
                    'reference_type'  => 'order',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            $order = Order::create([
                'order_code'     => $this->nextOrderCode(),
                'user_id'        => $user->id,
                'room_number'    => $roomNumber,
                'order_type'     => (!$hasNonGalon && count($orderItems) === 1) ? 'quick_galon' : 'regular',
                'total_amount'   => $total,
                'status'         => 'menunggu',
                'payment_status' => 'belum_bayar',
                'payment_method' => $paymentMethod ?: array_key_first(config('kos.payment_methods')),
                'notes'          => $orderNotes,
            ]);

            foreach ($orderItems as &$row) {
                $row['order_id'] = $order->id;
            }
            unset($row);
            OrderItem::insert($orderItems);

            foreach ($stockLogs as &$log) {
                $log['reference_id'] = $order->id;
                $log['notes'] = "Pesanan #{$order->order_code} oleh Kamar {$roomNumber}";
            }
            unset($log);
            StockLog::insert($stockLogs);

            $this->notifier->notify(
                $user->id,
                'Pesanan Berhasil Masuk Antrean!',
                "Pesanan #{$order->order_code} sebesar Rp " . number_format($total, 0, ',', '.') . ' telah diterima. Petugas akan segera memproses.',
                'order',
                route('penghuni.pesanan'),
            );

            $this->notifier->notifyRole(
                'petugas',
                "Pesanan Baru dari {$roomNumber}!",
                "Pesanan #{$order->order_code} ({$order->order_type}) menunggu untuk diproses.",
                'order',
                route('petugas.antrean'),
            );

            return $order;
        });
    }

    /**
     * Majukan status pesanan satu langkah. Pesanan dikunci agar aman dari
     * aksi bersamaan (misalnya penghuni membatalkan saat petugas memproses).
     *
     * @throws DomainException jika transisi tidak sah
     */
    public function advance(int $orderId, User $petugas, ?string $requestedStatus = null): Order
    {
        return DB::transaction(function () use ($orderId, $petugas, $requestedStatus) {
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();

            $next = self::NEXT_STATUS[$order->status] ?? null;

            if ($next === null) {
                throw new DomainException('Pesanan ini sudah ' . ($order->status === 'batal' ? 'dibatalkan' : 'selesai') . ' dan statusnya tidak dapat diubah lagi.');
            }

            if ($requestedStatus !== null && $requestedStatus !== $next) {
                throw new DomainException("Pesanan #{$order->order_code} sudah berubah (sekarang: {$order->status}), mungkin diproses petugas lain. Antrean sudah diperbarui, silakan cek lagi.");
            }

            $data = ['status' => $next, 'processed_by' => $petugas->id];
            if ($next === 'selesai') {
                $data['completed_at'] = now();
                $data['payment_status'] = 'lunas';
            }
            $order->update($data);

            $messages = [
                'diproses' => ['Pesanan Sedang Diproses', "Pesanan #{$order->order_code} sedang disiapkan oleh petugas ({$petugas->name})."],
                'diantar'  => ['Pesanan Sedang Diantar!', "Pesanan #{$order->order_code} sedang diantar menuju ke {$order->room_number}. Mohon bersiap menerima."],
                'selesai'  => ['Pesanan Telah Selesai', "Pesanan #{$order->order_code} telah selesai diantar ke {$order->room_number}. Terima kasih!"],
            ];

            [$title, $message] = $messages[$next];
            $this->notifier->notify($order->user_id, $title, $message, 'order', route('penghuni.pesanan'));

            return $order;
        });
    }

    /**
     * Batalkan pesanan milik penghuni dan kembalikan stok.
     *
     * @throws DomainException jika pesanan tidak lagi dapat dibatalkan
     */
    public function cancel(int $orderId, User $user): Order
    {
        return DB::transaction(function () use ($orderId, $user) {
            // Kunci baris pesanan lebih dulu: status dicek ulang setelah lock.
            $order = Order::where('user_id', $user->id)
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$order->isCancellable()) {
                throw new DomainException('Pesanan tidak dapat dibatalkan karena sedang disiapkan, diantar, atau sudah selesai.');
            }

            $order->load('items');

            $order->update([
                'status' => 'batal',
                'notes'  => trim(($order->notes ? $order->notes . ' • ' : '') . 'Dibatalkan oleh penghuni ' . now()->format('d/m/Y H:i')),
            ]);

            $items = CatalogItem::withTrashed()
                ->whereIn('id', $order->items->pluck('catalog_item_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($order->items as $orderItem) {
                $catalogItem = $items->get($orderItem->catalog_item_id);
                if (!$catalogItem) {
                    continue;
                }

                $before = $catalogItem->stock;
                $catalogItem->increment('stock', $orderItem->quantity);

                StockLog::create([
                    'catalog_item_id' => $catalogItem->id,
                    'user_id'         => $user->id,
                    'type'            => 'in',
                    'quantity'        => $orderItem->quantity,
                    'before_stock'    => $before,
                    'after_stock'     => $before + $orderItem->quantity,
                    'reference_type'  => 'order_cancellation',
                    'reference_id'    => $order->id,
                    'notes'           => "Pengembalian stok pembatalan pesanan #{$order->order_code}",
                ]);
            }

            $this->notifier->notify(
                $user->id,
                'Pesanan Dibatalkan',
                "Pesanan #{$order->order_code} telah berhasil Anda batalkan.",
                'order',
                route('penghuni.pesanan'),
            );

            $this->notifier->notifyRole(
                'petugas',
                "Pesanan #{$order->order_code} Dibatalkan",
                "Penghuni Kamar {$order->room_number} telah membatalkan pesanan ini.",
                'order',
                route('petugas.antrean'),
            );

            return $order;
        });
    }

    /**
     * Gabungkan baris duplikat, batasi jumlah, dan rapikan catatan.
     *
     * @return array<int, array{quantity:int, notes:?string}>
     */
    private function normalizeCart(array $cart, string $errorKey): array
    {
        $lines = [];

        foreach ($cart as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $qty = (int) ($row['quantity'] ?? 0);

            if ($id <= 0 || $qty <= 0) {
                continue;
            }

            $note = trim((string) ($row['notes'] ?? ''));
            if (mb_strlen($note) > self::MAX_NOTE_LENGTH) {
                $this->fail($errorKey, 'Catatan per item maksimal ' . self::MAX_NOTE_LENGTH . ' karakter.');
            }

            $lines[$id]['quantity'] = ($lines[$id]['quantity'] ?? 0) + $qty;
            $lines[$id]['notes'] = $lines[$id]['notes'] ?? ($note !== '' ? $note : null);

            if ($lines[$id]['quantity'] > self::MAX_QTY_PER_ITEM) {
                $this->fail($errorKey, 'Jumlah per item maksimal ' . self::MAX_QTY_PER_ITEM . '.');
            }
        }

        if (empty($lines)) {
            $this->fail($errorKey, 'Keranjang pesanan masih kosong.');
        }

        ksort($lines);

        return $lines;
    }

    /**
     * Kode berurutan per hari (ORD-20261109-0001), dicek unik agar tidak bertabrakan.
     */
    private function nextOrderCode(): string
    {
        $prefix = 'ORD-' . Carbon::now()->format('Ymd') . '-';
        $seq = Order::where('order_code', 'like', $prefix . '%')->count() + 1;

        do {
            $code = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $seq++;
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
