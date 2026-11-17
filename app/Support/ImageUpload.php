<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pengelolaan foto kamar & menu katalog pada disk publik.
 * Path yang tersimpan di database selalu relatif terhadap disk (mis. "catalog/abc.jpg").
 */
class ImageUpload
{
    /** Aturan validasi standar untuk unggahan foto. */
    public static function rules(): array
    {
        return ['nullable', 'image', 'mimes:' . implode(',', config('kos.upload.image_mimes')), 'max:' . config('kos.upload.image_max_kb')];
    }

    /**
     * Tentukan nilai kolom gambar baru dari request.
     * - Ada berkas baru: simpan, hapus berkas lama, kembalikan path baru.
     * - Centang "hapus foto": hapus berkas lama, kembalikan null.
     * - Selain itu: kembalikan path lama apa adanya.
     */
    public static function resolve(Request $request, string $dir, ?string $current, string $field = 'image'): ?string
    {
        $disk = config('kos.upload.image_disk');

        if ($request->hasFile($field)) {
            $path = $request->file($field)->store($dir, $disk);
            static::delete($current);

            return $path;
        }

        if ($request->boolean('remove_' . $field)) {
            static::delete($current);

            return null;
        }

        return $current;
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(config('kos.upload.image_disk'))->delete($path);
        }
    }

    /** URL publik foto, atau placeholder sesuai jenisnya bila belum ada foto. */
    public static function url(?string $path, string $placeholder): string
    {
        if ($path) {
            return Storage::disk(config('kos.upload.image_disk'))->url($path);
        }

        return asset('images/placeholders/' . $placeholder . '.svg');
    }
}
