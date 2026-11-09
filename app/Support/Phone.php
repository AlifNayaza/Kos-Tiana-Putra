<?php

namespace App\Support;

class Phone
{
    /**
     * Ubah nomor telepon Indonesia ke format internasional tanpa tanda plus (untuk wa.me).
     * "0812-3456-7890" -> "6281234567890", "+62 812..." -> "62812...", "812..." -> "62812...".
     */
    public static function international(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if ($digits === '' || strlen($digits) < 8) {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        return '62' . $digits;
    }

    /**
     * Tautan WhatsApp dengan pesan terisi, atau null bila nomor tidak valid.
     */
    public static function whatsappUrl(?string $number, string $message = ''): ?string
    {
        $intl = self::international($number);

        if ($intl === null) {
            return null;
        }

        return 'https://wa.me/' . $intl . ($message !== '' ? '?text=' . rawurlencode($message) : '');
    }

    /**
     * Tautan panggilan telepon (tel:), atau null bila nomor tidak valid.
     */
    public static function telUrl(?string $number): ?string
    {
        $intl = self::international($number);

        return $intl === null ? null : 'tel:+' . $intl;
    }
}
