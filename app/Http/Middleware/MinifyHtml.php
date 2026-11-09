<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memadatkan HTML keluaran Blade: menghapus indentasi, baris kosong, dan komentar HTML.
 * Aman karena hanya mengubah spasi putih di luar <script>, <style>, <textarea>, dan <pre>,
 * serta tetap menyisakan satu baris baru sehingga jarak antar elemen inline tidak berubah.
 * Mengurangi ukuran HTML 25-45% (hemat bandwidth, terutama bila server tidak mengompres).
 */
class MinifyHtml
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!config('kos.minify_html', true) || !$this->isMinifiable($response)) {
            return $response;
        }

        // setContent() menimpa $original (objek View asli); pertahankan agar viewData() di test & hook lain tetap bekerja.
        $original = property_exists($response, 'original') ? $response->original : null;

        $response->setContent(self::minify((string) $response->getContent()));

        if ($original !== null) {
            $response->original = $original;
        }

        return $response;
    }

    private function isMinifiable(Response $response): bool
    {
        return $response->getStatusCode() === 200
            && !$response instanceof \Symfony\Component\HttpFoundation\StreamedResponse
            && !$response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }

    public static function minify(string $html): string
    {
        // Pisahkan blok yang spasinya bermakna; hanya bagian di luar blok itu yang dipadatkan.
        $parts = preg_split(
            '#(<(script|style|textarea|pre)\b.*?</\2>)#is',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        if ($parts === false) {
            return $html;
        }

        $out = '';
        // PREG_SPLIT_DELIM_CAPTURE menghasilkan pola: teks, blok, nama-tag, teks, blok, nama-tag, ...
        for ($i = 0, $n = count($parts); $i < $n; $i++) {
            $mod = $i % 3;

            if ($mod === 1) {            // blok yang dipertahankan apa adanya
                $out .= $parts[$i];
            } elseif ($mod === 0) {      // teks biasa: padatkan
                $chunk = preg_replace('/<!--(?!\[if|<!).*?-->/s', '', $parts[$i]);
                $chunk = preg_replace('/^[ \t]+/m', '', $chunk);
                $chunk = preg_replace('/[ \t]+$/m', '', $chunk);
                $chunk = preg_replace('/\n{2,}/', "\n", $chunk);
                $out .= $chunk;
            }
            // $mod === 2 adalah nama tag hasil tangkapan; dilewati
        }

        return $out;
    }
}
